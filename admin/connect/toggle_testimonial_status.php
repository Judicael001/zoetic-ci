<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE testimonials SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../testimonials.php?message=" . urlencode("Statut mis à jour.") . "&status=success");
} else {
    header("Location: ../testimonials.php?message=" . urlencode("ID invalide.") . "&status=error");
}
exit;