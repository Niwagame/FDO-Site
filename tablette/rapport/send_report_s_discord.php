<?php
require_once '../../includes/webhook_discord.php';
session_start();
global $pdo;

if (!isset($rapport_id)) {
    echo "ID du rapport non spécifié.";
    exit();
}

// Détails rapport
$stmt = $pdo->prepare("
    SELECT r.date_arrestation, a.description AS motif_description
    FROM rapports r
    LEFT JOIN amende a ON r.motif = a.id
    WHERE r.id = ?
");
$stmt->execute([$rapport_id]);
$rapportData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rapportData) {
    echo "Rapport non trouvé.";
    exit();
}

// Individus
$stmt = $pdo->prepare("
    SELECT c.nom, c.prenom
    FROM casiers c
    JOIN rapports_individus ri ON c.id = ri.casier_id
    WHERE ri.rapport_id = ?
");
$stmt->execute([$rapport_id]);
$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$supprime_par = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

$message = "❌ **Rapport supprimé par :** $supprime_par\n";
$message .= "**Motif :** {$rapportData['motif_description']}\n";
$message .= "**Date d'Arrestation :** {$rapportData['date_arrestation']}\n";
$message .= "**Individus concernés :**\n";
foreach ($individus as $individu) {
    $message .= "- {$individu['nom']} {$individu['prenom']}\n";
}

sendToDiscord('rapport', $message);
?>
