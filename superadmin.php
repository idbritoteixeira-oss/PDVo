<?php
// ============================================================
// PDVo - API: SuperAdmin
// POST /api/superadmin.php?action=login
// GET  /api/superadmin.php?action=dashboard
// GET  /api/superadmin.php?action=pedidos[&status=pendente]
// POST /api/superadmin.php?action=confirmar   {pedido_id}
// POST /api/superadmin.php?action=rejeitar    {pedido_id, observacoes}
// GET  /api/superadmin.php?action=clientes
// POST /api/superadmin.php?action=suspender   {tenant_slug}
// POST /api/superadmin.php?action=reativar    {tenant_slug}
// GET  /api/superadmin.php?action=email_log
// POST /api/superadmin.php?action=alterar_senha {senha_atual, senha_nova}
// GET  /api/superadmin.php?action=get_config_pix
// POST /api/superadmin.php?action=salvar_config_pix
// POST /api/superadmin.php?action=logout
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/master_db.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? (getBody()['action'] ?? '');
$data   = getBody();

// ---- Login ----
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST esperado.'], 405);

    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';
    if (!$email || !$senha) jsonResponse(['error' => 'Credenciais obrigatórias.'], 422);

    $masterPdo = getMasterPDO();
    $stmt = $masterPdo->prepare("SELECT * FROM superadmin_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $sa = $stmt->fetch();

    if (!$sa || !password_verify($senha, $sa['senha'])) {
        jsonResponse(['error' => 'Credenciais inválidas.'], 401);
    }

    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);
    $_SESSION['superadmin_id']    = $sa['id'];
    $_SESSION['superadmin_nome']  = $sa['nome'];
    $_SESSION['superadmin_email'] = $sa['email'];

    jsonResponse(['ok' => true, 'nome' => $sa['nome']]);
}

// ---- Logout ----
if ($action === 'logout') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_destroy();
    jsonResponse(['ok' => true]);
}

// ---- Auth guard ----
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['superadmin_id'])) {
    jsonResponse(['error' => 'Não autenticado.', 'code' => 'SA_UNAUTH'], 401);
}

$masterPdo = getMasterPDO();
$driver    = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
$nowSql    = masterNowSql($driver);

// Garante tabela email_log (compatível com MySQL e SQLite)
_ensureEmailLog($masterPdo, $driver);

// ---- Dashboard ----
if ($action === 'dashboard') {
    $totalClientes  = (int)$masterPdo->query("SELECT COUNT(*) FROM tenants WHERE status='ativo'")->fetchColumn();
    $pendentes      = (int)$masterPdo->query("SELECT COUNT(*) FROM pedidos_ativacao WHERE status IN ('pendente','aguardando_confirmacao')")->fetchColumn();
    $totalPedidos   = (int)$masterPdo->query("SELECT COUNT(*) FROM pedidos_ativacao")->fetchColumn();
    $receitaConfirm = (float)$masterPdo->query("SELECT COALESCE(SUM(valor),0) FROM pedidos_ativacao WHERE status='confirmado'")->fetchColumn();

    $recentPedidos = $masterPdo->query(
        "SELECT * FROM pedidos_ativacao ORDER BY created_at DESC LIMIT 6"
    )->fetchAll();

    // tickets abertos
    $ticketsAbertos = 0;
    try {
        $ticketsAbertos = (int)$masterPdo->query("SELECT COUNT(*) FROM suporte_tickets WHERE status='aguardando_sa'")->fetchColumn();
    } catch (Throwable $e) {}

    // Online users: sessions seen in last 10 minutes
    $onlineUsers = 0;
    try {
        if ($driver === 'mysql') {
            $onlineUsers = (int)$masterPdo->query(
                "SELECT COUNT(*) FROM tenant_activity WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
            )->fetchColumn();
        } else {
            $onlineUsers = (int)$masterPdo->query(
                "SELECT COUNT(*) FROM tenant_activity WHERE last_seen >= datetime('now','localtime','-10 minutes')"
            )->fetchColumn();
        }
    } catch (Throwable $e) {}

    jsonResponse([
        'total_clientes'     => $totalClientes,
        'pedidos_pendentes'  => $pendentes,
        'total_pedidos'      => $totalPedidos,
        'receita_confirmada' => $receitaConfirm,
        'recent_pedidos'     => $recentPedidos,
        'tickets_abertos'    => $ticketsAbertos,
        'online_users'       => $onlineUsers,
    ]);
}

