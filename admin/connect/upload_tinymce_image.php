<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

// TinyMCE s'attend à recevoir une réponse JSON
header('Content-Type: application/json');

reset($_FILES);
$temp = current($_FILES);

if (!$temp || !isset($temp['tmp_name']) || !is_uploaded_file($temp['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier détecté ou erreur de transfert.']);
    exit;
}

if (is_uploaded_file($temp['tmp_name'])) {
    // Vérification de l'extension
    if (!preg_match("/\.(gif|jpe?g|png|webp)$/i", $temp['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Format d\'image non supporté (JPG, PNG, WEBP, GIF uniquement).']);
        exit;
    }

    // Vérification du type MIME réel pour plus de sécurité
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($temp['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type de fichier invalide.']);
        exit;
    }

    $uploadsDir = __DIR__ . '/../../uploads/tinymce';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // Génération d'un nom sécurisé
    $fileName = generate_secure_filename($temp['name']);
    $destination = $uploadsDir . '/' . $fileName;

    if (move_uploaded_file($temp['tmp_name'], $destination)) {
        // Appliquer le filigrane automatique configuré dans init.php
        apply_watermark($destination);

        // On retourne le chemin relatif par rapport à la racine du site
        // TinyMCE s'occupera de l'afficher dans l'éditeur
        $location = 'uploads/tinymce/' . $fileName;
        echo json_encode(['location' => $location]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Échec de l\'enregistrement sur le serveur.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier détecté.']);
}