<?php
session_start();
require_once '../config.php';

// Récupération des infos Discord depuis la config
$discord = $config['discord'];
$bot_token = $discord['bot_token'];
$guild_id = $discord['guild_id'];
$client_id = $discord['client_id'];
$client_secret = $discord['client_secret'];
$redirect_uri = $discord['redirect_uri'];

// Rôles autorisés à se connecter (adaptés à ton système centralisé dans [roles])
$roles = $config['roles'];
$authorized_roles = [
    $roles['bco'],       // BCSO
    $roles['cs'],        // CS
    $roles['doj'],       // DOJ
    $roles['bci'],       // BCI
    $roles['lead_bci'],  // Lead BCI
];

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Requête pour obtenir le token d'accès
    $token_url = 'https://discord.com/api/oauth2/token';
    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($token_url, false, $context);
    $response = json_decode($result, true);

    if ($result === false || json_last_error() !== JSON_ERROR_NONE) {
        echo "Erreur lors de l'appel à l'API Discord ou réponse JSON invalide.";
        var_dump($http_response_header);
        exit();
    }

    if (isset($response['access_token'])) {
        $access_token = $response['access_token'];
        $_SESSION['discord_access_token'] = $access_token;

        // Obtenir les informations de l'utilisateur
        $user_url = "https://discord.com/api/v10/users/@me";
        $user_options = [
            'http' => [
                'header' => "Authorization: Bearer $access_token\r\n",
                'method' => 'GET',
            ]
        ];

        $user_context = stream_context_create($user_options);
        $user_result = file_get_contents($user_url, false, $user_context);
        $user_info = json_decode($user_result, true);

        if (isset($user_info['id'], $user_info['username'])) {
            $user_id = $user_info['id'];
            $username = $user_info['username'];

            // Utiliser cURL pour récupérer les informations de membre dans le serveur (guilde)
            $guild_member_url = "https://discord.com/api/v10/guilds/$guild_id/members/$user_id";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $guild_member_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $bot_token"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $guild_member_result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Erreur de requête cURL : ' . curl_error($ch);
                exit();
            }
            curl_close($ch);

            $guild_member_info = json_decode($guild_member_result, true);
            $user_roles = $guild_member_info['roles'] ?? [];
            $nickname = $guild_member_info['nick'] ?? $username;

            // Vérifie si l'utilisateur a l'un des rôles autorisés
            if (array_intersect($authorized_roles, $user_roles)) {
                $expiresAt = date("Y-m-d H:i:s", time() + 3600);

                // Vérifie si l'utilisateur est déjà dans la base de données
                $stmt = $pdo->prepare("SELECT * FROM users WHERE discord_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if ($user) {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, discriminator = ?, roles = ?, auth_token = ?, expires_at = ? WHERE discord_id = ?");
                    $stmt->execute([$username, $user_info['discriminator'], json_encode($user_roles), $access_token, $expiresAt, $user_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (discord_id, username, discriminator, roles, auth_token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $username, $user_info['discriminator'], json_encode($user_roles), $access_token, $expiresAt]);
                }

                $_SESSION['user_authenticated'] = true;
                $_SESSION['discord_id'] = $user_id;
                $_SESSION['discord_username'] = $username;
                $_SESSION['discord_nickname'] = $nickname;
                $_SESSION['roles'] = $user_roles;

                setcookie("user_id", $pdo->lastInsertId(), time() + 3600, "/");

                header('Location: /tablette/casier/liste.php');
                exit();
            } else {
                echo "Vous n'avez pas le rôle requis pour accéder à cette page.";
                exit();
            }
        } else {
            echo "Impossible de récupérer les informations de l'utilisateur.";
            exit();
        }
    } else {
        echo "Erreur lors de l'authentification : échec de la récupération du token d'accès.";
        var_dump($response);
        exit();
    }
} else {
    echo "Code d'authentification manquant";
    exit();
}
