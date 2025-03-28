<?php
session_start();
require_once '../../config.php';

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

// Récupère tous les interrogatoires avec jointure sur casiers (nom/prénom) et agent
$stmt = $pdo->query("
    SELECT i.id, i.date_interrogatoire, i.agent_id, c.nom, c.prenom
    FROM interrogatoires i
    LEFT JOIN casiers c ON i.casier_id = c.id
    ORDER BY i.date_interrogatoire DESC
");
$interrogatoires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Interrogatoires</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>📄 Liste des Interrogatoires</h2>

    <?php if (count($interrogatoires) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Individu Interrogé</th>
                    <th>Agent Interrogateur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($interrogatoires as $interro): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d/m/Y à H\hi", strtotime($interro['date_interrogatoire']))); ?></td>
                        <td><?= htmlspecialchars($interro['prenom'] . ' ' . $interro['nom']); ?></td>
                        <td><?= htmlspecialchars($interro['agent_id']); ?></td>
                        <td>
                            <a href="details.php?id=<?= $interro['id']; ?>" class="button">Détails</a>
                            <a href="modifier.php?id=<?= $interro['id']; ?>" class="button">Modifier</a>
                            <a href="supprimer.php?id=<?= $interro['id']; ?>" class="button" onclick="return confirm('Supprimer cet interrogatoire ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun interrogatoire enregistré.</p>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
