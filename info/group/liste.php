<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

// Gestion de la recherche
$search = $_GET['search'] ?? '';
$query = "SELECT id, name, type FROM user_groups";
if ($search) {
    $query .= " WHERE name LIKE :search OR type LIKE :search";
}
$query .= " ORDER BY type, name";
$stmt = $pdo->prepare($query);

if ($search) {
    $stmt->execute(['search' => '%' . $search . '%']);
} else {
    $stmt->execute();
}

$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Groupes</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des Groupes</h2>

    <!-- Bouton Ajouter Groupe -->
    <div style="margin-bottom: 15px;">
        <a href="ajout.php" class="button">Ajouter Groupe</a>
    </div>

    <!-- Barre de recherche -->
    <form method="GET" action="liste.php">
        <input type="text" name="search" placeholder="Rechercher un groupe..." value="<?= htmlspecialchars($search); ?>">
        <button type="submit">Rechercher</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Nom du Groupe</th>
                <th>Type</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($groups) > 0): ?>
                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= htmlspecialchars($group['name']); ?></td>
                        <td><?= htmlspecialchars($group['type']); ?></td>
                        <td><a href="details.php?id=<?= $group['id']; ?>" class="button">Voir Détails</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Aucun groupe trouvé.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
