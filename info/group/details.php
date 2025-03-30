<?php
session_start();
require_once '../../config.php';

$role_bco = $roles['bco'];
$role_doj = $roles['doj'];

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true || !hasRole($role_bco) && !hasRole($role_doj)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "Groupe non spécifié.";
    exit();
}


$group_id = $_GET['id'];

// Récupérer les détails du groupe
$stmt = $pdo->prepare("SELECT * FROM user_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    echo "Groupe non trouvé.";
    exit();
}

// Récupérer les individus affiliés au groupe
$stmt = $pdo->prepare("
    SELECT c.id, c.nom, c.prenom
    FROM casiers c
    WHERE c.affiliation = ?
    ORDER BY c.nom, c.prenom
");
$stmt->execute([$group['name']]);
$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rapports associés aux individus du groupe
$stmt = $pdo->prepare("
    SELECT 
        r.id AS rapport_id, 
        r.date_arrestation, 
        a.description AS motif, 
        r.officier_id AS agent, 
        ri.casier_id, 
        c.nom AS individu_nom, 
        c.prenom AS individu_prenom
    FROM rapports r
    JOIN rapports_individus ri ON r.id = ri.rapport_id
    LEFT JOIN amende a ON r.motif = a.id
    LEFT JOIN casiers c ON ri.casier_id = c.id
    WHERE ri.casier_id IN (SELECT id FROM casiers WHERE affiliation = ?)
    ORDER BY r.date_arrestation DESC
");
$stmt->execute([$group['name']]);
$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Récupérer les saisies associées aux rapports des individus du groupe
$stmt = $pdo->prepare("
    SELECT sc.quantite, s.nom AS objet_nom, s.poids, r.date_arrestation, r.id AS rapport_id
    FROM saisie_c sc
    JOIN saisie s ON sc.saisie_id = s.id
    JOIN rapports r ON sc.idrapport = r.id
    WHERE sc.idcasier IN (SELECT id FROM casiers WHERE affiliation = ?)
    ORDER BY r.date_arrestation DESC
");
$stmt->execute([$group['name']]);
$saisies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        s.nom AS objet_nom, 
        SUM(sc.quantite) AS total_quantite
    FROM saisie_c sc
    JOIN saisie s ON sc.saisie_id = s.id
    WHERE sc.idcasier IN (SELECT id FROM casiers WHERE affiliation = ?)
    GROUP BY s.nom
    ORDER BY total_quantite DESC
");
$stmt->execute([$group['name']]);
$total_saisies = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Groupe <?= htmlspecialchars($group['name']); ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Détails du Groupe <?= htmlspecialchars($group['name']); ?></h2>
    <p><strong>Nom :</strong> <?= htmlspecialchars($group['name']); ?></p>
    <p><strong>Type :</strong> <?= htmlspecialchars($group['type']); ?></p>

    <h3>Individus Associés</h3>
    <?php if (count($individus) > 0): ?>
        <ul>
            <?php foreach ($individus as $individu): ?>
                <li>
                    <a href="/tablette/casier/details.php?id=<?= $individu['id']; ?>">
                        <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun individu associé à ce groupe.</p>
    <?php endif; ?>

        <h3>Rapports Associés</h3>
    <?php if (count($rapports) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date d'Arrestation</th>
                    <th>Motif</th>
                    <th>Agent</th>
                    <th>Individu</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapports as $rapport): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d-m-Y", strtotime($rapport['date_arrestation']))); ?></td>
                        <td><?= htmlspecialchars($rapport['motif']); ?></td>
                        <td><?= htmlspecialchars($rapport['agent'] ?? 'Inconnu'); ?></td>
                        <td>
                            <a href="/tablette/casier/details.php?id=<?= $rapport['casier_id']; ?>">
                                <?= htmlspecialchars($rapport['individu_nom'] . ' ' . $rapport['individu_prenom']); ?>
                            </a>
                        </td>
                        <td><a href="/tablette/rapport/details.php?id=<?= $rapport['rapport_id']; ?>" class="button">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun rapport associé à ce groupe.</p>
    <?php endif; ?>


    <h3>Objets Saisis</h3>
    <?php if (count($saisies) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Objet</th>
                    <th>Quantité</th>
                    <th>Poids Total (kg)</th>
                    <th>Date de Saisie</th>
                    <th>Rapport</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saisies as $saisie): ?>
                    <tr>
                        <td><?= htmlspecialchars($saisie['objet_nom']); ?></td>
                        <td><?= htmlspecialchars($saisie['quantite']); ?></td>
                        <td><?= htmlspecialchars(number_format($saisie['poids'] * $saisie['quantite'], 2)); ?></td>
                        <td><?= htmlspecialchars(date("d-m-Y", strtotime($saisie['date_arrestation']))); ?></td>
                        <td>
                            <a href="/tablette/rapport/details.php?id=<?= $saisie['rapport_id']; ?>" class="button">Voir Rapport</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun objet saisi pour ce groupe.</p>
    <?php endif; ?>

        <h3>Total Objets Saisis</h3>
    <?php if (count($total_saisies) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Nom de l'Objet</th>
                    <th>Quantité Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($total_saisies as $saisie): ?>
                    <tr>
                        <td><?= htmlspecialchars($saisie['objet_nom']); ?></td>
                        <td><?= htmlspecialchars($saisie['total_quantite']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun objet saisi pour ce groupe.</p>
    <?php endif; ?>

</div>


<?php include '../../includes/footer.php'; ?>
</body>
</html>

