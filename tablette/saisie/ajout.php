<?php
session_start();
require_once '../../config.php';

$individus = [];
$selectedCasier = null;
$rapports = [];
$objetOptions = [];

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
$stmt = $pdo->query("SELECT * FROM saisie");
$objetOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestion de la recherche d'individu par nom
if (isset($_POST['search']) && !empty($_POST['nom_search'])) {
    $nom_search = $_POST['nom_search'];
    $stmt = $pdo->prepare("SELECT * FROM casiers WHERE nom LIKE ? OR prenom LIKE ?");
    $stmt->execute(['%' . $nom_search . '%', '%' . $nom_search . '%']);
    $individus = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Vérification si un casier est sélectionné
if (isset($_POST['select_casier_id']) && !empty($_POST['select_casier_id'])) {
    $casier_id = $_POST['select_casier_id'];
    $stmt = $pdo->prepare("SELECT * FROM casiers WHERE id = ?");
    $stmt->execute([$casier_id]);
    $selectedCasier = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT r.id, r.date_arrestation, a.description AS motif 
        FROM rapports r
        JOIN rapports_individus ri ON r.id = ri.rapport_id
        LEFT JOIN amende a ON r.motif = a.id
        WHERE ri.casier_id = ?
        ORDER BY r.date_arrestation DESC
    ");
    $stmt->execute([$casier_id]);
    $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement du formulaire d'ajout de saisies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_saisie'])) {
    $casier_id = $_POST['casier_id'];
    $rapport_id = $_POST['rapport_id'];
    $saisiesAjoutees = []; // Pour stocker les saisies ajoutées

    // Récupération des informations de l'individu et du rapport pour Discord
    $stmt = $pdo->prepare("SELECT nom, prenom FROM casiers WHERE id = ?");
    $stmt->execute([$casier_id]);
    $individuDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT date_arrestation, a.description AS motif FROM rapports r LEFT JOIN amende a ON r.motif = a.id WHERE r.id = ?");
    $stmt->execute([$rapport_id]);
    $rapportDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach ($_POST['saisies'] as $saisie) {
        if (empty($saisie['saisie_id']) || empty($saisie['quantite'])) {
            echo "<p>Erreur : saisie_id ou quantité manquants pour une saisie spécifique.</p>";
            continue;
        }

        $saisie_id = $saisie['saisie_id'];
        $quantite = $saisie['quantite'];
        $numero_serie = $saisie['numero_serie'] ?? null;

        // Récupérer les détails de l'objet (poids, catégorie) depuis la table `saisie`
        $stmt = $pdo->prepare("SELECT nom, categorie FROM saisie WHERE id = ?");
        $stmt->execute([$saisie_id]);
        $saisieDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($saisieDetails) {
            // Mise à jour de la quantité dans `saisie`
            $stmt = $pdo->prepare("UPDATE saisie SET quantite = quantite + ? WHERE id = ?");
            $stmt->execute([$quantite, $saisie_id]);

            // Si la catégorie est "Armes", ajouter l'arme dans `s_armes` avec le numéro de série et casier_id
            if ($saisieDetails['categorie'] === "Armes" && $numero_serie) {
                $stmt = $pdo->prepare("INSERT INTO s_armes (nom, numero_serie, casier_id) VALUES (?, ?, ?)");
                $stmt->execute([$saisieDetails['nom'], $numero_serie, $casier_id]);
                $arme_id = $pdo->lastInsertId();

                // Associer l'arme au casier dans `saisie_c`
                $stmt = $pdo->prepare("INSERT INTO saisie_c (idcasier, idrapport, saisie, quantite, saisie_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$casier_id, $rapport_id, $arme_id, $quantite, $saisie_id]);
            } else {
                // Association pour les autres saisies
                $stmt = $pdo->prepare("INSERT INTO saisie_c (idcasier, idrapport, saisie, quantite, saisie_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$casier_id, $rapport_id, $saisie_id, $quantite, $saisie_id]);
            }

            // Ajouter à la liste des saisies ajoutées pour Discord
            $saisiesAjoutees[] = [
                'nom' => $saisieDetails['nom'],
                'quantite' => $quantite,
                'numero_serie' => $numero_serie ?? null
            ];            
        } else {
            echo "<p>Erreur : l'objet saisi avec l'ID $saisie_id n'a pas été trouvé dans la table `saisie`.</p>";
        }
    }

    // Inclure le fichier pour envoyer les saisies ajoutées à Discord, en passant les détails de l'individu et du rapport
    require_once 'saisie_discord.php';

    $officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    sendSaisieWithCasierToDiscord($officier_id, $individuDetails, $rapportDetails, $saisiesAjoutees);
    
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter des Saisies</title>
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
            const searchResults = searchObjet(inputElement.value);
            const selectElement = inputElement.nextElementSibling;
            selectElement.innerHTML = '<option value="">-- Résultats de la recherche --</option>';
            searchResults.forEach(objet => {
                const option = document.createElement('option');
                option.value = objet.id;
                option.textContent = objet.nom;
                option.setAttribute('data-categorie', objet.categorie); // Stocke la catégorie dans l'option
                selectElement.appendChild(option);
            });
        }

        function updateSaisieDetails(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const categorie = selectedOption.getAttribute('data-categorie');
            const numeroSerieField = selectElement.parentElement.querySelector('.numero-serie-field');
            
            if (categorie === "Armes") {
                numeroSerieField.style.display = "block"; // Affiche le champ numéro de série pour les armes
            } else {
                numeroSerieField.style.display = "none"; // Cache le champ numéro de série pour les autres catégories
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
    <h2>Ajouter des Saisies</h2>

    <form method="POST" action="ajout.php">
        <label>Rechercher un individu par nom :</label>
        <input type="text" name="nom_search" placeholder="Nom de l'individu" value="<?= isset($nom_search) ? htmlspecialchars($nom_search) : ''; ?>">
        <button type="submit" name="search">Rechercher</button>
    </form>

    <?php if (!empty($individus)): ?>
        <h3>Résultats de la recherche :</h3>
        <form method="POST" action="ajout.php">
            <label>Sélectionnez un individu :</label>
            <select name="select_casier_id" onchange="this.form.submit()">
                <option value="">-- Choisissez un individu --</option>
                <?php foreach ($individus as $individu): ?>
                    <option value="<?= htmlspecialchars($individu['id']); ?>">
                        <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>

    <?php if ($selectedCasier): ?>
        <h3>Individu sélectionné :</h3>
        <p><strong>Nom :</strong> <?= htmlspecialchars($selectedCasier['nom'] . ' ' . $selectedCasier['prenom']); ?></p>

        <?php if (count($rapports) > 0): ?>
            <form method="POST" action="ajout.php">
                <input type="hidden" name="casier_id" value="<?= htmlspecialchars($selectedCasier['id']); ?>">

                <h3>Rapports d'Arrestation Associés</h3>
                <label>Sélectionnez un rapport :</label>
                <select name="rapport_id" required>
                    <option value="">-- Choisissez un rapport --</option>
                    <?php foreach ($rapports as $rapport): ?>
                        <option value="<?= htmlspecialchars($rapport['id']); ?>">
                            <?= htmlspecialchars($rapport['date_arrestation'] . ' - ' . $rapport['motif']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <h3>Détails des Saisies</h3>
                <div id="saisies-container">
                    <div class="saisie-fields">
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
                <button type="button" onclick="addSaisieField()">+</button>
                <button type="submit" name="add_saisie" class="add-saisie-button">Ajouter les Saisies</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
