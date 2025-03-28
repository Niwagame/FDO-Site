<?php
session_start();
require_once '../../config.php';

// Vérification rôle lead_bci
$config = parse_ini_file('../../config.ini', true);
$lead_bci_id = $config['roles']['lead_bci'] ?? null;

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !in_array($lead_bci_id, $_SESSION['roles'] ?? [])
) {
    echo "<p style='color:red;text-align:center;'>Accès refusé : seuls les lead BCI peuvent supprimer un interrogatoire.</p>";
    exit();
}

// Vérification ID interrogatoire
$interro_id = $_GET['id'] ?? null;
if (!$interro_id) {
    echo "ID d'interrogatoire manquant.";
    exit();
}

// Récupération de l'interrogatoire
$stmt = $pdo->prepare("SELECT * FROM interrogatoires WHERE id = ?");
$stmt->execute([$interro_id]);
$interrogatoire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$interrogatoire) {
    echo "Interrogatoire introuvable.";
    exit();
}

// Suppression des fichiers liés (si présents)
$media = json_decode($interrogatoire['fichiers_media'] ?? '[]', true);
foreach ($media as $filePath) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

// Suppression de l'interrogatoire
$stmt = $pdo->prepare("DELETE FROM interrogatoires WHERE id = ?");
$stmt->execute([$interro_id]);

// Redirection
header("Location: liste.php?casier_id=" . $interrogatoire['casier_id']);
exit();
?>
