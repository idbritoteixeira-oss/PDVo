<?php
// ============================================================
// PDVo - API: Categorias e Subcategorias
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

$pdo    = getPDO();
$tid    = tid();
$method = $_SERVER['REQUEST_METHOD'];

if (!empty($_GET['subcategorias'])) {
    $catId = (int)($_GET['categoria_id'] ?? 0);
    if ($catId) {
        $stmt = $pdo->prepare('SELECT * FROM subcategorias WHERE categoria_id=? AND tenant_id=? ORDER BY nome');
        $stmt->execute([$catId, $tid]);
    } else {
        $stmt = $pdo->prepare('SELECT s.*, c.nome AS categoria_nome FROM subcategorias s JOIN categorias c ON c.id=s.categoria_id WHERE s.tenant_id=? ORDER BY c.nome,s.nome');
        $stmt->execute([$tid]);
    }
    jsonResponse(['subcategorias' => $stmt->fetchAll()]);
}

switch ($method) {
    case 'GET':
        $stmt = $pdo->prepare('SELECT c.*, COUNT(p.id) AS total_produtos FROM categorias c LEFT JOIN products p ON p.categoria_id=c.id AND p.ativo=1 AND p.tenant_id=? WHERE c.tenant_id=? GROUP BY c.id ORDER BY c.nome');
        $stmt->execute([$tid, $tid]);
        jsonResponse(['categorias' => $stmt->fetchAll()]);
        break;

    case 'POST':
        requirePerfil(['admin', 'gerente']);
        $data = getBody();

        if (!empty($data['subcategoria'])) {
            $catId = (int)($data['categoria_id'] ?? 0);
            $nome  = trim($data['nome'] ?? '');
            if (!$catId || !$nome) jsonResponse(['error' => 'Categoria e nome são obrigatórios.'], 422);
            $stmt = $pdo->prepare('INSERT INTO subcategorias (tenant_id,categoria_id,nome,descricao) VALUES (?,?,?,?)');
            $stmt->execute([$tid, $catId, $nome, $data['descricao'] ?? null]);
            jsonResponse(['message' => 'Subcategoria criada.', 'id' => (int)$pdo->lastInsertId()], 201);
        }

        $nome = trim($data['nome'] ?? '');
        if (!$nome) jsonResponse(['error' => 'Nome é obrigatório.'], 422);
        $stmt = $pdo->prepare('INSERT INTO categorias (tenant_id,nome,descricao) VALUES (?,?,?)');
        $stmt->execute([$tid, $nome, $data['descricao'] ?? null]);
        jsonResponse(['message' => 'Categoria criada.', 'id' => (int)$pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requirePerfil(['admin', 'gerente']);
        $data = getBody();
        $id   = (int)($data['id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        if (!$id || !$nome) jsonResponse(['error' => 'ID e nome são obrigatórios.'], 422);

        if (!empty($data['subcategoria'])) {
            $pdo->prepare('UPDATE subcategorias SET nome=?,descricao=? WHERE id=? AND tenant_id=?')
                ->execute([$nome, $data['descricao'] ?? null, $id, $tid]);
        } else {
            $pdo->prepare('UPDATE categorias SET nome=?,descricao=? WHERE id=? AND tenant_id=?')
                ->execute([$nome, $data['descricao'] ?? null, $id, $tid]);
        }
        jsonResponse(['message' => 'Atualizado com sucesso.']);
        break;

    case 'DELETE':
        requirePerfil(['admin', 'gerente']);
        $id   = (int)($_GET['id'] ?? 0);
        $tipo = $_GET['tipo'] ?? 'categoria';
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 422);

        if ($tipo === 'subcategoria') {
            $pdo->prepare('DELETE FROM subcategorias WHERE id=? AND tenant_id=?')->execute([$id, $tid]);
        } else {
            $pdo->prepare('DELETE FROM categorias WHERE id=? AND tenant_id=?')->execute([$id, $tid]);
        }
        jsonResponse(['message' => 'Removido com sucesso.']);
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
