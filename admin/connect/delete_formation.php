<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM formations WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Formation supprimée avec succès.";
        $status = "success";
    } catch (PDOException $e) {
        $msg = "Erreur : Cette formation est probablement liée à des inscriptions.";
        $status = "error";
    }
} else {
    $msg = "Identifiant de formation invalide.";
    $status = "error";
}

header("Location: ../formations.php?message=" . urlencode($msg) . "&status=" . $status);
exit;