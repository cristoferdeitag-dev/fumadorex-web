<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST only']); exit; }

// Read Stripe secret from /api/.env.php (NOT in git, uploaded manually)
$config_file = __DIR__ . '/.env.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server config missing']);
    exit;
}
$config = require $config_file;
$STRIPE_SECRET = $config['STRIPE_SECRET'] ?? '';
if (!$STRIPE_SECRET) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe key not configured']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email inválido']);
    exit;
}
if (!$name || strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre requerido']);
    exit;
}

// $6,999.00 MXN = 699900 centavos
$amount = 699900;

$post_fields = [
    'amount' => $amount,
    'currency' => 'mxn',
    'receipt_email' => $email,
    'description' => 'Programa Fumadorex - 8 semanas',
    'metadata[email]' => $email,
    'metadata[name]' => $name,
    'metadata[phone]' => $phone,
    'metadata[source]' => 'fumadorex.com.mx landing inline',
    'metadata[curso_fecha]' => '2026-07-25',
    'automatic_payment_methods[enabled]' => 'true',
];

$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $STRIPE_SECRET],
    CURLOPT_POSTFIELDS => http_build_query($post_fields),
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Stripe error',
        'http_code' => $http_code,
        'curl_error' => $curl_err,
        'stripe_response' => json_decode($response, true),
    ]);
    exit;
}

$data = json_decode($response, true);
echo json_encode([
    'client_secret' => $data['client_secret'],
    'payment_intent_id' => $data['id'],
    'amount' => $amount,
    'currency' => 'mxn',
]);