// ---- Listar pedidos ----
if ($action === 'pedidos') {
    $status = trim($_GET['status'] ?? '');
    if ($status && in_array($status, ['pendente','aguardando_confirmacao','confirmado','rejeitado'])) {
        $stmt = $masterPdo->prepare("SELECT * FROM pedidos_ativacao WHERE status = ? ORDER BY created_at DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $masterPdo->query("SELECT * FROM pedidos_ativacao ORDER BY created_at DESC");
    }
    jsonResponse(['pedidos' => $stmt->fetchAll()]);
}

// ---- Confirmar pagamento → ativar tenant + enviar e-mail ----
if ($action === 'confirmar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int)($data['pedido_id'] ?? 0);
    if (!$pedidoId) jsonResponse(['error' => 'pedido_id obrigatório.'], 422);

    require_once __DIR__ . '/config/init_sqlite.php';
    $resultado = ativarTenant($pedidoId, $masterPdo);

    if (!$resultado['ok']) jsonResponse(['error' => $resultado['error']], 400);

    $stmt = $masterPdo->prepare("SELECT * FROM pedidos_ativacao WHERE id = ?");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch();

    $emailEnviado = false;
    if ($pedido) {
        $emailEnviado = _enviarEmailAtivacao($pedido, $resultado['slug'], $masterPdo, $driver);
    }

    jsonResponse([
        'ok'            => true,
        'message'       => 'Tenant ativado com sucesso!',
        'slug'          => $resultado['slug'],
        'senha_inicial' => $resultado['senha_inicial'],
        'email_enviado' => $emailEnviado,
    ]);
}

// ---- Rejeitar pedido ----
if ($action === 'rejeitar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int)($data['pedido_id'] ?? 0);
    $obs      = trim($data['observacoes'] ?? 'Rejeitado pelo administrador.');
    if (!$pedidoId) jsonResponse(['error' => 'pedido_id obrigatório.'], 422);

    $masterPdo->prepare(
        "UPDATE pedidos_ativacao SET status='rejeitado', observacoes=?, updated_at=({$nowSql}) WHERE id=?"
    )->execute([$obs, $pedidoId]);

    jsonResponse(['ok' => true, 'message' => 'Pedido rejeitado.']);
}

// ---- Listar clientes ----
if ($action === 'clientes') {
    $clientes = $masterPdo->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll();
    jsonResponse(['clientes' => $clientes]);
}

// ---- Suspender tenant ----
if ($action === 'suspender' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($data['tenant_slug'] ?? '');
    if (!$slug) jsonResponse(['error' => 'tenant_slug obrigatório.'], 422);
    $masterPdo->prepare("UPDATE tenants SET status='suspenso' WHERE slug=?")->execute([$slug]);
    jsonResponse(['ok' => true]);
}

// ---- Reativar tenant ----
if ($action === 'reativar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($data['tenant_slug'] ?? '');
    if (!$slug) jsonResponse(['error' => 'tenant_slug obrigatório.'], 422);
    $masterPdo->prepare("UPDATE tenants SET status='ativo' WHERE slug=?")->execute([$slug]);
    jsonResponse(['ok' => true]);
}

// ---- Log de e-mails ----
if ($action === 'email_log') {
    $logs = $masterPdo->query("SELECT * FROM email_log ORDER BY enviado_em DESC LIMIT 100")->fetchAll();
    jsonResponse(['logs' => $logs]);
}

// ---- Alterar senha ----
if ($action === 'alterar_senha' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $atual  = $data['senha_atual'] ?? '';
    $nova   = $data['senha_nova']  ?? '';
    $saId   = (int)($_SESSION['superadmin_id'] ?? 0);

    if (!$atual || strlen($nova) < 8) jsonResponse(['error' => 'Nova senha deve ter ao menos 8 caracteres.'], 422);

    $stmt = $masterPdo->prepare("SELECT * FROM superadmin_users WHERE id = ?");
    $stmt->execute([$saId]);
    $sa = $stmt->fetch();

    if (!$sa || !password_verify($atual, $sa['senha'])) jsonResponse(['error' => 'Senha atual incorreta.'], 403);

    $masterPdo->prepare("UPDATE superadmin_users SET senha=? WHERE id=?")
        ->execute([password_hash($nova, PASSWORD_DEFAULT), $saId]);
    jsonResponse(['ok' => true, 'message' => 'Senha alterada com sucesso.']);
}

// ---- Obter configurações PIX ----
if ($action === 'get_config_pix') {
    _ensureConfigPix($masterPdo, $driver);
    $cfg = getSuperadminConfig($masterPdo);
    jsonResponse(['ok' => true, 'config' => $cfg]);
}

// ---- Salvar configurações PIX ----
if ($action === 'salvar_config_pix' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    _ensureConfigPix($masterPdo, $driver);
    $campos  = ['pix_chave','pix_nome','pix_cidade','email_notificacao','email_remetente'];
    $replace = $driver === 'mysql' ? 'REPLACE INTO' : 'INSERT OR REPLACE INTO';
    foreach ($campos as $campo) {
        if (array_key_exists($campo, $data)) {
            $masterPdo->prepare("$replace superadmin_config (chave, valor) VALUES (?,?)")
                ->execute([$campo, trim((string)($data[$campo] ?? ''))]);
        }
    }
    jsonResponse(['ok' => true, 'message' => 'Configurações PIX salvas com sucesso!']);
}

// ---- Retorna token do cron ----
if ($action === 'get_cron_token') {
    $configFile = dirname(__DIR__) . '/../config.php';
    if (file_exists($configFile)) require_once $configFile;
    $token = defined('CRON_TOKEN')
        ? CRON_TOKEN
        : substr(hash('sha256', defined('SECRET_KEY') ? SECRET_KEY : 'pdvo_cron_default'), 0, 32);
    jsonResponse(['ok' => true, 'token' => $token]);
}

// ---- Listar indicações ----
if ($action === 'listar_indicacoes') {
    $stmt = $masterPdo->query("SELECT * FROM indicacoes ORDER BY created_at DESC LIMIT 300");
    jsonResponse(['indicacoes' => $stmt->fetchAll()]);
}

// ---- Validar indicação ----
if ($action === 'validar_indicacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)($data['id'] ?? 0);
    $obs = trim($data['observacoes'] ?? '');
    if (!$id) jsonResponse(['error' => 'id obrigatório.'], 422);
    $masterPdo->prepare("UPDATE indicacoes SET status='validado', observacoes=?, validated_at=({$nowSql}) WHERE id=?")
        ->execute([$obs, $id]);
    jsonResponse(['ok' => true, 'message' => 'Indicação validada!']);
}

// ---- Rejeitar indicação ----
if ($action === 'rejeitar_indicacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)($data['id'] ?? 0);
    $obs = trim($data['observacoes'] ?? '');
    if (!$id) jsonResponse(['error' => 'id obrigatório.'], 422);
    $masterPdo->prepare("UPDATE indicacoes SET status='rejeitado', observacoes=? WHERE id=?")->execute([$obs, $id]);
    jsonResponse(['ok' => true, 'message' => 'Indicação rejeitada.']);
}

// ---- Obter configurações de Indicações ----
if ($action === 'get_config_indicacao') {
    _ensureConfigKeys($masterPdo, $driver, ['indicacao_ativo','indicacao_recompensa_desc','indicacao_valor_recompensa']);
    $cfg = getSuperadminConfig($masterPdo);
    jsonResponse(['ok' => true, 'config' => $cfg]);
}

// ---- Salvar configurações de Indicações ----
if ($action === 'salvar_config_indicacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['indicacao_ativo','indicacao_recompensa_desc','indicacao_valor_recompensa'];
    _ensureConfigKeys($masterPdo, $driver, $keys);
    $replace = $driver === 'mysql' ? 'REPLACE INTO' : 'INSERT OR REPLACE INTO';
    foreach ($keys as $campo) {
        if (array_key_exists($campo, $data)) {
            $masterPdo->prepare("$replace superadmin_config (chave, valor) VALUES (?,?)")
                ->execute([$campo, trim((string)($data[$campo] ?? ''))]);
        }
    }
    jsonResponse(['ok' => true, 'message' => 'Configurações de indicações salvas!']);
}

// ---- Obter configurações do Aviso de App ----
if ($action === 'get_config_app_aviso') {
    $keys = ['app_aviso_ativo','app_aviso_titulo','app_aviso_mensagem','app_aviso_url_android','app_aviso_url_ios'];
    _ensureConfigKeys($masterPdo, $driver, $keys);
    $cfg = getSuperadminConfig($masterPdo);
    jsonResponse(['ok' => true, 'config' => $cfg]);
}

// ---- Salvar configurações do Aviso de App ----
if ($action === 'salvar_config_app_aviso' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['app_aviso_ativo','app_aviso_titulo','app_aviso_mensagem','app_aviso_url_android','app_aviso_url_ios'];
    _ensureConfigKeys($masterPdo, $driver, $keys);
    $replace = $driver === 'mysql' ? 'REPLACE INTO' : 'INSERT OR REPLACE INTO';
    foreach ($keys as $campo) {
        if (array_key_exists($campo, $data)) {
            $masterPdo->prepare("$replace superadmin_config (chave, valor) VALUES (?,?)")
                ->execute([$campo, trim((string)($data[$campo] ?? ''))]);
        }
    }
    jsonResponse(['ok' => true, 'message' => 'Configurações do App salvas!']);
}

// ---- Chart data (dashboard) ----
if ($action === 'chart_data') {
    $period = $_GET['period'] ?? 'week';
    $days   = $period === 'week' ? 7 : 30;

    if ($driver === 'mysql') {
        $rowsReceita = $masterPdo->prepare("
            SELECT DATE(created_at) AS dia, COALESCE(SUM(valor),0) AS total
            FROM pedidos_ativacao
            WHERE status='confirmado'
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL :d DAY)
            GROUP BY DATE(created_at)
            ORDER BY dia ASC
        ");
        $rowsReceita->bindValue(':d', $days, PDO::PARAM_INT);
        $rowsReceita->execute();

        $rowsStatus = $masterPdo->query("
            SELECT status, COUNT(*) AS cnt FROM pedidos_ativacao GROUP BY status
        ")->fetchAll();
    } else {
        $rowsReceita = $masterPdo->prepare("
            SELECT DATE(created_at) AS dia, COALESCE(SUM(valor),0) AS total
            FROM pedidos_ativacao
            WHERE status='confirmado'
              AND created_at >= date('now','-' || :d || ' days')
            GROUP BY DATE(created_at)
            ORDER BY dia ASC
        ");
        $rowsReceita->bindValue(':d', $days, PDO::PARAM_INT);
        $rowsReceita->execute();
        $rowsStatus = $masterPdo->query("SELECT status, COUNT(*) AS cnt FROM pedidos_ativacao GROUP BY status")->fetchAll();
    }

    $receita = $rowsReceita->fetchAll();

    $labels = []; $vals = [];
    $hoje   = new DateTime(); $intv = new DateInterval('P1D');
    $start  = (clone $hoje)->modify("-{$days} days");
    $period_iter = new DatePeriod($start, $intv, $hoje);
    $byDay  = [];
    foreach ($receita as $r) { $byDay[$r['dia']] = (float)$r['total']; }
    foreach ($period_iter as $dt) {
        $k = $dt->format('Y-m-d');
        $labels[] = $dt->format($days <= 7 ? 'd/m' : 'd/m');
        $vals[]   = $byDay[$k] ?? 0;
    }

    $statusCounts = [];
    foreach ($rowsStatus as $r) { $statusCounts[$r['status']] = (int)$r['cnt']; }

    jsonResponse(['ok' => true, 'labels' => $labels, 'receita' => $vals, 'status_counts' => $statusCounts]);
}

// ---- Limpar pedidos rejeitados ----
if ($action === 'limpar_rejeitados' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $masterPdo->query("SELECT COUNT(*) FROM pedidos_ativacao WHERE status='rejeitado'");
    $cnt  = (int)$stmt->fetchColumn();
    $masterPdo->exec("DELETE FROM pedidos_ativacao WHERE status='rejeitado'");
    jsonResponse(['ok' => true, 'removidos' => $cnt]);
}

// ---- Editar cliente (plano/vencimento) ----
if ($action === 'editar_cliente' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug     = trim($data['tenant_slug']    ?? '');
    $plano    = trim($data['plano_nome']     ?? '');
    $venc     = trim($data['data_vencimento']?? '');
    $dias     = (int)($data['adicionar_dias'] ?? 0);
    if (!$slug) jsonResponse(['error' => 'tenant_slug obrigatório.'], 422);

    // Calcula novo vencimento
    if ($dias > 0) {
        // Adiciona dias a partir de hoje
        $novaData = (new DateTime())->modify("+{$dias} days")->format('Y-m-d');
    } elseif ($venc && preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
        $novaData = $venc;
    } else {
        $novaData = null;
    }

    $updates = [];
    $params  = [];
    if ($plano) { $updates[] = "plano_nome=?"; $params[] = $plano; }
    if ($novaData) { $updates[] = "data_vencimento=?"; $params[] = $novaData; }
    if (empty($updates)) jsonResponse(['error' => 'Nada a atualizar.'], 422);

    $params[] = $slug;
    try {
        $masterPdo->prepare("UPDATE tenants SET " . implode(', ', $updates) . " WHERE slug=?")->execute($params);
    } catch (Throwable $e) {
        // Tenta sem coluna data_vencimento caso não exista
        if ($plano) {
            $masterPdo->prepare("UPDATE tenants SET plano_nome=? WHERE slug=?")->execute([$plano, $slug]);
        }
    }
    jsonResponse(['ok' => true, 'message' => 'Cliente atualizado!', 'nova_data' => $novaData]);
}

// ---- Excluir entrada do log de e-mail ----
if ($action === 'excluir_email_log' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($data['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id obrigatório.'], 422);
    $masterPdo->prepare("DELETE FROM email_log WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

// ---- Enviar e-mail manual ----
if ($action === 'enviar_email_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $para    = trim($data['para']    ?? '');
    $assunto = trim($data['assunto'] ?? '');
    $corpo   = trim($data['corpo']   ?? '');
    if (!$para || !$assunto || !$corpo) jsonResponse(['error' => 'para, assunto e corpo são obrigatórios.'], 422);
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'E-mail de destino inválido.'], 422);

    $cfg          = getSuperadminConfig($masterPdo);
    $remetente    = $cfg['email_remetente'] ?? 'noreply@pdvo.local';
    $nomeSistema  = 'PDVo Sistema';

    $enviado = false;
    $erro    = '';
    try {
        $headers  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$nomeSistema} <{$remetente}>\r\n";
        $headers .= "Reply-To: {$remetente}\r\n";
        $bodyHtml = nl2br(htmlspecialchars($corpo));
        $enviado  = mail($para, $assunto, "<html><body style='font-family:sans-serif;color:#1e293b'>{$bodyHtml}</body></html>", $headers);
        if (!$enviado) $erro = error_get_last()['message'] ?? 'Função mail() retornou false';
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }

    // Log
    try {
        $replace = $driver === 'mysql' ? 'INSERT INTO' : 'INSERT INTO';
        $masterPdo->prepare("INSERT INTO email_log (para_email, assunto, status, erro, enviado_em) VALUES (?,?,?,?,({$nowSql}))")
            ->execute([$para, $assunto, $enviado ? 'enviado' : 'erro', $erro ?: null]);
    } catch (Throwable $e) {}

    if ($enviado) jsonResponse(['ok' => true, 'message' => 'E-mail enviado!']);
    else jsonResponse(['error' => 'E-mail não pôde ser entregue: ' . $erro, 'tentativa' => true], 500);
}

// ---- Estatísticas do banco ----
if ($action === 'db_stats') {
    $tabelas = ['tenants','pedidos_ativacao','superadmin_users','superadmin_config','email_log','indicacoes','suporte_tickets'];
    $counts  = [];
    foreach ($tabelas as $t) {
        try { $counts[$t] = (int)$masterPdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn(); }
        catch (Throwable $e) {}
    }

    $versao = null; $sizeMb = null;
    if ($driver === 'mysql') {
        try { $versao = $masterPdo->query("SELECT VERSION()")->fetchColumn(); } catch (Throwable $e) {}
        try {
            $stmt = $masterPdo->query("SELECT ROUND(SUM(data_length+index_length)/1048576,2) AS mb FROM information_schema.tables WHERE table_schema=DATABASE()");
            $sizeMb = $stmt->fetchColumn();
        } catch (Throwable $e) {}
    } else {
        $versao = 'SQLite ' . ($masterPdo->query("SELECT sqlite_version()")->fetchColumn() ?? '3');
        $dbFile = defined('SQLITE_PATH') ? SQLITE_PATH : (dirname(__DIR__) . '/data/master.db');
        if (file_exists($dbFile)) $sizeMb = round(filesize($dbFile) / 1048576, 2);
    }

    jsonResponse(['ok' => true, 'tabelas' => $counts, 'driver' => $driver, 'versao' => $versao, 'size_mb' => $sizeMb]);
}

// ---- Backup do banco ----
if ($action === 'backup_db') {
    if ($driver !== 'mysql') {
        // SQLite: retorna o arquivo binário
        $dbFile = defined('SQLITE_PATH') ? SQLITE_PATH : (dirname(__DIR__) . '/data/master.db');
        if (!file_exists($dbFile)) jsonResponse(['error' => 'Arquivo SQLite não encontrado.'], 404);
        $fname = 'pdvo_backup_' . date('Ymd_His') . '.db';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . filesize($dbFile));
        readfile($dbFile); exit;
    }

    // MySQL: gera SQL dump simples
    $tabelas = ['tenants','pedidos_ativacao','superadmin_users','superadmin_config','email_log','indicacoes','suporte_tickets'];
    $dump    = "-- PDVo Backup MySQL\n-- Gerado em: " . date('Y-m-d H:i:s') . "\n\nSET NAMES utf8mb4;\n\n";
    foreach ($tabelas as $t) {
        try {
            $rows = $masterPdo->query("SELECT * FROM {$t}")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) continue;
            $cols  = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $dump .= "-- TABLE: {$t}\n";
            foreach ($rows as $row) {
                $vals = implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $masterPdo->quote((string)$v), $row));
                $dump .= "INSERT IGNORE INTO `{$t}` ({$cols}) VALUES ({$vals});\n";
            }
            $dump .= "\n";
        } catch (Throwable $e) {}
    }

    $fname = 'pdvo_backup_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    echo $dump; exit;
}

