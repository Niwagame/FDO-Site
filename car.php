<?php
session_start();
require_once 'config.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Récupération de la recherche de l'utilisateur
$search = strtolower($_GET['search'] ?? '');

// Requête SQL pour rechercher dans les grades et les véhicules
$query = "
    SELECT g.grade_name, v.vehicle_name, v.vehicle_code 
    FROM grades g
    JOIN grade_vehicle gv ON g.id = gv.grade_id
    JOIN vehicles v ON gv.vehicle_id = v.id
    WHERE LOWER(g.grade_name) LIKE :search OR LOWER(v.vehicle_name) LIKE :search
    ORDER BY g.grade_name, v.vehicle_name
";
$stmt = $pdo->prepare($query);
$stmt->execute(['search' => '%' . $search . '%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche de Véhicules et Grades</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
    <h2>Recherche de Véhicules et Grades</h2>

    <!-- Barre de recherche -->
    <form method="GET" action="/car.php">
        <input type="text" name="search" placeholder="Rechercher un véhicule ou un grade..." value="<?= htmlspecialchars($search); ?>">
        <button type="submit">Rechercher</button>
    </form>

    <!-- Tableau dynamique des résultats -->
    <table>
        <thead>
            <tr>
                <th>Grade</th>
                <th>Véhicule</th>
                <th>Code Véhicule</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($results) > 0): ?>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?= htmlspecialchars($result['grade_name']); ?></td>
                        <td><?= htmlspecialchars($result['vehicle_name']); ?></td>
                        <td><?= htmlspecialchars($result['vehicle_code']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Aucun résultat trouvé.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
