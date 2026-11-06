<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json');

$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    echo json_encode(['status' => 'error', 'message' => 'Session ID manquant.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_message, bot_reply, created_at FROM chatbot_logs WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$sessionId]);
    $transcript = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'transcript' => $transcript]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données.']);
}