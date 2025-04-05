<?php
require_once '../config.php';

$role_sa = $roles['sa'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_sa)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}
?>

// Récupérer le discord_id depuis l'URL
$discord_id = $_GET['discord_id'] ?? null;
if (!$discord_id) {
    echo "<p style='color: red; text-align: center;'>ID Discord manquant.</p>";
    exit();
}

// Récupérer les infos de l'agent
$stmt = $pdo->prepare("SELECT * FROM sa_effectif WHERE discord_id = ?");
$stmt->execute([$discord_id]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    echo "<p style='color: red; text-align: center;'>Agent non trouvé.</p>";
    exit();
}

// Récupérer les détails
$stmt = $pdo->prepare("SELECT * FROM agent_details WHERE discord_id = ?");
$stmt->execute([$discord_id]);
$details = $stmt->fetch(PDO::FETCH_ASSOC);

// Charger les spécialisations depuis config
$specialisations = array_diff(array_keys($config['roles']), ['cs', 'doj', 'lead_bci']);

// Inclure le header
include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="../css/sa_effectif.css">

<div class="container">
    <h2>Détails de l'Agent</h2>

    <p><strong>Matricule :</strong> <?= htmlspecialchars($agent['matricule']) ?></p>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($agent['prenom']) ?></p>
    <p><strong>Nom :</strong> <?= htmlspecialchars($agent['nom']) ?></p>
    <p><strong>Grade :</strong> <?= htmlspecialchars($agent['grade']) ?></p>
    <p><strong>Dernière MAJ :</strong> <?= htmlspecialchars($agent['date_maj']) ?></p>

    <?php if (!empty($details['photo'])): ?>
    <div class="agent-photo">
        <img src="/assets/agents/<?= htmlspecialchars($details['photo']) ?>" alt="Photo d'identité" style="max-height: 180px; border-radius: 5px; border: 2px solid #555;">
    </div>
    <?php else: ?>
        <p><em>Pas de photo enregistrée.</em></p>
    <?php endif; ?>


    <h3>Spécialisations</h3>
    <table>
        <thead>
            <tr>
                <?php foreach ($specialisations as $spec): ?>
                    <th><?= strtoupper($spec) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ($specialisations as $spec): ?>
                    <td class="spec-cell <?= !empty($agent[$spec]) ? 'cell-checked ' . $spec : '' ?>">
                        <?= !empty($agent[$spec]) ? '✔' : '' ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>

    <h3>Informations Supplémentaires</h3>
    <p><strong>Numéro de téléphone :</strong>
        <?= htmlspecialchars($details['phone_number'] ?? 'Non renseigné') ?>
    </p>

    <h3>Armes attribuées</h3>
    <?php if (!empty($details['weapons'])):
        $weapons = json_decode($details['weapons'], true);
        if (is_array($weapons) && count($weapons) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom de l'arme</th>
                        <th>Numéro de série</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weapons as $weapon): ?>
                        <tr>
                            <td><?= htmlspecialchars($weapon['name']) ?></td>
                            <td><?= htmlspecialchars($weapon['serial']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucune arme enregistrée.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Aucune arme enregistrée.</p>
    <?php endif; ?>

    <br>
    <a href="sa_details_edit.php?discord_id=<?= urlencode($discord_id) ?>" class="btn-refresh">✏️ Modifier les informations</a>
</div>

<?php include '../includes/footer.php'; ?>
