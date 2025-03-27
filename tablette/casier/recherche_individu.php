<?php
require_once '../../config.php';

$query = $_GET['query'] ?? '';
$filter = $_GET['filter'] ?? 'nom';

// Sécuriser le filtre pour éviter les injections SQL
$allowedFilters = ['nom', 'prenom', 'affiliation'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'nom';
}

// Requête SQL pour la recherche
$stmt = $pdo->prepare("SELECT id, nom, prenom, affiliation FROM casiers WHERE $filter LIKE :query");
$stmt->execute(['query' => "%$query%"]);
$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Affichage des résultats
if (count($individus) > 0) {
    foreach ($individus as $individu) {
        echo "<tr>";
        echo "<td><a href='details.php?id=" . htmlspecialchars($individu['id']) . "'>" . htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']) . "</a></td>";
        echo "<td>" . htmlspecialchars($individu['affiliation']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='2'><em>Aucun résultat trouvé</em></td></tr>";
}
?>
