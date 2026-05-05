<?php
// ============================================================
// PDVo - API: Login (Multi-tenant)
// POST /api/login.php
// Body: { "tenant_slug": "demo", "email": "...", "senha": "..." }
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/master_db.php';

cors();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido.'], 405);
}

$body       = getBody();
$tenantSlug = trim($body['tenant_slug'] ?? 'demo');
$email      = trim($body['email'] ?? '');
$senha      = $body['senha'] ?? '';

if (!$email || !$senha) {
    jsonResponse(['error' => 'E-mail e senha são obrigatórios.'], 422);
}

try {
    // 1. Verifica tenant no master DB
    $masterPdo = getMasterPDO();
    $stmtT     = $masterPdo->prepare("SELECT * FROM tenants WHERE slug = ? LIMIT 1");
    $stmtT->execute([$tenantSlug]);
    $tenant = $stmtT->fetch();

    if (!$tenant) {
        jsonResponse(['error' => 'Empresa não encontrada. Verifique o código da empresa.'], 404);
    }
    if ($tenant['status'] === 'pendente') {
        jsonResponse(['error' => 'Empresa aguardando ativação. Verifique o pagamento.', 'code' => 'TENANT_PENDING'], 402);
    }
    if ($tenant['status'] === 'suspenso') {
        jsonResponse(['error' => 'Empresa suspensa. Entre em contato com o suporte.', 'code' => 'TENANT_SUSPENDED'], 403);
    }
    if ($tenant['status'] === 'cancelado') {
        jsonResponse(['error' => 'Assinatura cancelada.', 'code' => 'TENANT_CANCELLED'], 403);
    }

    $tenantId = (int)$tenant['id'];

    // Detectar driver
    $configPath = dirname(__DIR__) . '/config.php';
    if (!file_exists($configPath)) $configPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($configPath)) require_once $configPath;
    $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';

    if ($driver === 'mysql') {
        // === MYSQL: banco único, isola por tenant_id ===
        $stmtU = $masterPdo->prepare("SELECT id,nome,email,senha,perfil,ativo FROM users WHERE email=? AND tenant_id=? LIMIT 1");
        $stmtU->execute([$email, $tenantId]);
        $user = $stmtU->fetch();

        if (!$user || !password_verify($senha, $user['senha'])) {
            jsonResponse(['error' => 'E-mail ou senha incorretos.'], 401);
        }
        if (!$user['ativo']) {
            jsonResponse(['error' => 'Usuário inativo. Contate o administrador.'], 403);
        }

        $planoSt = $masterPdo->prepare("SELECT * FROM config_plano WHERE tenant_id=? ORDER BY id DESC LIMIT 1");
        $planoSt->execute([$tenantId]);
        $plano = $planoSt->fetch();

        $temaSt = $masterPdo->prepare("SELECT valor FROM configuracoes WHERE chave='tema' AND tenant_id=? LIMIT 1");
        $temaSt->execute([$tenantId]);
        $temaRow = $temaSt->fetch();
        $tema = $temaRow ? ($temaRow['valor'] ?: 'light') : 'light';

        $empSt = $masterPdo->prepare("SELECT valor FROM configuracoes WHERE chave='nome_empresa' AND tenant_id=? LIMIT 1");
        $empSt->execute([$tenantId]);
        $empRow = $empSt->fetch();
        $nomeEmpresa = $empRow ? ($empRow['valor'] ?: $tenant['razao_social']) : $tenant['razao_social'];

        $masterPdo->prepare("UPDATE users SET ultimo_login=NOW() WHERE id=? AND tenant_id=?")->execute([$user['id'], $tenantId]);

    } else {
        // === SQLITE: arquivo por tenant ===
        $dataDir = dirname(__DIR__, 2) . '/.data';
        $dbPath  = $tenant['db_path'] ?: ($dataDir . '/tenant_' . $tenantSlug . '.sqlite');

        require_once __DIR__ . '/config/init_sqlite.php';
        $tenantPdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $tenantPdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        pdvoInitSqlite($tenantPdo);

        $stmtU = $tenantPdo->prepare("SELECT id,nome,email,senha,perfil,ativo FROM users WHERE email=? LIMIT 1");
        $stmtU->execute([$email]);
        $user = $stmtU->fetch();

        if (!$user || !password_verify($senha, $user['senha'])) {
            jsonResponse(['error' => 'E-mail ou senha incorretos.'], 401);
        }
        if (!$user['ativo']) {
            jsonResponse(['error' => 'Usuário inativo. Contate o administrador.'], 403);
        }

        $plano = $tenantPdo->query("SELECT * FROM config_plano LIMIT 1")->fetch();

        $temaRow = $tenantPdo->prepare("SELECT valor FROM configuracoes WHERE chave='tema' LIMIT 1");
        $temaRow->execute();
        $t = $temaRow->fetch();
        $tema = ($t && !empty($t['valor'])) ? $t['valor'] : 'light';

        $empSt = $tenantPdo->prepare("SELECT valor FROM configuracoes WHERE chave='nome_empresa' LIMIT 1");
        $empSt->execute();
        $empRow = $empSt->fetch();
        $nomeEmpresa = $empRow ? ($empRow['valor'] ?: $tenant['razao_social']) : $tenant['razao_social'];

        $tenantPdo->prepare("UPDATE users SET ultimo_login=datetime('now','localtime') WHERE id=?")->execute([$user['id']]);
    }

    // Plano fallback
    if (!$plano) {
        $plano = [
            'rank_nome'        => $tenant['plano_nome'] ?? 'Basic',
            'saldo_pago'       => 1,
            'data_vencimento'  => $tenant['data_vencimento'] ?? '2099-12-31',
            'limite_produtos'  => 100,
            'limite_operadores'=> 1,
            'limite_gerentes'  => 0,
        ];
    }

    // Sessão PHP
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);

    $_SESSION['user_id']           = $user['id'];
    $_SESSION['nome']              = $user['nome'];
    $_SESSION['email']             = $user['email'];
    $_SESSION['perfil']            = strtolower($user['perfil'] ?: 'operador');
    $_SESSION['tenant_slug']       = $tenantSlug;
    $_SESSION['tenant_id']         = $tenantId;
    $_SESSION['tenant_empresa']    = $nomeEmpresa;
    $_SESSION['tema']              = $tema;
    $_SESSION['plano_rank']        = $plano['rank_nome']         ?? 'Basic';
    $_SESSION['plano_pago']        = (bool)($plano['saldo_pago'] ?? true);
    $_SESSION['plano_vencimento']  = $plano['data_vencimento']   ?? '2099-12-31';
    $_SESSION['limite_produtos']   = $plano['limite_produtos']   ?? 100;
    $_SESSION['limite_operadores'] = $plano['limite_operadores'] ?? 1;
    $_SESSION['limite_gerentes']   = $plano['limite_gerentes']   ?? 0;

    jsonResponse([
        'message' => 'Login realizado com sucesso.',
        'tema'    => $tema,
        'tenant'  => [
            'slug'       => $tenantSlug,
            'empresa'    => $nomeEmpresa,
            'plano_nome' => $tenant['plano_nome'],
            'vencimento' => $tenant['data_vencimento'],
        ],
        'user' => [
            'id'     => (int)$user['id'],
            'nome'   => $user['nome'],
            'email'  => $user['email'],
            'perfil' => $_SESSION['perfil'],
            'plano'  => [
                'rank'       => $plano['rank_nome'],
                'vencimento' => $plano['data_vencimento'],
                'limites'    => [
                    'produtos'   => (int)$plano['limite_produtos'],
                    'operadores' => (int)$plano['limite_operadores'],
                    'gerentes'   => (int)$plano['limite_gerentes'],
                ],
            ],
        ],
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Erro ao processar login: ' . $e->getMessage()], 500);
}
