<?php
require_once '../../includes/webhook_discord.php';

function sendCasierToDiscord($nom, $prenom, $date_naissance, $num_tel, $affiliation, $entreprise, $officier_id) {
    $date_naissance = $date_naissance ?: 'N/A';
    $num_tel = $num_tel ?: 'N/A';
    $affiliation = $affiliation ?: 'N/A';
    $entreprise = $entreprise ?: 'N/A';

    $message = "**Casier créé par :** $officier_id\n";
    $message .= "**Nom :** $nom\n";
    $message .= "**Prénom :** $prenom\n";
    $message .= "**Date de Naissance :** $date_naissance\n";
    $message .= "**Téléphone :** $num_tel\n";
    $message .= "**Affiliation :** $affiliation\n";
    $message .= "**Entreprise :** $entreprise";

    sendToDiscord('casier', $message);
}
?>
