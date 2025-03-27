<?php
include '../../config.php';

// Vérifie que la requête de recherche est présente
$query = $_GET['query'] ?? '';
if (empty($query)) {
    echo json_encode([]);
    exit();
}

// Divise la requête pour permettre une recherche sur nom et prénom
$queryParts = explode(' ', trim($query));
$sqlConditions = [];
$params = [];

foreach ($queryParts as $part) {
    $sqlConditions[] = "(nom LIKE ? OR prenom LIKE ?)";
    $params[] = "%$part%";
    $params[] = "%$part%";
}

// Construit la requête SQL
$sql = "SELECT id, nom, prenom FROM casiers WHERE " . implode(" AND ", $sqlConditions);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Envoie des résultats en JSON
echo json_encode($individus);
?>