// ---- Ajustar indicação (editar observações + ajuste de ganho) ----
if ($action === 'ajustar_indicacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($data['id']          ?? 0);
    $obs     = trim($data['observacoes']  ?? '');
    $ajuste  = $data['reward_ajuste']     ?? null;
    $status  = trim($data['status']       ?? '');
    if (!$id) jsonResponse(['error' => 'id obrigatório.'], 422);

    // Garante coluna reward_ajuste
    try { $masterPdo->exec("ALTER TABLE indicacoes ADD COLUMN reward_ajuste DECIMAL(10,2) DEFAULT 0"); } catch (Throwable $e) {}

    $sets = []; $params = [];
    if ($obs !== '')   { $sets[] = "observacoes=?";    $params[] = $obs; }
    if ($ajuste !== null) { $sets[] = "reward_ajuste=?"; $params[] = (float)$ajuste; }
    if ($status && in_array($status, ['pendente','validado','rejeitado'])) {
        $sets[] = "status=?"; $params[] = $status;
        if ($status === 'validado') { $sets[] = "validated_at=({$nowSql})"; }
    }
    if (empty($sets)) jsonResponse(['error' => 'Nada a atualizar.'], 422);
    $params[] = $id;
    $masterPdo->prepare("UPDATE indicacoes SET " . implode(', ', $sets) . " WHERE id=?")->execute($params);
    jsonResponse(['ok' => true, 'message' => 'Indicação ajustada!']);
}

