<?php
// ============================================================
// PDVo — Cron: Alertas de Vencimento de Assinatura
// Dispara e-mails 3 dias antes, 1 dia antes e no dia.
//
// Uso via CLI (cPanel Cron Jobs):
//   php /home/.../public_html/pdvo/api/cron_vencimento.php
//
// Uso via URL (cPanel → Cron URL):
//   https://pdv.enxos.online/pdvo/api/cron_vencimento.php?token=SEU_TOKEN
//
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ---- Proteção de acesso ----
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    // Via HTTP: exige token
    $tokenEnviado = trim($_GET['token'] ?? '');

    // Carrega config para usar DB_DRIVER e SECRET_KEY
    $configFile = dirname(__DIR__) . '/config.php';
    if (file_exists($configFile)) require_once $configFile;

    $tokenEsperado = defined('CRON_TOKEN')
        ? CRON_TOKEN
        : substr(hash('sha256', defined('SECRET_KEY') ? SECRET_KEY : 'pdvo_cron_default'), 0, 32);

    if (!hash_equals($tokenEsperado, $tokenEnviado)) {
        http_response_code(403);
        die(json_encode(['error' => 'Acesso negado.']));
    }

    header('Content-Type: application/json');
} else {
    $configFile = dirname(__DIR__) . '/config.php';
    if (file_exists($configFile)) require_once $configFile;
}

require_once __DIR__ . '/config/master_db.php';

// ---- Executa o envio ----
$resultado = enviarAlertasVencimento();

