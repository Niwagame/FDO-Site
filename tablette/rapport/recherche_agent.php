<?php
include '../../config.php';

// Vérifie que la requête de recherche est présente
$query = $_GET['query'] ?? '';
if (empty($query)) {
    echo json_encode([]);
    exit();
}

// Divise la requête pour permettre une recherche partielle
$queryParts = explode(' ', trim($query));
$sqlConditions = [];
$params = [];

foreach ($queryParts as $part) {
    $sqlConditions[] = "(nom LIKE ?)";
    $params[] = "%$part%";
}

// Construit la requête SQL
$sql = "SELECT id, nom FROM effectif WHERE " . implode(" AND ", $sqlConditions) . " LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($agents);
?>
