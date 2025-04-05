<?php
require_once '../config.php';

$discordId = $_POST['discord_id'] ?? null;
if (!$discordId) {
    exit("ID Discord manquant.");
}

$botToken = $config['discord']['bot_token'];
$guildId = $config['discord']['guild_id'];

// Requête à l'API Discord
$ch = curl_init("https://discord.com/api/v10/guilds/{$guildId}/members/{$discordId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bot $botToken"
    ],
    CURLOPT_SSL_VERIFYPEER => false // utile en local
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check HTTP code
if ($httpCode !== 200) {
    exit("Erreur API Discord. HTTP $httpCode");
}

$member = json_decode($response, true);
if (!isset($member['user'])) {
    exit("Membre non trouvé");
}

// ✂️ Extraction des infos
$nickname = $member['nick'] ?? $member['user']['username'] ?? null;

if (!preg_match('/^(\d+)\s+\|\s+(\w+)\s+(\w+)/', $nickname, $matches)) {
    exit("Format de nickname invalide. Reçu : " . htmlspecialchars($nickname));
}

$matricule = $matches[1];
$prenom    = $matches[2];
$nom       = $matches[3];

// 🎖️ Détection du grade
$grade = '';
foreach ($config['discord_roles'] as $gradeName => $roleId) {
    if (in_array($roleId, $member['roles'])) {
        $grade = $gradeName;
        break;
    }
}

// 🔧 Mise à jour des spécialisations
$excluded_roles = ['cs', 'doj', 'lead_bci'];
$role_flags = [];
foreach ($roles as $spec => $role_id) {
    if (!in_array($spec, $excluded_roles)) {
        $role_flags[$spec] = in_array($role_id, $member['roles']);
    }
}

// Création de la requête dynamique
$setFields = "matricule = ?, prenom = ?, nom = ?, grade = ?, date_maj = NOW()";
$params = [$matricule, $prenom, $nom, $grade];

foreach ($role_flags as $spec => $hasRole) {
    $setFields .= ", $spec = ?";
    $params[] = $hasRole;
}

$params[] = $discordId;

// 🔄 Exécuter la mise à jour
$sql = "UPDATE sa_effectif SET $setFields WHERE discord_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// ✅ Redirection
header("Location: sa_effectif.php");
exit();
