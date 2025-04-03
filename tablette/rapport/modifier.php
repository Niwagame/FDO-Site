<?php
session_start();
require_once '../../config.php';
require_once 'rapport_discord.php';
include '../../includes/header.php';

$role_bco = $roles['bco'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_bco)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO peuvent modifier un rapport.</p>";
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Rapport non spécifié.";
    exit();
}

$rapport_id = intval($_GET['id']);

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

$stmt = $pdo->prepare("
    SELECT c.id AS casier_id, c.nom, c.prenom
    FROM casiers c
    JOIN rapports_individus ri ON c.id = ri.casier_id
    WHERE ri.rapport_id = ?
");
$stmt->execute([$rapport_id]);
$individus_actuels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$agents_actuels = explode(', ', $rapport['officier_id'] ?? '');

$stmt = $pdo->query("SELECT id, description, montant, peine, article, details FROM amende");
$amendes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT droit, heure_droit FROM droit_miranda WHERE rapport_id = ?");
$stmt->execute([$rapport_id]);
$miranda_droits = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_arrestation = $_POST['date_arrestation'] ?? '';
    $motif = $_POST['motif'] ?? '';
    $amende = $_POST['amende'] ?? '';
    $retention = $_POST['retention'] ?? '';
    $rapport_text = $_POST['rapport_text'] ?? '';
    $coop = $_POST['coop'] ?? '';
    $heure_privation_liberte = $_POST['heure_privation_liberte'] ?? '';
    $miranda_time = $_POST['miranda_time'] ?? '';
    $individus = isset($_POST['individus']) ? explode(',', rtrim($_POST['individus'], ',')) : [];
    $agents = isset($_POST['agents']) ? explode(',', rtrim($_POST['agents'], ',')) : [];

    $agents_concat = implode(', ', $agents);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE rapports 
            SET date_arrestation = ?, motif = ?, amende = ?, retention = ?, rapport_text = ?, coop = ?, heure_privation_liberte = ?, miranda_time = ?, officier_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$date_arrestation, $motif, $amende, $retention, $rapport_text, $coop, $heure_privation_liberte, $miranda_time, $agents_concat, $rapport_id]);

        $stmt = $pdo->prepare("DELETE FROM rapports_individus WHERE rapport_id = ?");
        $stmt->execute([$rapport_id]);

        $stmt = $pdo->prepare("INSERT INTO rapports_individus (rapport_id, casier_id) VALUES (?, ?)");
        foreach ($individus as $casier_id) {
            $stmt->execute([$rapport_id, $casier_id]);
        }

        $stmt = $pdo->prepare("DELETE FROM droit_miranda WHERE rapport_id = ?");
        $stmt->execute([$rapport_id]);

        if (!empty($_POST['droit_miranda_nom']) && !empty($_POST['droit_miranda_heure'])) {
            $stmt = $pdo->prepare("INSERT INTO droit_miranda (rapport_id, droit, heure_droit) VALUES (?, ?, ?)");
            foreach ($_POST['droit_miranda_nom'] as $index => $droit_nom) {
                $droit_heure = $_POST['droit_miranda_heure'][$index] ?? null;
                if (!empty($droit_nom) && !empty($droit_heure)) {
                    $stmt->execute([$rapport_id, $droit_nom, $droit_heure]);
                }
            }
        }

        $pdo->commit();

        $modifie_par = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';
        sendReportUpdateToDiscord($rapport_id, $modifie_par);

        header("Location: details.php?id=" . $rapport_id);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<p>Erreur lors de la mise à jour du rapport : " . $e->getMessage() . "</p>";
    }
}
?>

