<?php
session_start();
require_once '../../config.php';

$interro_id = $_GET['id'] ?? null;
if (!$interro_id) {
    echo "Interrogatoire non spécifié.";
    exit();
}

// Vérif autorisation : lead_bci uniquement
$config = parse_ini_file('../../config.ini', true);
$lead_bci_id = $config['roles']['lead_bci'] ?? null;

if (
    !isset($_SESSION['user_authenticated']) ||
    $_SESSION['user_authenticated'] !== true ||
    !in_array($lead_bci_id, $_SESSION['roles'] ?? [])
) {
    echo "<p style='color:red;text-align:center;'>Accès refusé : seuls les lead BCI peuvent modifier un interrogatoire.</p>";
    exit();
}

// Récupération
$stmt = $pdo->prepare("SELECT * FROM interrogatoires WHERE id = ?");
$stmt->execute([$interro_id]);
$interrogatoire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$interrogatoire) {
    echo "Interrogatoire introuvable.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date_interrogatoire'] ?? null;
    $deposition = $_POST['deposition'] ?? '';
    $analyse = $_POST['analyse'] ?? '';
    $hypotheses = $_POST['hypotheses'] ?? '';
    $faits = $_POST['faits_importants'] ?? '';
    $questions = $_POST['questions_posees'] ?? '';
    $reponses = $_POST['reponses'] ?? '';
    $infos = $_POST['infos_complementaires'] ?? '';

    // Gestion des médias
    $medias = json_decode($interrogatoire['fichiers_media'] ?? '[]', true);
    $uploadDir = __DIR__ . '/../../assets/interrogatoire/';
    $webPath = '/assets/interrogatoire/';

    if (!empty($_FILES['fichiers_media']['name'][0])) {
        foreach ($_FILES['fichiers_media']['tmp_name'] as $key => $tmp_name) {
            $fileName = time() . '_' . basename($_FILES['fichiers_media']['name'][$key]);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($tmp_name, $targetFilePath)) {
                $medias[] = $webPath . $fileName;
            }
        }
    }

    $stmt = $pdo->prepare("
        UPDATE interrogatoires
        SET date_interrogatoire = ?, deposition = ?, analyse = ?, hypotheses = ?, faits_importants = ?, questions_posees = ?, reponses = ?, infos_complementaires = ?, fichiers_media = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $date ?: null, $deposition, $analyse, $hypotheses, $faits,
        $questions, $reponses, $infos, json_encode($medias),
        $interro_id
    ]);

    header("Location: details.php?id=" . $interro_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'Interrogatoire</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>✏️ Modifier l'Interrogatoire</h2>

    <form method="post" enctype="multipart/form-data">
        <label>Date de l'interrogatoire :</label>
        <input type="date" name="date_interrogatoire" value="<?= htmlspecialchars($interrogatoire['date_interrogatoire'] ?? '') ?>">

        <label>Déposition :</label>
        <textarea name="deposition" required><?= htmlspecialchars($interrogatoire['deposition']) ?></textarea>

        <label>Analyse de l'interrogatoire :</label>
        <textarea name="analyse" required><?= htmlspecialchars($interrogatoire['analyse']) ?></textarea>

        <label>Hypothèses émises :</label>
        <textarea name="hypotheses"><?= htmlspecialchars($interrogatoire['hypotheses']) ?></textarea>

        <label>Faits importants :</label>
        <textarea name="faits_importants"><?= htmlspecialchars($interrogatoire['faits_importants']) ?></textarea>

        <label>Questions posées :</label>
        <textarea name="questions_posees"><?= htmlspecialchars($interrogatoire['questions_posees']) ?></textarea>

        <label>Réponses :</label>
        <textarea name="reponses"><?= htmlspecialchars($interrogatoire['reponses']) ?></textarea>

        <label>Informations complémentaires :</label>
        <textarea name="infos_complementaires"><?= htmlspecialchars($interrogatoire['infos_complementaires']) ?></textarea>

        <label>Ajouter des fichiers :</label>
        <input type="file" name="fichiers_media[]" multiple>

        <?php
        $medias = json_decode($interrogatoire['fichiers_media'] ?? '[]', true);
        if (!empty($medias)) {
            echo '<h4>Médias existants :</h4><ul>';
            foreach ($medias as $media) {
                $ext = pathinfo($media, PATHINFO_EXTENSION);
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo "<li><img src=\"$media\" alt=\"media\" style=\"max-width:100px;\"></li>";
                } else {
                    echo "<li><a href=\"$media\" target=\"_blank\">$media</a></li>";
                }
            }
            echo '</ul>';
        }
        ?>

        <button type="submit">💾 Enregistrer les modifications</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
