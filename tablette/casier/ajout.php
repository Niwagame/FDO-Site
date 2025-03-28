<?php
include '../../config.php';
include '../../includes/header.php';
require_once 'casier_discord.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

// Utiliser le surnom Discord si disponible, sinon utiliser le pseudo global
$officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

// Récupération de la liste des entreprises pour la liste déroulante
$stmt = $pdo->query("SELECT id, nom FROM entreprise ORDER BY nom");
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des groupes pour la liste déroulante d'affiliation
$stmt = $pdo->query("SELECT id, name FROM user_groups ORDER BY name");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
<link rel="stylesheet" href="../../css/styles.css">
    <h2>Ajouter un Casier</h2>
    <form action="ajout.php" method="POST" enctype="multipart/form-data">
        <label>Nom :</label>
        <input type="text" name="nom" placeholder="Nom" required>

        <label>Prénom :</label>
        <input type="text" name="prenom" placeholder="Prénom" required>

        <label>Date de Naissance :</label>
        <input type="date" name="date_naissance" required>

        <label>Numéro de Téléphone :</label>
        <input type="tel" name="num_tel" placeholder="Numéro de téléphone">

        <label>Affiliation :</label>
        <select name="affiliation">
            <option value="">-- Sélectionnez une affiliation --</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= htmlspecialchars($group['name']); ?>"><?= htmlspecialchars($group['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Grade :</label>
        <input type="text" name="grade" placeholder="Grade">

        <label>Entreprise :</label>
        <select name="entreprise_id">
            <option value="">-- Sélectionnez une entreprise --</option>
            <?php foreach ($entreprises as $entreprise): ?>
                <option value="<?= htmlspecialchars($entreprise['id']); ?>"><?= htmlspecialchars($entreprise['nom']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Photo :</label>
        <input type="file" name="photo">

        <button type="submit">Ajouter le Casier</button>
    </form>
</div>

<?php
// Traitement du formulaire d'ajout de casier
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_naissance = $_POST['date_naissance'];
    $num_tel = $_POST['num_tel'];
    $affiliation = $_POST['affiliation'];
    $grade = $_POST['grade'];
    $entreprise_id = !empty($_POST['entreprise_id']) ? $_POST['entreprise_id'] : null;
    $photo = $_FILES['photo']['name'];

    // Vérification des doublons pour le nom, prénom et date de naissance
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM casiers WHERE nom = ? AND prenom = ? AND date_naissance = ?");
    $stmt->execute([$nom, $prenom, $date_naissance]);
    if ($stmt->fetchColumn() > 0) {
        echo "<p style='color: red;'>Un casier avec ce nom, prénom et date de naissance existe déjà.</p>";
        exit();
    }

    // Vérification des doublons pour le nom de la photo
    if (!empty($photo)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM casiers WHERE photo = ?");
        $stmt->execute([$photo]);
        if ($stmt->fetchColumn() > 0) {
            echo "<p style='color: red;'>Une photo avec ce nom existe déjà. Veuillez renommer l'image.</p>";
            exit();
        }
        
        // Sauvegarde de la photo dans le dossier images
        $target_dir = "../../assets/images/";
        $target_file = $target_dir . basename($photo);
        move_uploaded_file($_FILES['photo']['tmp_name'], $target_file);
    }

    // Insertion dans la base de données avec `officier_id`
    $stmt = $pdo->prepare("INSERT INTO casiers (nom, prenom, date_naissance, num_tel, affiliation, entreprise_id, photo, officier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $date_naissance, $num_tel, $affiliation, $entreprise_id, $photo, $officier_id]);

    // Récupérer le nom de l'entreprise pour Discord
    $entreprise = "N/A";
    if ($entreprise_id) {
        $stmt = $pdo->prepare("SELECT nom FROM entreprise WHERE id = ?");
        $stmt->execute([$entreprise_id]);
        $entreprise = $stmt->fetchColumn() ?? "N/A";
    }

    // Récupère l'ID du casier après insertion
    $casier_id = $pdo->lastInsertId();

    // Envoi à Discord
    sendCasierCreationToDiscord($casier_id, $nom, $prenom, $date_naissance, $num_tel, $affiliation, $entreprise, $officier_id);

    echo "<p>Casier ajouté avec succès !</p>";
}
?>

<?php include '../../includes/footer.php'; ?>
