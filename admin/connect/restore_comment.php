<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE blog_comments SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la restauration.']);
}