<?php
session_start();
require_once '../../config.php';

// Récupère l'ID du rôle BCSO depuis config.ini
$role_bco = $roles['bco'] ?? null;

// Vérifie l'authentification et l'autorisation BCSO
if (
    !isset($_SESSION['user_authenticated']) ||
    $_SESSION['user_authenticated'] !== true ||
    !hasRole($role_bco)
) {
    echo "<p style='color:red; text-align:center;'>Accès refusé : seuls les membres du BCSO peuvent ajouter un casier.</p>";
    exit();
}

// Gestion de l'ajout d'un groupe
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';

    if (!empty($name) && !empty($type)) {
        $stmt = $pdo->prepare("INSERT INTO user_groups (name, type) VALUES (:name, :type)");
        $stmt->execute(['name' => $name, 'type' => $type]);
        $message = 'Le groupe a été ajouté avec succès.';
    } else {
        $message = 'Veuillez remplir tous les champs.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Groupe</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Ajouter un Groupe</h2>

    <?php if ($message): ?>
        <p class="success-message"><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" action="ajout.php">
        <!-- Champ Nom du Groupe -->
        <div>
            <label for="name">Nom du Groupe :</label>
            <input type="text" id="name" name="name" placeholder="Nom du groupe" required>
        </div>

        <!-- Liste déroulante Type de Groupe -->
        <div>
            <label for="type">Type de Groupe :</label>
            <select id="type" name="type" required>
                <option value="" disabled selected>Choisir un type</option>
                <option value="Gang">Gang</option>
                <option value="Organisation">Organisation</option>
                <option value="Famille">Famille</option>
                <option value="MC">MC</option>
            </select>
        </div>

        <!-- Bouton d'envoi -->
        <div>
            <button type="submit" class="button">Ajouter</button>
            <a href="liste.php" class="button">Annuler</a>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
