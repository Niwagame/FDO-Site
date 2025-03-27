<?php
require_once __DIR__ . '/../config.php'; // Inclut le config global

function sendToDiscord($type, $message) {
    global $config;

    if (!isset($config['discord_webhooks'][$type])) {
        error_log("Webhook Discord introuvable pour le type : $type");
        return false;
    }

    $webhookUrl = $config['discord_webhooks'][$type];
    $data = ["content" => $message];

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("Erreur Discord cURL ($type) : " . curl_error($ch));
    }
    curl_close($ch);

    return true;
}
?>
