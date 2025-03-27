<?php
include '../../config.php';
include '../../includes/header.php';
include 'send_report_discord.php'; // Inclure le fichier pour envoyer le message à Discord

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit();
}

// Utiliser le surnom Discord si disponible, sinon utiliser le pseudo global
$officier_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date_arrestation = $_POST['date_arrestation'];
    $individus = explode(',', rtrim($_POST['individus'], ','));
    $agents = explode(',', rtrim($_POST['agents'], ','));
    $motif = $_POST['motif'];
    $amende = $_POST['amende'];
    $retention = $_POST['retention'];
    $rapport_text = $_POST['rapport_text'];
    $coop = $_POST['coop'];
    $miranda_time = $_POST['miranda_time'];
    $demandes_droits = $_POST['demandes_droits'];
    $heure_droits = $_POST['heure_droits'];

    // Concaténer les noms des agents impliqués pour les enregistrer dans officier_id
    $agents_concat = implode(', ', $agents);

    try {
        // Insertion du rapport dans la table `rapports`
        $stmt = $pdo->prepare("INSERT INTO rapports (date_arrestation, motif, amende, retention, rapport_text, coop, miranda_time, demandes_droits, heure_droits, officier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$date_arrestation, $motif, $amende, $retention, $rapport_text, $coop, $miranda_time, $demandes_droits, $heure_droits, $agents_concat]);

        // Récupérer l'ID du rapport nouvellement inséré
        $rapport_id = $pdo->lastInsertId();

        // Insérer les individus associés dans `rapports_individus`
        $stmt = $pdo->prepare("INSERT INTO rapports_individus (rapport_id, casier_id) VALUES (?, ?)");
        foreach ($individus as $casier_id) {
            $stmt->execute([$rapport_id, $casier_id]);
        }

        // Appel de la fonction pour envoyer le message à Discord
        sendReportToDiscord($rapport_id);

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

        <!-- Champ de recherche et sélection multiple des individus -->
        <label>Individus Impliqués :</label>
        <input type="text" id="search-individu" placeholder="Rechercher un individu...">
        <div id="individu-results" style="border: 1px solid #ddd; display: none;"></div>
        <div id="individus-selected">
            <h4>Individus Sélectionnés :</h4>
        </div>
        <input type="hidden" name="individus" id="individus-input">

        <!-- Champ de recherche et sélection multiple des agents -->
        <label>Agents Impliqués :</label>
        <input type="text" id="search-agent" placeholder="Rechercher un agent...">
        <div id="agent-results" style="border: 1px solid #ddd; display: none;"></div>
        <div id="agents-selected">
            <h4>Agents Sélectionnés :</h4>
        </div>
        <input type="hidden" name="agents" id="agents-input">

        <!-- Champ de recherche pour un motif -->
        <label>Rechercher un Motif :</label>
        <input type="text" id="motif-search" placeholder="Saisir un motif..." oninput="searchMotif()" autocomplete="off">

        <!-- Liste déroulante des motifs dynamiquement remplie -->
        <div style="display: flex; align-items: center; gap: 5px;">
            <select name="motif" id="motif-select" onchange="updateAmendeRetentions()" required style="width: 100%; margin-top: 5px;">
                <option value="">-- Sélectionnez un motif --</option>
            </select>
            <!-- Point d'interrogation pour afficher les détails du motif sélectionné -->
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

        <label>Droits Miranda lus à :</label>
        <input type="time" name="miranda_time" required>

        <label>Droits demandés :</label>
        <input type="text" name="demandes_droits" placeholder="Boire, manger, avocat..." required>

        <label>Heure des droits réalisés :</label>
        <input type="time" name="heure_droits" required>

        <button type="submit">Ajouter le Rapport</button>
    </form>
</div>

<!-- JavaScript pour la recherche de motifs, d'individus et d'agents -->
<script>
    const selectedIndividus = new Set();
    const selectedAgents = new Set();

    // Fonction de recherche de motif
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
                })
                .catch(error => console.error('Erreur de recherche de motif:', error));
        } else {
            document.getElementById('motif-select').innerHTML = '<option value="">-- Sélectionnez un motif --</option>';
        }
    }

    function updateAmendeRetentions() {
        const motifSelect = document.getElementById('motif-select');
        const selectedOption = motifSelect.options[motifSelect.selectedIndex];
        const infoTooltip = document.getElementById('info-tooltip');

        if (selectedOption.value) {
            document.getElementById('amende').value = selectedOption.getAttribute('data-montant') || '';
            document.getElementById('retention').value = selectedOption.getAttribute('data-peine') || '';

            // Mettre à jour le tooltip avec les détails du motif pour qu’il soit uniquement accessible au survol
            infoTooltip.setAttribute('title', `Article ${selectedOption.getAttribute('data-article') || 'Non spécifié'} : ${selectedOption.getAttribute('data-details') || 'Détails non spécifiés'}`);
        } else {
            document.getElementById('amende').value = '';
            document.getElementById('retention').value = '';
            infoTooltip.removeAttribute('title');
        }
    }

    // Recherche dynamique pour les individus
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
                })
                .catch(error => console.error('Erreur de recherche:', error));
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

    // Recherche dynamique pour les agents
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
                })
                .catch(error => console.error('Erreur de recherche:', error));
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
