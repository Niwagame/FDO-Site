<?php
require_once '../../config.php';

$query = $_GET['query'] ?? '';
$filter = $_GET['filter'] ?? 'individus';

$allowedFilters = ['individus', 'date_arrestation', 'motif'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'individus';
}

$sql = "
    SELECT r.id AS rapport_id, r.date_arrestation, a.description AS motif, 
           GROUP_CONCAT(CONCAT(c.nom, ' ', c.prenom) SEPARATOR ', ') AS individus
    FROM rapports r
    LEFT JOIN amende a ON r.motif = a.id
    LEFT JOIN rapports_individus ri ON r.id = ri.rapport_id
    LEFT JOIN casiers c ON ri.casier_id = c.id
";

$params = [];

if (!empty($query)) {
    if ($filter == 'individus') {
        $sql .= " WHERE CONCAT(c.nom, ' ', c.prenom) LIKE :query";
    } elseif ($filter == 'date_arrestation') {
        $sql .= " WHERE r.date_arrestation LIKE :query";
    } elseif ($filter == 'motif') {
        $sql .= " WHERE a.description LIKE :query";
    }
    $params['query'] = "%$query%";
}

$sql .= " GROUP BY r.id ORDER BY r.date_arrestation DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rapports) > 0) {
    foreach ($rapports as $rapport) {
        echo "<tr>";
        echo "<td>";
        $individus = $rapport['individus'] ?? '';
        if ($individus) {
            $individus_list = explode(', ', $individus);
            foreach ($individus_list as $individu) {
                echo htmlspecialchars($individu) . "<br>";
            }
        } else {
            echo "<em>Aucun individu impliqué</em>";
        }
        echo "</td>";
        echo "<td>" . htmlspecialchars($rapport['date_arrestation']) . "</td>";
        echo "<td>" . htmlspecialchars($rapport['motif'] ?? 'Non spécifié') . "</td>";
        echo "<td><a href='details.php?id=" . htmlspecialchars($rapport['rapport_id']) . "' class='button'>Afficher Détails</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'><em>Aucun résultat trouvé</em></td></tr>";
}
?>