// ---- Listar templates de e-mail ----
if ($action === 'listar_email_templates') {
    _ensureEmailTemplates($masterPdo, $driver);
    $tpls = $masterPdo->query("SELECT * FROM email_templates ORDER BY created_at DESC")->fetchAll();
    jsonResponse(['ok' => true, 'templates' => $tpls]);
}

// ---- Salvar template de e-mail (criar ou atualizar) ----
if ($action === 'salvar_email_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    _ensureEmailTemplates($masterPdo, $driver);
    $id      = (int)($data['id']      ?? 0);
    $nome    = trim($data['nome']    ?? '');
    $assunto = trim($data['assunto'] ?? '');
    $corpo   = trim($data['corpo']   ?? '');
    if (!$nome || !$assunto) jsonResponse(['error' => 'nome e assunto são obrigatórios.'], 422);

    if ($id > 0) {
        $masterPdo->prepare("UPDATE email_templates SET nome=?, assunto=?, corpo=? WHERE id=?")
            ->execute([$nome, $assunto, $corpo, $id]);
        jsonResponse(['ok' => true, 'message' => 'Template atualizado!']);
    } else {
        $masterPdo->prepare("INSERT INTO email_templates (nome, assunto, corpo) VALUES (?,?,?)")
            ->execute([$nome, $assunto, $corpo]);
        jsonResponse(['ok' => true, 'message' => 'Template criado!', 'id' => (int)$masterPdo->lastInsertId()]);
    }
}

