<?php
session_start();
require_once '../../config.php';
require_once 'rapport_discord.php';

$role_bco = $roles['bco'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_bco)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO peuvent ajouter un rapport.</p>";
    exit();
}


if (!isset($_GET['id'])) {
    echo "Rapport non spécifié.";
    exit();
}

$rapport_id = $_GET['id'];

// Récupération des détails du rapport pour les pré-remplir
$stmt = $pdo->prepare("
    SELECT r.*, a.description AS motif_description, a.montant AS motif_montant, a.peine AS motif_peine, a.article AS motif_article, a.details AS motif_details
    FROM rapports r
    LEFT JOIN amende a ON r.motif = a.id
    WHERE r.id = ?
");
$stmt->execute([$rapport_id]);
$rapport = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rapport) {
    echo "Rapport non trouvé.";
    exit();
}

// Récupération des individus impliqués actuels
$stmt = $pdo->prepare("
    SELECT c.id AS casier_id, c.nom, c.prenom
    FROM casiers c
    JOIN rapports_individus ri ON c.id = ri.casier_id
    WHERE ri.rapport_id = ?
");
$stmt->execute([$rapport_id]);
$individus_actuels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des agents impliqués actuels (ajouter une colonne `agents` dans la table rapports si nécessaire)
$agents_actuels = explode(', ', $rapport['officier_id'] ?? '');

// Récupération des amendes pour le menu déroulant des motifs
$stmt = $pdo->query("SELECT id, description, montant, peine, article, details FROM amende");
$amendes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_arrestation = $_POST['date_arrestation'];
    $motif = $_POST['motif'];
    $amende = $_POST['amende'];
    $retention = $_POST['retention'];
    $rapport_text = $_POST['rapport_text'];
    $coop = $_POST['coop'];
    $miranda_time = $_POST['miranda_time'];
    $demandes_droits = $_POST['demandes_droits'];
    $heure_droits = $_POST['heure_droits'];
    $individus = explode(',', rtrim($_POST['individus'], ','));
    $agents = explode(',', rtrim($_POST['agents'], ','));

    // Concaténer les noms des agents impliqués pour les enregistrer dans officier_id
    $agents_concat = implode(', ', $agents);

    // Mise à jour des informations du rapport dans la base de données
    $stmt = $pdo->prepare("
        UPDATE rapports 
        SET date_arrestation = ?, motif = ?, amende = ?, retention = ?, rapport_text = ?, coop = ?, miranda_time = ?, demandes_droits = ?, heure_droits = ?, officier_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$date_arrestation, $motif, $amende, $retention, $rapport_text, $coop, $miranda_time, $demandes_droits, $heure_droits, $agents_concat, $rapport_id]);

    // Mise à jour des individus impliqués
    $stmt = $pdo->prepare("DELETE FROM rapports_individus WHERE rapport_id = ?");
    $stmt->execute([$rapport_id]);

    foreach ($individus as $casier_id) {
        $stmt = $pdo->prepare("INSERT INTO rapports_individus (rapport_id, casier_id) VALUES (?, ?)");
        $stmt->execute([$rapport_id, $casier_id]);
    }

    $modifie_par = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
    sendReportUpdateToDiscord($rapport_id, $modifie_par);


    header("Location: details.php?id=" . $rapport_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Rapport d'Arrestation</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Modifier le Rapport d'Arrestation</h2>
    <form action="modifier.php?id=<?= htmlspecialchars($rapport_id); ?>" method="post">

        <label>Date d'Arrestation :</label>
        <input type="date" name="date_arrestation" value="<?= htmlspecialchars($rapport['date_arrestation'] ?? ''); ?>" required>

        <!-- Individus impliqués avec ajout dynamique -->
        <label>Individus Impliqués :</label>
        <input type="text" id="search-individu" placeholder="Rechercher un individu...">
        <div id="individu-results" style="border: 1px solid #ddd; display: none; max-height: 150px; overflow-y: auto;"></div>
    <div id="individus-selected">
        <?php foreach ($individus_actuels as $individu): ?>
            <div class="individu-selected" data-id="<?= htmlspecialchars($individu['casier_id']); ?>">
                <?= htmlspecialchars($individu['nom']) . ' ' . htmlspecialchars($individu['prenom']); ?>
                <button type="button" class="btn-remove-individu" 
                    onclick="deleteIndividu(<?= htmlspecialchars($individu['casier_id']); ?>, <?= htmlspecialchars($rapport_id); ?>)">X</button>
            </div>
        <?php endforeach; ?>
</div>



        <input type="hidden" name="individus" id="individus-input" value="<?= implode(',', array_column($individus_actuels, 'casier_id')); ?>">

        <!-- Agents impliqués avec ajout dynamique -->
        <label>Agents Impliqués :</label>
        <input type="text" id="search-agent" placeholder="Rechercher un agent...">
        <div id="agent-results" style="border: 1px solid #ddd; display: none; max-height: 150px; overflow-y: auto;"></div>
        <div id="agents-selected">
            <h4>Agents Sélectionnés :</h4>
            <?php foreach ($agents_actuels as $agent): ?>
                <div class="agent-selected" data-name="<?= htmlspecialchars($agent); ?>">
                    <?= htmlspecialchars($agent); ?>
                    <button type="button" onclick="removeAgent('<?= htmlspecialchars($agent); ?>')">X</button>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="agents" id="agents-input" value="<?= implode(',', $agents_actuels); ?>">

        <!-- Liste des motifs avec point d'interrogation pour afficher l'article et les détails -->
        <label>Motif :</label>
        <select name="motif" id="motif" required onchange="updateAmendeRetentions()">
            <option value="">-- Sélectionnez un motif --</option>
            <?php
            $amendesJS = [];
            foreach ($amendes as $amende) {
                $selected = $rapport['motif'] == $amende['id'] ? 'selected' : '';
                echo "<option value='{$amende['id']}' $selected>{$amende['description']}</option>";
                $amendesJS[$amende['id']] = [
                    'montant' => $amende['montant'],
                    'peine' => $amende['peine'],
                    'article' => $amende['article'],
                    'details' => $amende['details']
                ];
            }
            ?>
        </select>
        <span id="info-tooltip" title="Cliquez sur un motif pour voir les détails" style="cursor: pointer;">❓</span>
        <div id="details-display" style="display: none; position: absolute; background: #fff; border: 1px solid #ccc; padding: 10px; z-index: 1000;"></div>

        <!-- Champs d'amende et de rétention pré-remplis et modifiables -->
        <label>Amende :</label>
        <input type="number" name="amende" id="amende" value="<?= htmlspecialchars($rapport['amende'] ?? ''); ?>" placeholder="Montant de l'amende" required>

        <label>Peine :</label>
        <input type="text" name="retention" id="retention" value="<?= htmlspecialchars($rapport['retention'] ?? ''); ?>" placeholder="Durée de rétention" required>

        <label>Rapport d'Arrestation :</label>
        <textarea name="rapport_text" placeholder="Détails du rapport" required><?= htmlspecialchars($rapport['rapport_text'] ?? ''); ?></textarea>

        <!-- Champs supplémentaires -->
        <label>Individu Coopératif (0 à 10) :</label>
        <input type="number" name="coop" min="0" max="10" value="<?= htmlspecialchars($rapport['coop'] ?? ''); ?>" required>

        <label>Droits Miranda lus à :</label>
        <input type="time" name="miranda_time" value="<?= htmlspecialchars($rapport['miranda_time'] ?? ''); ?>" required>

        <label>Droits demandés :</label>
        <input type="text" name="demandes_droits" value="<?= htmlspecialchars($rapport['demandes_droits'] ?? ''); ?>" placeholder="Boire, manger, avocat..." required>

        <label>Heure des droits réalisés :</label>
        <input type="time" name="heure_droits" value="<?= htmlspecialchars($rapport['heure_droits'] ?? ''); ?>" required>

        <button type="submit">Enregistrer les Modifications</button>
    </form>
</div>

<script>
    const amendes = <?= json_encode($amendesJS); ?>;
    const selectedIndividus = new Set(<?= json_encode(array_column($individus_actuels, 'casier_id')); ?>);
    const selectedAgents = new Set(<?= json_encode($agents_actuels); ?>);

    function updateAmendeRetentions() {
        const motifId = document.getElementById("motif").value;
        const tooltip = document.getElementById("info-tooltip");
        const detailsDiv = document.getElementById("details-display");

        if (motifId && amendes[motifId]) {
            document.getElementById("amende").value = amendes[motifId].montant || '';
            document.getElementById("retention").value = amendes[motifId].peine || '';

            tooltip.onmouseover = function() {
                detailsDiv.innerHTML = `<strong>${amendes[motifId].article} :</strong> ${amendes[motifId].details}`;
                detailsDiv.style.display = "block";
            };
            tooltip.onmouseout = function() {
                detailsDiv.style.display = "none";
            };
        } else {
            document.getElementById("amende").value = '';
            document.getElementById("retention").value = '';
            detailsDiv.style.display = "none";
        }
    }

    document.getElementById('search-individu').addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length > 1) {
            fetch(`/tablette/rapport/recherche_individu.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('individu-results');
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        resultsDiv.style.display = 'block';
                        data.forEach(individu => {
                            if (!selectedIndividus.has(individu.id)) {
                                const div = document.createElement('div');
                                div.classList.add('individu-result');
                                div.innerHTML = `
                                    <span>${individu.nom} ${individu.prenom}</span>
                                    <button type="button" onclick="addIndividu(${individu.id}, '${individu.nom}', '${individu.prenom}')">+</button>
                                `;
                                resultsDiv.appendChild(div);
                            }
                        });
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        } else {
            document.getElementById('individu-results').style.display = 'none';
        }
    });

    function addIndividu(id, nom, prenom) {
        if (!selectedIndividus.has(id)) {
            selectedIndividus.add(id);
            const selectedDiv = document.createElement('div');
            selectedDiv.classList.add('individu-selected');
            selectedDiv.setAttribute('data-id', id);
            selectedDiv.innerHTML = `
                <span>${nom} ${prenom}</span>
                <button type="button" onclick="removeIndividu(${id})">X</button>
            `;
            document.getElementById('individus-selected').appendChild(selectedDiv);
            updateIndividusInput();
        }
    }

    function deleteIndividu(casierId, rapportId) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer cet individu ?")) return;

    fetch('/tablette/rapport/supprimer_individu.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `rapport_id=${encodeURIComponent(rapportId)}&casier_id=${encodeURIComponent(casierId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Suppression réussie dans l'interface
            const individuDiv = document.querySelector(`.individu-selected[data-id='${casierId}']`);
            if (individuDiv) {
                individuDiv.remove();
            }

            // Mettre à jour l'entrée cachée
            const individusInput = document.getElementById('individus-input');
            const currentIds = individusInput.value.split(',').filter(id => id !== casierId.toString());
            individusInput.value = currentIds.join(',');

            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la suppression:', error);
        alert("Une erreur s'est produite.");
    });
}


function updateIndividusInput() {
    const selectedIds = Array.from(selectedIndividus);
    console.log("Mise à jour du champ individus-input avec les IDs :", selectedIds);
    document.getElementById('individus-input').value = selectedIds.join(',');
}
    document.getElementById('search-agent').addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length > 1) {
            fetch(`/tablette/rapport/recherche_agent.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('agent-results');
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        resultsDiv.style.display = 'block';
                        data.forEach(agent => {
                            if (!selectedAgents.has(agent.nom)) {
                                const div = document.createElement('div');
                                div.classList.add('agent-result');
                                div.innerHTML = `
                                    <span>${agent.nom}</span>
                                    <button type="button" onclick="addAgent('${agent.nom}')">+</button>
                                `;
                                resultsDiv.appendChild(div);
                            }
                        });
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        } else {
            document.getElementById('agent-results').style.display = 'none';
        }
    });

    function addAgent(nom) {
        if (!selectedAgents.has(nom)) {
            selectedAgents.add(nom);
            const selectedDiv = document.createElement('div');
            selectedDiv.classList.add('agent-selected');
            selectedDiv.setAttribute('data-name', nom);
            selectedDiv.innerHTML = `
                <span>${nom}</span>
                <button type="button" onclick="removeAgent('${nom}')">X</button>
            `;
            document.getElementById('agents-selected').appendChild(selectedDiv);
            updateAgentsInput();
        }
    }

    function removeAgent(nom) {
        selectedAgents.delete(nom);
        const selectedDiv = document.querySelector(`.agent-selected[data-name="${nom}"]`);
        if (selectedDiv) selectedDiv.remove();
        updateAgentsInput();
    }

    function updateAgentsInput() {
        document.getElementById('agents-input').value = Array.from(selectedAgents).join(',');
    }
</script>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
