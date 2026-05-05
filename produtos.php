<?php
// ============================================================
// PDVo - API: Produtos (tenant_id isolamento centralizado)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();
requireSubscription();

$pdo    = getPDO();
$tid    = tid();
$method = $_SERVER['REQUEST_METHOD'];
$now    = nowSql();

if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
}

function handleImageUpload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    if (!in_array($file['type'], ['image/jpeg','image/png','image/webp'])) return null;
    $uploadDir = __DIR__ . '/../assets/img/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $dest = $uploadDir . 'prod_' . uniqid() . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dest) ? 'assets/img/products/' . basename($dest) : null;
}

switch ($method) {

    case 'GET':
        if (!empty($_GET['barcode'])) {
            $stmt = $pdo->prepare('SELECT p.*, c.nome AS categoria_nome FROM products p LEFT JOIN categorias c ON c.id = p.categoria_id WHERE p.codigo_barras = ? AND p.ativo = 1 AND p.tenant_id = ? LIMIT 1');
            $stmt->execute([trim($_GET['barcode']), $tid]);
            $product = $stmt->fetch();
            if (!$product) jsonResponse(['error' => 'Produto não encontrado.'], 404);
            jsonResponse(['product' => $product]);
        }

        $where = ['p.tenant_id = ?']; $params = [$tid];
        if (!empty($_GET['busca'])) {
            $where[]  = '(p.nome LIKE ? OR p.codigo_barras LIKE ?)';
            $busca    = '%' . $_GET['busca'] . '%';
            $params   = array_merge($params, [$busca, $busca]);
        }
        if (!empty($_GET['categoria_id'])) { $where[] = 'p.categoria_id = ?'; $params[] = (int)$_GET['categoria_id']; }
        if (isset($_GET['ativo'])) { $where[] = 'p.ativo = ?'; $params[] = (int)$_GET['ativo']; }
        else { $where[] = 'p.ativo = 1'; }

        $limit  = max(1, min(200, (int)($_GET['limit']  ?? 20)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $sql = "SELECT p.*, c.nome AS categoria_nome FROM products p LEFT JOIN categorias c ON c.id = p.categoria_id WHERE " . implode(' AND ', $where) . " ORDER BY p.nome ASC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $products = $stmt->fetchAll();

        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE " . implode(' AND ', $where));
        $cntStmt->execute($params);

        jsonResponse(['products' => $products, 'total' => (int)$cntStmt->fetchColumn()]);
        break;

    case 'POST':
        requirePerfil(['admin', 'gerente']);

        $limite = isset($_SESSION['limite_produtos']) ? (int)$_SESSION['limite_produtos'] : 100;
        if ($limite !== -1) {
            $cntSt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE ativo=1 AND tenant_id=?");
            $cntSt->execute([$tid]);
            if ((int)$cntSt->fetchColumn() >= $limite) {
                jsonResponse(['error' => "Limite de {$limite} produtos atingido no seu plano. Faça upgrade para adicionar mais."], 403);
            }
        }

        $body = [];
        if (empty($_FILES)) {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
        }
        $nome = trim($_POST['nome'] ?? $body['nome'] ?? '');
        if (!$nome) jsonResponse(['error' => 'Nome do produto é obrigatório.'], 422);

        $imagemUrl = null;
        if (isset($_FILES['imagem'])) $imagemUrl = handleImageUpload($_FILES['imagem']);

        $codBar    = $_POST['codigo_barras'] ?? $body['codigo_barras'] ?? null;
        $catId     = !empty($_POST['categoria_id'] ?? $body['categoria_id']) ? (int)($_POST['categoria_id'] ?? $body['categoria_id']) : null;
        $preco     = (float)($_POST['preco'] ?? $_POST['preco_venda'] ?? $body['preco'] ?? 0);
        $custo     = (float)($_POST['custo'] ?? $body['custo'] ?? 0);
        $estoque   = (float)($_POST['estoque'] ?? $body['estoque'] ?? 0);
        $eMin      = (float)($_POST['estoque_minimo'] ?? $body['estoque_minimo'] ?? 5);
        $unidade   = $_POST['unidade'] ?? $body['unidade'] ?? 'un';
        $descricao = $_POST['descricao'] ?? $body['descricao'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO products (tenant_id,codigo_barras,nome,descricao,categoria_id,preco,custo,estoque,estoque_minimo,unidade,imagem_url,ativo,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,1,{$now})");
        $stmt->execute([$tid, $codBar, $nome, $descricao, $catId, $preco, $custo, $estoque, $eMin, $unidade, $imagemUrl]);
        jsonResponse(['message' => 'Produto criado com sucesso!', 'id' => (int)$pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requirePerfil(['admin', 'gerente']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID do produto não informado.'], 422);

        $cur = $pdo->prepare('SELECT imagem_url FROM products WHERE id=? AND tenant_id=?');
        $cur->execute([$id, $tid]);
        $current = $cur->fetch();

        $imagemUrl = $current['imagem_url'] ?? null;
        if (isset($_FILES['imagem'])) {
            $nova = handleImageUpload($_FILES['imagem']);
            if ($nova) $imagemUrl = $nova;
        }

        $body      = json_decode(file_get_contents('php://input'), true) ?: [];
        $nome      = trim($_POST['nome'] ?? $body['nome'] ?? '');
        $codBar    = $_POST['codigo_barras'] ?? $body['codigo_barras'] ?? null;
        $catId     = !empty($_POST['categoria_id'] ?? $body['categoria_id']) ? (int)($_POST['categoria_id'] ?? $body['categoria_id']) : null;
        $preco     = (float)($_POST['preco'] ?? $_POST['preco_venda'] ?? $body['preco'] ?? 0);
        $custo     = (float)($_POST['custo'] ?? $body['custo'] ?? 0);
        $estoque   = (float)($_POST['estoque'] ?? $body['estoque'] ?? 0);
        $eMin      = (float)($_POST['estoque_minimo'] ?? $body['estoque_minimo'] ?? 5);
        $unidade   = $_POST['unidade'] ?? $body['unidade'] ?? 'un';
        $descricao = $_POST['descricao'] ?? $body['descricao'] ?? null;
        $ativo     = isset($_POST['ativo']) ? (int)(bool)$_POST['ativo'] : (isset($body['ativo']) ? (int)(bool)$body['ativo'] : 1);

        $stmt = $pdo->prepare("UPDATE products SET codigo_barras=?,nome=?,descricao=?,categoria_id=?,preco=?,custo=?,estoque=?,estoque_minimo=?,unidade=?,imagem_url=?,ativo=?,updated_at={$now} WHERE id=? AND tenant_id=?");
        $stmt->execute([$codBar, $nome, $descricao, $catId, $preco, $custo, $estoque, $eMin, $unidade, $imagemUrl, $ativo, $id, $tid]);
        jsonResponse(['message' => 'Produto atualizado com sucesso!']);
        break;

    case 'DELETE':
        requirePerfil(['admin', 'gerente']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID não informado.'], 422);
        $pdo->prepare('DELETE FROM products WHERE id=? AND tenant_id=?')->execute([$id, $tid]);
        jsonResponse(['message' => 'Produto excluído permanentemente.']);
        break;

    default:
        jsonResponse(['error' => 'Método não permitido.'], 405);
}
