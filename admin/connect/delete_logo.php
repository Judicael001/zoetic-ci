<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide']);
    exit;
}

try {
    // Récupérer le logo actuel
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_image'");
    $stmt->execute();
    $logoPath = $stmt->fetchColumn();

    if ($logoPath && file_exists(__DIR__ . '/../../' . $logoPath)) {
        // Supprimer le fichier
        @unlink(__DIR__ . '/../../' . $logoPath);
    }

    // Supprimer l'entrée de la base de données
    $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = 'logo_image'");
    $stmt->execute();

    // Journalisation
    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'DELETE', ?, ?)");
    $logStmt->execute([
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        "Suppression du logo du site",
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Logo supprimé avec succès']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
