<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';
require_once '../../src/db/realisations.php';

$mediaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$realisationId = isset($_GET['realisation_id']) ? (int)$_GET['realisation_id'] : 0;

if ($mediaId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM realisation_media WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($media) {
        $realisationId = $realisationId ?: (int)$media['realisation_id'];
        
        // Supprimer le fichier sur le disque s'il existe
        $filePath = __DIR__ . '/../../' . $media['file_path'];
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }

        $delStmt = $pdo->prepare("DELETE FROM realisation_media WHERE id = ?");
        $delStmt->execute([$mediaId]);

        log_admin_activity($pdo, 'DELETE', "Suppression du média #$mediaId de la réalisation #$realisationId", $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? 'Admin');

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header("Location: ../realisation_form.php?id=$realisationId&message=" . urlencode("Média supprimé avec succès.") . "&status=success");
        exit;
    }
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Média introuvable']);
    exit;
}

header("Location: ../realisations.php?message=" . urlencode("Média introuvable.") . "&status=error");
exit;
