<?php
session_start();
require_once '../../config.php';

$role_bco = $roles['bco'];
$role_doj = $roles['doj'];

// Vérifie que l'utilisateur a l’un des rôles requis
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true || !hasRole($role_bco) && !hasRole($role_doj)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

// Paramètres de recherche et pagination
$searchQuery = $_GET['query'] ?? '';
$filter = $_GET['filter'] ?? 'individus';
$limit = 10; // Nombre de rapports par page
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Calcul du nombre total de rapports correspondant à la recherche
$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT r.id)
    FROM rapports r
    LEFT JOIN amende a ON r.motif = a.id
    LEFT JOIN rapports_individus ri ON r.id = ri.rapport_id
    LEFT JOIN casiers c ON ri.casier_id = c.id
    WHERE 
        (:filter = 'individus' AND CONCAT(c.nom, ' ', c.prenom) LIKE :searchQuery)
        OR (:filter = 'date_arrestation' AND r.date_arrestation LIKE :searchQuery)
        OR (:filter = 'motif' AND a.description LIKE :searchQuery)
");
$countStmt->execute([
    'filter' => $filter,
    'searchQuery' => '%' . $searchQuery . '%'
]);
$totalRapports = $countStmt->fetchColumn();
$totalPages = ceil($totalRapports / $limit); // Nombre total de pages

// Requête pour récupérer les rapports avec limite et offset
$stmt = $pdo->prepare("
    SELECT r.id AS rapport_id, r.date_arrestation, a.description AS motif, 
           GROUP_CONCAT(CONCAT(c.nom, ' ', c.prenom) SEPARATOR ', ') AS individus
    FROM rapports r
    LEFT JOIN amende a ON r.motif = a.id
    LEFT JOIN rapports_individus ri ON r.id = ri.rapport_id
    LEFT JOIN casiers c ON ri.casier_id = c.id
    WHERE 
        (:filter = 'individus' AND CONCAT(c.nom, ' ', c.prenom) LIKE :searchQuery)
        OR (:filter = 'date_arrestation' AND r.date_arrestation LIKE :searchQuery)
        OR (:filter = 'motif' AND a.description LIKE :searchQuery)
    GROUP BY r.id
    ORDER BY r.date_arrestation DESC
    LIMIT :limit OFFSET :offset
");

// Liaison des variables
$stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
$stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();


$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Rapports d'Arrestation</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Liste des Rapports d'Arrestation</h2>

    <!-- Bouton pour ajouter un rapport -->
    <div style="margin-bottom: 15px;">
        <a href="ajout.php" class="button">Ajouter un Rapport</a>
    </div>

    <!-- Barre de recherche et filtre -->
        <div class="search-container">
        <form method="GET" action="liste.php" class="search-form">
            <input type="text" name="query" id="search-bar" placeholder="Rechercher un rapport..." value="<?= htmlspecialchars($searchQuery); ?>">
            <select name="filter" id="search-filter">
                <option value="individus" <?= $filter === 'individus' ? 'selected' : ''; ?>>Individus</option>
                <option value="date_arrestation" <?= $filter === 'date_arrestation' ? 'selected' : ''; ?>>Date d'Arrestation</option>
                <option value="motif" <?= $filter === 'motif' ? 'selected' : ''; ?>>Motif</option>
            </select>
            <button type="submit" class="button">Rechercher</button>
        </form>
    </div>
    <!-- Résultats de la recherche -->
    <div id="rapports-container">
        <?php if (count($rapports) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Individus Impliqués</th>
                        <th>Date d'Arrestation</th>
                        <th>Motif</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody id="rapports-list">
                    <?php foreach ($rapports as $rapport): ?>
                        <tr>
                            <td>
                            <?php 
                            $individus = $rapport['individus'] ?? '';
                            if ($individus) {
                                echo htmlspecialchars($individus); // Affiche les noms séparés par des virgules
                            } else {
                                echo "<em>Aucun individu impliqué</em>";
                            }
                            ?>
                            </td>
                            <td>
                            <?php 
                            $dateArrestation = $rapport['date_arrestation'];
                            if ($dateArrestation) {
                                $dateFormatee = (new DateTime($dateArrestation))->format('d-m-Y');
                                echo htmlspecialchars($dateFormatee);
                            } else {
                                echo "<em>Non spécifiée</em>";
                            }
                            ?>
                            </td>
                            <td><?= htmlspecialchars($rapport['motif'] ?? 'Non spécifié'); ?></td>
                            <td><a href="details.php?id=<?= htmlspecialchars($rapport['rapport_id']); ?>" class="button">Détails</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun rapport trouvé.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1; ?>&query=<?= htmlspecialchars($searchQuery); ?>&filter=<?= htmlspecialchars($filter); ?>" class="pagination-button prev">&laquo; Précédent</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i; ?>&query=<?= htmlspecialchars($searchQuery); ?>&filter=<?= htmlspecialchars($filter); ?>" class="pagination-button <?= $i === $page ? 'active' : ''; ?>"><?= $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1; ?>&query=<?= htmlspecialchars($searchQuery); ?>&filter=<?= htmlspecialchars($filter); ?>" class="pagination-button next">Suivant &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
