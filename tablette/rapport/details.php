<?php
session_start();
require_once '../../config.php';
require_once 'rapport_discord.php';

$role_bco = $roles['bco'];
$role_doj = $roles['doj'];

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true || !hasRole($role_bco) && !hasRole($role_doj)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

// Récupérer l'ID du rapport à partir des paramètres GET
$rapport_id = $_GET['id'] ?? null;
if (!$rapport_id) {
    echo "Rapport non spécifié.";
    exit();
}

// Supprimer le rapport si le formulaire de suppression est soumis
if (isset($_POST['delete'])) {
    $supprime_par = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    sendReportDeletionToDiscord($rapport_id, $supprime_par);

    $stmt = $pdo->prepare("DELETE FROM rapports WHERE id = ?");
    if ($stmt->execute([$rapport_id])) {
        header("Location: /tablette/rapport/liste.php");
        exit();
    } else {
        echo "Erreur lors de la suppression du rapport.";
        exit();
    }
}

// Récupérer les détails du rapport
$stmt = $pdo->prepare("
    SELECT r.*, a.description AS motif_description, a.article AS motif_article, a.details AS motif_details, r.officier_id AS agent
    FROM rapports r
    LEFT JOIN amende a ON r.motif = a.id
    WHERE r.id = ?
");
$stmt->execute([$rapport_id]);
$rapport = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rapport) {
    echo "Rapport non trouvé.";
    exit();
}

// Récupérer les individus impliqués dans le rapport
$stmt = $pdo->prepare("
    SELECT c.id AS casier_id, c.nom, c.prenom
    FROM casiers c
    JOIN rapports_individus ri ON c.id = ri.casier_id
    WHERE ri.rapport_id = ?
");
$stmt->execute([$rapport_id]);
$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les objets saisis associés au rapport
$stmt = $pdo->prepare("
    SELECT sc.quantite, s.nom AS objet_nom, s.poids, c.nom AS individu_nom, c.prenom AS individu_prenom, c.id AS casier_id
    FROM saisie_c sc
    JOIN saisie s ON sc.saisie_id = s.id
    JOIN casiers c ON sc.idcasier = c.id
    WHERE sc.idrapport = ?
");
$stmt->execute([$rapport_id]);
$saisies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Rapport d'Arrestation</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Détails du Rapport d'Arrestation</h2>
    <p><strong>Date d'Arrestation :</strong> <?= htmlspecialchars($rapport['date_arrestation'] ?? 'Non renseignée'); ?></p>

    <div class="motif-box">
        <p><strong>Motif :</strong> <?= htmlspecialchars($rapport['motif_description'] ?? 'Non renseigné'); ?></p>
        <hr>
        <p><strong>Article :</strong> <?= htmlspecialchars($rapport['motif_article'] ?? 'Non renseigné'); ?></p>
        <hr>
        <p><strong>Définitions :</strong> <?= htmlspecialchars($rapport['motif_details'] ?? 'Non renseignés'); ?></p>
    </div>
    <hr>
    <p><strong>Rapport d'arrestation:</strong> <?= nl2br(htmlspecialchars($rapport['rapport_text'] ?? 'Non renseigné')); ?></p>
    <p><strong>Coopération :</strong> <?= htmlspecialchars($rapport['coop'] ?? 'Non renseignée'); ?></p>
    <p><strong>Temps Miranda :</strong> <?= htmlspecialchars($rapport['miranda_time'] ?? 'Non renseigné'); ?></p>
    <p><strong>Droits Demander :</strong> <?= htmlspecialchars($rapport['demandes_droits'] ?? 'Non renseignées'); ?></p>
    <p><strong>Heure des Droits :</strong> <?= htmlspecialchars($rapport['heure_droits'] ?? 'Non renseignée'); ?></p>
    <p><strong>Amende :</strong> <?= isset($rapport['amende']) ? number_format($rapport['amende'], 0, ',', ' ') . " $" : 'Non renseignée'; ?></p>
    <p><strong>Peine :</strong> <?= htmlspecialchars($rapport['retention'] ?? 'Non renseignée'); ?></p>
    <p><strong>Agent :</strong> <?= htmlspecialchars($rapport['agent'] ?? 'Inconnu'); ?></p>

    <h3>Individus Impliqués</h3>
    <?php if (!empty($individus)): ?>
        <ul>
            <?php foreach ($individus as $individu): ?>
                <li><a href="/tablette/casier/details.php?id=<?= $individu['casier_id']; ?>"><?= htmlspecialchars($individu['nom']) . ' ' . htmlspecialchars($individu['prenom']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun individu impliqué dans ce rapport.</p>
    <?php endif; ?>

    <h3>Objets Saisis</h3>
    <?php if (!empty($saisies)): ?>
        <table>
            <thead>
                <tr>
                    <th>Objet</th>
                    <th>Quantité</th>
                    <th>Poids Total (kg)</th>
                    <th>Individu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saisies as $saisie): ?>
                    <tr>
                        <td><?= htmlspecialchars($saisie['objet_nom']); ?></td>
                        <td><?= htmlspecialchars($saisie['quantite']); ?></td>
                        <td><?= htmlspecialchars(number_format($saisie['poids'] * $saisie['quantite'], 2)); ?></td>
                        <td><a href="/tablette/casier/details.php?id=<?= $saisie['casier_id']; ?>"><?= htmlspecialchars($saisie['individu_nom'] . ' ' . $saisie['individu_prenom']); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun objet saisi pour ce rapport.</p>
    <?php endif; ?>

    <!-- Boutons de modification et suppression -->
    <form action="modifier.php" method="get" style="display: inline;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($rapport_id); ?>">
        <button type="submit" class="button edit">Modifier le Rapport</button>
    </form>

    <?php if (hasRole($role_bco)): ?>
    <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?');" style="display: inline;">
        <button type="submit" name="delete" class="button delete">Supprimer le Rapport</button>
    </form>
    <?php endif; ?>
</div>

<style>
    .motif-box {
        background-color: #222222;
        border: 1px solid #ccc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .motif-box hr {
        border: 0;
        border-top: 1px solid #ddd;
        margin: 10px 0;
    }
</style>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
