<?php
session_start();

// Durée de la session en secondes (4 heure)
$session_duration = 14400;

if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    // Vérifier le dernier moment d'activité
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_duration)) {
        // Si l'activité est supérieure à la durée, déconnecter l'utilisateur
        session_unset();
        session_destroy();
        
        // Supprimer l'utilisateur de la base de données pour plus de sécurité
        require_once 'config.php';
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        // Rediriger vers la page de connexion
        header("Location: /auth/login.php");
        exit();
    } else {
        // Mettre à jour le timestamp d'activité
        $_SESSION['last_activity'] = time();
    }
} else {
    // Si l'utilisateur n'est pas authentifié, rediriger vers la page de connexion
    header("Location: /auth/login.php");
    exit();
}
?>

