<?php
session_start();
require_once '../../config.php';
include '../../includes/header.php';
require_once 'rapport_discord.php';

$role_bco = $roles['bcso'];

if (
    !isset($_SESSION['user_authenticated']) || 
    $_SESSION['user_authenticated'] !== true || 
    !hasRole($role_bco)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO peuvent ajouter un rapport.</p>";
    exit();
}

$officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_arrestation = $_POST['date_arrestation'];
    $individus = explode(',', rtrim($_POST['individus'], ','));
    $agents = explode(',', rtrim($_POST['agents'], ','));
    $motif = $_POST['motif'];
    $amende = $_POST['amende'];
    $retention = $_POST['retention'];
    $rapport_text = $_POST['rapport_text'];
    $coop = $_POST['coop'];
    $heure_privation_liberte = $_POST['heure_privation_liberte'];
    $miranda_time = $_POST['miranda_time'];
    $agents_concat = implode(', ', $agents);

    try {
        $stmt = $pdo->prepare("INSERT INTO rapports (date_arrestation, motif, amende, retention, rapport_text, coop, heure_privation_liberte, miranda_time, officier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$date_arrestation, $motif, $amende, $retention, $rapport_text, $coop, $heure_privation_liberte, $miranda_time, $agents_concat]);        

        $rapport_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO rapports_individus (rapport_id, casier_id) VALUES (?, ?)");
        foreach ($individus as $casier_id) {
            $stmt->execute([$rapport_id, $casier_id]);
        }

        // ✅ Insérer les droits Miranda un à un (réparé)
        if (!empty($_POST['droit_miranda_nom']) && !empty($_POST['droit_miranda_heure'])) {
            $stmt = $pdo->prepare("INSERT INTO droit_miranda (rapport_id, droit, heure_droit) VALUES (?, ?, ?)");

            foreach ($_POST['droit_miranda_nom'] as $index => $droit_nom) {
                $droit_heure = $_POST['droit_miranda_heure'][$index] ?? null;

                if (!empty($droit_nom) && !empty($droit_heure)) {
                    $stmt->execute([$rapport_id, $droit_nom, $droit_heure]);
                }
            }
        }

        sendReportCreationToDiscord($rapport_id);
        echo "<p>Rapport d'arrestation ajouté avec succès !</p>";
    } catch (PDOException $e) {
        echo "<p>Erreur lors de l'ajout du rapport : " . $e->getMessage() . "</p>";
    }
}
?>

<div class="container">
    <link rel="stylesheet" href="../../css/styles.css">
    <h2>Ajouter un Rapport d'Arrestation</h2>
    <form action="ajout.php" method="POST" id="form-rapport">
        <label>Date d'Arrestation :</label>
        <input type="date" name="date_arrestation" required>

        <label>Individus Impliqués :</label>
        <input type="text" id="search-individu" placeholder="Rechercher un individu...">
        <div id="individu-results" style="border: 1px solid #ddd; display: none;"></div>
        <div id="individus-selected"><h4>Individus Sélectionnés :</h4></div>
        <input type="hidden" name="individus" id="individus-input">

        <label>Agents Impliqués :</label>
        <input type="text" id="search-agent" placeholder="Rechercher un agent...">
        <div id="agent-results" style="border: 1px solid #ddd; display: none;"></div>
        <div id="agents-selected"><h4>Agents Sélectionnés :</h4></div>
        <input type="hidden" name="agents" id="agents-input">

        <label>Rechercher un Motif :</label>
        <input type="text" id="motif-search" placeholder="Saisir un motif..." oninput="searchMotif()" autocomplete="off">

        <div style="display: flex; align-items: center; gap: 5px;">
            <select name="motif" id="motif-select" onchange="updateAmendeRetentions()" required style="width: 100%; margin-top: 5px;">
                <option value="">-- Sélectionnez un motif --</option>
            </select>
            <span id="info-tooltip" style="cursor: pointer; font-size: 20px; color: red;" title="">❓</span>
        </div>

        <label>Amende :</label>
        <input type="number" name="amende" id="amende" placeholder="Montant de l'amende" required>

        <label>Peine :</label>
        <input type="text" name="retention" id="retention" placeholder="Durée de rétention" required>

        <label>Rapport d'Arrestation :</label>
        <textarea name="rapport_text" placeholder="Détails du rapport" required></textarea>

        <label>Individu Coopératif (0 à 10) :</label>
        <input type="number" name="coop" min="0" max="10" required>

        <label>Heure de privation de liberté :</label>
        <input type="time" name="heure_privation_liberte" required>

        <label>Droit Miranda lu à :</label>
        <input type="time" name="miranda_time" required>

        <div id="droit-miranda-wrapper">
            <div id="droit-label" style="display: none; margin-top: 10px;">
                <label>Droits demandés :</label>
            </div>
            <div id="droit-miranda-container"></div>
            <button type="button" class="btn-ajout" onclick="addDroitMiranda()">+ Ajouter un droit</button>
        </div>

        <div style="text-align: right; margin-top: 20px;">
            <button type="submit" class="btn-principal">Ajouter le Rapport</button>
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
                            const display = `${agent.matricule} | ${agent.prenom} ${agent.nom}`;
                            if (!selectedAgents.has(display)) {
                                const div = document.createElement('div');
                                div.classList.add('agent-result');
                                div.innerHTML = `
                                    <span>${display}</span>
                                    <button type="button" onclick="addAgent('${display}')">+</button>
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
