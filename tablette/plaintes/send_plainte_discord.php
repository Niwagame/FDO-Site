<?php
require_once '../../includes/webhook_discord.php'; // utilise config.ini via config.php déjà inclus

if (!isset($plainte_id)) {
    echo "<p>Erreur : ID de la plainte introuvable.</p>";
    exit();
}

try {
    global $pdo;

    // 🔍 Récupération des infos
    $stmt = $pdo->prepare("
        SELECT p.sexe_plaignant, p.num_tel_plaignant, p.sexe_visee, p.num_tel_visee, 
               p.description_physique, p.motif_texte, p.agent_id, p.date_creation,
               plaignant.nom AS plaignant_nom, plaignant.prenom AS plaignant_prenom,
               visee.nom AS visee_nom, visee.prenom AS visee_prenom
        FROM plaintes p
        LEFT JOIN casiers AS plaignant ON p.plaignant_id = plaignant.id
        LEFT JOIN casiers AS visee ON p.personne_visee_id = visee.id
        WHERE p.id = ?
    ");
    $stmt->execute([$plainte_id]);
    $plainteData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plainteData) {
        echo "<p>Erreur : les détails de la plainte sont introuvables.</p>";
        exit();
    }

    // 📝 Préparer le message
    $message = "**Plainte enregistrée par : {$plainteData['agent_id']}**\n";
    $message .= "Nouvelle plainte enregistrée.\n\n";
    $message .= "**Nom du plaignant :** " . ($plainteData['plaignant_nom'] ?? "Inconnu") . "\n";
    $message .= "**Prénom du plaignant :** " . ($plainteData['plaignant_prenom'] ?? "Inconnu") . "\n";
    $message .= "**Sexe du plaignant :** " . ($plainteData['sexe_plaignant'] ?? "Inconnu") . "\n";
    $message .= "**Téléphone du plaignant :** " . ($plainteData['num_tel_plaignant'] ?? "Inconnu") . "\n";
    $message .= "**Nom de la personne visée :** " . ($plainteData['visee_nom'] ?? "Non précisé") . "\n";
    $message .= "**Prénom de la personne visée :** " . ($plainteData['visee_prenom'] ?? "Non précisé") . "\n";
    $message .= "**Sexe visé(e) :** " . ($plainteData['sexe_visee'] ?? "Non précisé") . "\n";
    $message .= "**Téléphone visé(e) :** " . ($plainteData['num_tel_visee'] ?? "Non précisé") . "\n";
    $message .= "**Description physique :** " . ($plainteData['description_physique'] ?? "Non précisé") . "\n";
    $message .= "**Motif de la plainte :** " . ($plainteData['motif_texte'] ?? "Non précisé") . "\n";
    $message .= "*Déposée le :* " . ($plainteData['date_creation'] ?? "Non précisé") . "\n";

    // 📤 Envoi via fonction centralisée
    sendToDiscord('plainte', $message);

    echo "<p>Message envoyé sur Discord avec succès !</p>";

} catch (PDOException $e) {
    echo "<p>Erreur BDD : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
