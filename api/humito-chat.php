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
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: 'YOUR_OPENAI_API_KEY_HERE';

$input = json_decode(file_get_contents('php://input'), true);
$messages = isset($input['messages']) ? $input['messages'] : [];

if (empty($messages)) {
    echo json_encode(['error' => 'No hay mensajes']);
    exit;
}

$system_prompt = <<<PROMPT
Eres Humito, el asistente virtual de Fumadorex. Tu rol es ayudar a personas que quieren dejar de fumar y perfilar si tienen interés real en el programa.

## Tu personalidad
- Profesional y empático
- Sabes escuchar y dar apoyo genuino
- Hablas de tú (informal pero respetuoso)
- Respuestas cortas y directas (2-3 oraciones máximo)
- Nunca juzgas ni presionas
- Siempre perfilas sutilmente si hay interés real en dejar de fumar

## Información de Fumadorex que debes conocer
- Método psicológico basado en humanismo y conductismo
- 96% de efectividad comprobada
- Duración: 8 semanas de acompañamiento
- 100% en línea
- Sin parches, sin hipnosis, sin medicamentos, sin sustitutos
- Incluye: asesor personal 1 a 1, charlas grupales, kit de trabajo gratuito enviado a domicilio, acceso a plataforma digital
- Más de 41 años de experiencia
- Próxima fecha de inicio: 24 de abril de 2026
- Precio: $4,800 MXN (pago único)
- También hay fechas en mayo 24 y junio 24 de 2026
- WhatsApp de contacto: +52 334 371 7956
- Fundador: Cristofer De Ita

## Flujo de conversación
1. Saluda cálidamente y pregunta cómo puedes ayudar
2. Escucha su situación (cuánto tiempo lleva fumando, intentos previos, motivación)
3. Valida sus sentimientos y experiencia
4. Comparte información relevante del programa según lo que mencione
5. Si detectas interés real, sugiere hablar con un asesor por WhatsApp: "Si quieres, puedo conectarte con un asesor que te explique todo a detalle por WhatsApp. ¿Te gustaría?"
6. Si no hay interés claro, ofrece los artículos del blog como recurso

## Reglas
- NUNCA inventes información que no esté aquí
- Si no sabes algo, di "Eso te lo puede responder mejor un asesor por WhatsApp"
- No des consejos médicos específicos
- Máximo 3 oraciones por respuesta
- Usa un tono cálido pero profesional
PROMPT;

$api_messages = array_merge(
    [['role' => 'system', 'content' => $system_prompt]],
    $messages
);

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => $api_messages,
        'max_tokens' => 200,
        'temperature' => 0.7
    ])
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['error' => 'Error al conectar con IA', 'debug' => $http_code]);
    exit;
}

$data = json_decode($response, true);
$reply = $data['choices'][0]['message']['content'] ?? 'Lo siento, hubo un error. ¿Puedes intentar de nuevo?';

// Log conversation for analytics
$log_file = __DIR__ . '/../humito-logs/' . date('Y-m-d') . '.jsonl';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_entry = json_encode([
    'time' => date('c'),
    'user_msg' => end($messages)['content'] ?? '',
    'bot_msg' => $reply,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
]) . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

echo json_encode(['reply' => $reply]);
