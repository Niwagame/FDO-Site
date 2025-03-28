<?php
require_once '../../includes/webhook_discord.php';
date_default_timezone_set('Europe/Paris');

// 🔹 Saisie ajoutée avec casier & rapport
function sendSaisieWithCasierToDiscord($officier_id, $individuDetails, $rapportDetails, $saisiesAjoutees) {
    if (empty($saisiesAjoutees) || empty($individuDetails) || empty($rapportDetails)) return;

    $date_now = date('d/m/Y à H\hi');

    $message = "📦 **Ajout de Saisie**\n";
    $message .= "**Saisie envoyée par :** $officier_id\n";
    $message .= "**Individu :** {$individuDetails['nom']} {$individuDetails['prenom']}\n";
    $message .= "**Rapport :** {$rapportDetails['date_arrestation']} - {$rapportDetails['motif']}\n";
    $message .= "**Objets saisis :**\n";

    foreach ($saisiesAjoutees as $saisie) {
        $message .= "- {$saisie['nom']} : {$saisie['quantite']}\n";
    }

    $message .= "\n🕒 Ajouté le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('saisie', $message);
}

// 🔹 Saisie ajoutée sans casier (saisie simple avec motif)
function sendSaisieWithoutCasierToDiscord($officier_id, $motif, $saisiesAjoutees) {
    if (empty($saisiesAjoutees) || empty($motif)) return;

    $date_now = date('d/m/Y à H\hi');

    $message = "📦 **Ajout de Saisie sans Rapport**\n";
    $message .= "**Saisie envoyée par :** $officier_id\n";
    $message .= "**Motif :** $motif\n";
    $message .= "**Objets saisis :**\n";

    foreach ($saisiesAjoutees as $saisie) {
        $message .= "- {$saisie['nom']} : {$saisie['quantite']}\n";
    }

    $message .= "\n🕒 Ajouté le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('saisie', $message);
}

// 🔹 Saisie supprimée
function sendSaisieRetireeToDiscord(array $saisiesRetirees) {
    if (empty($saisiesRetirees)) return;

    $user = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    $date_now = date('d/m/Y à H\hi');

    $message = "📤 **Sortie de Saisie effectuée par :** $user\n";
    $message .= "**Objets retirés :**\n";

    foreach ($saisiesRetirees as $saisie) {
        $message .= "- {$saisie['nom']} : {$saisie['quantite']}\n";
    }

    $message .= "\n🕒 Sortie le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('saisie', $message);
}
