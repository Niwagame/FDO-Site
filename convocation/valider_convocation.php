<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    try {
        // Supprimer la convocation une fois validée
        $stmt = $pdo->prepare("DELETE FROM convocations WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: liste_convocations.php');
        exit();
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la suppression de la convocation : " . $e->getMessage() . "</p>";
    }
}
?>
