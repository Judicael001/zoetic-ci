<?php
session_start();
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    reset($_FILES);
    $file = current($_FILES);

    if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $errorMsg = 'Fichier introuvable.';
        if ($file && $file['error'] === UPLOAD_ERR_INI_SIZE) $errorMsg = 'Le fichier dépasse la limite PHP (upload_max_filesize).';
        throw new Exception($errorMsg);
    }

    // Vérification du type MIME
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
    } else {
        $mimeType = $file['type'];
    }

    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception("Format d'image non supporté ($mimeType).");
    }

    $uploadsDir = __DIR__ . '/../../uploads/logos';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // Génération d'un nom sécurisé (évite les conflits et caractères spéciaux)
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
    $fileName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = $uploadsDir . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $logoPath = 'uploads/logos/' . $fileName;

        // 1. Récupérer l'ancien logo pour le supprimer du serveur
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_image'");
        $stmt->execute();
        $oldLogo = $stmt->fetchColumn();
        
        if ($oldLogo && file_exists(__DIR__ . '/../../' . $oldLogo)) {
            if (!str_contains($oldLogo, 'default')) { // Éviter de supprimer un logo par défaut
                @unlink(__DIR__ . '/../../' . $oldLogo);
            }
        }

        // 2. Mettre à jour la base de données (Table 'settings', Clé 'logo_image')
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$logoPath]);

        // 3. Journalisation
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'UPDATE', ?, ?)");
        $logStmt->execute([
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            "Mise à jour du logo du site",
            $_SERVER['REMOTE_ADDR']
        ]);

        ob_clean();
        // On retourne l'URL correcte pour l'affichage immédiat dans l'admin (avec ../)
        echo json_encode(['status' => 'success', 'url' => '../' . $logoPath, 'message' => 'Logo mis à jour avec succès.']);
    } else {
        throw new Exception("Échec du déplacement du fichier sur le serveur.");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}