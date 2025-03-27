<?php
require_once '../../includes/webhook_discord.php';

if (!empty($saisiesAjoutees) && !empty($motif)) {
    $officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

    $message = "**Nouvelle Saisie d'Objet**\n";
    $message .= "**Saisie envoyée par :** $officier_id\n";
    $message .= "Motif : $motif\n";
    $message .= "Objets saisis :\n";

    foreach ($saisiesAjoutees as $saisie) {
        $message .= "- {$saisie['nom']} : {$saisie['quantite']}\n";
    }

    sendToDiscord('saisie', $message);
} else {
    echo "<p>Erreur : aucune saisie ou motif disponible pour l'envoi vers Discord.</p>";
}
?>
