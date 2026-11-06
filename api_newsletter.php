<?php
require_once 'src/db/init.php';
$email = $_POST['email'] ?? '';

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
        $stmt->execute([$email]);

        // Log new newsletter subscription for admin notification
        try {
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([
                0, // System or visitor action
                'VISITOR',
                'NEW_NEWSLETTER_SUB',
                "Nouvel abonnement à la newsletter par " . $email,
                $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
            ]);
        } catch (Throwable $logError) {
            error_log('api_newsletter.php log failed: ' . $logError->getMessage());
        }
        echo json_encode(['status' => 'success', 'message' => 'Inscription réussie !']);
    } catch (Exception $e) {
        // Email déjà existant
        echo json_encode(['status' => 'info', 'message' => 'Vous êtes déjà inscrit.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email invalide.']);
}