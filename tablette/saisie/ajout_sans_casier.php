<?php
session_start();
require_once '../../config.php';

$role_bco = $roles['bcso'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_bco)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO peuvent ajoutes des saisie.</p>";
    exit();
}

// Récupération des objets disponibles dans `saisie` pour remplir la liste déroulante
$stmt = $pdo->query("SELECT id, nom, categorie FROM saisie");
$objetOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire d'ajout de saisies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_saisie'])) {
    $motif = $_POST['motif'];
    $saisiesAjoutees = []; // Pour stocker les saisies ajoutées

    foreach ($_POST['saisies'] as $saisie) {
        if (empty($saisie['saisie_id']) || empty($saisie['quantite'])) {
            echo "<p>Erreur : saisie_id ou quantité manquants pour une saisie spécifique.</p>";
            continue;
        }

        $saisie_id = $saisie['saisie_id'];
        $quantite = $saisie['quantite'];
        $numero_serie = $saisie['numero_serie'] ?? null;

        // Récupérer les détails de l'objet depuis la table `saisie`
        $stmt = $pdo->prepare("SELECT nom, categorie FROM saisie WHERE id = ?");
        $stmt->execute([$saisie_id]);
        $saisieDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($saisieDetails) {
            // Mise à jour de la quantité dans `saisie`
            $stmt = $pdo->prepare("UPDATE saisie SET quantite = quantite + ? WHERE id = ?");
            $stmt->execute([$quantite, $saisie_id]);

            // Si la catégorie est "Armes", ajouter l'arme avec le numéro de série
            if ($saisieDetails['categorie'] === "Armes" && $numero_serie) {
                $stmt = $pdo->prepare("INSERT INTO s_armes (nom, numero_serie) VALUES (?, ?)");
                $stmt->execute([$saisieDetails['nom'], $numero_serie]);
                $arme_id = $pdo->lastInsertId();

                // Associer l'arme dans `saisie_c`
                $stmt = $pdo->prepare("INSERT INTO saisie_c (saisie, quantite, saisie_id) VALUES (?, ?, ?)");
                $stmt->execute([$arme_id, $quantite, $saisie_id]);
            } else {
                // Association pour les autres saisies
                $stmt = $pdo->prepare("INSERT INTO saisie_c (saisie, quantite, saisie_id) VALUES (?, ?, ?)");
                $stmt->execute([$saisie_id, $quantite, $saisie_id]);
            }

            // Ajouter à la liste des saisies ajoutées pour Discord
            $saisiesAjoutees[] = ['nom' => $saisieDetails['nom'], 'quantite' => $quantite];
        } else {
            echo "<p>Erreur : l'objet saisi avec l'ID $saisie_id n'a pas été trouvé dans la table `saisie`.</p>";
        }
    }

    // Inclure le fichier pour envoyer les saisies ajoutées à Discord
    require_once 'saisie_discord.php';

    $officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    sendSaisieWithoutCasierToDiscord($officier_id, $motif, $saisiesAjoutees);
    
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter des Saisies sans Casier</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .saisie-fields {
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .add-saisie-button {
            float: right;
            margin-top: 10px;
        }
    </style>
    <script>
        const objetsDisponibles = <?= json_encode($objetOptions); ?>;

        function searchObjet(query) {
            return objetsDisponibles.filter(objet => objet.nom.toLowerCase().includes(query.toLowerCase()));
        }

        function addSaisieField() {
            const saisieContainer = document.getElementById('saisies-container');
            const saisieFields = document.createElement('div');
            saisieFields.classList.add('saisie-fields');
            const saisieIndex = saisieContainer.children.length;
            saisieFields.innerHTML = `
                <label>Objet saisi :</label>
                <input type="text" placeholder="Rechercher un objet..." oninput="updateSaisieDropdown(this)" required>
                <select name="saisies[${saisieIndex}][saisie_id]" onchange="updateSaisieDetails(this)" required>
                    <option value="">-- Résultats de la recherche --</option>
                </select>
                <label>Quantité :</label>
                <input type="number" name="saisies[${saisieIndex}][quantite]" min="1" required placeholder="Quantité">
                <div class="numero-serie-field" style="display: none;">
                    <label>Numéro de série :</label>
                    <input type="text" name="saisies[${saisieIndex}][numero_serie]" placeholder="Numéro de série">
                </div>
                <button type="button" onclick="removeSaisieField(this)">-</button>
            `;
            saisieContainer.appendChild(saisieFields);
        }

        function updateSaisieDropdown(inputElement) {
            const query = inputElement.value;
            const results = searchObjet(query);
            const selectElement = inputElement.nextElementSibling;
            selectElement.innerHTML = '<option value="">-- Résultats de la recherche --</option>';
            results.forEach(objet => {
                const option = document.createElement('option');
                option.value = objet.id;
                option.textContent = objet.nom;
                option.setAttribute('data-categorie', objet.categorie); // Ajoute la catégorie comme attribut
                selectElement.appendChild(option);
            });
        }

        function updateSaisieDetails(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const categorie = selectedOption.getAttribute('data-categorie');
            const numeroSerieField = selectElement.parentElement.querySelector('.numero-serie-field');
            
            // Affiche le champ numéro de série pour les armes
            if (categorie === "Armes") {
                numeroSerieField.style.display = "block";
            } else {
                numeroSerieField.style.display = "none";
            }
        }

        function removeSaisieField(button) {
            button.parentElement.remove();
        }
    </script>
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Ajouter des Saisies sans Casier</h2>

    <form method="POST" action="ajout_sans_casier.php">
        <label>Motif :</label>
        <input type="text" name="motif" placeholder="Motif de la saisie" required>

        <h3>Détails des Saisies</h3>
        <div id="saisies-container">
            <div class="saisie-fields">
                <label>Objet saisi :</label>
                <input type="text" placeholder="Rechercher un objet..." oninput="updateSaisieDropdown(this)" required>
                <select name="saisies[0][saisie_id]" onchange="updateSaisieDetails(this)" required>
                    <option value="">-- Résultats de la recherche --</option>
                </select>
                <label>Quantité :</label>
                <input type="number" name="saisies[0][quantite]" min="1" required placeholder="Quantité">
                <div class="numero-serie-field" style="display: none;">
                    <label>Numéro de série :</label>
                    <input type="text" name="saisies[0][numero_serie]" placeholder="Numéro de série">
                </div>
            </div>
        </div>
        <button type="button" onclick="addSaisieField()" class="add-saisie-button">+</button>
        <button type="submit" name="add_saisie">Ajouter les Saisies</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
