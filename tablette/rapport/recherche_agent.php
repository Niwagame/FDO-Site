<?php
include '../../config.php';

$query = $_GET['query'] ?? '';
if (empty($query)) {
    echo json_encode([]);
    exit();
}

// Divise la requête utilisateur (ex: "105 powell")
$queryParts = explode(' ', trim($query));
$sqlConditions = [];
$params = [];

foreach ($queryParts as $part) {
    $sqlConditions[] = "(matricule LIKE ? OR prenom LIKE ? OR nom LIKE ?)";
    $params[] = "%$part%";
    $params[] = "%$part%";
    $params[] = "%$part%";
}

// Nouvelle requête avec recherche multi-colonne
$sql = "SELECT discord_id, matricule, prenom, nom FROM sa_effectif 
        WHERE " . implode(" AND ", $sqlConditions) . " 
        ORDER BY grade DESC 
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($agents);
