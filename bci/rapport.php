<?php
session_start();
require_once '../config.php';

// Vérifie que l'utilisateur est connecté et a le rôle BCI
if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !isset($_SESSION['roles']) || 
    !in_array($roles['bci'], $_SESSION['roles'])
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : vous n'avez pas les permissions nécessaires.</p>";
    exit();
}

// Récupérer la liste des groupes
$stmt = $pdo->query("SELECT id, name FROM user_groups ORDER BY name");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générer un Rapport d'Enquête</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>Générer un Rapport d'Enquête</h2>

    <form method="POST" action="generer_rapport.php">
    <label for="group">Sélectionner un Groupe :</label>
    <select name="id" id="group" required>
        <option value="" disabled selected>Choisir un groupe</option>
        <?php foreach ($groups as $group): ?>
            <option value="<?= $group['id']; ?>"><?= htmlspecialchars($group['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="button">Générer Rapport</button>
</form>

</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
