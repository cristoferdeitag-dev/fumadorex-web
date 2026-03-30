<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Email inválido']);
    exit;
}

$db_host = '127.0.0.1';
$db_user = 'u781187371_7xH0l';
$db_pass = 'Nb5uzVjo2T';
$db_name = 'u781187371_eBdRp';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'error' => 'Error de conexión']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO fumadorex_suscriptores (email) VALUES (?) ON DUPLICATE KEY UPDATE fecha = CURRENT_TIMESTAMP");
$stmt->bind_param('s', $email);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => '¡Gracias por suscribirte!']);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar']);
}

$stmt->close();
$conn->close();
