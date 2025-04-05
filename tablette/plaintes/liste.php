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


// Récupération de toutes les plaintes avec les informations des plaignants et des personnes visées
$stmt = $pdo->prepare("
    SELECT p.id AS plainte_id, p.date_creation, 
           c1.nom AS plaignant_nom, c1.prenom AS plaignant_prenom, 
           c2.nom AS visee_nom, c2.prenom AS visee_prenom 
    FROM plaintes p
    LEFT JOIN casiers c1 ON p.plaignant_id = c1.id
    LEFT JOIN casiers c2 ON p.personne_visee_id = c2.id
    ORDER BY p.date_creation DESC
");
$stmt->execute();
$plaintes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Plaintes</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des Plaintes</h2>
    <a href="ajout.php" class="button">Ajouter une Plainte</a>
    
    <?php if (count($plaintes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Plaignant</th>
                    <th>Personne Visée</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plaintes as $plainte): ?>
                    <tr>
                        <td><?= htmlspecialchars($plainte['date_creation']); ?></td>
                        <td><?= htmlspecialchars($plainte['plaignant_nom'] . ' ' . $plainte['plaignant_prenom']); ?></td>
                        <td><?= isset($plainte['visee_nom']) ? htmlspecialchars($plainte['visee_nom'] . ' ' . $plainte['visee_prenom']) : '<em>Aucun</em>'; ?></td>
                        <td><a href="details.php?id=<?= $plainte['plainte_id']; ?>" class="button">Afficher Détails</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune plainte trouvée.</p>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
