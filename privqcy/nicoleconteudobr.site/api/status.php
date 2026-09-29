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
$identifier = trim((string)($input['identifier'] ?? ''));

if ($identifier === '' || !preg_match('/^[A-Za-z0-9._:-]{3,200}$/', $identifier)) {
    http_response_code(400);
    echo json_encode(['message' => 'Identificador inválido.']);
    exit;
}

try {
    $apiKey = nexuspag_api_key();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Pagamento não configurado no servidor.']);
    exit;
}

$url = 'https://nexuspag.com/api/pix/' . rawurlencode($identifier);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'x-api-key: ' . $apiKey,
        'Accept: application/json',
    ],
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlError) {
    http_response_code(502);
    echo json_encode(['message' => 'Falha ao consultar o pagamento.']);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['message' => 'Resposta inválida do gateway.']);
    exit;
}

if ($statusCode < 200 || $statusCode >= 300) {
    http_response_code($statusCode >= 400 && $statusCode < 600 ? $statusCode : 502);
    echo json_encode(['message' => $data['message'] ?? $data['error'] ?? 'Não foi possível consultar o pagamento.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => [
        'status' => strtolower((string)($data['status'] ?? 'pending')),
        'id' => $data['id'] ?? null,
        'txid' => $data['txid'] ?? null,
        'external_id' => $data['external_id'] ?? null,
        'amount' => $data['amount'] ?? null,
        'paid_at' => $data['paid_at'] ?? null,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
