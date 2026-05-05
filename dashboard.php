<?php
// ============================================================
// PDVo - API: Dashboard (tenant_id isolamento centralizado)
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

$pdo      = getPDO();
$tid      = tid();
$isSqlite = (DB_DRIVER === 'sqlite');

if ($isSqlite) {
    $today    = "date('now','localtime')";
    $minus7d  = "datetime('now','-7 days','localtime')";
    $minus12m = "datetime('now','-365 days','localtime')";
    $yearNow  = "strftime('%Y',datetime('now','localtime'))";
    $monthNow = "strftime('%m',datetime('now','localtime'))";
    $yearFmt  = "strftime('%Y', created_at)";
    $monthFmt = "strftime('%m', created_at)";
    $ymFmt    = "strftime('%Y-%m', created_at)";
    $dateOf   = "date(created_at)";
} else {
    $today    = 'CURDATE()';
    $minus7d  = 'DATE_SUB(NOW(), INTERVAL 7 DAY)';
    $minus12m = 'DATE_SUB(NOW(), INTERVAL 12 MONTH)';
    $yearNow  = 'YEAR(NOW())';
    $monthNow = 'MONTH(NOW())';
    $yearFmt  = 'YEAR(created_at)';
    $monthFmt = 'MONTH(created_at)';
    $ymFmt    = "DATE_FORMAT(created_at, '%Y-%m')";
    $dateOf   = 'DATE(created_at)';
}

$cntSt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE ativo=1 AND tenant_id=?');
$cntSt->execute([$tid]);
$totalProdutos = (int)$cntSt->fetchColumn();

$estSt = $pdo->prepare("SELECT id,nome,estoque,estoque_minimo,unidade FROM products WHERE ativo=1 AND estoque<=estoque_minimo AND tenant_id=? ORDER BY estoque ASC LIMIT 10");
$estSt->execute([$tid]);
$estoqueBaixo = $estSt->fetchAll();

$hojeSt = $pdo->prepare("SELECT COUNT(*) AS total_vendas, COALESCE(SUM(total),0) AS total_valor FROM sales WHERE {$dateOf}={$today} AND status='concluida' AND tenant_id=?");
$hojeSt->execute([$tid]);
$hoje = $hojeSt->fetch();

$mesSt = $pdo->prepare("SELECT COUNT(*) AS total_vendas, COALESCE(SUM(total),0) AS total_valor FROM sales WHERE {$yearFmt}={$yearNow} AND {$monthFmt}={$monthNow} AND status='concluida' AND tenant_id=?");
$mesSt->execute([$tid]);
$mes = $mesSt->fetch();

$diagSt = $pdo->prepare("SELECT {$dateOf} AS data, COUNT(*) AS qtd_vendas, COALESCE(SUM(total),0) AS total FROM sales WHERE created_at>={$minus7d} AND status='concluida' AND tenant_id=? GROUP BY {$dateOf} ORDER BY data ASC");
$diagSt->execute([$tid]);
$graficoDiario = $diagSt->fetchAll();

$mensSt = $pdo->prepare("SELECT {$ymFmt} AS mes, COUNT(*) AS qtd_vendas, COALESCE(SUM(total),0) AS total FROM sales WHERE created_at>={$minus12m} AND status='concluida' AND tenant_id=? GROUP BY {$ymFmt} ORDER BY mes ASC");
$mensSt->execute([$tid]);
$graficoMensal = $mensSt->fetchAll();

$fpSt = $pdo->prepare("SELECT forma_pagamento, COUNT(*) AS qtd, COALESCE(SUM(total),0) AS total FROM sales WHERE {$yearFmt}={$yearNow} AND {$monthFmt}={$monthNow} AND status='concluida' AND tenant_id=? GROUP BY forma_pagamento");
$fpSt->execute([$tid]);
$formasPagamento = $fpSt->fetchAll();

$yearFmtS  = $isSqlite ? "strftime('%Y', s.created_at)" : 'YEAR(s.created_at)';
$monthFmtS = $isSqlite ? "strftime('%m', s.created_at)" : 'MONTH(s.created_at)';
$topSt = $pdo->prepare("SELECT p.nome, SUM(si.quantidade) AS total_qtd, SUM(si.subtotal) AS total_valor FROM sale_items si JOIN products p ON p.id=si.product_id JOIN sales s ON s.id=si.sale_id WHERE {$yearFmtS}={$yearNow} AND {$monthFmtS}={$monthNow} AND s.status='concluida' AND si.tenant_id=? GROUP BY si.product_id ORDER BY total_qtd DESC LIMIT 5");
$topSt->execute([$tid]);
$topProdutos = $topSt->fetchAll();

$uSt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ativo=1 AND tenant_id=?');
$uSt->execute([$tid]);
$totalUsuarios = (int)$uSt->fetchColumn();

$cxSt = $pdo->prepare("SELECT c.id,c.fundo_caixa,c.total_vendas,c.abertura,u.nome AS operador FROM caixas c JOIN users u ON u.id=c.user_id WHERE c.status='aberto' AND c.tenant_id=? ORDER BY c.abertura DESC LIMIT 1");
$cxSt->execute([$tid]);
$caixaAberto = $cxSt->fetch();

jsonResponse([
    'total_produtos'   => $totalProdutos,
    'total_usuarios'   => $totalUsuarios,
    'estoque_baixo'    => $estoqueBaixo,
    'hoje'             => $hoje,
    'mes'              => $mes,
    'grafico_diario'   => $graficoDiario,
    'grafico_mensal'   => $graficoMensal,
    'formas_pagamento' => $formasPagamento,
    'top_produtos'     => $topProdutos,
    'caixa_aberto'     => $caixaAberto ?: null,
]);