// ---- Excluir template de e-mail ----
if ($action === 'excluir_email_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    _ensureEmailTemplates($masterPdo, $driver);
    $id = (int)($data['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id obrigatório.'], 422);
    $masterPdo->prepare("DELETE FROM email_templates WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

// ---- Trocar driver de banco ----
if ($action === 'trocar_driver' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoDriver = trim($data['driver'] ?? '');
    if (!in_array($novoDriver, ['sqlite', 'mysql', 'both'])) {
        jsonResponse(['error' => 'Driver inválido. Use sqlite, mysql ou both.'], 422);
    }
    $configFile = dirname(__DIR__) . '/config.php';
    if (!file_exists($configFile)) jsonResponse(['error' => 'config.php não encontrado no servidor.'], 404);

    $conteudo = file_get_contents($configFile);
    // Atualiza ou adiciona a constante DB_DRIVER
    if (preg_match("/define\s*\(\s*['\"]DB_DRIVER['\"][^)]*\)/", $conteudo)) {
        $conteudo = preg_replace(
            "/define\s*\(\s*['\"]DB_DRIVER['\"][^)]*\)/",
            "define('DB_DRIVER', '{$novoDriver}')",
            $conteudo
        );
    } else {
        $conteudo = rtrim($conteudo) . "\ndefine('DB_DRIVER', '{$novoDriver}');\n";
    }
    if (file_put_contents($configFile, $conteudo) === false) {
        jsonResponse(['error' => 'Não foi possível gravar config.php. Verifique as permissões.'], 500);
    }
    jsonResponse(['ok' => true, 'message' => "Driver alterado para '{$novoDriver}'. Reinicie a aplicação."]);
}

// ---- Enviar e-mail em massa (por template) ----
if ($action === 'enviar_email_massa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateId = (int)($data['template_id'] ?? 0);
    $assunto    = trim($data['assunto'] ?? '');
    $corpo      = trim($data['corpo']   ?? '');

    if ($templateId) {
        _ensureEmailTemplates($masterPdo, $driver);
        $stmt = $masterPdo->prepare("SELECT * FROM email_templates WHERE id=?");
        $stmt->execute([$templateId]);
        $tpl = $stmt->fetch();
        if ($tpl) { $assunto = $tpl['assunto']; $corpo = $tpl['corpo']; }
    }
    if (!$assunto || !$corpo) jsonResponse(['error' => 'assunto e corpo são obrigatórios.'], 422);

    $cfg       = getSuperadminConfig($masterPdo);
    $remetente = trim($cfg['email_remetente'] ?? '') ?: 'noreply@pdvo.local';

    $stmtC = $masterPdo->query("SELECT * FROM tenants WHERE status='ativo' AND email IS NOT NULL AND email != ''");
    $clientes = $stmtC->fetchAll();

    $enviados = 0; $falhos = 0;
    foreach ($clientes as $c) {
        $para = $c['email'] ?? '';
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) { $falhos++; continue; }

        $corpoFinal = str_replace(
            ['{{nome}}',  '{{razao}}',                  '{{slug}}',        '{{plano}}'],
            [$c['nome_resp'] ?? $c['nome'] ?? '', $c['razao_social'] ?? '', $c['slug'] ?? '', $c['plano_nome'] ?? ''],
            $corpo
        );

        $headers  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: PDVo Sistema <{$remetente}>\r\nReply-To: {$remetente}\r\n";
        $bodyHtml = nl2br(htmlspecialchars($corpoFinal));
        $enviado  = @mail($para, $assunto, "<html><body style='font-family:sans-serif;color:#1e293b;padding:20px'>{$bodyHtml}</body></html>", $headers);
        if ($enviado) $enviados++; else $falhos++;

        try {
            $masterPdo->prepare("INSERT INTO email_log (para_email, para_nome, assunto, status, enviado_em) VALUES (?,?,?,?,({$nowSql}))")
                ->execute([$para, $c['razao_social'] ?? '', $assunto, $enviado ? 'enviado' : 'falhou']);
        } catch (Throwable $e) {}
    }
    jsonResponse(['ok' => true, 'enviados' => $enviados, 'falhos' => $falhos, 'total' => count($clientes)]);
}

// ---- Dar Mimo (adicionar dias ao plano) ----
if ($action === 'dar_mimo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dias      = (int)($data['dias']      ?? 0);
    $alvo      = trim($data['alvo']       ?? 'online'); // 'online' | 'todos'
    $mensagem  = trim($data['mensagem']   ?? '');
    $notificar = !empty($data['notificar']);

    if ($dias < 1 || $dias > 365) jsonResponse(['error' => 'Informe entre 1 e 365 dias.'], 422);
    if (!in_array($alvo, ['online', 'todos'])) jsonResponse(['error' => 'Alvo inválido.'], 422);

    // Monta lista de tenants alvo
    if ($alvo === 'online') {
        try {
            if ($driver === 'mysql') {
                $stmtT = $masterPdo->query(
                    "SELECT t.* FROM tenants t
                     INNER JOIN tenant_activity ta ON ta.tenant_slug = t.slug
                     WHERE t.status='ativo' AND ta.last_seen >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
                );
            } else {
                $stmtT = $masterPdo->query(
                    "SELECT t.* FROM tenants t
                     INNER JOIN tenant_activity ta ON ta.tenant_slug = t.slug
                     WHERE t.status='ativo' AND ta.last_seen >= datetime('now','localtime','-10 minutes')"
                );
            }
            $clientes = $stmtT->fetchAll();
        } catch (Throwable $e) {
            // tenant_activity pode não existir ainda → fallback vazio
            $clientes = [];
        }
    } else {
        $clientes = $masterPdo->query("SELECT * FROM tenants WHERE status='ativo'")->fetchAll();
    }

    if (empty($clientes)) {
        jsonResponse(['ok' => true, 'presenteados' => 0, 'total' => 0, 'message' => 'Nenhum cliente encontrado para o alvo selecionado.']);
    }

    $cfg       = getSuperadminConfig($masterPdo);
    $remetente = trim($cfg['email_remetente'] ?? '') ?: 'noreply@pdvo.local';

    $presenteados = 0;
    foreach ($clientes as $c) {
        $slug = $c['slug'];
        try {
            if ($driver === 'mysql') {
                $masterPdo->prepare(
                    "UPDATE tenants SET data_vencimento = DATE_ADD(COALESCE(data_vencimento, CURDATE()), INTERVAL ? DAY) WHERE slug=?"
                )->execute([$dias, $slug]);
            } else {
                $masterPdo->prepare(
                    "UPDATE tenants SET data_vencimento = date(COALESCE(data_vencimento, date('now','localtime')), '+' || ? || ' days') WHERE slug=?"
                )->execute([$dias, $slug]);
            }
            $presenteados++;
        } catch (Throwable $e) { continue; }

        if ($notificar && !empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
            $nome    = htmlspecialchars($c['razao_social'] ?? $c['nome_resp'] ?? 'Cliente');
            $msgTxt  = $mensagem ?: "Você ganhou um presente especial da equipe PDVo!";
            $diasStr = $dias . ' dia' . ($dias > 1 ? 's' : '');
            $corpo   = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:36px 16px;">
  <tr><td align="center">
    <table width="540" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
      <tr><td style="background:linear-gradient(135deg,#1e40af,#3b82f6);padding:28px 32px;text-align:center;">
        <p style="margin:0;font-size:36px;">🎁</p>
        <h1 style="margin:4px 0 0;color:#fff;font-size:24px;font-weight:900;">Presente PDVo!</h1>
      </td></tr>
      <tr><td style="padding:32px;">
        <p style="color:#374151;font-size:15px;margin:0 0 12px;">Olá, <strong>{$nome}</strong>!</p>
        <p style="color:#374151;font-size:14px;line-height:1.6;margin:0 0 20px;">{$msgTxt}</p>
        <div style="text-align:center;padding:20px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;margin-bottom:20px;">
          <p style="margin:0;font-size:36px;font-weight:900;color:#15803d;">+{$diasStr}</p>
          <p style="margin:6px 0 0;color:#16a34a;font-size:13px;font-weight:600;">adicionados ao seu plano agora</p>
        </div>
        <p style="margin:0;color:#9ca3af;font-size:12px;text-align:center;">Com carinho, Equipe PDVo 💙</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
            try {
                $headers  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: PDVo Sistema <{$remetente}>\r\nReply-To: {$remetente}\r\n";
                $enviado  = @mail($c['email'], "🎁 PDVo — Você ganhou um presente!", $corpo, $headers);
                $masterPdo->prepare("INSERT INTO email_log (para_email, para_nome, assunto, status, enviado_em) VALUES (?,?,?,?,({$nowSql}))")
                    ->execute([$c['email'], $c['razao_social'] ?? '', '🎁 PDVo — Você ganhou um presente!', $enviado ? 'enviado' : 'falhou']);
            } catch (Throwable $e) {}
        }
    }

    $alvoLabel = $alvo === 'online' ? 'usuários online' : 'clientes ativos';
    jsonResponse(['ok' => true, 'presenteados' => $presenteados, 'total' => count($clientes), 'alvo' => $alvoLabel]);
}

// ---- Prévia: quantos clientes no alvo ----
if ($action === 'preview_mimo') {
    $alvo = trim($_GET['alvo'] ?? 'online');
    $count = 0;
    try {
        if ($alvo === 'online') {
            if ($driver === 'mysql') {
                $count = (int)$masterPdo->query(
                    "SELECT COUNT(DISTINCT t.id) FROM tenants t
                     INNER JOIN tenant_activity ta ON ta.tenant_slug = t.slug
                     WHERE t.status='ativo' AND ta.last_seen >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
                )->fetchColumn();
            } else {
                $count = (int)$masterPdo->query(
                    "SELECT COUNT(DISTINCT t.id) FROM tenants t
                     INNER JOIN tenant_activity ta ON ta.tenant_slug = t.slug
                     WHERE t.status='ativo' AND ta.last_seen >= datetime('now','localtime','-10 minutes')"
                )->fetchColumn();
            }
        } else {
            $count = (int)$masterPdo->query("SELECT COUNT(*) FROM tenants WHERE status='ativo'")->fetchColumn();
        }
    } catch (Throwable $e) {}
    jsonResponse(['ok' => true, 'count' => $count]);
}

// ---- Prévia: quantos clientes receberão o e-mail em massa ----
if ($action === 'preview_email_massa') {
    $count = (int)$masterPdo->query("SELECT COUNT(*) FROM tenants WHERE status='ativo' AND email IS NOT NULL AND email != ''")->fetchColumn();
    jsonResponse(['ok' => true, 'count' => $count]);
}

jsonResponse(['error' => 'Ação desconhecida.'], 404);

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

/**
 * Garante que as chaves existem em superadmin_config.
 */
function _ensureConfigKeys(PDO $pdo, string $driver, array $keys): void {
    $ignore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
    foreach ($keys as $k) {
        try { $pdo->prepare("$ignore superadmin_config (chave,valor) VALUES (?,'')")->execute([$k]); } catch (Throwable $e) {}
    }
}

/**
 * Garante que a tabela superadmin_config existe (MySQL e SQLite).
 */
function _ensureConfigPix(PDO $pdo, string $driver = 'sqlite'): void {
    $eng = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS superadmin_config (
            chave VARCHAR(80) NOT NULL,
            valor TEXT DEFAULT '',
            PRIMARY KEY (chave)
        )$eng");
        $ignore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
        foreach (['pix_chave','pix_nome','pix_cidade','email_notificacao','email_remetente'] as $k) {
            $pdo->prepare("$ignore superadmin_config (chave,valor) VALUES (?,'')")->execute([$k]);
        }
    } catch (Throwable $e) {}
}

