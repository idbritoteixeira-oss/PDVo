<?php
// ============================================================
// PDVo - API: Caixa (tenant_id isolamento centralizado)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

$pdo  = getPDO();
$tid  = tid();
$user = getCurrentUser();
$data = getBody();
$now  = nowSql();

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $caixaId = (int)($_GET['id'] ?? 0);

        if ($caixaId) {
            $stmt = $pdo->prepare('SELECT c.*, u.nome AS operador_nome FROM caixas c JOIN users u ON u.id=c.user_id WHERE c.id=? AND c.tenant_id=?');
            $stmt->execute([$caixaId, $tid]);
            $caixa = $stmt->fetch();
            if (!$caixa) jsonResponse(['error' => 'Caixa não encontrado.'], 404);

            $movs = $pdo->prepare('SELECT * FROM caixa_movimentacoes WHERE caixa_id=? AND tenant_id=? ORDER BY created_at DESC');
            $movs->execute([$caixaId, $tid]);
            $caixa['movimentacoes'] = $movs->fetchAll();
            jsonResponse(['caixa' => $caixa]);
        }

        $stmt = $pdo->prepare("SELECT * FROM caixas WHERE user_id=? AND status='aberto' AND tenant_id=? ORDER BY abertura DESC LIMIT 1");
        $stmt->execute([$user['id'], $tid]);
        jsonResponse(['caixa' => $stmt->fetch() ?: null]);
        break;

    case 'POST':
        $acao = $data['acao'] ?? '';

        if ($acao === 'abrir') {
            $stmt = $pdo->prepare("SELECT id FROM caixas WHERE user_id=? AND status='aberto' AND tenant_id=? LIMIT 1");
            $stmt->execute([$user['id'], $tid]);
            if ($stmt->fetch()) jsonResponse(['error' => 'Já existe um caixa aberto para este usuário.'], 409);

            $fundo = (float)($data['fundo_caixa'] ?? $data['saldo_abertura'] ?? 0);
            $stmt  = $pdo->prepare("INSERT INTO caixas (tenant_id,user_id,fundo_caixa,status,abertura) VALUES (?,?,?,'aberto',{$now})");
            $stmt->execute([$tid, $user['id'], $fundo]);
            jsonResponse(['message' => 'Caixa aberto com sucesso.', 'caixa_id' => (int)$pdo->lastInsertId()], 201);
        }

        if ($acao === 'fechar') {
            $caixaId = (int)($data['caixa_id'] ?? 0);
            if (!$caixaId) jsonResponse(['error' => 'caixa_id é obrigatório.'], 422);

            $stmt = $pdo->prepare("SELECT * FROM caixas WHERE id=? AND status='aberto' AND tenant_id=?");
            $stmt->execute([$caixaId, $tid]);
            if (!$stmt->fetch()) jsonResponse(['error' => 'Caixa não encontrado ou já fechado.'], 404);

            $pdo->prepare("UPDATE caixas SET status='fechado',fechamento={$now},observacoes=? WHERE id=? AND tenant_id=?")
                ->execute([$data['observacoes'] ?? null, $caixaId, $tid]);
            jsonResponse(['message' => 'Caixa fechado com sucesso.']);
        }

        if ($acao === 'movimentacao') {
            $caixaId = (int)($data['caixa_id'] ?? 0);
            $tipo    = $data['tipo'] ?? '';
            $valor   = (float)($data['valor'] ?? 0);
            $desc    = trim($data['descricao'] ?? '');

            if (!$caixaId || !in_array($tipo, ['entrada','saida']) || $valor <= 0 || !$desc) {
                jsonResponse(['error' => 'Dados inválidos para movimentação.'], 422);
            }

            $pdo->prepare('INSERT INTO caixa_movimentacoes (tenant_id,caixa_id,tipo,valor,descricao) VALUES (?,?,?,?,?)')
                ->execute([$tid, $caixaId, $tipo, $valor, $desc]);

            $coluna = $tipo === 'entrada' ? 'total_entradas' : 'total_saidas';
            $pdo->prepare("UPDATE caixas SET {$coluna}={$coluna}+? WHERE id=? AND tenant_id=?")->execute([$valor, $caixaId, $tid]);

            jsonResponse(['message' => "Movimentação de {$tipo} registrada."]);
        }

        jsonResponse(['error' => 'Ação inválida.'], 422);
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
