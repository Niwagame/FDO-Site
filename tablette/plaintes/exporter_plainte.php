<?php
session_start();
require_once '../../config.php';
require_once __DIR__ . '/../../bci/vendor/autoload.php';

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;

// Auth check
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    exit("Accès refusé.");
}

if (!isset($_POST['plainte_id'])) {
    exit("Aucune plainte spécifiée.");
}

$plainte_id = $_POST['plainte_id'];

// 📥 Récupération de la plainte
$stmt = $pdo->prepare("
    SELECT p.*, 
           pl.nom AS plaignant_nom, pl.prenom AS plaignant_prenom,
           vi.nom AS visee_nom, vi.prenom AS visee_prenom
    FROM plaintes p
    LEFT JOIN casiers pl ON p.plaignant_id = pl.id
    LEFT JOIN casiers vi ON p.personne_visee_id = vi.id
    WHERE p.id = ?
");
$stmt->execute([$plainte_id]);
$plainte = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$plainte) exit("Plainte introuvable.");

// 🔐 Config Google API
$client = new Client();
$client->setAuthConfig(__DIR__ . '/../../credentials.json');
$client->setScopes([Docs::DOCUMENTS, Drive::DRIVE]);
$client->setHttpClient(new \GuzzleHttp\Client(['verify' => false])); // Désactiver SSL temporairement

// 📄 ID du modèle
$templateId = $config['modele_gdoc']['plainte'] ?? null;

try {
    $drive = new Drive($client);
    $copy = new Drive\DriveFile(['name' => "Plainte - {$plainte['plaignant_nom']} {$plainte['plaignant_prenom']}"]);
    $newDoc = $drive->files->copy($templateId, $copy);
    $docId = $newDoc->id;

    // ➕ Ajout des droits d'édition publics
    $drive->permissions->create($docId, new Drive\Permission([
        'type' => 'anyone',
        'role' => 'writer'
    ]));
    
    $replacements = [
        '{{nom_plaignant}}' => $plainte['plaignant_nom'] . ' ' . $plainte['plaignant_prenom'],
        '{{sexe_plaignant}}' => $plainte['sexe_plaignant'],
        '{{num_tel_plaignant}}' => $plainte['num_tel_plaignant'],
        '{{nom_visee}}' => trim(($plainte['visee_nom'] ?? '') . ' ' . ($plainte['visee_prenom'] ?? '')) ?: 'Aucun',
        '{{sexe_visee}}' => $plainte['sexe_visee'] ?? 'Aucun',
        '{{num_tel_visee}}' => $plainte['num_tel_visee'] ?? 'Aucun',
        '{{description_physique}}' => $plainte['description_physique'],
        '{{motif}}' => $plainte['motif_texte'],
        '{{agent}}' => $plainte['agent_id'],
        '{{date}}' => date("d/m/Y à H\hi", strtotime($plainte['date_creation']))
    ];

    $docs = new Docs($client);
    $requests = [];
    foreach ($replacements as $key => $val) {
        $requests[] = [
            'replaceAllText' => [
                'containsText' => ['text' => $key, 'matchCase' => true],
                'replaceText' => $val
            ]
        ];
    }

    $docs->documents->batchUpdate($docId, new Docs\BatchUpdateDocumentRequest(['requests' => $requests]));

    $docLink = "https://docs.google.com/document/d/$docId/edit";

    // 🔗 Sauvegarde du lien
    $stmt = $pdo->prepare("UPDATE plaintes SET lien_document = ? WHERE id = ?");
    $stmt->execute([$docLink, $plainte_id]);

    header("Location: details.php?id=$plainte_id");
    exit();

} catch (Exception $e) {
    error_log("❌ Export erreur : " . $e->getMessage());
    exit("❌ Une erreur est survenue : " . $e->getMessage());
}
