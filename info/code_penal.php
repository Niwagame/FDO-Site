<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$search = $_GET['search'] ?? '';
$limit = 10;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM amende WHERE article LIKE ? OR description LIKE ?");
$countStmt->execute(['%' . $search . '%', '%' . $search . '%']);
$total_amendes = $countStmt->fetchColumn();
$total_pages = ceil($total_amendes / $limit);

$stmt = $pdo->prepare("SELECT * FROM amende WHERE article LIKE ? OR description LIKE ? ORDER BY article LIMIT $limit OFFSET $offset");
$stmt->execute(['%' . $search . '%', '%' . $search . '%']);
$amendes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Code Pénal</title>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>Code Pénal - Liste des Infractions et Amendes</h2>

    <form method="GET" action="code_penal.php" class="search-form">
        <input type="text" name="search" placeholder="Rechercher une infraction, un article ou un montant..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Rechercher</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th>Description</th>
                <th>Montant de l'Amende</th>
                <th>Peine</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($amendes) > 0): ?>
                <?php foreach ($amendes as $index => $amende): ?>
                    <tr>
                        <td><?= htmlspecialchars($amende['article']); ?></td>
                        <td><?= htmlspecialchars($amende['description']); ?></td>
                        <td><?= htmlspecialchars($amende['montant']); ?> $</td>
                        <td><?= htmlspecialchars($amende['peine']); ?></td>
                        <td>
                            <button class="toggle-details" data-index="<?= $index; ?>">&#9654;</button>
                        </td>
                    </tr>
                    <tr id="details-<?= $index; ?>" class="details-row" style="display: none;">
                        <td colspan="5">
                            <strong>Article :</strong> <?= htmlspecialchars($amende['article']); ?><br>
                            <strong>Détails :</strong> <?= htmlspecialchars($amende['details']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Aucune infraction trouvée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination améliorée -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1; ?>&search=<?= htmlspecialchars($search); ?>" class="pagination-button prev">&laquo; Précédent</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i; ?>&search=<?= htmlspecialchars($search); ?>" class="pagination-button <?= $i === $page ? 'active' : ''; ?>"><?= $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1; ?>&search=<?= htmlspecialchars($search); ?>" class="pagination-button next">Suivant &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.querySelectorAll('.toggle-details').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            const detailsRow = document.getElementById('details-' + index);
            detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
            this.innerHTML = detailsRow.style.display === 'none' ? '&#9654;' : '&#9660;';
        });
    });
</script>
</body>
</html>