/**
 * Garante que a tabela email_templates existe.
 */
function _ensureEmailTemplates(PDO $pdo, string $driver = 'sqlite'): void {
    $ai  = $driver === 'mysql'
        ? 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $now = $driver === 'mysql' ? 'NOW()' : "datetime('now','localtime')";
    $eng = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
            id         $ai,
            nome       VARCHAR(120) NOT NULL,
            assunto    TEXT NOT NULL,
            corpo      MEDIUMTEXT,
            created_at TEXT DEFAULT ({$now})
        )$eng");
    } catch (Throwable $e) {}
}

/**
 * Garante que a tabela email_log existe (MySQL e SQLite).
 */
function _ensureEmailLog(PDO $pdo, string $driver = 'sqlite'): void {
    $ai  = $driver === 'mysql'
        ? 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $now = $driver === 'mysql' ? 'NOW()' : "datetime('now','localtime')";
    $eng = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_log (
            id         $ai,
            para_email VARCHAR(191) NOT NULL,
            para_nome  VARCHAR(120) DEFAULT NULL,
            assunto    TEXT NOT NULL,
            corpo      MEDIUMTEXT,
            status     VARCHAR(20) DEFAULT 'enviado',
            erro       TEXT,
            enviado_em TEXT DEFAULT ({$now})
        )$eng");
    } catch (Throwable $e) {}
}

