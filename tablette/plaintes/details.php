<?php
session_start();
require_once '../../config.php';

$roles_bcs = $roles['bcso'];
$roles_doj = $roles['doj'];

if (
    !isset($_SESSION['user_authenticated']) ||
    $_SESSION['user_authenticated'] !== true ||
    !(hasRole($roles_bcs) || hasRole($roles_doj))
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "Plainte non spécifiée.";
    exit();
}

$plainte_id = $_GET['id'];

// Récupération des détails de la plainte
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
$plainte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plainte) {
    echo "Plainte non trouvée.";
    exit();
}

// Traitement de la suppression de la plainte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    require_once 'plaintes_discord.php';
    sendPlainteDeletionToDiscord($plainte_id);

    $stmt = $pdo->prepare("DELETE FROM plaintes WHERE id = ?");
    $stmt->execute([$plainte_id]);

    header('Location: liste.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la Plainte</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Détails de la Plainte</h2>
    <p><strong>Nom du Plaignant :</strong> <?= htmlspecialchars($plainte['plaignant_nom'] . ' ' . $plainte['plaignant_prenom']); ?></p>
    <p><strong>Sexe du Plaignant :</strong> <?= htmlspecialchars($plainte['sexe_plaignant']); ?></p>
    <p><strong>Numéro de Téléphone du Plaignant :</strong> <?= htmlspecialchars($plainte['num_tel_plaignant']); ?></p>

    <p><strong>Nom de la Personne Visée :</strong> <?= htmlspecialchars($plainte['visee_nom'] . ' ' . $plainte['visee_prenom'] ?? 'Aucun'); ?></p>
    <p><strong>Sexe de la Personne Visée :</strong> <?= htmlspecialchars($plainte['sexe_visee'] ?? 'Aucun'); ?></p>
    <p><strong>Numéro de Téléphone de la Personne Visée :</strong> <?= htmlspecialchars($plainte['num_tel_visee'] ?? 'Aucun'); ?></p>

    <p><strong>Description Physique :</strong> <?= htmlspecialchars($plainte['description_physique']); ?></p>
    <p><strong>Motif de la Plainte :</strong> <?= htmlspecialchars($plainte['motif_texte']); ?></p>
    <p><strong>Agent Chargé :</strong> <?= htmlspecialchars($plainte['agent_id']); ?></p>
    <p><strong>Date de Création :</strong> <?= htmlspecialchars($plainte['date_creation']); ?></p>

    <p><strong>Document Google Docs :</strong>
    <?php if (!empty($plainte['lien_document'])): ?>
        <span style="color: lime;">🟢</span>
        <a href="<?= htmlspecialchars($plainte['lien_document']); ?>" target="_blank">Ouvrir le document</a>
    <?php else: ?>
        <span style="color: red;">🔴 Aucun export disponible</span>
    <?php endif; ?>
    </p>


    <!-- Bouton de modification de la plainte -->
    <form action="modifier.php" method="get" style="display: inline;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($plainte_id); ?>">
        <button type="submit" class="button edit">Modifier la Plainte</button>
    </form>

    <!-- Bouton de suppression de la plainte -->
    <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette plainte ?');" style="display: inline;">
        <button type="submit" name="delete" class="button delete">Supprimer la Plainte</button>
    </form>

    <!-- Bouton d'export Google Docs -->
    <form action="exporter_plainte.php" method="post" style="display:inline;">
        <input type="hidden" name="plainte_id" value="<?= $plainte_id ?>">
        <button type="submit" class="button">📄 Exporter en Google Docs</button>
    </form>

</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
