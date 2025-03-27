<?php
require_once '../config.php';

$current_time = date("Y-m-d H:i:s");

// Supprimer les utilisateurs avec des sessions expirées
$stmt = $pdo->prepare("DELETE FROM users WHERE expires_at < ?");
$stmt->execute([$current_time]);

echo "Sessions expirées nettoyées à : " . $current_time;
