<?php
session_start();
require_once '../../config.php';

$role_cs = $roles['cs'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_cs)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du Command staff peuvent sortirs les saisie.</p>";
    exit();
}

// Récupération des objets disponibles dans `saisie` pour remplir la liste déroulante
$stmt = $pdo->query("SELECT * FROM saisie");
$objetOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tableau pour stocker les saisies retirées
$saisiesRetirees = [];

// Traitement du formulaire de sortie de saisies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saisie'])) {
    foreach ($_POST['saisies'] as $saisie) {
        if (empty($saisie['saisie_id']) || empty($saisie['quantite'])) {
            echo "<p>Erreur : saisie_id ou quantité manquants pour une saisie spécifique.</p>";
            continue;
        }

        $saisie_id = $saisie['saisie_id'];
        $quantite = $saisie['quantite'];

        // Récupérer le nom de l'objet avant de mettre à jour
        $stmt = $pdo->prepare("SELECT nom FROM saisie WHERE id = ?");
        $stmt->execute([$saisie_id]);
        $saisieNom = $stmt->fetchColumn();

        if ($saisieNom === false) {
            echo "<p>Erreur : Objet avec ID $saisie_id non trouvé.</p>";
            continue;
        }

        // Mise à jour de la quantité dans `saisie`
        $stmt = $pdo->prepare("UPDATE saisie SET quantite = quantite - ? WHERE id = ?");
        $stmt->execute([$quantite, $saisie_id]);

        // Ajout aux saisies retirées
        $saisiesRetirees[] = ['nom' => $saisieNom, 'quantite' => $quantite];

        // Vérification si la quantité est devenue 0 ou moins et suppression si nécessaire
        $stmt = $pdo->prepare("DELETE FROM saisie WHERE id = ? AND quantite <= 0");
        $stmt->execute([$saisie_id]);
    }

    // Inclure le fichier pour envoyer un message à Discord
    require_once 'saisie_discord.php';

    sendSaisieRetireeToDiscord($saisiesRetirees);
    

    // Redirection vers la page liste.php après le traitement
    header('Location: liste.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sortie des Saisies</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .saisie-fields {
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .remove-saisie-button {
            float: right;
            margin-top: 10px;
        }
    </style>
    <script>
        const objetsDisponibles = <?= json_encode($objetOptions); ?>;

        function addSaisieField() {
            const saisieContainer = document.getElementById('saisies-container');
            const saisieFields = document.createElement('div');
            saisieFields.classList.add('saisie-fields');
            saisieFields.innerHTML = `
                <label>Objet :</label>
                <select name="saisies[${saisieContainer.children.length}][saisie_id]" required>
                    <option value="">-- Choisissez un objet --</option>
                    ${objetsDisponibles.map(objet => `<option value="${objet.id}">${objet.nom}</option>`).join('')}
                </select>
                <label>Quantité à enlever :</label>
                <input type="number" name="saisies[${saisieContainer.children.length}][quantite]" min="1" required placeholder="Quantité">
                <button type="button" onclick="removeSaisieField(this)">-</button>
            `;
            saisieContainer.appendChild(saisieFields);
        }

        function removeSaisieField(button) {
            button.parentElement.remove();
        }
    </script>
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Sortie des Saisies</h2>
    <form method="POST" action="sortie.php">
        <div id="saisies-container">
            <div class="saisie-fields">
                <label>Objet :</label>
                <select name="saisies[0][saisie_id]" required>
                    <option value="">-- Choisissez un objet --</option>
                    <?php foreach ($objetOptions as $objet): ?>
                        <option value="<?= $objet['id']; ?>"><?= htmlspecialchars($objet['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Quantité à enlever :</label>
                <input type="number" name="saisies[0][quantite]" min="1" required placeholder="Quantité">
            </div>
        </div>
        <button type="button" onclick="addSaisieField()">+</button>
        <button type="submit" name="remove_saisie" class="remove-saisie-button">Retirer les Saisies</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
