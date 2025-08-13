<?php
require_once __DIR__ . '/config_whatsapp.php';

/**
 * Envia uma mensagem de texto via API do WhatsApp.
 *
 * @param string $numero Número de destino no formato internacional.
 * @param string $texto  Conteúdo da mensagem.
 * @return bool True em caso de sucesso.
 */
function enviar_mensagem(string $numero, string $texto): bool {
    $payload = json_encode([
        'to' => $numero,
        'text' => $texto,
    ]);

    $ch = curl_init(WHATSAPP_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_API_TOKEN,
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);

    curl_exec($ch);
    $err = curl_errno($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err !== 0) {
        echo "Erro ao enviar mensagem: " . curl_strerror($err) . PHP_EOL;
        return false;
    }

    return $status >= 200 && $status < 300;
}
?>
