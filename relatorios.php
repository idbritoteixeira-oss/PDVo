<?php
// ============================================================
// PDVo - API: Relatórios
// GET /api/relatorios.php?action=vendas&de=YYYY-MM-DD&ate=YYYY-MM-DD
// GET /api/relatorios.php?action=produtos_mais_vendidos&de=...&ate=...
// GET /api/relatorios.php?action=fluxo_caixa&de=...&ate=...
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();
requireSubscription();

$pdo    = getPDO();
$tid    = tid();
$action = $_GET['action'] ?? '';

$de  = $_GET['de']  ?? date('Y-m-01');
$ate = $_GET['ate'] ?? date('Y-m-d');

// Sanitiza datas
$de  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $de)  ? $de  : date('Y-m-01');
$ate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate) ? $ate : date('Y-m-d');

// ---- Relatório de Vendas ----
if ($action === 'vendas') {
    // Resumo geral
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_vendas,
            COALESCE(SUM(total), 0) AS receita_total,
            COALESCE(AVG(total), 0) AS ticket_medio,
            COALESCE(SUM(desconto), 0) AS total_descontos,
            COUNT(DISTINCT DATE(created_at)) AS dias_com_venda
        FROM sales
        WHERE tenant_id = ?
          AND status = 'finalizada'
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$tid, $de, $ate]);
    $resumo = $stmt->fetch();

    // Por dia
    $stmt = $pdo->prepare("
        SELECT
            DATE(created_at) AS dia,
            COUNT(*) AS num_vendas,
            COALESCE(SUM(total), 0) AS total_dia
        FROM sales
        WHERE tenant_id = ?
          AND status = 'finalizada'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY dia ASC
    ");
    $stmt->execute([$tid, $de, $ate]);
    $porDia = $stmt->fetchAll();

    // Por forma de pagamento
    $stmt = $pdo->prepare("
        SELECT
            forma_pagamento,
            COUNT(*) AS num,
            COALESCE(SUM(total), 0) AS total
        FROM sales
        WHERE tenant_id = ?
          AND status = 'finalizada'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY forma_pagamento
        ORDER BY total DESC
    ");
    $stmt->execute([$tid, $de, $ate]);
    $porPagamento = $stmt->fetchAll();

    // Listagem detalhada
    $stmt = $pdo->prepare("
        SELECT
            s.id, s.created_at, s.total, s.desconto,
            s.forma_pagamento, s.status,
            u.nome AS operador
        FROM sales s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.tenant_id = ?
          AND s.status = 'finalizada'
          AND DATE(s.created_at) BETWEEN ? AND ?
        ORDER BY s.created_at DESC
        LIMIT 500
    ");
    $stmt->execute([$tid, $de, $ate]);
    $vendas = $stmt->fetchAll();

    jsonResponse([
        'resumo'        => $resumo,
        'por_dia'       => $porDia,
        'por_pagamento' => $porPagamento,
        'vendas'        => $vendas,
        'periodo'       => ['de' => $de, 'ate' => $ate],
    ]);
}

// ---- Produtos mais vendidos ----
if ($action === 'produtos_mais_vendidos') {
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.nome, p.codigo_barras, p.unidade,
            p.preco,
            SUM(si.quantidade) AS qtd_vendida,
            SUM(si.subtotal)   AS receita,
            COUNT(DISTINCT si.sale_id) AS num_vendas
        FROM sale_items si
        JOIN sales s ON s.id = si.sale_id
        JOIN products p ON p.id = si.product_id
        WHERE s.tenant_id = ?
          AND s.status = 'finalizada'
          AND DATE(s.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.nome, p.codigo_barras, p.unidade, p.preco
        ORDER BY qtd_vendida DESC
        LIMIT 50
    ");
    $stmt->execute([$tid, $de, $ate]);
    jsonResponse(['produtos' => $stmt->fetchAll(), 'periodo' => ['de' => $de, 'ate' => $ate]]);
}

// ---- Fluxo de caixa (aberturas e fechamentos) ----
if ($action === 'fluxo_caixa') {
    $stmt = $pdo->prepare("
        SELECT
            c.id, c.aberto_em, c.fechado_em,
            c.saldo_inicial, c.saldo_final,
            c.total_vendas, c.status,
            u.nome AS operador
        FROM caixas c
        LEFT JOIN users u ON u.id = c.user_id
        WHERE c.tenant_id = ?
          AND DATE(c.aberto_em) BETWEEN ? AND ?
        ORDER BY c.aberto_em DESC
        LIMIT 200
    ");
    $stmt->execute([$tid, $de, $ate]);
    $caixas = $stmt->fetchAll();

    $stmt2 = $pdo->prepare("
        SELECT
            COALESCE(SUM(total_vendas), 0) AS receita,
            COALESCE(SUM(saldo_final - saldo_inicial), 0) AS saldo_liquido,
            COUNT(*) AS num_caixas
        FROM caixas
        WHERE tenant_id = ?
          AND DATE(aberto_em) BETWEEN ? AND ?
          AND status = 'fechado'
    ");
    $stmt2->execute([$tid, $de, $ate]);
    $resumo = $stmt2->fetch();

    jsonResponse(['caixas' => $caixas, 'resumo' => $resumo, 'periodo' => ['de' => $de, 'ate' => $ate]]);
}

// ---- Estoque snapshot ----
if ($action === 'estoque') {
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.nome, p.codigo_barras, p.unidade,
            p.preco, p.custo,
            p.estoque, p.estoque_minimo,
            c.nome AS categoria,
            (p.estoque * p.custo) AS valor_estoque
        FROM products p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE p.tenant_id = ? AND p.ativo = 1
        ORDER BY p.nome ASC
    ");
    $stmt->execute([$tid]);
    $produtos = $stmt->fetchAll();

    $valorTotal = array_sum(array_column($produtos, 'valor_estoque'));
    $abaixoMin  = count(array_filter($produtos, fn($p) => floatval($p['estoque']) <= floatval($p['estoque_minimo'])));

    jsonResponse([
        'produtos'    => $produtos,
        'valor_total' => $valorTotal,
        'abaixo_min'  => $abaixoMin,
    ]);
}

jsonResponse(['error' => 'Ação inválida.'], 404);
