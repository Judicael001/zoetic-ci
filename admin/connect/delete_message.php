<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Message supprimé avec succès.";
        $status = "success";
    } catch (PDOException $e) {
        $msg = "Erreur lors de la suppression du message.";
        $status = "error";
    }
} else {
    $msg = "Identifiant de message invalide.";
    $status = "error";
}

header("Location: ../messages.php?message_status=" . urlencode($msg) . "&status=" . $status);
exit;