<?php
require_once '../../includes/webhook_discord.php';

if (!empty($saisiesAjoutees) && !empty($individuDetails) && !empty($rapportDetails)) {
    $officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

    $message = "**Ajout de Saisie**\n";
    $message .= "**Saisie envoyée par :** $officier_id\n";
    $message .= "Individu : {$individuDetails['nom']} {$individuDetails['prenom']}\n";
    $message .= "Rapport : {$rapportDetails['date_arrestation']} - {$rapportDetails['motif']}\n";
    $message .= "Objets saisis :\n";

    foreach ($saisiesAjoutees as $saisie) {
        $message .= "- {$saisie['nom']} : {$saisie['quantite']}\n";
    }

    sendToDiscord('saisie', $message);
}
?>
