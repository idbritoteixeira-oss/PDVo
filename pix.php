<?php
// ============================================================
// PDVo - API: Gerador de QR Code PIX (Payload BR Code)
// POST /api/pix.php
// Body: { "valor": 10.50, "descricao": "Venda #123" }
// ============================================================

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

cors();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido.'], 405);
}

$data      = getBody();
$valor     = (float)($data['valor'] ?? 0);
$descricao = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $data['descricao'] ?? 'PDVo'), 0, 25);

if ($valor <= 0) jsonResponse(['error' => 'Valor inválido.'], 422);

$pdo = getPDO();
$tid = tid();
$stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE chave IN ('pix_chave','pix_nome','pix_cidade') AND tenant_id=?");
$stmt->execute([$tid]);
$cfg = [];
foreach ($stmt->fetchAll() as $r) { $cfg[$r['chave']] = $r['valor']; }

$pixChave  = trim($cfg['pix_chave']  ?? '');
$pixNome   = substr(preg_replace('/[^A-Za-z ]/', '', $cfg['pix_nome']   ?? 'Loja'),   0, 25);
$pixCidade = substr(preg_replace('/[^A-Za-z ]/', '', $cfg['pix_cidade'] ?? 'Brasil'), 0, 15);

if (!$pixChave) jsonResponse(['error' => 'Chave PIX não configurada. Vá em Configurações.'], 422);

function pixField(string $id, string $value): string {
    return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function pixMerchantAccountInfo(string $chave): string {
    return pixField('26', pixField('00', 'BR.GOV.BCB.PIX') . pixField('01', $chave));
}

function pixCrc16(string $payload): string {
    $poly = 0x1021; $result = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $result ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $result = ($result & 0x8000) ? ($result << 1) ^ $poly : $result << 1;
        }
    }
    return strtoupper(str_pad(dechex($result & 0xFFFF), 4, '0', STR_PAD_LEFT));
}

$valorStr = number_format($valor, 2, '.', '');
$payload  = pixField('00', '01');
$payload .= pixMerchantAccountInfo($pixChave);
$payload .= pixField('52', '0000');
$payload .= pixField('53', '986');
$payload .= pixField('54', $valorStr);
$payload .= pixField('58', 'BR');
$payload .= pixField('59', $pixNome);
$payload .= pixField('60', $pixCidade);
$payload .= pixField('62', pixField('05', '***'));
$payload .= '6304';
$payload .= pixCrc16($payload);

jsonResponse(['payload' => $payload, 'valor' => $valor, 'pix_chave' => $pixChave, 'pix_nome' => $pixNome]);
