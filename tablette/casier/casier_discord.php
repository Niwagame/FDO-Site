<?php
require_once '../../includes/webhook_discord.php';
date_default_timezone_set('Europe/Paris');


// 🔹 Casier ajouté
function sendCasierCreationToDiscord($casier_id, $nom, $prenom, $date_naissance, $num_tel, $affiliation, $entreprise, $officier_id) {
    $date_now = date('d/m/Y à H\hi');

    $message = "**📥 Casier créé par :** $officier_id\n";
    $message .= "**Nom :** " . ($nom ?: 'N/A') . "\n";
    $message .= "**Prénom :** " . ($prenom ?: 'N/A') . "\n";
    $message .= "**Date de Naissance :** " . ($date_naissance ?: 'N/A') . "\n";
    $message .= "**Téléphone :** " . ($num_tel ?: 'N/A') . "\n";
    $message .= "**Affiliation :** " . ($affiliation ?: 'N/A') . "\n";
    $message .= "**Entreprise :** " . ($entreprise ?: 'N/A') . "\n";
    $message .= "_ID du casier : [#$casier_id](https://bcso-gta.ovh/tablette/casier/details.php?id=$casier_id)_\n";
    $message .= "🕒 Fait le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('casier', $message);
}


// 🔹 Casier supprimé
function sendCasierDeletionToDiscord($casier_id, $nom, $prenom, $date_naissance, $num_tel, $affiliation, $entreprise, $officier_id) {
    $date_now = date('d/m/Y à H\hi');

    $message = "**🗑️ Casier supprimé par :** $officier_id\n";
    $message .= "**Nom :** " . ($nom ?: 'N/A') . "\n";
    $message .= "**Prénom :** " . ($prenom ?: 'N/A') . "\n";
    $message .= "**Date de Naissance :** " . ($date_naissance ?: 'N/A') . "\n";
    $message .= "**Téléphone :** " . ($num_tel ?: 'N/A') . "\n";
    $message .= "**Affiliation :** " . ($affiliation ?: 'N/A') . "\n";
    $message .= "**Entreprise :** " . ($entreprise ?: 'N/A') . "\n";
    $message .= "_ID du casier : [#$casier_id](https://bcso-gta.ovh/tablette/casier/details.php?id=$casier_id)_\n";
    $message .= "🕒 Fait le $date_now\n";
    $message .= "\n━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('casier', $message);
}

// 🔹 Casier modifié
function sendCasierUpdateToDiscord($casier_id, $nom, $prenom, $date_naissance, $num_tel, $affiliation, $grade, $entreprise_id, $officier_id) {
    $date_now = date('d/m/Y à H\hi');
    
    $message = "**✏️ Casier modifié par :** $officier_id\n";
    $message .= "**Nom :** " . ($nom ?: 'N/A') . "\n";
    $message .= "**Prénom :** " . ($prenom ?: 'N/A') . "\n";
    $message .= "**Date de Naissance :** " . ($date_naissance ?: 'N/A') . "\n";
    $message .= "**Téléphone :** " . ($num_tel ?: 'N/A') . "\n";
    $message .= "**Affiliation :** " . ($affiliation ?: 'N/A') . "\n";
    $message .= "**Grade :** " . ($grade ?: 'N/A') . "\n";
    $message .= "**Entreprise ID :** " . ($entreprise_id ?: 'Aucune') . "\n";
    $message .= "_ID du casier : [#$casier_id](https://bcso-gta.ovh/tablette/casier/details.php?id=$casier_id)_\n";
    $message .= "🕒 Fait le $date_now\n";
    $message .= "\n━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('casier', $message);
}
?>
