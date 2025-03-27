<?php
require_once '../../includes/webhook_discord.php';

function sendReportToDiscord($rapport_id) {
    global $pdo;

    // Détails du rapport
    $stmt = $pdo->prepare("
        SELECT r.date_arrestation, r.amende, r.retention, r.rapport_text, r.coop, r.miranda_time, 
               r.demandes_droits, r.heure_droits, r.officier_id, a.description AS motif_description
        FROM rapports r
        LEFT JOIN amende a ON r.motif = a.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rapport_id]);
    $rapportData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rapportData) {
        echo "<p>Erreur : le rapport est introuvable.</p>";
        return;
    }

    // Individus concernés
    $stmt = $pdo->prepare("
        SELECT c.nom, c.prenom
        FROM casiers c
        JOIN rapports_individus ri ON c.id = ri.casier_id
        WHERE ri.rapport_id = ?
    ");
    $stmt->execute([$rapport_id]);
    $individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 📝 Préparer le message
    $message = "## 📄 Nouveau Rapport Créé\n";
    $message .= "**Date :** " . ($rapportData['date_arrestation'] ?? 'N/A') . "\n";
    $message .= "**Motif :** " . ($rapportData['motif_description'] ?? 'N/A') . "\n";
    $message .= "**Amende :** " . ($rapportData['amende'] ? $rapportData['amende'] . " $" : 'N/A') . "\n";
    $message .= "**Peine :** " . ($rapportData['retention'] ?? 'N/A') . "\n";
    $message .= "**Rapport :** " . ($rapportData['rapport_text'] ?? 'N/A') . "\n";
    $message .= "**Coopération :** " . ($rapportData['coop'] ?? 'N/A') . "/10\n";
    $message .= "**Miranda :** " . ($rapportData['miranda_time'] ?? 'N/A') . "\n";
    $message .= "**Droits demandés :** " . ($rapportData['demandes_droits'] ?? 'N/A') . "\n";
    $message .= "**Heure droits :** " . ($rapportData['heure_droits'] ?? 'N/A') . "\n";
    $message .= "**Officier :** " . ($rapportData['officier_id'] ?? 'N/A') . "\n";
    $message .= "**Individus :**\n";
    foreach ($individus as $individu) {
        $message .= "- {$individu['nom']} {$individu['prenom']}\n";
    }

    sendToDiscord('rapport', $message);
}
?>
