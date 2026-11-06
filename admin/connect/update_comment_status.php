<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = $_GET['status'] ?? '';

if ($id <= 0 || !in_array($status, ['pending', 'approved', 'spam'])) {
    echo json_encode(['status' => 'error', 'message' => 'Paramètres invalides.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE blog_comments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données.']);
}