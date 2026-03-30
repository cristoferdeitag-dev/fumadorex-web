<?php
/**
 * Fumadorex Webhook — Facebook Lead Ads → ManyChat → WhatsApp
 * Colocar en: fumadorex.com.mx/webhook.php
 */

define('VERIFY_TOKEN',   'fumadorex_2026_secret');
define('MANYCHAT_TOKEN', '1548543:ef699d6f54bb573d422261f65bd0ed72'); // Reemplazar
define('PAGE_ID',        '362883273568556');   // Reemplazar

// ─── Verificación del Webhook (Meta lo llama 1 vez al registrarlo) ───────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']         ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge']    ?? '';

    if ($mode === 'subscribe' && $token === VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit('Forbidden');
}

// ─── Recepción de leads (POST) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    // Log para debug (puedes comentar en producción)
    file_put_contents('/tmp/fb_leads.log', date('c') . " | " . $raw . "\n", FILE_APPEND);

    if (!$data || $data['object'] !== 'page') {
        http_response_code(200); // Siempre responder 200 a Meta
        exit;
    }

    foreach ($data['entry'] as $entry) {
        if ($entry['id'] !== PAGE_ID) continue;

        foreach ($entry['changes'] as $change) {
            if ($change['field'] !== 'leadgen') continue;

            $lead_id = $change['value']['leadgen_id'] ?? null;
            $form_id = $change['value']['form_id']    ?? null;

            if (!$lead_id) continue;

            // Obtener datos del lead desde Meta Graph API
            $lead = fetch_lead($lead_id);
            if (!$lead) continue;

            $name  = $lead['name']  ?? 'Lead';
            $phone = $lead['phone'] ?? null;
            $email = $lead['email'] ?? null;

            // Enviar a ManyChat si hay teléfono
            if ($phone) {
                send_to_manychat($phone, $name, $email);
            }
        }
    }

    http_response_code(200);
    echo 'OK';
    exit;
}

// ─── Funciones ────────────────────────────────────────────────────────────────

function fetch_lead(string $lead_id): ?array {
    // Necesita Page Access Token
    $page_token = getenv('FB_PAGE_TOKEN') ?: '1027614072032885|ZdZn134KGsOpSWlpSphXgimD3_k';
    $url = "https://graph.facebook.com/v20.0/{$lead_id}?access_token={$page_token}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;

    $json = json_decode($res, true);
    $out  = [];

    // Mapear los field_data del formulario
    foreach ($json['field_data'] ?? [] as $field) {
        $key = strtolower($field['name']);
        $val = $field['values'][0] ?? null;

        if (str_contains($key, 'phone') || str_contains($key, 'telef') || str_contains($key, 'cel')) {
            $out['phone'] = normalizar_telefono($val);
        } elseif (str_contains($key, 'name') || str_contains($key, 'nombre')) {
            $out['name'] = $val;
        } elseif (str_contains($key, 'email') || str_contains($key, 'correo')) {
            $out['email'] = $val;
        }
    }

    return $out;
}

function normalizar_telefono(?string $phone): ?string {
    if (!$phone) return null;
    // Limpiar caracteres no numéricos
    $clean = preg_replace('/\D/', '', $phone);
    // Si es número mexicano de 10 dígitos, añadir código de país
    if (strlen($clean) === 10) {
        $clean = '52' . $clean;
    }
    return '+' . ltrim($clean, '+');
}

function send_to_manychat(string $phone, string $name, ?string $email): void {
    $token = MANYCHAT_TOKEN;

    // 1. Buscar o crear usuario por teléfono en ManyChat
    $url = "https://api.manychat.com/fb/subscriber/findByPhone";

    $payload = json_encode(['phone' => $phone]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($res, true);

    if ($code === 200 && isset($data['data']['id'])) {
        $subscriber_id = $data['data']['id'];
    } else {
        // Crear nuevo subscriber
        $subscriber_id = create_manychat_subscriber($phone, $name, $email);
    }

    if (!$subscriber_id) return;

    // 2. Enviar mensaje template de WhatsApp
    send_whatsapp_template($subscriber_id, $name);
}

function create_manychat_subscriber(string $phone, string $name, ?string $email): ?int {
    $token = MANYCHAT_TOKEN;
    $url   = "https://api.manychat.com/fb/subscriber/createSubscriberByPhone";

    $parts = explode(' ', trim($name), 2);
    $first = $parts[0] ?? $name;
    $last  = $parts[1] ?? '';

    $payload = json_encode([
        'phone'      => $phone,
        'first_name' => $first,
        'last_name'  => $last,
        'email'      => $email,
        'has_opted_in_to_receive_messages' => true,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($res, true);
    return $data['data']['id'] ?? null;
}

function send_whatsapp_template(int $subscriber_id, string $name): void {
    $token = MANYCHAT_TOKEN;
    $url   = "https://api.manychat.com/fb/sending/sendFlow";

    // ID del flow de ManyChat que contiene el template de WhatsApp
    // Reemplazar con el ID real de tu flow
    $flow_ns = 'TU_FLOW_NS_DE_MANYCHAT';

    $payload = json_encode([
        'subscriber_id' => $subscriber_id,
        'flow_ns'       => $flow_ns,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
