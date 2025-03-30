<?php
require_once '../../config.php';

$numero_serie = $_GET['numero'] ?? '';
$objet_nom = $_GET['objet'] ?? '';

if (empty($numero_serie) || empty($objet_nom)) {
    echo json_encode(['exists' => false]);
    exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM s_armes WHERE numero_serie = ? AND nom = ?");
$stmt->execute([$numero_serie, $objet_nom]);
$count = $stmt->fetchColumn();

echo json_encode(['exists' => $count > 0]);
