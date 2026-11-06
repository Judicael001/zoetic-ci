<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';
require_once '../../src/db/realisations.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE realisations SET is_featured = NOT is_featured WHERE id = ?");
    $stmt->execute([$id]);
    log_admin_activity($pdo, 'UPDATE', "Modification de la mise en avant de la réalisation #$id", $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? 'Admin');
    header("Location: ../realisations.php?message=" . urlencode("Mise en avant mise à jour.") . "&status=success");
} else {
    header("Location: ../realisations.php?message=" . urlencode("ID invalide.") . "&status=error");
}
exit;
