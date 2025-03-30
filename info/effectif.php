<?php
session_start();
require_once '../config.php';

$role_bco = $roles['bco'];
$role_doj = $roles['doj'];

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true || (!hasRole($role_bco) && !hasRole($role_doj))) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

// Récupération des infos depuis config.ini
$bot_token = $config['discord']['bot_token'];
$guild_id  = $config['discord']['guild_id'];
$discord_roles = $config['discord_roles'];
$grades = $discord_roles;

// Vide la table effectif
$pdo->query("TRUNCATE TABLE effectif");

// Fonction d'insertion
function updateEffectifTable($pdo, $name, $grade) {
    $stmt = $pdo->prepare("INSERT INTO effectif (nom, grade) VALUES (?, ?) ON DUPLICATE KEY UPDATE grade = VALUES(grade)");
    $stmt->execute([$name, $grade]);
}

// Récupération des membres
function getMembersByRole($role_id, $bot_token, $guild_id) {
    $url = "https://discord.com/api/v10/guilds/$guild_id/members?limit=1000";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot $bot_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Erreur de requête CURL : ' . curl_error($ch);
        return [];
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code === 429) {
        $retry_after = json_decode($response, true)['retry_after'] ?? 5;
        sleep(ceil($retry_after));
        return getMembersByRole($role_id, $bot_token, $guild_id);
    }

    if ($http_code !== 200) {
        echo "Erreur HTTP : $http_code. Vérifiez les permissions et le token.";
        return [];
    }

    curl_close($ch);
    $members = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($members)) {
        echo 'Erreur JSON ou réponse inattendue.';
        return [];
    }

    $filtered_members = array_filter($members, fn($m) => in_array($role_id, $m['roles']));
    return array_map(fn($m) => $m['nick'] ?? $m['user']['username'], $filtered_members);
}

// Mise à jour table
foreach ($grades as $grade => $role_id) {
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
    <?php foreach ($grades as $grade => $role_id): ?>
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
