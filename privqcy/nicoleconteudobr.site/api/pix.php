<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['message' => 'JSON inválido.']);
    exit;
}

/*
 * Never trust the amount sent by the browser. Keep the prices defined by the
 * site on the server so a client cannot alter the charge to an arbitrary value.
 */
$allowed = [
    9.90  => '15 dias',
    14.90 => '30 dias',
    32.90 => '3 meses',
    24.90 => '6 meses',
];

$amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
$matchedAmount = null;
foreach ($allowed as $price => $plan) {
    if (abs($amount - $price) < 0.001) {
        $matchedAmount = $price;
        $planName = $plan;
        break;
    }
}

if ($matchedAmount === null) {
    http_response_code(400);
    echo json_encode(['message' => 'Valor de plano inválido.']);
    exit;
}

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));

if ($name === '' || count(preg_split('/\s+/', $name)) < 2) {
    http_response_code(400);
    echo json_encode(['message' => 'Informe seu nome completo.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Informe um e-mail válido.']);
    exit;
}

function make_external_id(float $amount): string {
    $cents = (int)round($amount * 100);
    return 'site-' . $cents . '-' . bin2hex(random_bytes(12));
}

$externalId = make_external_id($matchedAmount);
$description = 'Assinatura ' . $planName . ' - Izabela Soares';

/*
 * If your production URL differs, set NEXUSPAG_WEBHOOK_URL on the server.
 * Polling remains available through status.php.
 */
$webhookUrl = getenv('NEXUSPAG_WEBHOOK_URL') ?: '';

$payload = [
    'amount' => round($matchedAmount, 2),
    'description' => $description,
    'external_id' => $externalId,
    'expiration' => 1800,
];

if ($webhookUrl !== '') {
    $payload['webhook_url'] = $webhookUrl;
}

try {
    $apiKey = nexuspag_api_key();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Pagamento não configurado no servidor.']);
    exit;
}

$ch = curl_init('https://nexuspag.com/api/pix/create');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_HTTPHEADER => [
        'x-api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlError) {
    http_response_code(502);
    echo json_encode(['message' => 'Não foi possível conectar ao gateway de pagamento.']);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['message' => 'Resposta inválida do gateway.']);
    exit;
}

if ($statusCode < 200 || $statusCode >= 300) {
    $message = $data['message'] ?? $data['error'] ?? 'O gateway recusou a cobrança.';
    http_response_code($statusCode >= 400 && $statusCode < 600 ? $statusCode : 502);
    echo json_encode(['message' => $message]);
    exit;
}

// Normalize NexusPag's response into the fields expected by the existing frontend.
// NexusPag returns the transaction inside "transaction".
$transaction = isset($data['transaction']) && is_array($data['transaction'])
    ? $data['transaction']
    : $data;

$pixCode = (string)($transaction['pix_copia_cola']
    ?? $transaction['pix_code']
    ?? $transaction['emv']
    ?? '');

$qrBase64 = (string)($transaction['qr_code_base64'] ?? '');
$qrUrl = '';

if ($qrBase64 !== '') {
    $qrUrl = strpos($qrBase64, 'data:image') === 0
        ? $qrBase64
        : 'data:image/png;base64,' . $qrBase64;
}

$identifier = $transaction['id']
    ?? $transaction['txid']
    ?? $transaction['external_id']
    ?? $externalId;

if ($pixCode === '') {
    http_response_code(502);
    echo json_encode(['message' => 'A NexusPag não retornou o código PIX.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'identifier' => $identifier,
    'transaction_id' => $transaction['id'] ?? null,
    'txid' => $transaction['txid'] ?? null,
    'pix_code' => $pixCode,
    'qr_code_url' => $qrUrl,
    'amount' => round((float)($transaction['amount'] ?? $matchedAmount), 2),
    'plan' => $planName,
    'status' => strtolower((string)($transaction['status'] ?? 'pending')),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
