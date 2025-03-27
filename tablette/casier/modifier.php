<?php
session_start();
require_once '../../config.php';

if (!isset($_GET['id'])) {
    echo "Individu non spécifié.";
    exit();
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

$casier_id = $_GET['id'];

// Récupération des détails de l'individu pour les pré-remplir
$stmt = $pdo->prepare("SELECT * FROM casiers WHERE id = ?");
$stmt->execute([$casier_id]);
$individu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$individu) {
    echo "Individu non trouvé.";
    exit();
}

// Récupération de la liste des entreprises
$stmt = $pdo->query("SELECT id, nom FROM entreprise ORDER BY nom");
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des groupes pour l'affiliation
$stmt = $pdo->query("SELECT id, name FROM user_groups ORDER BY name");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_naissance = $_POST['date_naissance'];
    $num_tel = $_POST['num_tel'];
    $affiliation = $_POST['affiliation'];
    $grade = $_POST['grade'];
    $entreprise_id = !empty($_POST['entreprise_id']) ? $_POST['entreprise_id'] : null;

    // Gestion de la nouvelle photo si elle est téléchargée
    if (!empty($_FILES['photo']['name'])) {
        $photo = basename($_FILES['photo']['name']);
        $upload_dir = '../../assets/images/';
        $upload_file = $upload_dir . $photo;

        // Supprimer l'ancienne photo si elle existe
        $old_photo_path = $upload_dir . $individu['photo'];
        if (file_exists($old_photo_path)) {
            unlink($old_photo_path);
        }

        // Déplacer la nouvelle photo
        move_uploaded_file($_FILES['photo']['tmp_name'], $upload_file);
    } else {
        $photo = $individu['photo']; // Garde la photo actuelle si aucune nouvelle photo n'est téléchargée
    }

    // Mettre à jour les informations du casier dans la base de données
    $stmt = $pdo->prepare("UPDATE casiers SET nom = ?, prenom = ?, date_naissance = ?, num_tel = ?, affiliation = ?, photo = ?, entreprise_id = ? WHERE id = ?");
    $stmt->execute([$nom, $prenom, $date_naissance, $num_tel, $affiliation, $photo, $entreprise_id, $casier_id]);

    // Rediriger vers la page de détails après modification
    header("Location: details.php?id=" . $casier_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Casier</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Modifier le Casier de <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?></h2>
    <form action="modifier.php?id=<?= htmlspecialchars($casier_id); ?>" method="post" enctype="multipart/form-data">
        <label>Nom :</label>
        <input type="text" name="nom" value="<?= htmlspecialchars($individu['nom']); ?>" required>

        <label>Prénom :</label>
        <input type="text" name="prenom" value="<?= htmlspecialchars($individu['prenom']); ?>" required>

        <label>Date de Naissance :</label>
        <input type="date" name="date_naissance" value="<?= htmlspecialchars($individu['date_naissance']); ?>" required>

        <label>Numéro de Téléphone :</label>
        <input type="text" name="num_tel" value="<?= htmlspecialchars($individu['num_tel']); ?>" required>

        <label>Affiliation :</label>
        <select name="affiliation">
            <option value="">-- Sélectionnez une affiliation --</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= htmlspecialchars($group['name']); ?>" <?= $group['name'] == $individu['affiliation'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($group['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Grade :</label>
        <input type="text" name="grade" value="<?= htmlspecialchars($individu['grade']); ?>" required>

        <!-- Liste déroulante pour les entreprises -->
        <label>Entreprise :</label>
        <select name="entreprise_id">
            <option value="">-- Sélectionnez une entreprise --</option>
            <?php foreach ($entreprises as $entreprise): ?>
                <option value="<?= htmlspecialchars($entreprise['id']); ?>" <?= $entreprise['id'] == $individu['entreprise_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($entreprise['nom']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Photo :</label>
        <?php if (!empty($individu['photo']) && file_exists('../../assets/images/' . $individu['photo'])): ?>
            <img src="../../assets/images/<?= htmlspecialchars($individu['photo']); ?>" alt="Photo de <?= htmlspecialchars($individu['nom']); ?>" width="100">
        <?php else: ?>
            <p><em>Photo non disponible</em></p>
        <?php endif; ?>
        <input type="file" name="photo">

        <button type="submit">Enregistrer les Modifications</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
