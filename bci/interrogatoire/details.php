<?php
session_start();
require_once '../../config.php';

// Récupération des rôles
$config = parse_ini_file('../../config.ini', true);
$bci_id = $config['roles']['bci'] ?? null;

// Vérifie que l'utilisateur est connecté et a le rôle BCI
if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !isset($_SESSION['roles']) || 
    !in_array($bci_id, $_SESSION['roles'])
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : vous n'avez pas les permissions nécessaires.</p>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "Interrogatoire non spécifié.";
    exit();
}

$id = $_GET['id'];

// Récupère les infos de l’interrogatoire avec l’individu
$stmt = $pdo->prepare("
    SELECT i.*, c.nom, c.prenom
    FROM interrogatoires i
    LEFT JOIN casiers c ON i.casier_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$interrogatoire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$interrogatoire) {
    echo "Interrogatoire introuvable.";
    exit();
}

// Gestion des médias (sous forme de chemins JSON)
$medias = [];
if (!empty($interrogatoire['fichiers_media'])) {
    $medias = json_decode($interrogatoire['fichiers_media'], true);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'Interrogatoire</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>🕵️‍♂️ Détails de l'Interrogatoire</h2>

    <p><strong>Date :</strong> 
        <?= $interrogatoire['created_at'] ? htmlspecialchars(date("d/m/Y à H\hi", strtotime($interrogatoire['created_at']))) : '<em>Non spécifiée</em>'; ?>
    </p>

    <p><strong>Individu interrogé :</strong> <?= htmlspecialchars($interrogatoire['prenom'] . ' ' . $interrogatoire['nom']); ?></p>
    <p><strong>Agent :</strong> <?= htmlspecialchars($interrogatoire['agent_id'] ?? 'Inconnu'); ?></p>

    <hr>

    <h3>Déposition</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['deposition'])); ?></p>

    <h3>Analyse</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['analyse'])); ?></p>

    <h3>Hypothèses émises</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['hypotheses'])); ?></p>

    <h3>Faits importants ou marquants</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['faits_importants'])); ?></p>

    <h3>Questions posées</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['questions_posees'])); ?></p>

    <h3>Réponses</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['reponses'])); ?></p>

    <h3>Informations complémentaires</h3>
    <p><?= nl2br(htmlspecialchars($interrogatoire['infos_complementaires'])); ?></p>

    <?php if (!empty($medias)): ?>
        <h3>Médias joints</h3>
        <ul>
            <?php foreach ($medias as $media): ?>
                <li>
                    <?php
                        $extension = pathinfo($media, PATHINFO_EXTENSION);
                        $url = htmlspecialchars($media);
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            echo "<img src=\"$url\" alt=\"Image\" style=\"max-width: 200px; margin: 10px 0;\">";
                        } else {
                            echo "<a href=\"$url\" target=\"_blank\">Voir fichier joint</a>";
                        }
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><em>Aucun média joint.</em></p>
    <?php endif; ?>

    <div class="button-container">
        <a href="modifier.php?id=<?= $interrogatoire['id']; ?>" class="button">✏️ Modifier</a>
        <a href="liste.php" class="button">↩️ Retour à la liste</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
