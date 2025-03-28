<?php
session_start();
require_once '../../config.php';
require_once 'casier_discord.php';

// Vérification de l'identifiant du casier
if (!isset($_GET['id'])) {
    echo "Individu non spécifié.";
    exit();
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

$casier_id = $_GET['id'];

// Récupération des détails de l'individu avec l'entreprise associée
$stmt = $pdo->prepare("
    SELECT c.*, e.nom AS entreprise_nom
    FROM casiers c
    LEFT JOIN entreprise e ON c.entreprise_id = e.id
    WHERE c.id = ?
");
$stmt->execute([$casier_id]);
$individu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$individu) {
    echo "Individu non trouvé.";
    exit();
}

// Formatage de la date de naissance
$date_naissance = date("d-m-Y", strtotime($individu['date_naissance']));

// Chemin de la photo de l'individu
$photoPath = '../../assets/images/' . $individu['photo'];

// Récupération des rapports associés
$stmt = $pdo->prepare("
    SELECT r.id AS rapport_id, r.date_arrestation, a.description AS motif, r.officier_id AS agent
    FROM rapports r
    JOIN rapports_individus ri ON r.id = ri.rapport_id
    LEFT JOIN amende a ON r.motif = a.id
    WHERE ri.casier_id = ?
    ORDER BY r.date_arrestation DESC
");
$stmt->execute([$casier_id]);
$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des objets saisis associés
$stmt = $pdo->prepare("
    SELECT sc.quantite, s.nom AS objet_nom, s.poids, r.date_arrestation, r.id AS rapport_id
    FROM saisie_c sc
    JOIN saisie s ON sc.saisie_id = s.id
    JOIN rapports r ON sc.idrapport = r.id
    WHERE sc.idcasier = ?
    ORDER BY r.date_arrestation DESC
");
$stmt->execute([$casier_id]);
$saisies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des plaintes associées
$stmt = $pdo->prepare("
    SELECT p.id AS plainte_id, p.date_creation, c1.nom AS plaignant_nom, c1.prenom AS plaignant_prenom, c2.nom AS visee_nom, c2.prenom AS visee_prenom, p.motif_texte
    FROM plaintes p
    LEFT JOIN casiers c1 ON p.plaignant_id = c1.id
    LEFT JOIN casiers c2 ON p.personne_visee_id = c2.id
    WHERE c1.id = :casier_id OR c2.id = :casier_id
");
$stmt->execute(['casier_id' => $casier_id]);
$plaintes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la suppression du casier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $required_role_id = '1354869471558373446';
    $user_roles = $_SESSION['roles'] ?? [];

    // Vérification du rôle pour la suppression
    if (!in_array($required_role_id, $user_roles)) {
        echo "Vous n'avez pas l'autorisation de supprimer ce casier.";
        exit();
    }

    // Envoi de la notification à Discord
    $officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    sendCasierDeletionToDiscord(
        $casier_id,
        $individu['nom'],
        $individu['prenom'],
        $date_naissance,
        $individu['num_tel'] ?? 'N/A',
        $individu['affiliation'] ?? 'N/A',
        $individu['entreprise_nom'] ?? 'N/A',
        $officier_id
    );

    // Suppression des rapports, saisies et plaintes associées
    foreach ($rapports as $rapport) {
        $rapport_id = $rapport['rapport_id'];
        $stmt = $pdo->prepare("DELETE FROM rapports WHERE id = ?");
        $stmt->execute([$rapport_id]);
        $stmt = $pdo->prepare("DELETE FROM rapports_individus WHERE rapport_id = ?");
        $stmt->execute([$rapport_id]);
    }

    $stmt = $pdo->prepare("DELETE FROM saisie_c WHERE idcasier = ?");
    $stmt->execute([$casier_id]);
    $stmt = $pdo->prepare("DELETE FROM plaintes WHERE plaignant_id = ? OR personne_visee_id = ?");
    $stmt->execute([$casier_id, $casier_id]);
    $stmt = $pdo->prepare("DELETE FROM casiers WHERE id = ?");
    $stmt->execute([$casier_id]);

    if (!empty($individu['photo']) && file_exists($photoPath)) {
        unlink($photoPath);
    }

    header('Location: liste.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Casier de <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Détails du Casier de <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?></h2>
    <p><strong>Nom :</strong> <?= htmlspecialchars($individu['nom']); ?></p>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($individu['prenom']); ?></p>
    <p><strong>Date de Naissance :</strong> <?= htmlspecialchars($date_naissance); ?></p>
    <p><strong>Numéro de Téléphone :</strong> <?= htmlspecialchars($individu['num_tel'] ?? 'N/A'); ?></p>
    <p><strong>Affiliation :</strong> <?= htmlspecialchars($individu['affiliation'] ?? 'N/A'); ?></p>
    <p><strong>Grade :</strong> <?= htmlspecialchars($individu['grade'] ?? 'N/A'); ?></p>
    <p><strong>Entreprise :</strong> <?= htmlspecialchars($individu['entreprise_nom'] ?? 'Aucune'); ?></p>
    <p><strong>Réalisé par :</strong> <?= htmlspecialchars($individu['officier_id'] ?? 'Inconnu'); ?></p>

    <?php if (!empty($individu['photo']) && file_exists($photoPath)): ?>
        <img src="<?= htmlspecialchars($photoPath); ?>" alt="Photo de <?= htmlspecialchars($individu['nom']); ?>" width="150">
    <?php else: ?>
        <p><em>Photo non disponible</em></p>
    <?php endif; ?>

    <!-- Affichage des rapports d'arrestation associés -->
    <h3>Rapports d'Arrestation Associés</h3>
    <?php if (count($rapports) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date d'Arrestation</th>
                    <th>Motif</th>
                    <th>Agent</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapports as $rapport): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d-m-Y", strtotime($rapport['date_arrestation']))); ?></td>
                        <td><?= htmlspecialchars($rapport['motif']); ?></td>
                        <td><?= htmlspecialchars($rapport['agent'] ?? 'Inconnu'); ?></td>
                        <td><a href="/tablette/rapport/details.php?id=<?= $rapport['rapport_id']; ?>" class="button">Afficher Détails</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun rapport d'arrestation associé.</p>
    <?php endif; ?>

    <!-- Affichage des objets saisis associés -->
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
                        <td><a href="/tablette/rapport/details.php?id=<?= $saisie['rapport_id']; ?>">Voir Rapport</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun objet saisi pour cet individu.</p>
    <?php endif; ?>

    <!-- Affichage des plaintes associées -->
    <h3>Plaintes Associées</h3>
    <?php if (count($plaintes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Plaignant</th>
                    <th>Personne Visée</th>
                    <th>Motif</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plaintes as $plainte): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d-m-Y", strtotime($plainte['date_creation']))); ?></td>
                        <td><?= htmlspecialchars($plainte['plaignant_nom'] . ' ' . $plainte['plaignant_prenom']); ?></td>
                        <td><?= htmlspecialchars($plainte['visee_nom'] ? $plainte['visee_nom'] . ' ' . $plainte['visee_prenom'] : 'Aucun'); ?></td>
                        <td><?= htmlspecialchars($plainte['motif_texte']); ?></td>
                        <td><a href="/tablette/plaintes/details.php?id=<?= $plainte['plainte_id']; ?>" class="button">Afficher Détails</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune plainte associée.</p>
    <?php endif; ?>

    <!-- Boutons de modification et de suppression -->
    <div class="button-container">
        <form action="modifier.php" method="get" style="display: inline;">
            <input type="hidden" name="id" value="<?= htmlspecialchars($casier_id); ?>">
            <button type="submit" class="button edit">Modifier le Casier</button>
        </form>
        <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce casier ?');" style="display: inline;">
            <button type="submit" name="delete" class="button delete">Supprimer le Casier</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
