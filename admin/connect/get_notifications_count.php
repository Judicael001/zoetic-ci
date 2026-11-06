<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json');

try {
    // On récupère le nombre d'éléments en attente/non lus
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM contacts WHERE is_read = 0) as unread_messages,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'pending') as pending_enrollments,
            (SELECT COUNT(*) FROM blog_comments WHERE status = 'pending' AND deleted_at IS NULL) as pending_comments
    ");
    $stats = $stmt->fetch();

    $total = (int)$stats['unread_messages'] + (int)$stats['pending_enrollments'] + (int)$stats['pending_comments'];

    echo json_encode(['status' => 'success', 'total' => $total]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la récupération des notifications.']);
}