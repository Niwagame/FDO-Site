<?php
require_once '../config.php';

$role_sa = $roles['sa'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_sa)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}

include '../includes/header.php';

// Récupérer les matricules utilisés
$stmt = $pdo->query("SELECT matricule FROM sa_effectif");
$usedMatricules = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'matricule');

// Générer les matricules disponibles (101 à 199)
$matricules = range(101, 199);
?>

<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="../css/matricules.css">

<div class="table-container">
    <h2>🧩 Matricules Disponibles</h2>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matricules as $mat): ?>
                <?php $isTaken = in_array($mat, $usedMatricules); ?>
                <tr class="<?= $isTaken ? 'matricule-taken' : 'matricule-free' ?>">
                    <td><?= $mat ?></td>
                    <td><?= $isTaken ? '❌ Pris' : '✅ Disponible' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
