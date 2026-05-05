<?php
// ============================================================
// PDVo - API: Estoque (tenant_id isolamento centralizado)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

$pdo  = getPDO();
$tid  = tid();
$user = getCurrentUser();

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $prodId = (int)($_GET['product_id'] ?? 0);
        $where  = ['em.tenant_id = ?'];
        $params = [$tid];
        if ($prodId) { $where[] = 'em.product_id = ?'; $params[] = $prodId; }

        $limit  = max(1, min(200, (int)($_GET['limit']  ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $stmt = $pdo->prepare('
            SELECT em.*, p.nome AS produto_nome, u.nome AS operador_nome
            FROM estoque_movimentacoes em
            JOIN products p ON p.id=em.product_id
            LEFT JOIN users u ON u.id=em.user_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY em.created_at DESC LIMIT ? OFFSET ?
        ');
        $stmt->execute(array_merge($params, [$limit, $offset]));
        jsonResponse(['movimentacoes' => $stmt->fetchAll()]);
        break;

    case 'POST':
        requirePerfil(['admin', 'gerente']);
        $data       = getBody();
        $prodId     = (int)($data['product_id'] ?? 0);
        $tipo       = $data['tipo'] ?? '';
        $quantidade = (float)($data['quantidade'] ?? 0);
        $motivo     = trim($data['motivo'] ?? '');

        if (!$prodId || !in_array($tipo, ['entrada','saida','ajuste','perda']) || $quantidade <= 0) {
            jsonResponse(['error' => 'Dados inválidos.'], 422);
        }

        $pdo->beginTransaction();
        try {
            $prod = $pdo->prepare('SELECT estoque FROM products WHERE id=? AND ativo=1 AND tenant_id=?');
            $prod->execute([$prodId, $tid]);
            $product = $prod->fetch();
            if (!$product) { $pdo->rollBack(); jsonResponse(['error' => 'Produto não encontrado.'], 404); }

            $estoqueAnt = (float)$product['estoque'];

            if ($tipo === 'ajuste') {
                $estoqueNovo = $quantidade;
            } elseif (in_array($tipo, ['saida','perda'])) {
                if ($estoqueAnt < $quantidade) { $pdo->rollBack(); jsonResponse(['error' => 'Quantidade maior que o estoque atual.'], 422); }
                $estoqueNovo = $estoqueAnt - $quantidade;
            } else {
                $estoqueNovo = $estoqueAnt + $quantidade;
            }

            $pdo->prepare('UPDATE products SET estoque=? WHERE id=? AND tenant_id=?')->execute([$estoqueNovo, $prodId, $tid]);
            $pdo->prepare('INSERT INTO estoque_movimentacoes (tenant_id,product_id,user_id,tipo,quantidade,motivo) VALUES (?,?,?,?,?,?)')
                ->execute([$tid, $prodId, $user['id'], $tipo, $quantidade, $motivo ?: null]);

            $pdo->commit();
            jsonResponse(['message' => 'Movimentação registrada.', 'estoque_anterior' => $estoqueAnt, 'estoque_novo' => $estoqueNovo]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Erro ao registrar movimentação.'], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
