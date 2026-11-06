<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

$ids = $_POST['ids'] ?? [];
$action = $_POST['action'] ?? '';

if (!empty($ids) && is_array($ids) && in_array($action, ['approve', 'spam', 'delete'])) {
    // Nettoyage des IDs
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    try {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("UPDATE blog_comments SET deleted_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = count($ids) . " commentaire(s) supprimé(s).";
        } else {
            $status = ($action === 'approve') ? 'approved' : 'spam';
            $stmt = $pdo->prepare("UPDATE blog_comments SET status = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));
            $msg = count($ids) . " commentaire(s) mis à jour.";
        }
        $status_msg = "success";
    } catch (PDOException $e) {
        error_log("Bulk Action Error: " . $e->getMessage());
        $msg = "Erreur lors de l'action groupée.";
        $status_msg = "error";
    }
} else {
    $msg = "Aucune sélection ou action invalide.";
    $status_msg = "error";
}

header("Location: ../comments.php?message=" . urlencode($msg) . "&status=" . $status_msg);
exit;