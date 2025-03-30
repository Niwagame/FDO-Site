<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charge la config depuis config.ini
$config = parse_ini_file(__DIR__ . '/config.ini', true);

// Récupération centralisée des rôles
$roles = $config['roles'];

// Connexion à la base de données
try {
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

// Fonction de vérification d’authentification
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

// Fonction pour vérifier si l'utilisateur a un rôle autorisé
function hasRole(...$required_roles) {
    $user_roles = $_SESSION['roles'] ?? [];
    return !empty(array_intersect($required_roles, $user_roles));
}
?>
