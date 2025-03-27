<?php
require_once '../../includes/webhook_discord.php';

if (!empty($saisiesRetirees)) {
    $message = "**Sortie de Saisie**\n";
    $message .= "Les objets suivants ont été retirés :\n";

    foreach ($saisiesRetirees as $saisie) {
        $message .= "- {$saisie['nom']} : {$saisie['quantite']}\n";
    }

    sendToDiscord('saisie', $message);
}
?>
