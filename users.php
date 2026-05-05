<?php
// ============================================================
// PDVo - API: Usuários (Com Trava Anti-Criação de Admin)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();
requireSubscription();
requirePerfil(['admin']);

$pdo    = getPDO();
$tid    = tid();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->prepare('SELECT id,nome,email,perfil,ativo,ultimo_login,created_at FROM users WHERE tenant_id=? ORDER BY nome');
        $stmt->execute([$tid]);
        jsonResponse(['users' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data   = getBody();
        $nome   = trim($data['nome']  ?? '');
        $email  = trim($data['email'] ?? '');
        $senha  = $data['senha']  ?? '';
        $perfil = $data['perfil'] ?? 'operador';

        if (!$nome || !$email || !$senha) {
            jsonResponse(['error' => 'Nome, e-mail e senha são obrigatórios.'], 422);
        }

        if ($perfil === 'admin') {
            jsonResponse(['error' => 'Não é permitido criar usuários com perfil de Administrador.'], 403);
        }

        if ($perfil === 'gerente' || $perfil === 'operador') {
            $limiteChave    = ($perfil === 'gerente') ? 'limite_gerentes' : 'limite_operadores';
            $limitePermitido = (int)($_SESSION[$limiteChave] ?? 0);

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE perfil=? AND ativo=1 AND tenant_id=?");
            $stmtCheck->execute([$perfil, $tid]);
            if ((int)$stmtCheck->fetchColumn() >= $limitePermitido && $limitePermitido !== -1) {
                jsonResponse([
                    'error'      => "Limite atingido! Seu plano permite apenas $limitePermitido " . ($perfil === 'gerente' ? 'gerente(s).' : 'operador(es).'),
                    'suggestion' => "Faça upgrade do seu plano para adicionar mais funcionários.",
                ], 403);
            }
        }

        if (strlen($senha) < 6) {
            jsonResponse(['error' => 'A senha deve ter pelo menos 6 caracteres.'], 422);
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO users (tenant_id,nome,email,senha,perfil) VALUES (?,?,?,?,?)');
            $stmt->execute([$tid, $nome, $email, password_hash($senha, PASSWORD_BCRYPT), $perfil]);
            jsonResponse(['message' => 'Usuário criado com sucesso.', 'id' => (int)$pdo->lastInsertId()], 201);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                jsonResponse(['error' => 'Este e-mail já está cadastrado.'], 409);
            }
            jsonResponse(['error' => 'Erro ao criar usuário.'], 500);
        }
        break;

    case 'PUT':
        $data   = getBody();
        $id     = (int)($data['id'] ?? 0);
        $nome   = trim($data['nome']  ?? '');
        $email  = trim($data['email'] ?? '');
        $perfil = $data['perfil'] ?? 'operador';
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 422);

        if ($perfil === 'admin') {
            $stmtSelf = $pdo->prepare("SELECT perfil FROM users WHERE id=? AND tenant_id=?");
            $stmtSelf->execute([$id, $tid]);
            $oldPerfil = $stmtSelf->fetchColumn();
            if ($oldPerfil !== 'admin') {
                jsonResponse(['error' => 'Você não pode promover um usuário a Administrador.'], 403);
            }
        }

        if (!empty($data['senha'])) {
            if (strlen($data['senha']) < 6) jsonResponse(['error' => 'Senha muito curta.'], 422);
            $sql    = 'UPDATE users SET nome=?,email=?,perfil=?,ativo=?,senha=? WHERE id=? AND tenant_id=?';
            $params = [$nome, $email, $perfil, (int)($data['ativo'] ?? 1), password_hash($data['senha'], PASSWORD_BCRYPT), $id, $tid];
        } else {
            $sql    = 'UPDATE users SET nome=?,email=?,perfil=?,ativo=? WHERE id=? AND tenant_id=?';
            $params = [$nome, $email, $perfil, (int)($data['ativo'] ?? 1), $id, $tid];
        }
        $pdo->prepare($sql)->execute($params);
        jsonResponse(['message' => 'Usuário atualizado com sucesso.']);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 422);
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            jsonResponse(['error' => 'Você não pode excluir sua própria conta.'], 403);
        }
        $pdo->prepare('UPDATE users SET ativo=0 WHERE id=? AND tenant_id=?')->execute([$id, $tid]);
        jsonResponse(['message' => 'Usuário desativado com sucesso.']);
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
