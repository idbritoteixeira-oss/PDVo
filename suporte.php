<?php
// ============================================================
// PDVo - API: Suporte / Tickets
// GET  /api/suporte.php?action=listar          (client: seus tickets | SA: todos)
// POST /api/suporte.php?action=abrir           {assunto, mensagem}  (client)
// POST /api/suporte.php?action=responder       {ticket_id, resposta} (SA)
// POST /api/suporte.php?action=fechar          {ticket_id}           (SA ou dono)
// POST /api/suporte.php?action=reabrir         {ticket_id}           (client)
// GET  /api/suporte.php?action=contagem_abertos  (client)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/master_db.php';

cors();

$action = $_GET['action'] ?? (getBody()['action'] ?? '');
$data   = getBody();

// Detecta se requisição vem de SA ou de cliente normal
$isSA = false;
$saId = null;
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['superadmin_id'])) {
    $isSA = true;
    $saId = (int)$_SESSION['superadmin_id'];
}

$masterPdo = getMasterPDO();
$driver    = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
$nowSql    = masterNowSql($driver);

// Garante tabela suporte_tickets
_ensureSuporteTickets($masterPdo, $driver);

// Se não é SA, exige login de cliente
if (!$isSA) {
    requireLogin();
    requireSubscription();
}

$tenantId = $isSA ? null : tid();

// ---- Listar tickets ----
if ($action === 'listar') {
    if ($isSA) {
        $stmt = $masterPdo->query("
            SELECT t.*, tn.razao_social, tn.slug
            FROM suporte_tickets t
            LEFT JOIN tenants tn ON tn.id = t.tenant_id
            ORDER BY
                CASE t.status WHEN 'aberto' THEN 0 WHEN 'aguardando_cliente' THEN 1 ELSE 2 END,
                t.created_at DESC
            LIMIT 200
        ");
    } else {
        $pdo  = getPDO();
        $stmt = $masterPdo->prepare("
            SELECT * FROM suporte_tickets
            WHERE tenant_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$tenantId]);
    }
    jsonResponse(['tickets' => $stmt->fetchAll()]);
}

// ---- Contagem de tickets abertos / com resposta não lida ----
if ($action === 'contagem_abertos') {
    if ($isSA) {
        $abertos    = (int)$masterPdo->query("SELECT COUNT(*) FROM suporte_tickets WHERE status='aberto'")->fetchColumn();
        $aguardando = (int)$masterPdo->query("SELECT COUNT(*) FROM suporte_tickets WHERE status='aguardando_sa'")->fetchColumn();
        jsonResponse(['abertos' => $abertos, 'aguardando_sa' => $aguardando, 'total' => $abertos + $aguardando]);
    } else {
        $stmt = $masterPdo->prepare("SELECT COUNT(*) FROM suporte_tickets WHERE tenant_id=? AND status='aguardando_cliente'");
        $stmt->execute([$tenantId]);
        $count = (int)$stmt->fetchColumn();
        jsonResponse(['nao_lidos' => $count]);
    }
}

// ---- Abrir ticket (cliente) ----
if ($action === 'abrir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isSA) jsonResponse(['error' => 'SA não abre tickets.'], 403);
    $assunto  = trim($data['assunto']  ?? '');
    $mensagem = trim($data['mensagem'] ?? '');
    if (!$assunto || !$mensagem) jsonResponse(['error' => 'Assunto e mensagem são obrigatórios.'], 422);

    $stmt = $masterPdo->prepare("
        INSERT INTO suporte_tickets (tenant_id, assunto, mensagem, status, created_at, updated_at)
        VALUES (?, ?, ?, 'aguardando_sa', ({$nowSql}), ({$nowSql}))
    ");
    $stmt->execute([$tenantId, $assunto, $mensagem]);
    jsonResponse(['ok' => true, 'id' => (int)$masterPdo->lastInsertId(), 'message' => 'Ticket aberto com sucesso!']);
}

// ---- Responder ticket (SA) ----
if ($action === 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSA) jsonResponse(['error' => 'Apenas SuperAdmin pode responder.'], 403);
    $tid      = (int)($data['ticket_id'] ?? 0);
    $resposta = trim($data['resposta'] ?? '');
    if (!$tid || !$resposta) jsonResponse(['error' => 'ticket_id e resposta são obrigatórios.'], 422);

    $masterPdo->prepare("
        UPDATE suporte_tickets
        SET resposta_sa = ?, status = 'aguardando_cliente', respondido_em = ({$nowSql}), updated_at = ({$nowSql})
        WHERE id = ?
    ")->execute([$resposta, $tid]);

    jsonResponse(['ok' => true, 'message' => 'Resposta enviada ao cliente!']);
}

// ---- Fechar ticket ----
if ($action === 'fechar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int)($data['ticket_id'] ?? 0);
    if (!$tid) jsonResponse(['error' => 'ticket_id obrigatório.'], 422);

    if ($isSA) {
        $masterPdo->prepare("UPDATE suporte_tickets SET status='fechado', updated_at=({$nowSql}) WHERE id=?")->execute([$tid]);
    } else {
        $masterPdo->prepare("UPDATE suporte_tickets SET status='fechado', updated_at=({$nowSql}) WHERE id=? AND tenant_id=?")->execute([$tid, $tenantId]);
    }
    jsonResponse(['ok' => true]);
}

// ---- Reabrir ticket (cliente) ----
if ($action === 'reabrir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int)($data['ticket_id'] ?? 0);
    if (!$tid) jsonResponse(['error' => 'ticket_id obrigatório.'], 422);
    $masterPdo->prepare("UPDATE suporte_tickets SET status='aguardando_sa', updated_at=({$nowSql}) WHERE id=? AND tenant_id=?")->execute([$tid, $tenantId]);
    jsonResponse(['ok' => true, 'message' => 'Ticket reaberto.']);
}

// ---- Marcar como lido pelo cliente ----
if ($action === 'marcar_lido' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int)($data['ticket_id'] ?? 0);
    if (!$tid) jsonResponse(['error' => 'ticket_id obrigatório.'], 422);
    $masterPdo->prepare("UPDATE suporte_tickets SET status='aberto', updated_at=({$nowSql}) WHERE id=? AND tenant_id=?")->execute([$tid, $tenantId]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Ação desconhecida.'], 404);

// ============================================================
// AUXILIARES
// ============================================================
function _ensureSuporteTickets(PDO $pdo, string $driver): void {
    $ai  = $driver === 'mysql'
        ? 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $now = $driver === 'mysql' ? 'NOW()' : "datetime('now','localtime')";
    $eng = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS suporte_tickets (
            id            $ai,
            tenant_id     INT NOT NULL,
            assunto       VARCHAR(255) NOT NULL,
            mensagem      TEXT NOT NULL,
            resposta_sa   TEXT DEFAULT NULL,
            status        VARCHAR(40) DEFAULT 'aguardando_sa',
            respondido_em TEXT DEFAULT NULL,
            created_at    TEXT DEFAULT ({$now}),
            updated_at    TEXT DEFAULT ({$now})
        )$eng");
    } catch (Throwable $e) {}
}
