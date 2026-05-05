<?php
// ============================================================
// PDVo - API: Healthcheck
// ============================================================
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');
cors();

try {
    $pdo  = getPDO();
    $pdo->query('SELECT 1');
    echo json_encode([
        'status'    => 'online',
        'driver'    => DB_DRIVER,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'offline', 'error' => 'Banco indisponível.']);
}
