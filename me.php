<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/master_db.php';
cors();
requireLogin();

startSession();
$tema = $_SESSION['tema'] ?? 'light';

// Track tenant activity for online user count in superadmin dashboard
try {
    $masterPdo = getMasterPDO();
    $drv       = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
    $sessId    = session_id();
    $userId    = (int)($_SESSION['user_id'] ?? 0);

    $eng = $drv === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
    $masterPdo->exec("CREATE TABLE IF NOT EXISTS tenant_activity (
        session_id VARCHAR(128) PRIMARY KEY,
        user_id    INT DEFAULT 0,
        last_seen  TEXT
    )$eng");

    if ($drv === 'mysql') {
        $masterPdo->prepare(
            "INSERT INTO tenant_activity (session_id,user_id,last_seen) VALUES (?,?,NOW())
             ON DUPLICATE KEY UPDATE last_seen=NOW(), user_id=VALUES(user_id)"
        )->execute([$sessId, $userId]);
    } else {
        $masterPdo->prepare(
            "INSERT OR REPLACE INTO tenant_activity (session_id,user_id,last_seen)
             VALUES (?,?,datetime('now','localtime'))"
        )->execute([$sessId, $userId]);
    }
} catch (Throwable $e) {}

jsonResponse([
    'user' => getCurrentUser(),
    'tema' => $tema,
]);
