<?php
require_once '../../includes/webhook_discord.php';

function sendCasierDeletionToDiscord($nom, $prenom, $date_naissance, $num_tel, $affiliation, $entreprise, $officier_id) {
    // Sécurité sur les valeurs vides
    $date_naissance = $date_naissance ?: 'N/A';
    $num_tel = $num_tel ?: 'N/A';
    $affiliation = $affiliation ?: 'N/A';
    $entreprise = $entreprise ?: 'N/A';

    // Message Discord
    $message = "**Casier supprimé par :** $officier_id\n";
    $message .= "**Nom :** $nom\n";
    $message .= "**Prénom :** $prenom\n";
    $message .= "**Date de Naissance :** $date_naissance\n";
    $message .= "**Téléphone :** $num_tel\n";
    $message .= "**Affiliation :** $affiliation\n";
    $message .= "**Entreprise :** $entreprise";

    // Envoi via système centralisé
    sendToDiscord('casier', $message);
}
?>
