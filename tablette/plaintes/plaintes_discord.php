<?php
require_once '../../includes/webhook_discord.php';

function sendPlainteToDiscord($plainte_id) {
    global $pdo;

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
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) return;

    $url = "https://bcso-gta.ovh/tablette/plaintes/details.php?id={$plainte_id}";
    $date = date('d/m/Y à H\hi');

    $message = "📋 **Nouvelle Plainte enregistrée par :** {$p['agent_id']}\n";
    $message .= "**Plaignant :** {$p['plaignant_nom']} {$p['plaignant_prenom']} ({$p['sexe_plaignant']} / 📞 {$p['num_tel_plaignant']})\n";
    $message .= "**Personne visée :** " . ($p['visee_nom'] ? "{$p['visee_nom']} {$p['visee_prenom']} ({$p['sexe_visee']} / 📞 {$p['num_tel_visee']})" : "Non spécifiée") . "\n";
    $message .= "**Description physique :** {$p['description_physique']}\n";
    $message .= "**Motif :** {$p['motif_texte']}\n\n";
    $message .= "*Déposée le :* {$p['date_creation']}\n";
    $message .= "\n🔗 [Voir la plainte]($url)\n";
    $message .= "🕒 Enregistrée le $date\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('plainte', $message);
}

function sendPlainteUpdateToDiscord($plainte_id) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT p.*, 
               plaignant.nom AS plaignant_nom, plaignant.prenom AS plaignant_prenom,
               visee.nom AS visee_nom, visee.prenom AS visee_prenom
        FROM plaintes p
        LEFT JOIN casiers AS plaignant ON p.plaignant_id = plaignant.id
        LEFT JOIN casiers AS visee ON p.personne_visee_id = visee.id
        WHERE p.id = ?
    ");
    $stmt->execute([$plainte_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) return;

    $auteur = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    $url = "https://bcso-gta.ovh/tablette/plaintes/details.php?id={$plainte_id}";
    $date = date('d/m/Y à H\hi');

    $message = "✏️ **Plainte modifiée par :** $auteur\n";
    $message .= "**Plaignant :** {$p['plaignant_nom']} {$p['plaignant_prenom']} ({$p['sexe_plaignant']} / 📞 {$p['num_tel_plaignant']})\n";
    $message .= "**Personne visée :** " . ($p['visee_nom'] ? "{$p['visee_nom']} {$p['visee_prenom']} ({$p['sexe_visee']} / 📞 {$p['num_tel_visee']})" : "Non spécifiée") . "\n";
    $message .= "**Description physique :** {$p['description_physique']}\n";
    $message .= "**Motif :** {$p['motif_texte']}\n\n";
    $message .= "🔗 [Voir la plainte]($url)\n";
    $message .= "🕒 Modifiée le $date\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('plainte', $message);
}

function sendPlainteDeletionToDiscord($plainte_id) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT p.*, 
               plaignant.nom AS plaignant_nom, plaignant.prenom AS plaignant_prenom,
               visee.nom AS visee_nom, visee.prenom AS visee_prenom
        FROM plaintes p
        LEFT JOIN casiers AS plaignant ON p.plaignant_id = plaignant.id
        LEFT JOIN casiers AS visee ON p.personne_visee_id = visee.id
        WHERE p.id = ?
    ");
    $stmt->execute([$plainte_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) return;

    $auteur = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    $date = date('d/m/Y à H\hi');

    $message = "❌ **Plainte supprimée par :** $auteur\n";
    $message .= "**Plaignant :** {$p['plaignant_nom']} {$p['plaignant_prenom']}\n";
    $message .= "**Personne visée :** " . ($p['visee_nom'] ? "{$p['visee_nom']} {$p['visee_prenom']}" : "Non spécifiée") . "\n";
    $message .= "**Motif :** {$p['motif_texte']}\n";
    $message .= "**Sexe :** {$p['sexe_visee']}\n";
    $message .= "🕒 Supprimée le $date\n";
    $message .= "━━━━━━━━━━━━━━━━━━━";

    sendToDiscord('plainte', $message);
}
?>
