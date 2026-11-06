<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$ids = $_POST['ids'] ?? [];
$action = $_POST['action'] ?? '';

if (empty($ids) || !is_array($ids)) {
    header("Location: ../messages.php?message_status=" . urlencode("Aucun message sélectionné.") . "&status=error");
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $msg = count($ids) . " message(s) supprimé(s).";
    } elseif ($action === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $msg = count($ids) . " message(s) marqué(s) comme lu(s).";
    }
    $status = "success";
} catch (PDOException $e) {
    $msg = "Erreur lors de l'opération groupée.";
    $status = "error";
}

header("Location: ../messages.php?message_status=" . urlencode($msg) . "&status=" . $status);
exit;