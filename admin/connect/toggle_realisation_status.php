<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';
require_once '../../src/db/realisations.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE realisations SET status = IF(status = 'published', 'draft', 'published') WHERE id = ?");
    $stmt->execute([$id]);
    log_admin_activity($pdo, 'UPDATE', "Basculement du statut de la réalisation #$id", $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? 'Admin');
    header("Location: ../realisations.php?message=" . urlencode("Statut de la réalisation mis à jour.") . "&status=success");
} else {
    header("Location: ../realisations.php?message=" . urlencode("ID invalide.") . "&status=error");
}
exit;
