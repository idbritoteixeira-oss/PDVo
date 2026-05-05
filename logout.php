<?php
// ============================================================
// PDVo - API: Logout (JSON — JS faz o redirect)
// ============================================================
require_once __DIR__ . '/config/auth.php';
cors();
destroySession();
jsonResponse(['message' => 'Sessão encerrada.']);
