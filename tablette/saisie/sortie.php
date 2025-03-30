<?php
session_start();
require_once '../../config.php';

$role_cs = $roles['cs'];

if (
    !isset($_SESSION['user_authenticated']) ||
    $_SESSION['user_authenticated'] !== true ||
    !hasRole($role_cs)
) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du Command staff peuvent sortir les saisies.</p>";
    exit();
}

// Récupération des objets
$stmt = $pdo->query("SELECT * FROM saisie");
$objetOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$saisiesRetirees = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saisie'])) {
    require_once 'saisie_discord.php';

    foreach ($_POST['saisies'] as $saisie) {
        if (empty($saisie['saisie_id']) || empty($saisie['quantite'])) {
            echo "<p>Erreur : saisie_id ou quantité manquants pour une saisie spécifique.</p>";
            continue;
        }

        $saisie_id = $saisie['saisie_id'];
        $quantite = $saisie['quantite'];
        $numero_serie = $saisie['numero_serie'] ?? null;

        $stmt = $pdo->prepare("SELECT nom, categorie FROM saisie WHERE id = ?");
        $stmt->execute([$saisie_id]);
        $saisieDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$saisieDetails) {
            echo "<p>Erreur : Objet avec ID $saisie_id non trouvé.</p>";
            continue;
        }

        $nom = $saisieDetails['nom'];
        $categorie = $saisieDetails['categorie'];

        if ($categorie === "Armes" && $numero_serie) {
            $stmt = $pdo->prepare("SELECT id FROM s_armes WHERE numero_serie = ? AND nom = ?");
            $stmt->execute([$numero_serie, $nom]);
            $arme = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($arme) {
                $pdo->prepare("DELETE FROM s_armes WHERE id = ?")->execute([$arme['id']]);
                $saisiesRetirees[] = ['nom' => $nom . ' (N°: ' . $numero_serie . ')', 'quantite' => 1];
            } else {
                echo "<p style='color: red;'>Erreur : Numéro de série <strong>$numero_serie</strong> non trouvé pour <strong>$nom</strong>.</p>";
                continue;
            }
        } else {
            $stmt = $pdo->prepare("UPDATE saisie SET quantite = quantite - ? WHERE id = ?");
            $stmt->execute([$quantite, $saisie_id]);

            $saisiesRetirees[] = ['nom' => $nom, 'quantite' => $quantite];

            $stmt = $pdo->prepare("DELETE FROM saisie WHERE id = ? AND quantite <= 0");
            $stmt->execute([$saisie_id]);
        }
    }

    sendSaisieRetireeToDiscord($saisiesRetirees);
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
        .numero-serie-field {
            display: none;
        }
        .serie-status {
        display: block;
        margin-top: 4px;
        font-size: 14px;
        font-weight: bold;
        padding-left: 2px;
        }

    .serie-status.valid {
        color: #4CAF50;
        }

    .serie-status.invalid {
        color: #ff4c4c;
        }
    </style>
    <script>
        const objetsDisponibles = <?= json_encode($objetOptions); ?>;

        function searchObjet(query) {
            return objetsDisponibles.filter(objet =>
                objet.nom.toLowerCase().includes(query.toLowerCase())
            );
        }

        function addSaisieField() {
            const saisieContainer = document.getElementById('saisies-container');
            const index = saisieContainer.children.length;

            const wrapper = document.createElement('div');
            wrapper.classList.add('saisie-fields');

            wrapper.innerHTML = `
                <input type="text" placeholder="Rechercher un objet..." oninput="updateSaisieDropdown(this)" required>
                <select name="saisies[${index}][saisie_id]" onchange="updateSaisieDetails(this)" required>
                    <option value="">-- Résultats de la recherche --</option>
                </select>
                <label>Quantité :</label>
                <input type="number" name="saisies[${index}][quantite]" min="1" required placeholder="Quantité">
                <div class="numero-serie-field" style="display: none;">
                    <label>Numéro de série :</label>
                    <input type="text" name="saisies[${index}][numero_serie]" placeholder="Numéro de série" oninput="checkNumeroSerie(this)">
                    <span class="serie-status"></span>
                </div>
                <button type="button" onclick="removeSaisieField(this)">-</button>
            `;

            saisieContainer.appendChild(wrapper);
        }

        function updateSaisieDropdown(inputElement) {
            const selectElement = inputElement.nextElementSibling;
            const results = searchObjet(inputElement.value);
            selectElement.innerHTML = '<option value="">-- Résultats de la recherche --</option>';
            results.forEach(objet => {
                const option = document.createElement('option');
                option.value = objet.id;
                option.textContent = objet.nom;
                option.setAttribute('data-categorie', objet.categorie);
                selectElement.appendChild(option);
            });
        }

        function updateSaisieDetails(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const categorie = selectedOption.getAttribute('data-categorie');
            const numeroSerieField = selectElement.parentElement.querySelector('.numero-serie-field');
            if (categorie === "Armes") {
                numeroSerieField.style.display = "block";
            } else {
                numeroSerieField.style.display = "none";
            }
        }

        function checkNumeroSerie(input) {
            const numero = input.value.trim();
            const statusSpan = input.nextElementSibling;
            const selectElement = input.closest('.saisie-fields').querySelector('select');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const objetNom = selectedOption.textContent.trim();

            if (!numero || !objetNom) {
                statusSpan.textContent = "";
                statusSpan.className = "serie-status";
                return;
            }

            fetch(`check_serie.php?numero=${encodeURIComponent(numero)}&objet=${encodeURIComponent(objetNom)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        statusSpan.textContent = "✅ Existe";
                        statusSpan.className = "serie-status valid";
                    } else {
                        statusSpan.textContent = "❌ Inexistant";
                        statusSpan.className = "serie-status invalid";
                    }
                })
                .catch(() => {
                    statusSpan.textContent = "⚠️ Erreur";
                    statusSpan.className = "serie-status invalid";
                });
        }


        function removeSaisieField(button) {
            button.parentElement.remove();
        }

        window.addEventListener('DOMContentLoaded', () => {
            addSaisieField(); // ajoute un champ par défaut
        });
    </script>
</head>
<body>
<?php include '../../includes/header.php'; ?>

<div class="container">
    <h2>Sortie des Saisies</h2>
    <form method="POST" action="sortie.php">
        <div id="saisies-container"></div>
        <button type="button" onclick="addSaisieField()">➕ Ajouter une saisie</button>
        <button type="submit" name="remove_saisie" class="remove-saisie-button">✅ Retirer les Saisies</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
