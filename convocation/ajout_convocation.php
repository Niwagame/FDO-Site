<?php
include '../config.php';
include '../includes/header.php';
require_once __DIR__ . '/../bci/vendor/autoload.php';

// Récupère l'ID du rôle BCSO depuis config.ini
$role_bco = $roles['bco'] ?? null;

// Vérifie l'authentification et l'autorisation BCSO
if (
    !isset($_SESSION['user_authenticated']) ||
    $_SESSION['user_authenticated'] !== true ||
    !hasRole($role_bco)
) {
    echo "<p style='color:red; text-align:center;'>Accès refusé : seuls les membres du BCSO peuvent ajouter un casier.</p>";
    exit();
}

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;

// 🔐 Configuration Google Client
$client = new Client();
$client->setAuthConfig(__DIR__ . '/../bci/credentials.json');
$client->setScopes([Docs::DOCUMENTS, Drive::DRIVE]);
$client->setHttpClient(new \GuzzleHttp\Client(['verify' => false])); // Désactiver SSL temporairement

// 🔑 ID du modèle Google Docs
$templateId = '13QS4yUV4gsvRfj-ifX97lImCW0SULEGx_A1mMllfQEY';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'];
    $date_convocation = date("Y-m-d", strtotime($_POST['date_convocation']));
    $heure_convocation = $_POST['heure_convocation'];
    $motif = $_POST['motif'];

    try {
        // 📥 Enregistrer la convocation dans la BDD
        $agent_id = $_SESSION['discord_nickname'] ?? $_SESSION['discord_username'] ?? 'Inconnu';

        $stmt = $pdo->prepare("INSERT INTO convocations (nom, date_convocation, heure_convocation, motif, agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $date_convocation, $heure_convocation, $motif, $agent_id]);        
        $convocationId = $pdo->lastInsertId();

        // 🚀 Copier le modèle Google Docs
        $driveService = new Drive($client);
        $copy = new Drive\DriveFile(['name' => "Convocation - $nom"]);
        $newDocument = $driveService->files->copy($templateId, $copy);
        $documentId = $newDocument->id;

        // 🔥 Remplacement des variables
        $replacements = [
            '{{nom}}' => $nom,
            '{{Date}}' => date("d/m/Y", strtotime($date_convocation)),
            '{{Heure}}' => $heure_convocation,
            '{{Motif}}' => $motif
        ];

        $docsService = new Docs($client);
        $requests = [];
        foreach ($replacements as $placeholder => $value) {
            $requests[] = [
                'replaceAllText' => [
                    'containsText' => ['text' => $placeholder, 'matchCase' => true],
                    'replaceText' => $value
                ]
            ];
        }

        $docsService->documents->batchUpdate($documentId, new Docs\BatchUpdateDocumentRequest([
            'requests' => $requests
        ]));

        // 📂 Vérifier et créer le dossier si nécessaire
        $directoryPath = __DIR__ . "/../assets/convocation/";
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }

        // 📥 Télécharger le Google Doc en PDF
        $pdfFileName = "convocation_" . preg_replace("/[^a-zA-Z0-9_-]/", "_", $nom) . ".pdf";
        $pdfPath = "assets/convocation/" . $pdfFileName;

        $exportedFile = $driveService->files->export($documentId, 'application/pdf', ['alt' => 'media']);
        file_put_contents(__DIR__ . "/../" . $pdfPath, $exportedFile->getBody()->getContents());

        // 📝 Mettre à jour la convocation avec le chemin du PDF
        $stmt = $pdo->prepare("UPDATE convocations SET convoque = ? WHERE id = ?");
        $stmt->execute([$pdfPath, $convocationId]);

        // 🚀 Redirection immédiate vers la liste des convocations
        header("Location: liste_convocations.php");
        exit();

    } catch (Exception $e) {
        // 🔴 Log des erreurs + affichage en cas de problème
        error_log("❌ Erreur : " . $e->getMessage());
        die("<p>❌ Une erreur est survenue : " . $e->getMessage() . "</p>");
    }
}
?>

<div class="container">
    <h2>Ajouter une Convocation</h2>
    <form action="ajout_convocation.php" method="POST">
        <label>Nom :</label>
        <input type="text" name="nom" required>

        <label>Motif :</label>
        <input type="text" name="motif" required>

        <label>Date :</label>
        <input type="date" name="date_convocation" required>

        <label>Heure :</label>
        <input type="time" name="heure_convocation" required>

        <button type="submit">Ajouter</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
