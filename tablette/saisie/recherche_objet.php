<?php
require_once '../../config.php';

if (!isset($_GET['query']) || empty($_GET['query'])) {
    echo json_encode([]);
    exit();
}

$query = '%' . $_GET['query'] . '%';

$stmt = $pdo->prepare("SELECT id, nom, categorie FROM saisie WHERE nom LIKE ?");
$stmt->execute([$query]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
