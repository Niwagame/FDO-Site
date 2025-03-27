<?php
// Inclure la configuration de la base de données
include '../../config.php';

// Vérifier si une requête de recherche est définie
$query = $_GET['query'] ?? '';

if ($query) {
    // Préparer et exécuter la requête SQL pour rechercher les individus par nom ou prénom
    $stmt = $pdo->prepare("SELECT id, nom, prenom, num_tel FROM casiers WHERE nom LIKE ? OR prenom LIKE ?");
    $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
    
    // Récupérer tous les résultats
    $individus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Définir le type de contenu en JSON et envoyer les résultats
    header('Content-Type: application/json');
    echo json_encode($individus);
} else {
    // Si aucune requête n'est définie, renvoyer un tableau vide
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
