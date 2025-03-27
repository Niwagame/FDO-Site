<?php
require_once '../config.php';
session_start();

$discord = $config['discord'];
$client_id = $discord['client_id'];
$redirect_uri = $discord['redirect_uri'];
$scope = $discord['scope'];
$response_type = $discord['response_type'];

$discord_login_url = "https://discord.com/api/oauth2/authorize?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type={$response_type}&scope={$scope}";
?>


<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>Connexion au système de casier BCSO</h2>
    <a href="<?= $discord_login_url ?>">
        <button>Connexion via Discord</button>
    </a>
</div>

<?php include '../includes/footer.php'; ?>
