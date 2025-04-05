<?php
require_once '../config.php';

// 🔐 Authentification
$role_bcso = $roles['bcso'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_bcso)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}

// 🗓️ Détermine le dernier dimanche
$aujourdhui = date('Y-m-d');
$dernier_dimanche = date('Y-m-d', strtotime('last sunday', strtotime($aujourdhui)));

// 📊 Requête SQL pour les amendes hebdomadaires
$sql = "
    SELECT
        YEARWEEK(date_arrestation, 1) AS semaine,
        SUM(amende * nb_individus) AS total_amendes
    FROM (
        SELECT
            r.id,
            r.date_arrestation,
            r.amende,
            COUNT(ri.casier_id) AS nb_individus
        FROM rapports r
        JOIN rapports_individus ri ON r.id = ri.rapport_id
        WHERE r.date_arrestation <= :dernier_dimanche
        GROUP BY r.id
    ) AS rapports_calcules
    GROUP BY YEARWEEK(date_arrestation, 1)
    ORDER BY semaine DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['dernier_dimanche' => $dernier_dimanche]);
$statistiques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 📎 Inclus le header
include '../includes/header.php';
?>

<!-- CSS -->
<link rel="stylesheet" href="../css/styles.css">

<div class="container">
    <h2>📊 Statistiques des Amendes Hebdomadaires</h2>

    <table>
        <thead>
            <tr>
                <th>Semaine</th>
                <th>Total des Amendes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statistiques as $stat): ?>
                <tr>
                    <td>
                    <?php
                    $year = substr($stat['semaine'], 0, 4);
                    $week = substr($stat['semaine'], 4, 2);

                    // 📅 Lundi de la semaine
                    $start = new DateTime();
                    $start->setISODate($year, $week); // par défaut, ça commence le lundi

                    // 📅 Lundi suivant
                    $end = clone $start;
                    $end->modify('+7 days');

                    echo $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
                    ?>
                    </td>
                    <td><?= number_format($stat['total_amendes'], 2, ',', ' ') ?> $</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
