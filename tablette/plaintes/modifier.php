<?php
session_start();
require_once '../../config.php';

// 🔐 Vérifie authentification et rôle BCSO
$role_bco = $roles['bco'] ?? null;

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true ||
    !hasRole($role_bco)
) {
    echo "<p style='color:red; text-align:center;'>Accès refusé : seuls les membres du BCSO peuvent modifier une plainte.</p>";
    exit();
}

// Vérification de l'ID de la plainte à modifier
if (!isset($_GET['id'])) {
    echo "Plainte non spécifiée.";
    exit();
}

$plainte_id = $_GET['id'];

// Récupérer les informations actuelles de la plainte
$stmt = $pdo->prepare("
    SELECT p.*, 
           plaignant.nom AS plaignant_nom, plaignant.prenom AS plaignant_prenom,
           visee.nom AS visee_nom, visee.prenom AS visee_prenom
    FROM plaintes p
    LEFT JOIN casiers AS plaignant ON p.plaignant_id = plaignant.id
    LEFT JOIN casiers AS visee ON p.personne_visee_id = visee.id
    WHERE p.id = ?
");
$stmt->execute([$plainte_id]);
$plainte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plainte) {
    echo "Plainte non trouvée.";
    exit();
}

// Traitement de la mise à jour de la plainte
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plaignant_id = $_POST['plaignant_id'];
    $sexe_plaignant = $_POST['sexe_plaignant'];
    $num_tel_plaignant = $_POST['num_tel_plaignant'];
    $personne_visee_id = $_POST['personne_visee_id'] ?? NULL;
    $sexe_visee = $_POST['sexe_visee'] ?? NULL;
    $num_tel_visee = $_POST['num_tel_visee'];
    $description_physique = $_POST['description_physique'];
    $motif_texte = $_POST['motif_texte'];
    $agent_id = $_POST['agent_id'];

    try {
        // Mise à jour de la plainte dans la base de données
        $stmt = $pdo->prepare("
            UPDATE plaintes 
            SET plaignant_id = ?, sexe_plaignant = ?, num_tel_plaignant = ?, 
                personne_visee_id = ?, sexe_visee = ?, num_tel_visee = ?, 
                description_physique = ?, motif_texte = ?, agent_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$plaignant_id, $sexe_plaignant, $num_tel_plaignant, 
                        $personne_visee_id, $sexe_visee, $num_tel_visee, 
                        $description_physique, $motif_texte, $agent_id, $plainte_id]);

        require_once 'plaintes_discord.php';
        sendPlainteUpdateToDiscord($plainte_id);
        // Rediriger vers la page de détails après modification
        header("Location: details.php?id=" . $plainte_id);
        exit();
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la mise à jour de la plainte : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier la Plainte</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Modifier la Plainte</h2>
    <form action="modifier.php?id=<?= htmlspecialchars($plainte_id); ?>" method="POST">
        <!-- Informations du plaignant -->
        <label>Prénom Nom plaignant :</label>
        <input type="text" id="search-plaignant" value="<?= htmlspecialchars($plainte['plaignant_nom'] . ' ' . $plainte['plaignant_prenom']); ?>" placeholder="Rechercher un plaignant...">
        <div id="plaignant-results" style="border: 1px solid #ddd; display: none;"></div>
        <input type="hidden" name="plaignant_id" id="plaignant-input" value="<?= htmlspecialchars($plainte['plaignant_id']); ?>">

        <label>Sexe du plaignant :</label>
        <select name="sexe_plaignant" required>
            <option value="Homme" <?= $plainte['sexe_plaignant'] === 'Homme' ? 'selected' : ''; ?>>Homme</option>
            <option value="Femme" <?= $plainte['sexe_plaignant'] === 'Femme' ? 'selected' : ''; ?>>Femme</option>
        </select>

        <label>Numéro de téléphone du plaignant :</label>
        <input type="tel" name="num_tel_plaignant" value="<?= htmlspecialchars($plainte['num_tel_plaignant']); ?>" required>

        <!-- Informations de la personne visée -->
        <label>Prénom Nom de la personne visée :</label>
        <input type="text" id="search-visee" value="<?= htmlspecialchars($plainte['visee_nom'] . ' ' . $plainte['visee_prenom'] ?? ''); ?>" placeholder="Rechercher une personne visée... (peut être aucun)">
        <div id="visee-results" style="border: 1px solid #ddd; display: none;"></div>
        <input type="hidden" name="personne_visee_id" id="visee-input" value="<?= htmlspecialchars($plainte['personne_visee_id'] ?? ''); ?>">

        <label>Sexe de la personne visée :</label>
        <select name="sexe_visee">
            <option value="">Aucun</option>
            <option value="Homme" <?= $plainte['sexe_visee'] === 'Homme' ? 'selected' : ''; ?>>Homme</option>
            <option value="Femme" <?= $plainte['sexe_visee'] === 'Femme' ? 'selected' : ''; ?>>Femme</option>
        </select>

        <label>Numéro de téléphone de la personne visée :</label>
        <input type="tel" name="num_tel_visee" value="<?= htmlspecialchars($plainte['num_tel_visee']); ?>">

        <label>Description physique :</label>
        <textarea name="description_physique" required><?= htmlspecialchars($plainte['description_physique']); ?></textarea>

        <label>Motif de la plainte :</label>
        <textarea name="motif_texte" required><?= htmlspecialchars($plainte['motif_texte']); ?></textarea>

        <label>Agent chargé :</label>
        <input type="text" name="agent_id" value="<?= htmlspecialchars($plainte['agent_id']); ?>" readonly>

        <button type="submit">Enregistrer les Modifications</button>
    </form>
</div>

<!-- JavaScript pour la recherche dynamique des plaignants et personnes visées -->
<script>
    function searchIndividu(inputId, resultsId, inputHiddenId) {
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
                            div.innerHTML = `${individu.nom} ${individu.prenom} <button type="button" onclick="selectIndividu(${individu.id}, '${individu.nom}', '${individu.prenom}', '${inputId}', '${inputHiddenId}')">Sélectionner</button>`;
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

    function selectIndividu(id, nom, prenom, inputId, inputHiddenId) {
        document.getElementById(inputId).value = nom + ' ' + prenom;
        document.getElementById(inputHiddenId).value = id;
        document.getElementById(inputId + '-results').style.display = 'none';
    }

    document.getElementById('search-plaignant').addEventListener('input', () => searchIndividu('search-plaignant', 'plaignant-results', 'plaignant-input'));
    document.getElementById('search-visee').addEventListener('input', () => searchIndividu('search-visee', 'visee-results', 'visee-input'));
</script>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
