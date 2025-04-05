<?php
require_once '../config.php';

// 🔐 Authentification
$role_bcso = $roles['bcso'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_bcso)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}

// 📦 Spécialisations à afficher (filtrées)
$specialisations = array_diff(array_keys($config['roles']), ['cs', 'doj', 'lead_bci']);

// 📊 Récupération des membres
$stmt = $pdo->query("SELECT * FROM sa_effectif ORDER BY grade DESC");
$effectif = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🧩 Header
include '../includes/header.php';
?>

<!-- Styles -->
<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="../css/sa_effectif.css">

<div class="table-container">
    <h2>📋 Effectif du BCSO (Gestion SA)</h2>

    <form method="post" action="sa_effectif_refresh_all.php" style="text-align:right;">
        <button class="btn-refresh" type="submit">🔄 Rafraîchir tout l'effectif</button>
    </form>

    <table>
        <thead>
            <!-- Catégories -->
            <tr>
                <th rowspan="2">Matricule</th>
                <th rowspan="2">Prénom</th>
                <th rowspan="2">Nom</th>
                <th rowspan="2">Grade</th>

                <th colspan="3">Division</th>
                <th colspan="5">Spécialisation</th>
                <th colspan="4">Traffic Unit</th>
                <th colspan="2">CNU</th>
                <th colspan="2">Autre</th>

                <th rowspan="2">Dernière MAJ</th>
                <th rowspan="2">🔁</th>
            </tr>

            <!-- Sous-catégories -->
            <tr>
                <th>BCI</th><th>SERT</th><th>FTF</th>
                <th>K9</th><th>CNU</th><th>PRU</th><th>SA</th><th>MRU</th>
                <th>MARY</th><th>HSU</th><th>ASU</th><th>MARINE</th>
                <th>NEGOCIATION</th><th>TERRAIN</th>
                <th>BCSO</th><th>SYNDICAT</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($effectif as $m): ?>
            <tr class="grade-row <?= strtolower(str_replace(' ', '-', $m['grade'])) ?>">
                <td>
                <a href="agent_details.php?discord_id=<?= urlencode($m['discord_id']) ?>" class="matricule-link">
                    <?= htmlspecialchars($m['matricule']) ?>
                </a>
                </td>
                <td><?= htmlspecialchars($m['prenom']) ?></td>
                <td><?= htmlspecialchars($m['nom']) ?></td>
                <td><?= htmlspecialchars($m['grade']) ?></td>

                <!-- Division -->
                <?php foreach (['bci', 'sert', 'ftf'] as $spec): ?>
                    <td class="spec-cell <?= !empty($m[$spec]) ? 'cell-checked ' . $spec : '' ?>">
                        <?= !empty($m[$spec]) ? '✔' : '' ?>
                    </td>
                <?php endforeach; ?>

                <!-- Spécialisation -->
                <?php foreach (['k9', 'cnu', 'pru', 'sa', 'mru'] as $spec): ?>
                    <td class="spec-cell <?= !empty($m[$spec]) ? 'cell-checked ' . $spec : '' ?>">
                        <?= !empty($m[$spec]) ? '✔' : '' ?>
                    </td>
                <?php endforeach; ?>

                <!-- Traffic Unit -->
                <?php foreach (['mary', 'hsu', 'asu', 'marine'] as $spec): ?>
                    <td class="spec-cell <?= !empty($m[$spec]) ? 'cell-checked ' . $spec : '' ?>">
                        <?= !empty($m[$spec]) ? '✔' : '' ?>
                    </td>
                <?php endforeach; ?>

                <!-- CNU -->
                <?php foreach (['negociation', 'terrain'] as $spec): ?>
                    <td class="spec-cell <?= !empty($m[$spec]) ? 'cell-checked ' . $spec : '' ?>">
                        <?= !empty($m[$spec]) ? '✔' : '' ?>
                    </td>
                <?php endforeach; ?>

                <!-- Autre -->
                <?php foreach (['bcso', 'syndicat'] as $spec): ?>
                    <td class="spec-cell <?= !empty($m[$spec]) ? 'cell-checked ' . $spec : '' ?>">
                        <?= !empty($m[$spec]) ? '✔' : '' ?>
                    </td>
                <?php endforeach; ?>

                <td><?= htmlspecialchars($m['date_maj']) ?></td>
                <td>
                    <form method="post" action="sa_effectif_refresh_one.php">
                        <input type="hidden" name="discord_id" value="<?= $m['discord_id'] ?>">
                        <button class="btn-refresh" type="submit">↻</button>
                    </form>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
