<?php
// ============================================================
// PDVo - API: Configurações do Sistema
// GET  /api/configuracoes.php  - Listar todas
// POST /api/configuracoes.php  - Salvar (upsert)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

$pdo = getPDO();
$tid = tid();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $stmt = $pdo->prepare('SELECT chave, valor FROM configuracoes WHERE tenant_id=? ORDER BY chave');
        $stmt->execute([$tid]);
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['chave']] = $row['valor'];
        }
        jsonResponse($config);
        break;

    case 'POST':
        requirePerfil(['admin']);
        $data = getBody();

        if (!is_array($data) || empty($data)) {
            jsonResponse(['error' => 'Dados inválidos.'], 422);
        }

        try {
            if (DB_DRIVER === 'sqlite') {
                $stmt = $pdo->prepare('
                    INSERT INTO configuracoes (tenant_id, chave, valor)
                    VALUES (?, ?, ?)
                    ON CONFLICT(tenant_id, chave) DO UPDATE SET valor = excluded.valor
                ');
                foreach ($data as $chave => $valor) {
                    if (!empty($chave)) $stmt->execute([$tid, trim($chave), $valor]);
                }
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO configuracoes (tenant_id, chave, valor)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ');
                foreach ($data as $chave => $valor) {
                    if (!empty($chave)) $stmt->execute([$tid, trim($chave), (string)$valor]);
                }
            }

            if (isset($data['tema']) && session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['tema'] = $data['tema'];
            }

            jsonResponse(['message' => 'Configurações salvas com sucesso.']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
