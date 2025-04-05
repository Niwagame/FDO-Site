<?php
session_start();
require_once '../../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;

// Vérification de l'authentification et des autorisations
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    echo "<p style='color:red; text-align:center;'>Accès refusé.</p>";
    exit();
}

// Récupération de l'ID de l'interrogatoire
if (!isset($_POST['interrogatoire_id'])) {
    echo "Interrogatoire non spécifié.";
    exit();
}

$interrogatoire_id = $_POST['interrogatoire_id'];

// Récupération des informations de l'interrogatoire
$stmt = $pdo->prepare("
    SELECT i.*, c.nom, c.prenom
    FROM interrogatoires i
    LEFT JOIN casiers c ON i.casier_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$interrogatoire_id]);
$interrogatoire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$interrogatoire) {
    echo "Interrogatoire introuvable.";
    exit();
}

// Configuration du client Google
$client = new Client();
$client->setAuthConfig(__DIR__ . '/../../credentials.json');
$client->setScopes([Docs::DOCUMENTS, Drive::DRIVE]);
$client->setHttpClient(new \GuzzleHttp\Client(['verify' => false])); // ✅ Désactive SSL en local

// ID du modèle Google Docs depuis config.ini
$templateId = $config['modele_gdoc']['interrogatoire'];

try {
    // Copier le modèle
    $driveService = new Drive($client);
    $copy = new Drive\DriveFile(['name' => "Interrogatoire - {$interrogatoire['prenom']} {$interrogatoire['nom']}"]);
    $newDocument = $driveService->files->copy($templateId, $copy);
    $documentId = $newDocument->id;

    // ✅ Donner les droits d'édition à tous ceux qui ont le lien
    $permission = new Drive\Permission([
        'type' => 'anyone',            // Tout le monde
        'role' => 'writer'             // Peut modifier
    ]);

    $driveService->permissions->create(
        $documentId,
        $permission,
        ['fields' => 'id']
    );

    // Préparer les remplacements
    $replacements = [
        '{{prenom}}' => $interrogatoire['prenom'],
        '{{nom}}' => $interrogatoire['nom'],
        '{{date}}' => date("d/m/Y", strtotime($interrogatoire['created_at'])),
        '{{agent}}' => $interrogatoire['agent_id'],
        '{{deposition}}' => $interrogatoire['deposition'],
        '{{analyse}}' => $interrogatoire['analyse'],
        '{{hypotheses}}' => $interrogatoire['hypotheses'],
        '{{faits_importants}}' => $interrogatoire['faits_importants'],
        '{{questions_posees}}' => $interrogatoire['questions_posees'],
        '{{reponses}}' => $interrogatoire['reponses'],
        '{{infos_complementaires}}' => $interrogatoire['infos_complementaires'],
    ];

    // Remplacer les variables dans le document
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

    // Récupérer le lien du document
    $documentLink = "https://docs.google.com/document/d/{$documentId}/edit";

    // Mettre à jour la base de données avec le lien du document
    $stmt = $pdo->prepare("UPDATE interrogatoires SET lien_document = ? WHERE id = ?");
    $stmt->execute([$documentLink, $interrogatoire_id]);

    // Rediriger vers la page de détails de l'interrogatoire
    header("Location: details.php?id={$interrogatoire_id}");
    exit();

} catch (Exception $e) {
    error_log("Erreur lors de l'exportation : " . $e->getMessage());
    echo "<p>❌ Une erreur est survenue lors de l'exportation :</p>";
    echo "<pre>" . $e->getMessage() . "</pre>"; // ➕ Ajoute ça
}

?>
