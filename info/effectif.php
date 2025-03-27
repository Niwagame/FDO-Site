<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

// Récupération du bot token et de l'ID du serveur depuis la config
$bot_token = $config['discord']['bot_token'];
$guild_id = $config['discord']['guild_id'];

// Récupération des rôles Discord
$roles = $discord_roles;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vider la table effectif avant d'insérer de nouvelles données
$pdo->query("TRUNCATE TABLE effectif");

// Fonction pour mettre à jour la table des effectifs
function updateEffectifTable($pdo, $name, $grade) {
    $stmt = $pdo->prepare("INSERT INTO effectif (nom, grade) VALUES (?, ?) ON DUPLICATE KEY UPDATE grade = VALUES(grade)");
    $stmt->execute([$name, $grade]);
}

// Fonction pour récupérer la liste des membres d'un rôle spécifique
function getMembersByRole($role_id, $bot_token, $guild_id) {
    $url = "https://discord.com/api/v10/guilds/$guild_id/members?limit=1000";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot $bot_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Enlever la vérification SSL si besoin
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Erreur de requête CURL : ' . curl_error($ch);
        return [];
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code === 429) {
        $retry_after = json_decode($response, true)['retry_after'] ?? 5;
        echo "<p>Rate limit atteint. Réessayer après {$retry_after} secondes.</p>";
        sleep(ceil($retry_after));
        return getMembersByRole($role_id, $bot_token, $guild_id); // Réessaye après l'attente
    }

    if ($http_code !== 200) {
        echo "Erreur HTTP : $http_code. Vérifiez les permissions et le token.";
        return [];
    }

    curl_close($ch);

    $members = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'Erreur JSON : ' . json_last_error_msg();
        return [];
    }

    if (!$members || !is_array($members)) {
        echo "Erreur de récupération des membres ou réponse inattendue.";
        return [];
    }

    // Filtrer les membres ayant le rôle spécifié
    $filtered_members = array_filter($members, function ($member) use ($role_id) {
        return in_array($role_id, $member['roles']);
    });

    return array_map(function ($member) {
        // Utilise le surnom si présent, sinon le nom d'utilisateur
        return !empty($member['nick']) ? $member['nick'] : $member['user']['username'];
    }, $filtered_members);
}

// Mettre à jour la table effectif avec les données récupérées
foreach ($roles as $grade => $role_id) {
    $members = getMembersByRole($role_id, $bot_token, $guild_id);
    foreach ($members as $member) {
        updateEffectifTable($pdo, $member, $grade);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Effectifs BCSO</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>Effectifs BCSO</h2>
    <?php foreach ($roles as $grade => $role_id): ?>
        <h3><?= htmlspecialchars($grade); ?></h3>
        <ul>
            <?php
            $members = getMembersByRole($role_id, $bot_token, $guild_id);
            if (count($members) > 0): 
                foreach ($members as $member): ?>
                    <li><?= htmlspecialchars($member); ?></li>
                <?php endforeach; 
            else: ?>
                <li><em>Aucun membre dans ce grade</em></li>
            <?php endif; ?>
        </ul>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
