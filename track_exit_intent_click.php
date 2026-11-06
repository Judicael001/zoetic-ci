<?php
require_once __DIR__ . '/src/db/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formation = $_POST['formation'] ?? 'Inconnue';
    $slug = $_POST['slug'] ?? 'Inconnu';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            0, 
            'VISITOR', 
            'EXIT_INTENT_CLICK', 
            "Conversion modale de sortie : l'utilisateur a cliqué sur la formation '$formation' depuis l'article '$slug'", 
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        error_log("Tracking Error: " . $e->getMessage());
    }
}