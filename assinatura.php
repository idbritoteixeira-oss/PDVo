<?php
// ============================================================
// PDVo - API: Assinatura
// GET  /api/assinatura.php              - Info assinatura atual
// POST /api/assinatura.php gerar_pix    - Gera PIX + salva pedido
// POST /api/assinatura.php planos       - Lista planos
// POST /api/assinatura.php confirmar_pagamento - Cliente confirma que pagou
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_length()) ob_clean();

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/master_db.php';

header('Content-Type: application/json');
cors();

$PLANOS = [
    1 => ['id' => 1, 'nome' => 'BASIC',  'valor' => 10.00,  'limite_produtos' => 100, 'limite_operadores' => 1,  'limite_gerentes' => 0,  'destaque' => false],
    2 => ['id' => 2, 'nome' => 'PRO',    'valor' => 25.00,  'limite_produtos' => -1,  'limite_operadores' => 10, 'limite_gerentes' => 1,  'destaque' => true],
    3 => ['id' => 3, 'nome' => 'SUPER',  'valor' => 109.99, 'limite_produtos' => -1,  'limite_operadores' => -1, 'limite_gerentes' => -1, 'destaque' => false],
];

$method = $_SERVER['REQUEST_METHOD'];
$data   = getBody();
$action = $data['action'] ?? $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        // Endpoints públicos (sem autenticação)
        if ($action === 'get_app_config') {
            try {
                $mPdo = getMasterPDO();
                $cfg  = getSuperadminConfig($mPdo);
                echo json_encode([
                    'ok'          => true,
                    'ativo'       => ($cfg['app_aviso_ativo'] ?? '0') === '1',
                    'titulo'      => $cfg['app_aviso_titulo']      ?? 'Baixe nosso App!',
                    'mensagem'    => $cfg['app_aviso_mensagem']    ?? '',
                    'url_android' => $cfg['app_aviso_url_android'] ?? '',
                    'url_ios'     => $cfg['app_aviso_url_ios']     ?? '',
                ]);
            } catch (Exception $e) {
                echo json_encode(['ok' => false]);
            }
            break;
        }
        // ---- Minhas Indicações (GET) ----
        if ($action === 'minhas_indicacoes') {
            requireLogin();
            $slug = trim($_GET['slug'] ?? '');
            if (!$slug) { echo json_encode(['ok' => false, 'error' => 'Slug não informado.']); exit; }
            try {
                $mPdo = getMasterPDO();
                // Try with reward_ajuste column (may not exist yet)
                try {
                    $stmt = $mPdo->prepare(
                        "SELECT COUNT(*) as total,
                                SUM(CASE WHEN status='validado' THEN 1 ELSE 0 END) as validados,
                                SUM(CASE WHEN status='validado' AND reward_ajuste IS NOT NULL THEN reward_ajuste ELSE 0 END) as valor_ganho
                         FROM indicacoes WHERE indicador_slug = ?"
                    );
                    $stmt->execute([$slug]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $ex2) {
                    // Fallback without reward_ajuste column
                    $stmt = $mPdo->prepare(
                        "SELECT COUNT(*) as total,
                                SUM(CASE WHEN status='validado' THEN 1 ELSE 0 END) as validados
                         FROM indicacoes WHERE indicador_slug = ?"
                    );
                    $stmt->execute([$slug]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $row['valor_ganho'] = 0;
                }
                $cfg = getSuperadminConfig($mPdo);
                echo json_encode([
                    'ok'              => true,
                    'total'           => (int)($row['total'] ?? 0),
                    'validados'       => (int)($row['validados'] ?? 0),
                    'valor_ganho'     => (float)($row['valor_ganho'] ?? 0),
                    'ativo'           => ($cfg['indicacao_ativo'] ?? '0') === '1',
                    'recompensa_desc' => $cfg['indicacao_recompensa_desc'] ?? '',
                ]);
            } catch (Exception $e) {
                echo json_encode(['ok' => false, 'error' => 'Erro ao buscar indicações.']);
            }
            exit;
        }

        // Endpoints autenticados
        requireLogin();
        try {
            $pdo   = getPDO();
            $plano = $pdo->query("SELECT * FROM config_plano LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $plano = null;
        }
        echo json_encode(['status' => 'success', 'assinatura_atual' => $plano, 'planos' => array_values($PLANOS)]);
        break;

    case 'POST':
        if ($action === 'planos') {
            echo json_encode(['planos' => array_values($PLANOS)]);
            break;
        }

        // ---- Gerar PIX ----
        if ($action === 'gerar_pix') {
            $planoId = (int)($data['plano_id'] ?? 0);
            if (!isset($PLANOS[$planoId])) { echo json_encode(['error' => 'Plano inexistente.']); exit; }

            $razao  = trim($data['razao_social'] ?? $data['razao'] ?? '');
            $nome   = trim($data['nome'] ?? '');
            $email  = trim($data['email'] ?? '');
            $whats  = trim($data['whatsapp'] ?? $data['whats'] ?? '');
            $codInd = trim($data['codigo_indicacao'] ?? '');

            if (!$razao || !$nome || !$email || !$whats) {
                echo json_encode(['error' => 'Preencha todos os dados antes de gerar o PIX.']); exit;
            }

            // Lê configurações PIX do superadmin_config (master DB)
            try {
                $masterPdo = getMasterPDO();
                $cfg       = getSuperadminConfig($masterPdo);
            } catch (Exception $e) {
                $cfg = [];
            }

            // Valida código de indicação (deve ser slug de tenant ativo)
            $indicadorInfo = null;
            if ($codInd) {
                try {
                    $masterPdo = getMasterPDO();
                    $stmtInd   = $masterPdo->prepare("SELECT id, email, razao_social FROM tenants WHERE slug = ? AND status = 'ativo' LIMIT 1");
                    $stmtInd->execute([$codInd]);
                    $indicadorInfo = $stmtInd->fetch();
                    if (!$indicadorInfo) $codInd = ''; // ignora código inválido
                } catch (Exception $e) { $codInd = ''; }
            }

            $pixChave  = trim($cfg['pix_chave']  ?? '') ?: 'naoconfigurado@pdvo.app';
            $pixNome   = substr(preg_replace('/[^A-Za-z ]/', '', trim($cfg['pix_nome']  ?? '') ?: 'PDVo Sistema'), 0, 25);
            $pixCidade = substr(preg_replace('/[^A-Za-z ]/', '', trim($cfg['pix_cidade'] ?? '') ?: 'SAO PAULO'), 0, 15);

            $plano   = $PLANOS[$planoId];
            $ref     = strtoupper(uniqid('PDV'));
            $payload = gerarPayloadPix($pixChave, $pixNome, $pixCidade, (float)$plano['valor'], "PDVo " . $plano['nome']);

            $pedidoId = null;
            try {
                $masterPdo = getMasterPDO();
                $masterPdo->prepare("INSERT INTO pedidos_ativacao (razao_social, nome, email, whatsapp, plano_id, plano_nome, valor, status, payload_pix, ref, codigo_indicacao)
                    VALUES (?,?,?,?,?,?,?,'pendente',?,?,?)")
                    ->execute([$razao, $nome, $email, $whats, $planoId, $plano['nome'], $plano['valor'], $payload, $ref, $codInd ?: null]);
                $pedidoId = (int)$masterPdo->lastInsertId();

                // Registra indicação se código válido
                if ($codInd && $indicadorInfo && $pedidoId) {
                    $masterPdo->prepare("INSERT INTO indicacoes (indicador_slug, indicador_email, indicador_razao, indicado_email, indicado_razao, pedido_id, pedido_ref, status)
                        VALUES (?,?,?,?,?,?,?,'pendente')")
                        ->execute([$codInd, $indicadorInfo['email'], $indicadorInfo['razao_social'], $email, $razao, $pedidoId, $ref]);
                }
            } catch (Exception $e) { error_log('[PDVo] gerar_pix error: ' . $e->getMessage()); }

            $resp = ['ok' => true, 'payload' => $payload, 'valor' => $plano['valor'], 'rank' => $plano['nome'], 'ref' => $ref];
            if ($codInd && $indicadorInfo) $resp['indicacao_valida'] = true;
            echo json_encode($resp);
            break;
        }

        // ---- Cliente confirma que pagou (notifica superadmin) ----
        if ($action === 'confirmar_pagamento') {
            $ref = trim($data['ref'] ?? '');
            if ($ref) {
                try {
                    $masterPdo = getMasterPDO();
                    $driver    = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
                    $nowExpr   = masterNowSql($driver);

                    $masterPdo->prepare("UPDATE pedidos_ativacao SET status='aguardando_confirmacao', updated_at=({$nowExpr}) WHERE ref=?")
                        ->execute([$ref]);

                    // Busca dados do pedido para montar o e-mail
                    $stmt = $masterPdo->prepare("SELECT * FROM pedidos_ativacao WHERE ref = ?");
                    $stmt->execute([$ref]);
                    $pedido = $stmt->fetch();

                    // Envia alerta para o e-mail de notificação do superadmin
                    $cfg            = getSuperadminConfig($masterPdo);
                    $emailNotif     = trim($cfg['email_notificacao'] ?? '');
                    $emailRemetente = trim($cfg['email_remetente']   ?? '');

                    // Fallback: usa domínio do servidor para não ser rejeitado
                    if (!$emailRemetente) {
                        $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'pdvo.app');
                        $emailRemetente = 'noreply@' . $host;
                    }

                    if ($pedido && $emailNotif) {
                        _notificarSuperadmin($pedido, $emailNotif, $emailRemetente, $masterPdo, $driver);
                    }
                } catch (Exception $e) {
                    // Loga silenciosamente mas não falha para o cliente
                    error_log('[PDVo] confirmar_pagamento error: ' . $e->getMessage());
                }
            }
            echo json_encode(['ok' => true, 'message' => 'Solicitação registrada! Nossa equipe vai confirmar e ativar seu acesso em breve.']);
            break;
        }

        // ---- Configuração do App (pública — para mostrar modal no login) ----
        if ($action === 'get_app_config') {
            try {
                $mPdo = getMasterPDO();
                $cfg  = getSuperadminConfig($mPdo);
                echo json_encode([
                    'ok'           => true,
                    'ativo'        => ($cfg['app_aviso_ativo'] ?? '0') === '1',
                    'titulo'       => $cfg['app_aviso_titulo']    ?? 'Baixe nosso App!',
                    'mensagem'     => $cfg['app_aviso_mensagem']  ?? '',
                    'url_android'  => $cfg['app_aviso_url_android'] ?? '',
                    'url_ios'      => $cfg['app_aviso_url_ios']     ?? '',
                ]);
            } catch (Exception $e) {
                echo json_encode(['ok' => false]);
            }
            exit;
        }

        // ---- Minhas Indicações (por slug do tenant) ----
        if ($action === 'get_indicacoes') {
            $slug = trim($data['slug'] ?? '');
            if (!$slug) { echo json_encode(['error' => 'Informe o código da empresa.']); exit; }
            try {
                $mPdo = getMasterPDO();
                // Valida que o slug existe
                $stmtT = $mPdo->prepare("SELECT razao_social, email FROM tenants WHERE slug = ? LIMIT 1");
                $stmtT->execute([$slug]);
                $tenant = $stmtT->fetch();
                if (!$tenant) {
                    $cfg2 = getSuperadminConfig($mPdo);
                    echo json_encode(['error' => 'Empresa não encontrada com este código.', 'ativo' => ($cfg2['indicacao_ativo'] ?? '0') === '1']);
                    exit;
                }
                // Busca indicações
                $stmtI = $mPdo->prepare("SELECT indicado_email, indicado_razao, status, observacoes, created_at, validated_at FROM indicacoes WHERE indicador_slug = ? ORDER BY created_at DESC");
                $stmtI->execute([$slug]);
                $lista = $stmtI->fetchAll();
                // Config de indicações
                $cfg = getSuperadminConfig($mPdo);
                echo json_encode([
                    'ok'             => true,
                    'razao'          => $tenant['razao_social'],
                    'indicacoes'     => $lista,
                    'ativo'          => ($cfg['indicacao_ativo'] ?? '0') === '1',
                    'recompensa_desc'=> $cfg['indicacao_recompensa_desc'] ?? '',
                ]);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erro ao buscar indicações.']);
            }
            exit;
        }

        echo json_encode(['error' => 'Ação inválida.']);
        break;

    case 'GET':
        // ---- Config pública do App (para o login) ----
        if ($action === 'get_app_config') {
            try {
                $mPdo = getMasterPDO();
                $cfg  = getSuperadminConfig($mPdo);
                echo json_encode([
                    'ok'          => true,
                    'ativo'       => ($cfg['app_aviso_ativo'] ?? '0') === '1',
                    'titulo'      => $cfg['app_aviso_titulo']      ?? 'Baixe nosso App!',
                    'mensagem'    => $cfg['app_aviso_mensagem']    ?? '',
                    'url_android' => $cfg['app_aviso_url_android'] ?? '',
                    'url_ios'     => $cfg['app_aviso_url_ios']     ?? '',
                ]);
            } catch (Exception $e) {
                echo json_encode(['ok' => false]);
            }
            exit;
        }
        break;

    default:
        echo json_encode(['error' => 'Método não permitido.']);
}

// ---- Notificação ao superadmin ----
function _notificarSuperadmin(array $pedido, string $para, string $remetente, PDO $masterPdo, string $driver = 'sqlite'): void {
    $assunto = "🔔 PDVo — Novo pagamento aguardando confirmação";
    $razao   = htmlspecialchars($pedido['razao_social']);
    $nome    = htmlspecialchars($pedido['nome']);
    $plano   = htmlspecialchars($pedido['plano_nome']);
    $valor   = 'R$ ' . number_format((float)$pedido['valor'], 2, ',', '.');
    $ref     = htmlspecialchars($pedido['ref'] ?? '—');
    $email   = htmlspecialchars($pedido['email']);
    $whats   = htmlspecialchars($pedido['whatsapp'] ?? '—');

    $corpo = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Novo Pagamento</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1e40af,#3b82f6);padding:28px 32px;">
            <h1 style="margin:0;color:#fff;font-size:24px;font-weight:900;">PDVo</h1>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.7);font-size:11px;letter-spacing:.2em;text-transform:uppercase;">SuperAdmin — Alerta de Pagamento</p>
          </td>
        </tr>
        <tr>
          <td style="padding:28px 32px;">
            <h2 style="margin:0 0 6px;color:#111827;font-size:18px;">🔔 Pagamento PIX confirmado pelo cliente</h2>
            <p style="margin:0 0 20px;color:#6b7280;font-size:13px;">Um cliente confirmou o pagamento e está aguardando ativação da conta.</p>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:22px;">
              <tr><td style="padding:18px 22px;">
                <table width="100%" cellpadding="4">
                  <tr><td style="color:#6b7280;font-size:12px;width:38%">Empresa</td><td style="color:#111827;font-size:13px;font-weight:700;">{$razao}</td></tr>
                  <tr><td style="color:#6b7280;font-size:12px;">Responsável</td><td style="color:#111827;font-size:13px;">{$nome}</td></tr>
                  <tr><td style="color:#6b7280;font-size:12px;">E-mail</td><td style="color:#111827;font-size:13px;">{$email}</td></tr>
                  <tr><td style="color:#6b7280;font-size:12px;">WhatsApp</td><td style="color:#111827;font-size:13px;">{$whats}</td></tr>
                  <tr><td style="color:#6b7280;font-size:12px;">Plano</td><td style="color:#2563eb;font-size:13px;font-weight:700;">{$plano}</td></tr>
                  <tr><td style="color:#6b7280;font-size:12px;">Valor</td><td style="color:#16a34a;font-size:14px;font-weight:800;">{$valor}</td></tr>
                  <tr><td style="color:#6b7280;font-size:12px;">Referência</td><td style="color:#111827;font-size:12px;font-family:monospace;">{$ref}</td></tr>
                </table>
              </td></tr>
            </table>

            <p style="color:#374151;font-size:13px;margin:0 0 6px;">Acesse o painel SuperAdmin para verificar o comprovante e ativar o acesso do cliente.</p>
            <p style="color:#9ca3af;font-size:11px;margin:0;">PDVo Sistema — Notificação automática</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: PDVo Notificações <{$remetente}>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $enviado = false;
    $erro    = null;
    try { $enviado = @mail($para, $assunto, $corpo, $headers); }
    catch (Throwable $e) { $erro = $e->getMessage(); }

    // Garante tabela e registra no log
    try {
        if ($driver === 'mysql') {
            $masterPdo->exec("CREATE TABLE IF NOT EXISTS email_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                para_email VARCHAR(255),
                para_nome VARCHAR(255),
                assunto VARCHAR(500),
                corpo MEDIUMTEXT,
                status VARCHAR(20) DEFAULT 'pendente',
                erro TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $masterPdo->exec("CREATE TABLE IF NOT EXISTS email_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                para_email TEXT, para_nome TEXT, assunto TEXT, corpo TEXT,
                status TEXT DEFAULT 'pendente', erro TEXT,
                criado_em TEXT DEFAULT (datetime('now','localtime'))
            )");
        }
        $masterPdo->prepare("INSERT INTO email_log (para_email, para_nome, assunto, corpo, status, erro) VALUES (?,?,?,?,?,?)")
            ->execute([$para, 'SuperAdmin', $assunto, $corpo, $enviado ? 'enviado' : 'falhou', $erro]);
    } catch (Exception $e) {
        error_log('[PDVo] email_log insert error: ' . $e->getMessage());
    }
}

// ---- Funções PIX ----
function pixField($id, $value): string {
    return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function gerarPayloadPix($chave, $nome, $cidade, $valor, $desc): string {
    $valorStr = number_format($valor, 2, '.', '');
    $gui   = pixField('00', 'BR.GOV.BCB.PIX');
    $key   = pixField('01', $chave);
    $ma    = pixField('26', $gui . $key);
    $body  = pixField('00','01') . $ma . pixField('52','0000') . pixField('53','986')
           . pixField('54', $valorStr) . pixField('58','BR')
           . pixField('59', substr($nome, 0, 25)) . pixField('60', substr($cidade, 0, 15))
           . pixField('62', pixField('05','***')) . '6304';
    return $body . pixCrc16($body);
}

function pixCrc16($payload): string {
    $res = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $res ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $res = ($res & 0x8000) ? ($res << 1) ^ 0x1021 : $res << 1;
        }
    }
    return strtoupper(str_pad(dechex($res & 0xFFFF), 4, '0', STR_PAD_LEFT));
}