/**
 * Envia e-mail de boas-vindas/ativação e registra no log.
 */
function _enviarEmailAtivacao(array $pedido, string $slug, PDO $masterPdo, string $driver = 'sqlite'): bool {
    $para        = $pedido['email'];
    $nomeCliente = $pedido['nome'];
    $razao       = $pedido['razao_social'];
    $plano       = $pedido['plano_nome'];
    $assunto     = "PDVo — Sua conta {$razao} foi ativada!";

    $corpo   = _templateEmailAtivacao($nomeCliente, $razao, $plano, $slug, $pedido['email']);
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: PDVo Sistema <noreply@pdvo.app>\r\n";
    $headers .= "Reply-To: suporte@pdvo.app\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $enviado = false;
    $erro    = null;
    try {
        $enviado = @mail($para, $assunto, $corpo, $headers);
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
    if (!$enviado && !$erro) {
        $erro = 'mail() retornou false — configure sendmail/SMTP no servidor.';
    }

    _ensureEmailLog($masterPdo, $driver);

    $nowSql = masterNowSql($driver);
    try {
        $masterPdo->prepare("
            INSERT INTO email_log (para_email, para_nome, assunto, corpo, status, erro, enviado_em)
            VALUES (?, ?, ?, ?, ?, ?, ({$nowSql}))
        ")->execute([
            $para, $nomeCliente, $assunto, $corpo,
            $enviado ? 'enviado' : 'falhou', $erro,
        ]);
    } catch (Throwable $e) {}

    return $enviado;
}

