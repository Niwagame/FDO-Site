<?php
session_start();
require_once '../../config.php';

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

// Récupération des interrogatoires avec nom/prénom et ID casier
$stmt = $pdo->query("
    SELECT i.id, i.created_at, i.agent_id, i.casier_id, c.nom, c.prenom
    FROM interrogatoires i
    LEFT JOIN casiers c ON i.casier_id = c.id
    ORDER BY i.created_at DESC
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
                    <th>Date & Heure</th>
                    <th>Individu Interrogé</th>
                    <th>Agent Interrogateur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($interrogatoires as $interro): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d/m/Y à H\hi", strtotime($interro['created_at']))); ?></td>
                        <td>
                            <a href="/tablette/casier/details.php?id=<?= $interro['casier_id']; ?>" class="individu-link">
                                <?= htmlspecialchars($interro['prenom'] . ' ' . $interro['nom']); ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($interro['agent_id'] ?? 'Inconnu'); ?></td>
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
