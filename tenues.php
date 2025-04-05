<?php
session_start();
require_once 'config.php'; // Connexion à la BDD

$role_bco = $roles['bcso'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_bco)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}
?>

// Récupération de toutes les tenues
$stmt = $pdo->query("SELECT * FROM tenues ORDER BY id");
$tenues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected = $_GET['grade'] ?? null;
$tenueSelectionnee = null;

if ($selected) {
    foreach ($tenues as $tenue) {
        if ($tenue['grade'] === $selected) {
            $tenueSelectionnee = $tenue;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tenues BCSO</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        select { padding: 5px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #999; padding: 10px; text-align: center; }
        th { background-color: #333; color: orange; }
        img { margin-top: 20px; max-width: 300px; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
    <h2>👔 Sélection d'une Tenue BCSO</h2>

    <form method="get">
        <label for="grade">Choisir un grade :</label>
        <select name="grade" id="grade" onchange="this.form.submit()">
            <option value="">-- Sélectionnez un grade --</option>
            <?php foreach ($tenues as $tenue): ?>
                <option value="<?= htmlspecialchars($tenue['grade']) ?>" <?= $selected === $tenue['grade'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tenue['grade']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($tenueSelectionnee): ?>
        <h3 style="margin-top: 30px;">Tenue pour le grade : <?= htmlspecialchars($tenueSelectionnee['grade']) ?></h3>
        <img src="<?= htmlspecialchars($tenueSelectionnee['image_path']) ?>" class="image-tenue" alt="Tenue de <?= htmlspecialchars($tenueSelectionnee['grade']) ?>">

        <table>
            <tr><th>Élément</th><th>Code</th><th>Texture</th></tr>
            <?php
            $elements = [
                'Chapeaux' => ['chapeaux_code', 'chapeaux_texture'],
                'Echarpe & Chaîne' => ['echarpe_code', 'echarpe_texture'],
                'Veste' => ['veste_code', 'veste_texture'],
                'T-Shirt' => ['tshirt_code', 'tshirt_texture'],
                'Body Armor' => ['body_armor_code', 'body_armor_texture'],
                'Sac & Parachutes' => ['sac_code', 'sac_texture'],
                'Haut du Corps' => ['haut_code', 'haut_texture'],
                'Bas du Corps' => ['bas_code', 'bas_texture'],
                'Chaussures' => ['chaussures_code', 'chaussures_texture'],
                'Decales' => ['decales_code', 'decales_texture'],
            ];

            foreach ($elements as $label => [$codeKey, $textureKey]): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td><?= $tenueSelectionnee[$codeKey] ?></td>
                    <td><?= $tenueSelectionnee[$textureKey] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <p style="margin-top: 15px;"><strong>💡 Astuce :</strong> Ajuster le code <strong>Decales</strong> en fonction du grade pour le badge.</p>
    <?php elseif ($selected): ?>
        <p style="color: red;">Aucune tenue trouvée pour ce grade.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