/**
 * Template HTML do e-mail de ativação.
 */
function _templateEmailAtivacao(string $nome, string $razao, string $plano, string $slug, string $email): string {
    $senhaInicial = htmlspecialchars($email);
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Conta PDVo Ativada</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Inter',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1e40af,#3b82f6);padding:36px 40px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:32px;font-weight:900;letter-spacing:-1px;">PDVo</h1>
            <p style="margin:6px 0 0;color:rgba(255,255,255,.7);font-size:12px;letter-spacing:.2em;text-transform:uppercase;">Sistema de Ponto de Venda</p>
          </td>
        </tr>
        <tr>
          <td style="padding:40px 40px 32px;">
            <h2 style="margin:0 0 8px;color:#111827;font-size:22px;font-weight:800;">Sua conta foi ativada!</h2>
            <p style="margin:0 0 24px;color:#6b7280;font-size:14px;">Olá, <strong style="color:#111827;">{$nome}</strong>! Seu pagamento foi confirmado.</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;">
              <tr><td style="padding:20px 24px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="padding:6px 0;font-size:13px;color:#6b7280;width:40%">Empresa</td>
                    <td style="padding:6px 0;font-size:13px;color:#111827;font-weight:700;">{$razao}</td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font-size:13px;color:#6b7280;">Plano</td>
                    <td style="padding:6px 0;font-size:13px;color:#2563eb;font-weight:700;">{$plano}</td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font-size:13px;color:#6b7280;">Código da Empresa</td>
                    <td style="padding:6px 0;font-size:13px;color:#111827;font-family:monospace;font-weight:700;">{$slug}</td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font-size:13px;color:#6b7280;">Senha Inicial</td>
                    <td style="padding:6px 0;font-size:13px;color:#111827;font-family:monospace;">{$senhaInicial}</td>
                  </tr>
                </table>
              </td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;margin-bottom:28px;">
              <tr><td style="padding:16px 20px;font-size:13px;color:#92400e;">
                <strong>Importante:</strong> Altere sua senha imediatamente após o primeiro login em <strong>Configurações → Usuários</strong>.
              </td></tr>
            </table>
            <p style="margin:0;color:#6b7280;font-size:13px;">
              Acesse o sistema usando o <strong>Código da Empresa</strong> acima.<br>
              <strong style="color:#111827;">Equipe PDVo</strong>
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;">
            <p style="margin:0;color:#9ca3af;font-size:11px;">&copy; 2026 PDVo · Sistema de PDV Online · Todos os direitos reservados</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
