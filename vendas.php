<?php
// ============================================================
// PDVo - API: Vendas (tenant_id isolamento centralizado)
// GET    - Listar / detalhe
// POST   - Criar venda com itens
// PUT    - Cancelar venda
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

$pdo    = getPDO();
$tid    = tid();
$method = $_SERVER['REQUEST_METHOD'];
$user   = getCurrentUser();
$now    = nowSql();

switch ($method) {

    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare('
                SELECT s.*, u.nome AS operador_nome
                FROM sales s JOIN users u ON u.id=s.user_id
                WHERE s.id=? AND s.tenant_id=?
            ');
            $stmt->execute([(int)$_GET['id'], $tid]);
            $sale = $stmt->fetch();
            if (!$sale) jsonResponse(['error' => 'Venda não encontrada.'], 404);

            $items = $pdo->prepare('
                SELECT si.*, p.nome AS produto_nome, p.codigo_barras, p.unidade
                FROM sale_items si JOIN products p ON p.id=si.product_id
                WHERE si.sale_id=? AND si.tenant_id=?
            ');
            $items->execute([$sale['id'], $tid]);
            $sale['itens'] = $items->fetchAll();
            jsonResponse(['venda' => $sale]);
        }

        $where  = ['s.tenant_id = ?'];
        $params = [$tid];

        if (!empty($_GET['data_inicio'])) { $where[] = 'DATE(s.created_at) >= ?'; $params[] = $_GET['data_inicio']; }
        if (!empty($_GET['data_fim']))    { $where[] = 'DATE(s.created_at) <= ?'; $params[] = $_GET['data_fim']; }
        if (!empty($_GET['status']))          { $where[] = 's.status = ?';           $params[] = $_GET['status']; }
        if (!empty($_GET['forma_pagamento'])) { $where[] = 's.forma_pagamento = ?';  $params[] = $_GET['forma_pagamento']; }

        $limit  = max(1, min(200, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $stmt = $pdo->prepare('
            SELECT s.*, u.nome AS operador_nome
            FROM sales s JOIN users u ON u.id=s.user_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY s.created_at DESC LIMIT ? OFFSET ?
        ');
        $stmt->execute(array_merge($params, [$limit, $offset]));
        jsonResponse(['vendas' => $stmt->fetchAll(), 'limit' => $limit, 'offset' => $offset]);
        break;

    case 'POST':
        $data    = getBody();
        $itens   = $data['itens'] ?? [];
        $caixaId = $data['caixa_id'] ?? null;

        if (empty($itens)) jsonResponse(['error' => 'A venda deve ter pelo menos um item.'], 422);

        if ($caixaId) {
            $cx = $pdo->prepare('SELECT id FROM caixas WHERE id=? AND status="aberto" AND tenant_id=?');
            $cx->execute([(int)$caixaId, $tid]);
            if (!$cx->fetch()) jsonResponse(['error' => 'Caixa não está aberto.'], 422);
        }

        $pdo->beginTransaction();
        try {
            $numero      = 'V' . date('Ymd') . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $subtotal    = 0;
            $itemsToSave = [];

            foreach ($itens as $item) {
                $prodId   = (int)($item['product_id'] ?? 0);
                $qty      = (float)($item['quantidade'] ?? 0);
                $preco    = (float)($item['preco_unitario'] ?? 0);
                $descItem = (float)($item['desconto_item'] ?? 0);

                if (!$prodId || $qty <= 0 || $preco < 0) {
                    $pdo->rollBack();
                    jsonResponse(['error' => 'Item inválido na venda.'], 422);
                }

                $prod = $pdo->prepare('SELECT estoque, nome FROM products WHERE id=? AND ativo=1 AND tenant_id=?');
                $prod->execute([$prodId, $tid]);
                $product = $prod->fetch();
                if (!$product) { $pdo->rollBack(); jsonResponse(['error' => "Produto ID {$prodId} não encontrado."], 404); }
                if ($product['estoque'] < $qty) {
                    $pdo->rollBack();
                    jsonResponse(['error' => "Estoque insuficiente para: {$product['nome']}. Disponível: {$product['estoque']}"], 422);
                }

                $subItem   = ($preco * $qty) - $descItem;
                $subtotal += $subItem;
                $itemsToSave[] = [$prodId, $qty, $preco, $descItem, $subItem];
            }

            $desconto    = (float)($data['desconto']     ?? 0);
            $taxaServico = (float)($data['taxa_servico'] ?? 0);
            $total       = $subtotal - $desconto + $taxaServico;
            $valorPago   = (float)($data['valor_pago']   ?? $total);
            $troco       = max(0, $valorPago - $total);

            $stmt = $pdo->prepare("
                INSERT INTO sales
                (tenant_id,caixa_id,user_id,numero_venda,subtotal,desconto,taxa_servico,total,
                 forma_pagamento,valor_pago,troco,status,observacoes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $tid, $caixaId ?: null, $user['id'], $numero,
                $subtotal, $desconto, $taxaServico, $total,
                $data['forma_pagamento'] ?? 'dinheiro',
                $valorPago, $troco, 'concluida', $data['observacoes'] ?? null,
            ]);
            $saleId = (int)$pdo->lastInsertId();

            $iStmt = $pdo->prepare('INSERT INTO sale_items (tenant_id,sale_id,product_id,quantidade,preco_unitario,desconto_item,subtotal) VALUES (?,?,?,?,?,?,?)');
            $sStmt = $pdo->prepare('UPDATE products SET estoque=estoque-? WHERE id=? AND tenant_id=?');

            foreach ($itemsToSave as [$prodId, $qty, $preco, $descItem, $subItem]) {
                $iStmt->execute([$tid, $saleId, $prodId, $qty, $preco, $descItem, $subItem]);
                // Decremento explícito de estoque (compatível com MySQL sem triggers)
                if (DB_DRIVER === 'mysql') {
                    $sStmt->execute([$qty, $prodId, $tid]);
                }
            }

            if ($caixaId) {
                $pdo->prepare('UPDATE caixas SET total_vendas=total_vendas+? WHERE id=? AND tenant_id=?')
                    ->execute([$total, $caixaId, $tid]);
            }

            $pdo->commit();
            jsonResponse([
                'message'      => 'Venda realizada com sucesso.',
                'sale_id'      => $saleId,
                'numero_venda' => $numero,
                'total'        => $total,
                'troco'        => $troco,
            ], 201);

        } catch (PDOException $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Erro ao processar venda: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        requirePerfil(['admin', 'gerente']);
        $data   = getBody();
        $id     = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        if (!$id || $status !== 'cancelada') jsonResponse(['error' => 'Dados inválidos para atualização.'], 422);

        $pdo->beginTransaction();
        try {
            $sale = $pdo->prepare("SELECT status FROM sales WHERE id=? AND tenant_id=?");
            $sale->execute([$id, $tid]);
            $saleRow = $sale->fetch();
            if (!$saleRow || $saleRow['status'] === 'cancelada') {
                $pdo->rollBack();
                jsonResponse(['error' => 'Venda não encontrada ou já cancelada.'], 404);
            }

            $pdo->prepare("UPDATE sales SET status='cancelada' WHERE id=? AND tenant_id=?")->execute([$id, $tid]);

            // Restaurar estoque (necessário em MySQL — sem trigger)
            if (DB_DRIVER === 'mysql') {
                $items = $pdo->prepare("SELECT product_id, quantidade FROM sale_items WHERE sale_id=? AND tenant_id=?");
                $items->execute([$id, $tid]);
                $restStmt = $pdo->prepare("UPDATE products SET estoque=estoque+? WHERE id=? AND tenant_id=?");
                foreach ($items->fetchAll() as $si) {
                    $restStmt->execute([$si['quantidade'], $si['product_id'], $tid]);
                }
            }

            $pdo->commit();
            jsonResponse(['message' => 'Venda cancelada. Estoque restaurado.']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Erro ao cancelar venda.'], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
