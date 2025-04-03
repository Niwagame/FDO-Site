<?php
require_once '../../includes/webhook_discord.php';
date_default_timezone_set('Europe/Paris');

function sendReportCreationToDiscord($rapport_id) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT r.date_arrestation, r.amende, r.retention, r.rapport_text, r.coop, r.miranda_time, 
               r.officier_id, a.description AS motif_description
        FROM rapports r
        LEFT JOIN amende a ON r.motif = a.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rapport_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return;

    // Récupérer les individus
    $stmt = $pdo->prepare("
        SELECT c.nom, c.prenom
        FROM casiers c
        JOIN rapports_individus ri ON c.id = ri.casier_id
        WHERE ri.rapport_id = ?
    ");
    $stmt->execute([$rapport_id]);
    $individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les droits Miranda
    $stmt = $pdo->prepare("
        SELECT droit, heure_droit 
        FROM droit_miranda 
        WHERE rapport_id = ?
    ");
    $stmt->execute([$rapport_id]);
    $droits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $date_now = date('d/m/Y à H\hi');

    $message = "📄 **Rapport créé par :** {$data['officier_id']}\n";
    $message .= "**Date :** {$data['date_arrestation']}\n";
    $message .= "**Motif :** {$data['motif_description']}\n";
    $message .= "**Amende :** " . ($data['amende'] ? "{$data['amende']} $" : 'N/A') . "\n";
    $message .= "**Peine :** {$data['retention']}\n";
    $message .= "**Coopération :** {$data['coop']}/10\n";
    $message .= "**Miranda :** {$data['miranda_time']}\n";

    if (!empty($droits)) {
        $message .= "**Droits demandés :**\n";
        foreach ($droits as $d) {
            $message .= "- {$d['droit']} à {$d['heure_droit']}\n";
        }
    }

    $message .= "**Individus concernés :**\n";
    foreach ($individus as $i) {
        $message .= "- {$i['nom']} {$i['prenom']}\n";
    }

    $message .= "\n*_ID du rapport : #{$rapport_id}_*\n";
    $message .= "🔗 [Voir le rapport](https://bcso-gta.ovh/tablette/rapport/details.php?id=$rapport_id)\n";
    $message .= "🕒 Créé le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('rapport', $message);
}

function sendReportUpdateToDiscord($rapport_id) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT r.date_arrestation, r.amende, r.retention, r.rapport_text, r.coop, r.miranda_time, 
               r.officier_id, a.description AS motif_description
        FROM rapports r
        LEFT JOIN amende a ON r.motif = a.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rapport_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return;

    // Individus
    $stmt = $pdo->prepare("
        SELECT c.nom, c.prenom
        FROM casiers c
        JOIN rapports_individus ri ON c.id = ri.casier_id
        WHERE ri.rapport_id = ?
    ");
    $stmt->execute([$rapport_id]);
    $individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Droits
    $stmt = $pdo->prepare("
        SELECT droit, heure_droit 
        FROM droit_miranda 
        WHERE rapport_id = ?
    ");
    $stmt->execute([$rapport_id]);
    $droits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    $date_now = date('d/m/Y à H\hi');

    $message = "✏️ **Rapport modifié par :** $user\n";
    $message .= "**Date :** {$data['date_arrestation']}\n";
    $message .= "**Motif :** {$data['motif_description']}\n";
    $message .= "**Amende :** " . ($data['amende'] ? "{$data['amende']} $" : 'N/A') . "\n";
    $message .= "**Peine :** {$data['retention']}\n";
    $message .= "**Coopération :** {$data['coop']}/10\n";
    $message .= "**Miranda :** {$data['miranda_time']}\n";

    if (!empty($droits)) {
        $message .= "**Droits demandés :**\n";
        foreach ($droits as $d) {
            $message .= "- {$d['droit']} à {$d['heure_droit']}\n";
        }
    }

    $message .= "**Individus concernés :**\n";
    foreach ($individus as $i) {
        $message .= "- {$i['nom']} {$i['prenom']}\n";
    }

    $message .= "\n*_ID du rapport : #{$rapport_id}_*\n";
    $message .= "🔗 [Voir le rapport](https://bcso-gta.ovh/tablette/rapport/details.php?id=$rapport_id)\n";
    $message .= "🕒 Modifié le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('rapport', $message);
}

function sendReportDeletionToDiscord($rapport_id) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT r.date_arrestation, a.description AS motif_description
        FROM rapports r
        LEFT JOIN amende a ON r.motif = a.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rapport_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return;

    $stmt = $pdo->prepare("
        SELECT c.nom, c.prenom
        FROM casiers c
        JOIN rapports_individus ri ON c.id = ri.casier_id
        WHERE ri.rapport_id = ?
    ");
    $stmt->execute([$rapport_id]);
    $individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    $date_now = date('d/m/Y à H\hi');

    $message = "❌ **Rapport supprimé par :** $user\n";
    $message .= "**Date :** {$data['date_arrestation']}\n";
    $message .= "**Motif :** {$data['motif_description']}\n";
    $message .= "**Individus concernés :**\n";
    foreach ($individus as $i) {
        $message .= "• {$i['nom']} {$i['prenom']}\n";
    }

    $message .= "\n*_ID du rapport : #{$rapport_id}_*\n";
    $message .= "🔗 [Lien direct](https://bcso-gta.ovh/tablette/rapport/details.php?id=$rapport_id)\n";
    $message .= "🕒 Supprimé le $date_now\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('rapport', $message);
}
?>