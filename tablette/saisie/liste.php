<?php
session_start();
require_once '../../config.php';

$role_bco = $roles['bcso'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_bco)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO peuvent voir les saisies.</p>";
    exit();
}

$search = $_GET['search'] ?? '';

$query = "SELECT * FROM saisie";
$params = [];

if (!empty($search)) {
    $query .= " WHERE nom LIKE ? OR categorie LIKE ? OR nom IN (
        SELECT nom FROM s_armes WHERE numero_serie LIKE ?
    )";    
    $params = [
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%'
    ];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$saisies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Poids total
$poids_total = 0;
foreach ($saisies as $saisie) {
    $quantite = $saisie['quantite'] ?? 0;
    $poids = $saisie['poids'] ?? 0;
    $poids_total += $poids * $quantite;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Saisies</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des Saisies</h2>

    <!-- Barre d'action : recherche + boutons -->
    <div class="action-bar">
        <form method="GET" action="liste.php" class="search-form">
            <input type="text" name="search" placeholder="Rechercher par nom, catégorie ou N° série" value="<?= htmlspecialchars($search); ?>">
            <button type="submit" class="button">Rechercher</button>
        </form>

        <div class="button-group">
            <a href="ajout.php" class="button add-button">Ajouter Saisie</a>
            <a href="ajout_sans_casier.php" class="button add-button">Ajouter Saisie sans Rapport</a>
            <a href="sortie.php" class="button add-button">Sortie Saisie</a>
        </div>
    </div>

    <!-- Tableau -->
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Quantité</th>
                <th>Poids Total (kg)</th>
                <th>Catégorie</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($saisies) > 0): ?>
                <?php foreach ($saisies as $saisie): ?>
                    <tr>
                        <td>
                            <img src="/<?= str_replace('\\', '/', htmlspecialchars($saisie['image'] ?? '')); ?>" alt="Image de <?= htmlspecialchars($saisie['nom'] ?? ''); ?>" width="50">
                        </td>
                        <td>
                            <?php if ($saisie['categorie'] === 'Armes'): ?>
                                <a href="armes.php?nom=<?= urlencode($saisie['nom']); ?>">
                                    <?= htmlspecialchars($saisie['nom']); ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($saisie['nom'] ?? ''); ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($saisie['quantite'] ?? 0); ?></td>
                        <td><?= htmlspecialchars(number_format(($saisie['poids'] ?? 0) * ($saisie['quantite'] ?? 0), 2)); ?></td>
                        <td><?= htmlspecialchars($saisie['categorie'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Aucune saisie trouvée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right;"><strong>Poids total des saisies (kg):</strong></td>
                <td colspan="2"><?= number_format($poids_total, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
