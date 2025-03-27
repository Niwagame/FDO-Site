<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;

// 🔐 Config Google
$client = new Client();
$client->setAuthConfig(__DIR__ . '/credentials.json');
$client->setScopes([Docs::DOCUMENTS, Drive::DRIVE]);
$client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

// 📝 ID de ton modèle
$templateId = '13em-1m_e0oefRVwXDD1msY7HAQGPLFIu1mkwdFitSTY';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    // 🔍 Groupe sélectionné
    $stmt = $pdo->prepare("SELECT * FROM user_groups WHERE id = ?");
    $stmt->execute([$id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group) die("Groupe introuvable.");

    // 👤 Membres du groupe (avec grade)
    $stmt = $pdo->prepare("SELECT nom, prenom, grade FROM casiers WHERE affiliation = ?");
    $stmt->execute([$group['name']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 📑 Rapports
    $stmt = $pdo->prepare("
        SELECT r.date_arrestation, a.description AS motif
        FROM rapports r
        LEFT JOIN amende a ON r.motif = a.id
        WHERE r.id IN (
            SELECT rapport_id FROM rapports_individus 
            WHERE casier_id IN (SELECT id FROM casiers WHERE affiliation = ?)
        )
    ");
    $stmt->execute([$group['name']]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 📦 Saisies
    $stmt = $pdo->prepare("
        SELECT s.nom, sc.quantite
        FROM saisie_c sc
        JOIN saisie s ON sc.saisie_id = s.id
        WHERE sc.idcasier IN (SELECT id FROM casiers WHERE affiliation = ?)
    ");
    $stmt->execute([$group['name']]);
    $saisies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 📄 Copier le modèle
    $driveService = new Drive($client);
    $copy = new Drive\DriveFile(['name' => 'Rapport - ' . $group['name']]);
    $newDocument = $driveService->files->copy($templateId, $copy);
    $documentId = $newDocument->id;

    // 📂 Charger le nouveau document
    $docsService = new Docs($client);
    $document = $docsService->documents->get($documentId);
    $bodyElements = $document->getBody()->getContent();

    // 🔎 Trouver le 2e tableau (membres revendiqués)
    $tables = [];
    foreach ($bodyElements as $element) {
        if ($element->getTable()) {
            $tables[] = $element;
        }
    }

    if (count($tables) < 2) {
        die("Le tableau des membres (2e tableau) est introuvable.");
    }

    $memberTable = $tables[1];
    $tableIndex = $memberTable->getStartIndex();
    $table = $memberTable->getTable();
    $tableRows = $table->getTableRows();

    // 🛠 Replacements de texte
    $replacements = [
        '{{nom_groupe}}' => $group['name'],
        '{{type_organisation}}' => $group['type'],
        '{{liste_rapports}}' => implode("\n", array_map(
            fn($r) => "- {$r['motif']} (Date : " . date("d-m-Y", strtotime($r['date_arrestation'])) . ")",
            $reports
        )),
        '{{liste_saisies}}' => implode("\n", array_map(
            fn($s) => "- {$s['nom']} : {$s['quantite']} unités",
            $saisies
        )),
    ];

    $requests = [];

    foreach ($replacements as $placeholder => $value) {
        $requests[] = [
            'replaceAllText' => [
                'containsText' => ['text' => $placeholder, 'matchCase' => true],
                'replaceText' => $value
            ]
        ];
    }

    // ✅ Remplir le tableau des membres
    for ($i = 0; $i < count($members); $i++) {
        if (!isset($tableRows[$i + 1])) {
            // On s'arrête si on dépasse le nombre de lignes dispo
            break;
        }

        $row = $tableRows[$i + 1]; // Ligne suivante après l'en-tête
        $cells = $row->getTableCells();
        $member = $members[$i];

        // Colonne 1 : Nom
        $requests[] = [
            'insertText' => [
                'location' => ['index' => $cells[0]->getContent()[0]->getStartIndex() + 1],
                'text' => $member['nom']
            ]
        ];

        // Colonne 2 : Prénom
        $requests[] = [
            'insertText' => [
                'location' => ['index' => $cells[1]->getContent()[0]->getStartIndex() + 1],
                'text' => $member['prenom']
            ]
        ];

        // Colonne 3 : Grade
        $requests[] = [
            'insertText' => [
                'location' => ['index' => $cells[2]->getContent()[0]->getStartIndex() + 1],
                'text' => $member['grade'] ?? '-'
            ]
        ];
    }

    // 🔄 Appliquer tout
    $docsService->documents->batchUpdate($documentId, new Docs\BatchUpdateDocumentRequest([
        'requests' => $requests
    ]));

    // 🔓 Rendre le document éditable publiquement
    $permission = new Drive\Permission([
        'type' => 'anyone',
        'role' => 'writer'
    ]);
    $driveService->permissions->create($documentId, $permission);

    // 🔗 Rediriger vers le document
    header("Location: https://docs.google.com/document/d/{$documentId}/edit");
    exit;
} else {
    die("Erreur : Groupe non sélectionné.");
}
?>
