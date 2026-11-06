<?php
session_start();

// Vérification de l'authentification
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../../src/db/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
    if (!isset($_FILES['logo_file'])) {
        throw new Exception('Aucun fichier téléchargé');
    }

    if ($_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Erreur lors du téléchargement';
        if ($_FILES['logo_file']['error'] === UPLOAD_ERR_INI_SIZE) {
            $errorMsg = 'Le fichier dépasse la limite du serveur';
        } elseif ($_FILES['logo_file']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errorMsg = 'Le fichier dépasse la limite du formulaire';
        }
        throw new Exception($errorMsg);
    }

    $file = $_FILES['logo_file'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxSize) {
        throw new Exception('La taille du fichier dépasse 5MB');
    }

    if ($file['size'] === 0) {
        throw new Exception('Le fichier est vide');
    }

    // Vérifier que c'est bien une image avec finfo
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Le fichier n\'est pas une image valide (MIME: ' . $mimeType . ')');
        }
    } else {
        // Fallback sur $_FILES['type'] si finfo n'est pas disponible
        if (!in_array($file['type'], $allowedMimes)) {
            throw new Exception('Format de fichier non autorisé. Formats acceptés: JPEG, PNG, WebP, SVG');
        }
    }

    // Créer le dossier s'il n'existe pas
    $uploadDir = __DIR__ . '/../../uploads/logos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier sécurisé
    $filename = generate_secure_filename($file['name']);
    $filepath = $uploadDir . '/' . $filename;

    // Optimiser et sauvegarder le fichier (Max 500px pour un logo)
    if (!process_image_upload($file['tmp_name'], $filepath, 500, 85)) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }

    // Récupérer l'ancien logo pour le supprimer
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_image'");
    $stmt->execute();
    $oldLogo = $stmt->fetchColumn();

    if ($oldLogo && file_exists(__DIR__ . '/../../' . $oldLogo)) {
        @unlink(__DIR__ . '/../../' . $oldLogo);
    }

    // Sauvegarder le chemin dans la base de données
    $logoPath = 'uploads/logos/' . $filename;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$logoPath]);

    // Génération automatique des favicons à partir du nouveau logo
    if (function_exists('generate_favicon_from_image')) {
        generate_favicon_from_image($filepath);
    }

    // Journalisation
    $adminId = $_SESSION['admin_id'] ?? 0;
    $adminName = $_SESSION['admin_name'] ?? 'Admin/Système';

    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'UPDATE', ?, ?)");
    $logStmt->execute([
        $adminId,
        $adminName,
        "Mise à jour du logo du site",
        $_SERVER['REMOTE_ADDR']
    ]);

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Logo téléchargé avec succès',
        'logo_path' => $logoPath,
        'url' => '../' . $logoPath
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
