<?php
session_start();
require_once '../../config.php';

$role_bco = $roles['bcso'];
$role_doj = $roles['doj'];

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true || !hasRole($role_bco) && !hasRole($role_doj)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

// Récupérer toutes les entreprises
$stmt = $pdo->query("SELECT id, nom, secteur FROM entreprise ORDER BY secteur, nom");
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Entreprises</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des Entreprises</h2>

    <table>
        <thead>
            <tr>
                <th>Nom de l'Entreprise</th>
                <th>Secteur</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entreprises as $entreprise): ?>
                <tr>
                    <td><?= htmlspecialchars($entreprise['nom']); ?></td>
                    <td><?= htmlspecialchars($entreprise['secteur']); ?></td>
                    <td><a href="details.php?id=<?= $entreprise['id']; ?>" class="button">Voir Détails</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
