<?php
include '../config.php';
include '../includes/header.php';

$role_bco = $roles['bco'];
$role_doj = $roles['doj'];

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true || !hasRole($role_bco) && !hasRole($role_doj)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé : seuls les membres du BCSO ou du DOJ peuvent accéder à cette page.</p>";
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$current_date = date('Y-m-d');
$current_time = date('H:i:s');

$convocations = [];

try {
    $stmt = $pdo->query("SELECT * FROM convocations ORDER BY date_convocation, heure_convocation");
    $convocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p>❌ Erreur lors de la récupération des convocations.</p>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $pdo->prepare("SELECT convoque FROM convocations WHERE id = ?");
        $stmt->execute([$id]);
        $convocation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($convocation && !empty($convocation['convoque'])) {
            $pdfPath = __DIR__ . "/../" . $convocation['convoque'];

            if (file_exists($pdfPath) && is_writable($pdfPath)) {
                unlink($pdfPath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM convocations WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: liste_convocations.php");
        exit();

    } catch (Exception $e) {
        echo "<p>❌ Une erreur est survenue.</p>";
    }
}
?>

<div class="container">
    <link rel="stylesheet" href="../css/styles.css">
    <h2>Liste des Convocations</h2>

    <a href="ajout_convocation.php" class="btn action-button">Ajouter une Convocation</a>
    
    <table border="1">
        <tr>
            <th>Nom</th>
            <th>Motif</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Agent</th>
            <th>Convocation</th>
            <th>Statut</th>
            <th>Action</th>
        </tr>

        <?php if (!empty($convocations)): ?>
            <?php foreach ($convocations as $convocation): ?>
                <tr>
                    <td><?= htmlspecialchars($convocation['nom']); ?></td>
                    <td><?= htmlspecialchars($convocation['motif']); ?></td>
                    <td><?= date("d/m/Y", strtotime($convocation['date_convocation'])); ?></td>
                    <td><?= htmlspecialchars($convocation['heure_convocation']); ?></td>
                    <td><?= htmlspecialchars($convocation['agent']); ?></td>

                    <td>
                        <?php if (!empty($convocation['convoque'])): ?>
                            <a href="../<?= htmlspecialchars($convocation['convoque']); ?>" target="_blank">Télécharger</a>
                        <?php else: ?>
                            Non disponible
                        <?php endif; ?>
                    </td>

                    <td class="<?php
                        if ($convocation['est_venu']) {
                            echo "status-present";
                        } elseif ($convocation['date_convocation'] < $current_date || 
                                 ($convocation['date_convocation'] == $current_date && $convocation['heure_convocation'] < $current_time)) {
                            echo "status-passed";
                        } else {
                            echo "status-upcoming";
                        }
                    ?>">
                        <?= $convocation['est_venu'] ? "Présent" : (($convocation['date_convocation'] < $current_date || 
                            ($convocation['date_convocation'] == $current_date && $convocation['heure_convocation'] < $current_time)) 
                            ? "Convocation dépassée" : "À venir"); ?>
                    </td>

                    <td>
                        <?php if (!$convocation['est_venu']): ?>
                            <form action="liste_convocations.php" method="POST">
                                <input type="hidden" name="id" value="<?= $convocation['id']; ?>">
                                <button type="submit" class="btn action-button">Marquer comme présent</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Aucune convocation trouvée.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
