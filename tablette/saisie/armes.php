<?php
session_start();
require_once '../../config.php';

if (!isset($_GET['nom'])) {
    echo "Type d'arme non spécifié.";
    exit();
}

$role_bco = $roles['bco'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_bco)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO peuvent voir les saisie d'armes.</p>";
    exit();
}

$nom_arme = $_GET['nom'];

// Récupération de toutes les armes de ce type avec les informations du propriétaire
$stmt = $pdo->prepare("
    SELECT sa.id, sa.numero_serie, sa.casier_id, c.nom AS casier_nom, c.prenom AS casier_prenom
    FROM s_armes sa
    LEFT JOIN casiers c ON sa.casier_id = c.id
    WHERE sa.nom = ?
");

$stmt->execute([$nom_arme]);
$armes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des <?= htmlspecialchars($nom_arme); ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des <?= htmlspecialchars($nom_arme); ?></h2>
    <table>
        <thead>
            <tr>
                <th>Numéro de Série</th>
                <th>Propriétaire</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($armes as $arme): ?>
                <tr>
                    <td><?= htmlspecialchars($arme['numero_serie']); ?></td>
                    <td>
                        <?php if ($arme['casier_nom']): ?>
                            <a href="/tablette/casier/details.php?id=<?= $arme['casier_id']; ?>">
                                <?= htmlspecialchars($arme['casier_nom'] . ' ' . $arme['casier_prenom']); ?>
                            </a>
                        <?php else: ?>
                            Aucun
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
