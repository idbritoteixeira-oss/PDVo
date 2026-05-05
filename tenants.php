<?php
// ============================================================
// PDVo - API: Tenants (lista pública de empresas ativas)
// GET /api/tenants.php          - Lista tenants ativos (para login)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/master_db.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método não permitido.'], 405);
}

$masterPdo = getMasterPDO();
$busca     = trim($_GET['q'] ?? '');

if ($busca) {
    $stmt = $masterPdo->prepare("
        SELECT slug, razao_social, plano_nome FROM tenants
        WHERE status = 'ativo' AND (razao_social LIKE ? OR slug LIKE ? OR email LIKE ?)
        ORDER BY razao_social ASC LIMIT 20
    ");
    $like = '%' . $busca . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $masterPdo->query("
        SELECT slug, razao_social, plano_nome FROM tenants
        WHERE status = 'ativo'
        ORDER BY razao_social ASC LIMIT 50
    ");
}

jsonResponse(['tenants' => $stmt->fetchAll()]);