<link rel="stylesheet" href="../../css/styles.css">
<div class="container">
    <h2>Modifier le Rapport d'Arrestation</h2>
    <form action="modifier.php?id=<?= htmlspecialchars($rapport_id); ?>" method="post" id="form-rapport">

        <label>Date d'Arrestation :</label>
        <input type="date" name="date_arrestation" value="<?= htmlspecialchars($rapport['date_arrestation'] ?? ''); ?>" required>

        <label>Individus Impliqués :</label>
        <input type="text" id="search-individu" placeholder="Rechercher un individu...">
        <div id="individu-results" style="border: 1px solid #ddd; display: none;"></div>
        <div id="individus-selected">
            <h4>Individus Sélectionnés :</h4>
            <?php foreach ($individus_actuels as $individu): ?>
                <div class="individu-selected" data-id="<?= $individu['casier_id']; ?>">
                    <?= htmlspecialchars($individu['nom'] . ' ' . $individu['prenom']); ?>
                    <button type="button" onclick="removeIndividu(<?= $individu['casier_id']; ?>)">X</button>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="individus" id="individus-input" value="<?= implode(',', array_column($individus_actuels, 'casier_id')); ?>">

        <label>Agents Impliqués :</label>
        <input type="text" id="search-agent" placeholder="Rechercher un agent...">
        <div id="agent-results" style="border: 1px solid #ddd; display: none;"></div>
        <div id="agents-selected"><h4>Agents Sélectionnés :</h4>
            <?php foreach ($agents_actuels as $agent): ?>
                <div class="agent-selected" data-name="<?= $agent; ?>">
                    <?= htmlspecialchars($agent); ?>
                    <button type="button" onclick="removeAgent('<?= $agent; ?>')">X</button>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="agents" id="agents-input" value="<?= implode(',', $agents_actuels); ?>">

        <label>Rechercher un Motif :</label>
        <input type="text" id="motif-search" oninput="searchMotif()" placeholder="Saisir un motif..." autocomplete="off">
        <div style="display: flex; align-items: center; gap: 5px;">
            <select name="motif" id="motif-select" onchange="updateAmendeRetentions()" required>
                <option value="">-- Sélectionnez un motif --</option>
                <?php foreach ($amendes as $amende): ?>
                    <option 
                        value="<?= $amende['id']; ?>" 
                        <?= ($rapport['motif'] == $amende['id']) ? 'selected' : ''; ?>
                        data-montant="<?= $amende['montant']; ?>" 
                        data-peine="<?= $amende['peine']; ?>"
                        data-article="<?= $amende['article']; ?>"
                        data-details="<?= htmlspecialchars($amende['details']); ?>"
                    >
                        <?= htmlspecialchars($amende['description']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="info-tooltip" style="cursor: pointer; font-size: 20px; color: red;" title="">❓</span>
        </div>

        <label>Amende :</label>
        <input type="number" name="amende" id="amende" value="<?= $rapport['amende']; ?>" required>

        <label>Peine :</label>
        <input type="text" name="retention" id="retention" value="<?= $rapport['retention']; ?>" required>

        <label>Rapport d'Arrestation :</label>
        <textarea name="rapport_text" required><?= htmlspecialchars($rapport['rapport_text']); ?></textarea>

        <label>Individu Coopératif (0 à 10) :</label>
        <input type="number" name="coop" min="0" max="10" value="<?= $rapport['coop']; ?>" required>

        <label>Heure de privation de liberté :</label>
        <input type="time" name="heure_privation_liberte" value="<?= $rapport['heure_privation_liberte']; ?>" required>

        <label>Droit Miranda lu à :</label>
        <input type="time" name="miranda_time" value="<?= $rapport['miranda_time']; ?>" required>

        <div id="droit-miranda-wrapper">
            <div id="droit-label" <?= empty($miranda_droits) ? 'style="display:none;"' : ''; ?>>
                <label>Droits demandés :</label>
            </div>
            <div id="droit-miranda-container">
                <?php foreach ($miranda_droits as $droit): ?>
                    <div class="droit-miranda-row" style="margin-bottom: 10px;">
                        <select name="droit_miranda_nom[]" required>
                            <option value="">-- Choisir un droit --</option>
                            <?php foreach (["Appel", "Manger", "Boire", "EMS", "Avocat"] as $opt): ?>
                                <option value="<?= $opt; ?>" <?= $droit['droit'] === $opt ? 'selected' : ''; ?>><?= $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="time" name="droit_miranda_heure[]" value="<?= $droit['heure_droit']; ?>" required>
                        <button type="button" onclick="this.parentElement.remove()">🗑</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-ajout" onclick="addDroitMiranda()">+ Ajouter un droit</button>
        </div>

        <div style="text-align: right; margin-top: 20px;">
            <button type="submit" class="btn-principal">Enregistrer les Modifications</button>
        </div>
    </form>
</div>

<script>
    const selectedIndividus = new Set();
    const selectedAgents = new Set();

    function addDroitMiranda() {
    document.getElementById('droit-label').style.display = 'block';

    const container = document.getElementById('droit-miranda-container');
    const div = document.createElement('div');
    div.classList.add('droit-miranda-row');
    div.style.marginBottom = '10px';
    div.innerHTML = `
    <select name="droit_miranda_nom[]" required>
        <option value="">-- Choisir un droit --</option>
        <option value="Appel">Appel</option>
        <option value="Manger">Manger</option>
        <option value="Boire">Boire</option>
        <option value="EMS">EMS</option>
        <option value="Avocat">Avocat</option>
    </select>
    <input type="time" name="droit_miranda_heure[]" required>
    <button type="button" onclick="this.parentElement.remove()">🗑</button>
    `;

    container.appendChild(div);
}


    function searchMotif() {
        const query = document.getElementById('motif-search').value.trim();
        if (query.length > 1) {
            fetch(`/tablette/rapport/recherche_motif.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const motifSelect = document.getElementById('motif-select');
                    motifSelect.innerHTML = '<option value="">-- Sélectionnez un motif --</option>';
                    data.forEach(motif => {
                        const option = document.createElement('option');
                        option.value = motif.id;
                        option.textContent = motif.description;
                        option.setAttribute('data-montant', motif.montant);
                        option.setAttribute('data-peine', motif.peine);
                        option.setAttribute('data-article', motif.article);
                        option.setAttribute('data-details', motif.details);
                        motifSelect.appendChild(option);
                    });
                });
        }
    }

    function updateAmendeRetentions() {
        const selected = document.getElementById('motif-select').selectedOptions[0];
        document.getElementById('amende').value = selected.getAttribute('data-montant') || '';
        document.getElementById('retention').value = selected.getAttribute('data-peine') || '';
        document.getElementById('info-tooltip').setAttribute('title', `Article ${selected.getAttribute('data-article')}: ${selected.getAttribute('data-details')}`);
    }

    // Recherche dynamique individus
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
            document.getElementById('individu-results').style.display = 'none';
            document.getElementById('search-individu').value = '';
        }
    }

    function removeIndividu(id) {
        selectedIndividus.delete(id);
        const selectedDiv = document.querySelector(`.individu-selected[data-id="${id}"]`);
        if (selectedDiv) selectedDiv.remove();
        updateIndividusInput();
    }

    function updateIndividusInput() {
        document.getElementById('individus-input').value = Array.from(selectedIndividus).join(',');
    }

    // Recherche dynamique agents
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
