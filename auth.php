<?php
// ============================================================
// PDVo - Helpers de Autenticação / Sessão / Assinaturas
// ============================================================

$configPath = dirname(__DIR__, 2) . '/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 1) . '/config.php';
}
if (file_exists($configPath)) require_once $configPath;

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        if (isApiRequest()) jsonResponse(['error' => 'Não autenticado.'], 401);
        header('Location: login.html');
        exit;
    }
}

/**
 * Exige que o perfil do usuário logado esteja na lista permitida.
 * Uso: requirePerfil(['admin', 'gerente']);
 */
function requirePerfil(array $perfisPermitidos): void {
    requireLogin();
    startSession();
    $perfil = strtolower($_SESSION['perfil'] ?? 'operador');
    if (!in_array($perfil, $perfisPermitidos)) {
        jsonResponse(['error' => 'Permissão insuficiente.'], 403);
    }
}

function requireSubscription(): void {
    requireLogin();
    startSession();
    $vencimento = $_SESSION['plano_vencimento'] ?? '0000-00-00';
    $statusPg   = $_SESSION['plano_pago'] ?? false;
    if (!$statusPg || $vencimento < date('Y-m-d')) {
        if (isApiRequest()) jsonResponse(['error' => 'Assinatura expirada.', 'code' => 'SUBSCRIPTION_EXPIRED'], 402);
        header('Location: assinatura.html');
        exit;
    }
}

function getCurrentUser(): array {
    startSession();
    return [
        'id'     => $_SESSION['user_id']  ?? null,
        'nome'   => $_SESSION['nome']     ?? '',
        'email'  => $_SESSION['email']    ?? '',
        'perfil' => $_SESSION['perfil']   ?? '',
        'plano'  => [
            'rank'       => $_SESSION['plano_rank']       ?? 'Basic',
            'vencimento' => $_SESSION['plano_vencimento'] ?? '',
            'limites'    => [
                'produtos'   => $_SESSION['limite_produtos']   ?? 5,
                'operadores' => $_SESSION['limite_operadores'] ?? 1,
                'gerentes'   => $_SESSION['limite_gerentes']   ?? 0,
            ]
        ]
    ];
}

function setUserSession(array $user, array $plano): void {
    startSession();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nome']    = $user['nome'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['perfil']  = $user['perfil'];

    $_SESSION['plano_rank']       = $plano['rank_nome']       ?? 'Basic';
    $_SESSION['plano_pago']       = (bool)($plano['saldo_pago']      ?? false);
    $_SESSION['plano_vencimento'] = $plano['data_vencimento'] ?? null;
    $_SESSION['limite_produtos']  = $plano['limite_produtos']  ?? 5;
    $_SESSION['limite_operadores']= $plano['limite_operadores']?? 1;
    $_SESSION['limite_gerentes']  = $plano['limite_gerentes']  ?? 0;
}

function destroySession(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function jsonResponse(mixed $data, int $code = 200): void {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function isApiRequest(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $uri    = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($accept, 'application/json') || str_contains($uri, '/api/');
}

function cors(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}
