<?php
include '../../config.php';

// Vérification de la présence d'un paramètre de requête
if (!isset($_GET['query']) || empty($_GET['query'])) {
    echo json_encode([]);
    exit;
}

$query = '%' . $_GET['query'] . '%';

// Préparation de la requête pour rechercher les motifs correspondants
$stmt = $pdo->prepare("SELECT id, description, montant, peine, article, details FROM amende WHERE description LIKE ?");
$stmt->execute([$query]);
$motifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retourne les motifs sous forme de JSON
echo json_encode($motifs);
?>
