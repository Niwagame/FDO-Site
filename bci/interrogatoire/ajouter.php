<?php
session_start();
require_once '../../config.php';
date_default_timezone_set('Europe/Paris');

if (!isset($_GET['casier_id'])) {
    echo "Casier non spécifié.";
    exit();
}

$casier_id = $_GET['casier_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datetime = date('Y-m-d H:i:s'); // Récupère automatiquement la date et l'heure actuelle 
    $deposition = $_POST['deposition'] ?? '';
    $analyse = $_POST['analyse'] ?? '';
    $hypotheses = $_POST['hypotheses'] ?? '';
    $faits = $_POST['faits_importants'] ?? '';
    $questions = $_POST['questions_posees'] ?? '';
    $reponses = $_POST['reponses'] ?? '';
    $infos = $_POST['infos_complementaires'] ?? '';

    // Gestion des fichiers médias
    $fichiers_media = [];
    $uploadDir = __DIR__ . '/../../assets/interrogatoire/';
    $webPath = '/assets/interrogatoire/';

    if (!empty($_FILES['fichiers_media']['name'][0])) {
        foreach ($_FILES['fichiers_media']['tmp_name'] as $key => $tmp_name) {
            $fileName = time() . '_' . basename($_FILES['fichiers_media']['name'][$key]);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($tmp_name, $targetFilePath)) {
                $fichiers_media[] = $webPath . $fileName;
            }
        }
    }

    // Agent depuis la session
    $agent = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

    // Insertion
    $stmt = $pdo->prepare("
        INSERT INTO interrogatoires (casier_id, date_interrogatoire, deposition, analyse, hypotheses, faits_importants, questions_posees, reponses, infos_complementaires, fichiers_media, agent_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $casier_id, $date, $deposition, $analyse, $hypotheses,
        $faits, $questions, $reponses, $infos,
        json_encode($fichiers_media), $agent
    ]);

    header("Location: liste.php?casier_id=" . $casier_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Interrogatoire</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Ajouter un Interrogatoire</h2>

    <div class="interro-guide">
        <h3>Guide pour l'interrogatoire</h3>
        <ul>
            <li><strong>Déposition :</strong> Retranscrire l’intégralité des faits</li>
            <li><strong>Analyse :</strong> Vérifier la cohérence et la véracité</li>
            <li><strong>Hypothèses :</strong> Deductions suite aux réponses</li>
            <li><strong>Faits marquants :</strong>
                <ul>
                    <li>Présence de blessés ? (Joindre ITT/EMS)</li>
                    <li>Présence d’illégal ? Quelle quantité ?</li>
                    <li>Affiliation à un gang, famille, orga ?</li>
                    <li>Saisies effectuées ? Lesquelles ?</li>
                </ul>
            </li>
            <li><strong>Questions à poser si drogue/arme :</strong>
                <ul>
                    <li>Où obtenue ?</li>
                    <li>Prix de revente ?</li>
                    <li>Infos sur le groupe ?</li>
                    <li>Lieu de fabrication ?</li>
                    <li>RDV fréquents ? N° d’un contact ?</li>
                </ul>
            </li>
            <li>⚠️ <strong>Réduction peine :</strong> Max 75%  voir avec cs (50% avec proc)</li>
            <li><strong>Informations complémentaires :</strong> Enquête, dangers, services publics, membres identifiés, etc.</li>
            <li>📷 <strong>Médias :</strong> Ajouter photos/vidéos si dispo</li>
        </ul>
    </div>

    <form method="post" enctype="multipart/form-data">

        <label>Déposition :</label>
        <textarea name="deposition" required></textarea>

        <label>Analyse de l'interrogatoire :</label>
        <textarea name="analyse" required></textarea>

        <label>Hypothèses émises :</label>
        <textarea name="hypotheses"></textarea>

        <label>Faits importants :</label>
        <textarea name="faits_importants"></textarea>

        <label>Questions posées :</label>
        <textarea name="questions_posees"></textarea>

        <label>Réponses :</label>
        <textarea name="reponses"></textarea>

        <label>Informations complémentaires :</label>
        <textarea name="infos_complementaires"></textarea>

        <label>Photos/Vidéos :</label>
        <input type="file" name="fichiers_media[]" multiple>

        <button type="submit">Enregistrer</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
