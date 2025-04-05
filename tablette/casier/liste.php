<?php
require_once '../../config.php';

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

// Autoriser uniquement les rôles BCO ou DOJ
if (!hasRole($roles['bcso'], $roles['doj'])) {
    echo "<p style='color:red;text-align:center;'>Accès refusé : vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>";
    exit();
}

// Initialisation des paramètres de recherche et de pagination
$searchQuery = $_GET['search'] ?? '';
$limit = 10; // Nombre d'éléments par page
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1; // Page actuelle
$offset = ($page - 1) * $limit; // Calcul de l'offset

// Calcul du nombre total de casiers
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM casiers 
    WHERE CONCAT(nom, ' ', prenom) LIKE :searchQuery 
    OR num_tel LIKE :searchQuery
");
$countStmt->execute(['searchQuery' => '%' . $searchQuery . '%']);
$totalIndividus = $countStmt->fetchColumn();
$totalPages = ceil($totalIndividus / $limit); // Nombre total de pages

// Requête pour récupérer les casiers avec limite et offset
$stmt = $pdo->prepare("
    SELECT * 
    FROM casiers 
    WHERE CONCAT(nom, ' ', prenom) LIKE :searchQuery 
    OR num_tel LIKE :searchQuery
    ORDER BY nom ASC, prenom ASC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Casiers</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des Casiers</h2>

    <!-- Conteneur pour la barre de recherche et le bouton "Ajouter un Casier" -->
    <div class="search-and-add">
        <!-- Champ de recherche -->
        <form method="get" action="liste.php" style="display: inline;">
            <input type="text" name="search" placeholder="Rechercher un individu..." value="<?= htmlspecialchars($searchQuery); ?>" />
            <button type="submit" class="button">Rechercher</button>
        </form>

        <!-- Bouton pour ajouter un nouveau casier -->
        <a href="ajout.php" class="button add-button">Ajouter un Casier</a>
    </div>

    <!-- Affichage de la table des casiers -->
    <table>
        <thead>
            <tr>
                <th>Nom Prénom</th>
                <th>Affiliation</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($individus) > 0): ?>
                <?php foreach ($individus as $individu): ?>
                    <tr>
                        <td>
                            <a href="details.php?id=<?= $individu['id']; ?>" class="individu-link">
                                <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($individu['affiliation'] ?? 'Aucune'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2"><em>Aucun individu trouvé.</em></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1; ?>&search=<?= htmlspecialchars($searchQuery); ?>" class="pagination-button prev">&laquo; Précédent</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i; ?>&search=<?= htmlspecialchars($searchQuery); ?>" class="pagination-button <?= $i === $page ? 'active' : ''; ?>"><?= $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1; ?>&search=<?= htmlspecialchars($searchQuery); ?>" class="pagination-button next">Suivant &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
