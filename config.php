<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charge la config depuis config.ini
$config = parse_ini_file(__DIR__ . '/config.ini', true);

// Récupère les rôles en tant que tableau associatif
$discord_roles = $config['discord_roles'];
$authorized_roles = $config['authorized_roles'];
$roles = $config['roles'];

try {
    // Connexion à la base de données depuis config.ini
    $db = $config['database'];
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']}",
        $db['user'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

function checkAuthentication() {
    global $pdo;

    if (isset($_COOKIE['session_token'])) {
        $session_token = $_COOKIE['session_token'];

        $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
        $stmt->execute([$session_token]);
        $session = $stmt->fetch();

        if ($session) {
            $_SESSION['user_authenticated'] = true;
            $_SESSION['user_id'] = $session['user_id'];
            return;
        }
    }

    header('Location: /auth/login.php');
    exit();
}
?>
