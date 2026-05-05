<?php
// ============================================================
// PDVo - Conexão PDO (Multi-tenant centralizado)
// SQLite: arquivo por tenant (dev/local)
// MySQL : banco único com tenant_id para isolamento (produção)
// ============================================================

$configPath = dirname(__DIR__, 2) . '/config.php';
if (!file_exists($configPath)) $configPath = dirname(__DIR__, 1) . '/config.php';
if (file_exists($configPath)) require_once $configPath;
if (!defined('DB_DRIVER')) define('DB_DRIVER', 'sqlite');

/**
 * Retorna PDO do tenant ativo.
 * MySQL: conexão master compartilhada (banco único, isolado por tenant_id).
 * SQLite: arquivo separado por tenant (modo dev/local).
 */
function getPDO(): PDO {
    if (DB_DRIVER === 'mysql') {
        require_once __DIR__ . '/master_db.php';
        return getMasterPDO();
    }

    static $pdos = [];
    if (session_status() === PHP_SESSION_NONE) session_start();
    $slug     = $_SESSION['tenant_slug'] ?? null;
    $cacheKey = $slug ?: '__default__';
    if (isset($pdos[$cacheKey])) return $pdos[$cacheKey];

    $dataDir = dirname(__DIR__, 2) . '/.data';
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    $dbPath = $slug
        ? $dataDir . '/tenant_' . preg_replace('/[^a-z0-9_]/', '', strtolower($slug)) . '.sqlite'
        : $dataDir . '/pdvo.sqlite';

    try {
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        require_once __DIR__ . '/init_sqlite.php';
        pdvoInitSqlite($pdo);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Falha na conexão com o banco de dados.']);
        exit;
    }

    $pdos[$cacheKey] = $pdo;
    return $pdo;
}

/**
 * ID inteiro do tenant ativo na sessão.
 * MySQL: usado em todos os WHERE para isolamento de dados.
 * SQLite: retorna 0 (isolamento é por arquivo).
 */
function tid(): int {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return (int)($_SESSION['tenant_id'] ?? 0);
}

/**
 * Expressão SQL de data/hora atual compatível com o driver.
 */
function nowSql(): string {
    return DB_DRIVER === 'mysql' ? 'NOW()' : "datetime('now','localtime')";
}
