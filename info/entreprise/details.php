<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    echo "Entreprise non spécifiée.";
    exit();
}

$entreprise_id = $_GET['id'];

// Récupérer les détails de l'entreprise
$stmt = $pdo->prepare("SELECT * FROM entreprise WHERE id = ?");
$stmt->execute([$entreprise_id]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    echo "Entreprise non trouvée.";
    exit();
}

// Récupérer les individus associés à cette entreprise
$stmt = $pdo->prepare("
    SELECT c.id, c.nom, c.prenom
    FROM casiers c
    WHERE c.entreprise_id = ?
    ORDER BY c.nom, c.prenom
");
$stmt->execute([$entreprise_id]);
$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'Entreprise <?= htmlspecialchars($entreprise['nom']); ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Détails de l'Entreprise <?= htmlspecialchars($entreprise['nom']); ?></h2>
    <p><strong>Nom :</strong> <?= htmlspecialchars($entreprise['nom']); ?></p>
    <p><strong>Secteur :</strong> <?= htmlspecialchars($entreprise['secteur']); ?></p>

    <h3>Individus Associés</h3>
    <?php if (count($individus) > 0): ?>
        <ul>
            <?php foreach ($individus as $individu): ?>
                <li><a href="../../casier/details.php?id=<?= $individu['id']; ?>"><?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun individu associé à cette entreprise.</p>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