if ($isCli) {
    echo "[PDVo Cron] " . date('Y-m-d H:i:s') . "\n";
    echo "Processados : {$resultado['total']}\n";
    echo "Enviados    : {$resultado['enviados']}\n";
    echo "Falhos      : {$resultado['falhos']}\n";
    if (!empty($resultado['detalhes'])) {
        foreach ($resultado['detalhes'] as $d) {
            echo "  [{$d['status']}] {$d['email']} — {$d['dias']} dia(s)\n";
        }
    }
} else {
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ================================================================
// Função principal
// ================================================================
function enviarAlertasVencimento(): array {
    $driver    = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
    $resultado = ['total' => 0, 'enviados' => 0, 'falhos' => 0, 'detalhes' => []];

    try {
        $masterPdo = getMasterPDO();
        $cfg       = getSuperadminConfig($masterPdo);

        $emailRemetente = trim($cfg['email_remetente'] ?? '');
        if (!$emailRemetente) {
            $host           = gethostname() ?: 'pdvo.app';
            $emailRemetente = 'noreply@pdvo.app';
        }

        // --- Busca tenants ativos que vencem em 3, 1 ou 0 dias ---
        if ($driver === 'mysql') {
            $sql = "SELECT id, razao_social, nome_resp, email, plano_nome, data_vencimento,
                           DATEDIFF(DATE(data_vencimento), CURDATE()) AS dias_restantes
                    FROM tenants
                    WHERE status = 'ativo'
                      AND DATEDIFF(DATE(data_vencimento), CURDATE()) IN (3, 1, 0)";
        } else {
            $sql = "SELECT id, razao_social, nome_resp, email, plano_nome, data_vencimento,
                           CAST(julianday(date(data_vencimento)) - julianday(date('now','localtime')) AS INTEGER) AS dias_restantes
                    FROM tenants
                    WHERE status = 'ativo'
                      AND CAST(julianday(date(data_vencimento)) - julianday(date('now','localtime')) AS INTEGER) IN (3, 1, 0)";
        }

        $tenants = $masterPdo->query($sql)->fetchAll();
        $resultado['total'] = count($tenants);

        foreach ($tenants as $t) {
            $diasRestantes = (int)$t['dias_restantes'];
            $enviado = _enviarEmailVencimento($t, $diasRestantes, $emailRemetente, $masterPdo, $driver);

            $resultado['detalhes'][] = [
                'email'  => $t['email'],
                'dias'   => $diasRestantes,
                'status' => $enviado ? 'enviado' : 'falhou',
            ];

            if ($enviado) $resultado['enviados']++;
            else          $resultado['falhos']++;
        }

    } catch (Throwable $e) {
        $resultado['erro'] = $e->getMessage();
        error_log('[PDVo Cron] Erro: ' . $e->getMessage());
    }

    return $resultado;
}

// ================================================================
// Monta e envia o e-mail de alerta
// ================================================================
function _enviarEmailVencimento(array $t, int $dias, string $remetente, PDO $masterPdo, string $driver): bool {
    $razao     = htmlspecialchars($t['razao_social']);
    $nome      = htmlspecialchars($t['nome_resp']);
    $email     = $t['email'];
    $plano     = htmlspecialchars($t['plano_nome']);
    $vencimento = date('d/m/Y', strtotime($t['data_vencimento']));

    if ($dias === 0) {
        $assunto  = "⚠️ PDVo — Sua assinatura vence HOJE!";
        $urgencia = "#dc2626";
        $titulo   = "⚠️ Sua assinatura vence hoje!";
        $mensagem = "Sua assinatura do plano <strong>{$plano}</strong> vence <strong>hoje ({$vencimento})</strong>. Para não perder o acesso ao PDVo, renove agora.";
        $badge    = "VENCE HOJE";
        $badgeBg  = "#dc2626";
    } elseif ($dias === 1) {
        $assunto  = "🔔 PDVo — Sua assinatura vence amanhã";
        $urgencia = "#f59e0b";
        $titulo   = "🔔 Sua assinatura vence amanhã!";
        $mensagem = "Sua assinatura do plano <strong>{$plano}</strong> vence <strong>amanhã ({$vencimento})</strong>. Renove hoje para garantir continuidade sem interrupções.";
        $badge    = "VENCE AMANHÃ";
        $badgeBg  = "#f59e0b";
    } else {
        $assunto  = "📅 PDVo — Sua assinatura vence em 3 dias";
        $urgencia = "#2563eb";
        $titulo   = "📅 Sua assinatura vence em 3 dias";
        $mensagem = "Sua assinatura do plano <strong>{$plano}</strong> vence em <strong>3 dias ({$vencimento})</strong>. Aproveite para renovar com antecedência e evitar qualquer interrupção.";
        $badge    = "VENCE EM 3 DIAS";
        $badgeBg  = "#2563eb";
    }

    $corpo = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>{$assunto}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

        <!-- Cabeçalho -->
        <tr>
          <td style="background:linear-gradient(135deg,#1e40af,#3b82f6);padding:28px 32px;">
            <h1 style="margin:0;color:#fff;font-size:24px;font-weight:900;">PDVo</h1>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.7);font-size:11px;letter-spacing:.2em;text-transform:uppercase;">Aviso de Vencimento</p>
          </td>
        </tr>

        <!-- Corpo -->
        <tr>
          <td style="padding:28px 32px;">

            <!-- Badge urgência -->
            <div style="display:inline-block;background:{$badgeBg};color:#fff;font-size:10px;font-weight:800;
                        letter-spacing:.15em;padding:4px 14px;border-radius:99px;margin-bottom:18px;">
              {$badge}
            </div>

            <h2 style="margin:0 0 8px;color:#111827;font-size:20px;">{$titulo}</h2>
            <p style="margin:0 0 22px;color:#4b5563;font-size:14px;line-height:1.6;">
              Olá, <strong>{$nome}</strong>! {$mensagem}
            </p>

            <!-- Detalhes da assinatura -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:24px;">
              <tr><td style="padding:18px 22px;">
                <table width="100%" cellpadding="5">
                  <tr>
                    <td style="color:#6b7280;font-size:12px;width:40%">Empresa</td>
                    <td style="color:#111827;font-size:13px;font-weight:700;">{$razao}</td>
                  </tr>
                  <tr>
                    <td style="color:#6b7280;font-size:12px;">Plano atual</td>
                    <td style="color:#2563eb;font-size:13px;font-weight:700;">{$plano}</td>
                  </tr>
                  <tr>
                    <td style="color:#6b7280;font-size:12px;">Vencimento</td>
                    <td style="color:{$urgencia};font-size:13px;font-weight:800;">{$vencimento}</td>
                  </tr>
                </table>
              </td></tr>
            </table>

            <!-- Botão renovar -->
            <table cellpadding="0" cellspacing="0" width="100%">
              <tr><td align="center">
                <a href="https://pdv.enxos.online/pdvo/assinatura.html"
                   style="display:inline-block;background:{$urgencia};color:#fff;font-size:14px;
                          font-weight:800;text-decoration:none;padding:14px 40px;border-radius:12px;
                          letter-spacing:.05em;">
                  Renovar Assinatura →
                </a>
              </td></tr>
            </table>

            <p style="color:#9ca3af;font-size:11px;margin:24px 0 0;text-align:center;">
              PDVo Sistema — Aviso automático &nbsp;|&nbsp; pdv.enxos.online
            </p>
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
    $headers .= "From: PDVo Sistema <{$remetente}>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $enviado = false;
    $erro    = null;
    try {
        $enviado = @mail($email, $assunto, $corpo, $headers);
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }

    // Salva no log
    try {
        $masterPdo->prepare(
            "INSERT INTO email_log (para_email, para_nome, assunto, corpo, status, erro) VALUES (?,?,?,?,?,?)"
        )->execute([$email, $nome, $assunto, $corpo, $enviado ? 'enviado' : 'falhou', $erro]);
    } catch (Exception $e) {}

    return $enviado;
}
