<?php
require_once '../config.php';

$role_sa = $roles['sa'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_sa)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}
?>

$discord_id = $_GET['discord_id'] ?? null;
if (!$discord_id) {
    echo "<p style='color:red; text-align:center;'>ID Discord manquant.</p>";
    exit();
}

// Liste des armes possibles
$available_weapons = [
    "Tazer", "Glock 17", "MP5", "Remington",
    "G36C", "Bean Bag", "Scar-H", "M4A1", "AR15",
];

// 🔎 Récupération agent
$stmt = $pdo->prepare("SELECT * FROM sa_effectif WHERE discord_id = ?");
$stmt->execute([$discord_id]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$agent) {
    echo "<p style='color:red; text-align:center;'>Agent non trouvé.</p>";
    exit();
}

// 🔎 Récupération détails
$stmt = $pdo->prepare("SELECT * FROM agent_details WHERE discord_id = ?");
$stmt->execute([$discord_id]);
$details = $stmt->fetch(PDO::FETCH_ASSOC);

$phone = $details['phone_number'] ?? '';
$photo = $details['photo'] ?? '';
$weapons = [];

if (!empty($details['weapons'])) {
    $parsed = json_decode($details['weapons'], true);
    if (is_array($parsed)) {
        $weapons = $parsed;
    }
}

// 📤 Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone_number = $_POST['phone_number'] ?? '';
    $weapons = [];
    $photo_filename = $photo;

    if (!empty($_POST['weapon_name']) && is_array($_POST['weapon_name'])) {
        foreach ($_POST['weapon_name'] as $index => $weaponName) {
            $serial = $_POST['weapon_serial'][$index] ?? '';
            if ($weaponName && $serial) {
                $weapons[] = ['name' => $weaponName, 'serial' => $serial];
            }
        }
    }

    // 🔄 Gestion de la photo (upload)
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === 0) {
        $target_dir = '../assets/agents/';
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'photo_' . $discord_id . '.' . $ext;
        $target_file = $target_dir . $new_filename;

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_filename = $new_filename;
            }
        }
    }

    $jsonWeapons = json_encode($weapons);

    if ($details) {
        $stmt = $pdo->prepare("UPDATE agent_details SET phone_number = ?, weapons = ?, photo = ? WHERE discord_id = ?");
        $stmt->execute([$phone_number, $jsonWeapons, $photo_filename, $discord_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO agent_details (discord_id, phone_number, weapons, photo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$discord_id, $phone_number, $jsonWeapons, $photo_filename]);
    }

    header("Location: agent_details.php?discord_id=" . urlencode($discord_id));
    exit();
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="../css/sa_effectif.css">

<div class="container">
    <h2>📝 Modifier les infos de <?= htmlspecialchars($agent['prenom']) ?> <?= htmlspecialchars($agent['nom']) ?></h2>

    <form method="post" enctype="multipart/form-data">
        <label for="phone_number">📱 Numéro de téléphone :</label>
        <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($phone) ?>" placeholder="06XXXXXXXX" required>

        <h3>🖼 Photo d'identité</h3>
        <?php if ($photo): ?>
            <p><img src="../assets/agents/<?= htmlspecialchars($photo) ?>" alt="Photo" style="max-height: 150px;"></p>
        <?php endif; ?>
        <input type="file" name="photo" accept="image/*">

        <h3>🔫 Armes attribuées</h3>
        <div id="weapons-container">
            <?php foreach ($weapons as $w): ?>
                <div class="weapon-row">
                    <select name="weapon_name[]">
                        <option value="">-- Choisir une arme --</option>
                        <?php foreach ($available_weapons as $aw): ?>
                            <option value="<?= $aw ?>" <?= $aw === $w['name'] ? 'selected' : '' ?>><?= $aw ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="weapon_serial[]" placeholder="Numéro de série" value="<?= htmlspecialchars($w['serial']) ?>" required>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn-refresh" onclick="addWeaponRow()">➕ Ajouter une arme</button>

        <br><br>
        <button class="btn-refresh" type="submit">💾 Enregistrer</button>
        <a href="agent_details.php?discord_id=<?= urlencode($discord_id) ?>" class="btn-refresh" style="background-color:#555;">⬅ Retour</a>
    </form>
</div>

<script>
function addWeaponRow() {
    const container = document.getElementById('weapons-container');
    const row = document.createElement('div');
    row.className = 'weapon-row';

    const select = document.createElement('select');
    select.name = 'weapon_name[]';
    select.innerHTML = `<option value="">-- Choisir une arme --</option>
        <?php foreach ($available_weapons as $aw): ?>
            <option value="<?= $aw ?>"><?= $aw ?></option>
        <?php endforeach; ?>`;

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'weapon_serial[]';
    input.placeholder = 'Numéro de série';
    input.required = true;

    row.appendChild(select);
    row.appendChild(input);
    container.appendChild(row);
}
</script>

<?php include '../includes/footer.php'; ?>
