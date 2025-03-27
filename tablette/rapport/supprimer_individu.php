<?php
session_start();
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification des données
    if (!isset($_POST['rapport_id']) || !isset($_POST['casier_id'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
        exit();
    }

    $rapport_id = intval($_POST['rapport_id']);
    $casier_id = intval($_POST['casier_id']);

    try {
        // Suppression de l'individu
        $stmt = $pdo->prepare("DELETE FROM rapports_individus WHERE rapport_id = ? AND casier_id = ?");
        $stmt->execute([$rapport_id, $casier_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Individu supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Aucune correspondance trouvée.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
    }
    exit();
}
?>
