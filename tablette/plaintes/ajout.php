<?php
include '../../config.php';
include '../../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

// Utiliser le surnom Discord si disponible, sinon utiliser le pseudo global pour l'agent chargé
$agent_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
?>

<div class="container">
    <link rel="stylesheet" href="../../css/styles.css">
    <h2>Ajouter une Plainte</h2>
    <form action="ajout.php" method="POST">
        <!-- Informations du plaignant -->
        <label>Prénom Nom plaignant :</label>
        <input type="text" id="search-plaignant" placeholder="Rechercher un plaignant...">
        <div id="plaignant-results" style="border: 1px solid #ddd; display: none;"></div>
        <input type="hidden" name="plaignant_id" id="plaignant-input">

        <label>Sexe du plaignant :</label>
        <select name="sexe_plaignant" required>
            <option value="Homme">Homme</option>
            <option value="Femme">Femme</option>
        </select>

        <label>Numéro de téléphone du plaignant :</label>
        <input type="tel" name="num_tel_plaignant" id="num_tel_plaignant" placeholder="Numéro de téléphone du plaignant" required readonly>

        <!-- Informations de la personne visée -->
        <label>Prénom Nom de la personne visée :</label>
        <input type="text" id="search-visee" placeholder="Rechercher une personne visée... (peut être aucun)">
        <div id="visee-results" style="border: 1px solid #ddd; display: none;"></div>
        <input type="hidden" name="personne_visee_id" id="visee-input">

        <label>Sexe de la personne visée :</label>
        <select name="sexe_visee">
            <option value="">Aucun</option>
            <option value="Homme">Homme</option>
            <option value="Femme">Femme</option>
        </select>

        <label>Numéro de téléphone de la personne visée :</label>
        <input type="tel" name="num_tel_visee" id="num_tel_visee" placeholder="Numéro de téléphone de la personne visée" readonly>

        <label>Description physique :</label>
        <textarea name="description_physique" placeholder="Description physique de la personne visée" required></textarea>

        <label>Motif de la plainte :</label>
        <textarea name="motif_texte" placeholder="Description du motif de la plainte" required></textarea>

        <label>Agent chargé :</label>
        <input type="text" name="agent_id" value="<?= htmlspecialchars($agent_id); ?>" readonly>

        <button type="submit">Ajouter la Plainte</button>
    </form>
</div>

<!-- JavaScript pour la recherche dynamique des plaignants et personnes visées -->
<script>
    function searchIndividu(inputId, resultsId, inputHiddenId, numTelId) {
        const query = document.getElementById(inputId).value.trim();
        if (query.length > 1) {
            fetch(`recherche_individu.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById(resultsId);
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        resultsDiv.style.display = 'block';
                        data.forEach(individu => {
                            const div = document.createElement('div');
                            div.classList.add('individu-result');
                            div.innerHTML = `${individu.nom} ${individu.prenom} <button type="button" onclick="selectIndividu(${individu.id}, '${individu.nom}', '${individu.prenom}', '${individu.num_tel}', '${inputId}', '${inputHiddenId}', '${numTelId}')">Sélectionner</button>`;
                            resultsDiv.appendChild(div);
                        });
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        } else {
            document.getElementById(resultsId).style.display = 'none';
        }
    }

    function selectIndividu(id, nom, prenom, num_tel, inputId, inputHiddenId, numTelId) {
        document.getElementById(inputId).value = nom + ' ' + prenom;
        document.getElementById(inputHiddenId).value = id;
        document.getElementById(numTelId).value = num_tel;
        document.getElementById(inputId + '-results').style.display = 'none';
    }

    document.getElementById('search-plaignant').addEventListener('input', () => searchIndividu('search-plaignant', 'plaignant-results', 'plaignant-input', 'num_tel_plaignant'));
    document.getElementById('search-visee').addEventListener('input', () => searchIndividu('search-visee', 'visee-results', 'visee-input', 'num_tel_visee'));
</script>

<?php
// Traitement du formulaire d'ajout de plainte
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des valeurs du formulaire
    $plaignant_id = $_POST['plaignant_id'] ?? null;
    $sexe_plaignant = $_POST['sexe_plaignant'] ?? null;
    $num_tel_plaignant = $_POST['num_tel_plaignant'] ?? null;
    $personne_visee_id = $_POST['personne_visee_id'] ?? null;
    $sexe_visee = $_POST['sexe_visee'] ?? null;
    $num_tel_visee = $_POST['num_tel_visee'] ?? null;
    $description_physique = $_POST['description_physique'] ?? null;
    $motif_texte = $_POST['motif_texte'] ?? null;
    $agent_id = $_POST['agent_id'] ?? null;

    try {
        // Insertion de la plainte dans la base de données
        $stmt = $pdo->prepare("INSERT INTO plaintes (plaignant_id, sexe_plaignant, num_tel_plaignant, personne_visee_id, sexe_visee, num_tel_visee, description_physique, motif_texte, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$plaignant_id, $sexe_plaignant, $num_tel_plaignant, $personne_visee_id, $sexe_visee, $num_tel_visee, $description_physique, $motif_texte, $agent_id]);

        // Récupération de l'ID de la plainte récemment ajoutée
        $plainte_id = $pdo->lastInsertId();

        // Inclure le fichier pour envoyer les détails de la plainte sur Discord
        require_once 'plaintes_discord.php';
        sendPlainteToDiscord($plainte_id);
        

        echo "<p>Plainte ajoutée avec succès !</p>";
    } catch (PDOException $e) {
        echo "<p>Erreur lors de l'ajout de la plainte : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<?php include '../../includes/footer.php'; ?>
