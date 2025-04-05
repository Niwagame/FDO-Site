<?php
require_once '../config.php';

$bot_token = $config['discord']['bot_token'];
$guild_id  = $config['discord']['guild_id'];
$grades    = $config['discord_roles'];
$roles     = $config['roles']; // Spécialisations

function logError($message) {
    file_put_contents(__DIR__ . '/logs/discord_error.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

function getDiscordMembers($bot_token, $guild_id) {
    $url = "https://discord.com/api/v10/guilds/$guild_id/members?limit=1000";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot $bot_token",
            "Content-Type: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        logError("CURL ERROR: $error");
        exit("Erreur API Discord : $error");
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        logError("HTTP ERROR $http_code - Response: $response");
        exit("Erreur API Discord : $http_code");
    }

    $members = json_decode($response, true);
    if (!is_array($members)) {
        logError("JSON ERROR : " . json_last_error_msg());
        exit("Erreur JSON : " . json_last_error_msg());
    }

    return $members;
}

function extractUserDetails($username) {
    // Nettoyage : supprime les caractères inutiles
    $username = trim(preg_replace('/\s*\|\s*/', '|', $username)); // Nettoie les espaces autour de |
    
    if (strpos($username, '|') !== false) {
        [$matricule, $fullName] = explode('|', $username, 2);
        $matricule = trim($matricule);
        $nameParts = explode(' ', trim($fullName));
    } else {
        $matricule = null;
        $nameParts = explode(' ', trim($username));
    }

    $prenom = $nameParts[0] ?? '';
    $nom    = $nameParts[1] ?? '';

    return [$matricule, $prenom, $nom];
}


function updateEffectif($pdo, $discord_id, $matricule, $prenom, $nom, $grade, $role_flags) {
    $columns = ['discord_id', 'matricule', 'prenom', 'nom', 'grade', 'date_maj'];
    $placeholders = ['?', '?', '?', '?', '?', 'NOW()'];
    $values = [$discord_id, $matricule, $prenom, $nom, $grade];

    foreach ($role_flags as $spec => $hasRole) {
        $columns[] = $spec;
        $placeholders[] = '?';
        $values[] = $hasRole ? 1 : 0;
    }

    $sql = "INSERT INTO sa_effectif (" . implode(',', $columns) . ") 
            VALUES (" . implode(',', $placeholders) . ")
            ON DUPLICATE KEY UPDATE 
                matricule = VALUES(matricule),
                prenom = VALUES(prenom),
                nom = VALUES(nom),
                grade = VALUES(grade),
                date_maj = NOW()";

    foreach (array_keys($role_flags) as $spec) {
        $sql .= ", $spec = VALUES($spec)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

// Supprime tout pour repartir propre
$pdo->query("TRUNCATE TABLE sa_effectif");
$members = getDiscordMembers($bot_token, $guild_id);
$imported = 0;

foreach ($members as $member) {
    $discord_id = $member['user']['id'];
    $username = $member['nick'] ?? $member['user']['username'];

    [$matricule, $prenom, $nom] = extractUserDetails($username);

    $grade = '';
    foreach ($grades as $g => $role_id) {
        if (in_array($role_id, $member['roles'])) {
            $grade = $g;
            break;
        }
    }

    $excluded_roles = ['cs', 'doj', 'lead_bci'];
    $role_flags = []; 
    foreach ($roles as $spec => $role_id) {
        if (!in_array($spec, $excluded_roles)) {
            $role_flags[$spec] = in_array($role_id, $member['roles']);
        }
    }
    

    if ($prenom && $nom) {
        updateEffectif($pdo, $discord_id, $matricule, $prenom, $nom, $grade, $role_flags);
        $imported++;
    }
}

echo "✅ Membres importés : $imported";

// Redirection
echo "<script>
    setTimeout(function() {
        window.location.href = '/sa/sa_effectif.php';
    }, 2000);
</script>";

echo "<p style='color: green;'>Mise à jour terminée. Redirection en cours...</p>";
