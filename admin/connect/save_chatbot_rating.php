<?php
session_start();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json; charset=utf-8');

// Récupération des paramètres
$messageId = (int)($_POST['message_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0); // 1-5 ou null
$helpful = $_POST['helpful'] ?? null; // 'yes', 'no', null
$feedback = trim($_POST['feedback'] ?? '');

if ($messageId <= 0 || ($rating < 1 || $rating > 5)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE chatbot_logs 
        SET rating = ?, helpful_feedback = ? 
        WHERE id = ? 
        LIMIT 1
    ");
    
    $helpfulValue = $helpful === 'yes' ? 1 : ($helpful === 'no' ? 0 : null);
    
    $stmt->execute([
        $rating,
        $helpfulValue,
        $messageId
    ]);
    
    // Si un feedback texte est fourni, le sauvegarder dans un log séparé
    if (!empty($feedback)) {
        $feedbackStmt = $pdo->prepare("
            INSERT INTO chatbot_feedback (message_id, feedback_text, created_at) 
            VALUES (?, ?, NOW())
        ");
        try {
            $feedbackStmt->execute([$messageId, mb_substr($feedback, 0, 1000)]);
        } catch (Exception $e) {
            // Table peut ne pas exister, c'est OK
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Évaluation sauvegardée. Merci de votre retour !'
    ]);
    
} catch (Exception $e) {
    error_log("Chatbot Rating Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la sauvegarde'
    ]);
}