<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error' => 'GET only']); exit; }

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if (!preg_match('/^pi_[A-Za-z0-9]+$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid PaymentIntent id']);
    exit;
}

$config_file = __DIR__ . '/.env.php';
if (!file_exists($config_file)) { http_response_code(500); echo json_encode(['error' => 'Server config missing']); exit; }
$config = require $config_file;
$STRIPE_SECRET = $config['STRIPE_SECRET'] ?? '';
if (!$STRIPE_SECRET) { http_response_code(500); echo json_encode(['error' => 'Stripe key not configured']); exit; }

$ch = curl_init('https://api.stripe.com/v1/payment_intents/' . urlencode($id));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $STRIPE_SECRET],
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Stripe lookup failed', 'http_code' => $http_code]);
    exit;
}

$pi = json_decode($response, true);
// Devuelve solo campos seguros (no exponer client_secret, customer ids, metadata sensible)
echo json_encode([
    'status' => $pi['status'] ?? null,
    'amount' => $pi['amount'] ?? null,
    'currency' => $pi['currency'] ?? null,
]);
