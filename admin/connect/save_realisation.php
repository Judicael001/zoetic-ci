<?php
session_start();
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';
require_once '../../src/db/realisations.php';

ensure_realisations_tables($pdo);

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = trim($_POST['title'] ?? '');
$categorySelect = trim($_POST['category_select'] ?? '');
$categoryCustom = trim($_POST['category_custom'] ?? '');
$category = !empty($categoryCustom) ? $categoryCustom : $categorySelect;

$clientName = trim($_POST['client_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$completionDate = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
$shortDescription = trim($_POST['short_description'] ?? '');
$description = trim($_POST['description'] ?? '');

$clientQuote = trim($_POST['client_quote'] ?? '');
$testimonialAuthor = trim($_POST['testimonial_author'] ?? '');
$testimonialType = in_array($_POST['testimonial_type'] ?? '', ['none', 'video', 'audio'], true) ? $_POST['testimonial_type'] : 'none';
$testimonialUrlExternal = trim($_POST['testimonial_url_external'] ?? '');

$status = (isset($_POST['status']) && $_POST['status'] === 'draft') ? 'draft' : 'published';
$isFeatured = isset($_POST['is_featured']) && $_POST['is_featured'] == '1' ? 1 : 0;
$displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;

$errors = [];
if (empty($title)) {
    $errors[] = "Le titre de la réalisation est requis.";
}
if (empty($category)) {
    $errors[] = "La catégorie de service est requise.";
}
if (empty($description)) {
    $errors[] = "La description de l'intervention est requise.";
}

if (!empty($errors)) {
    $targetUrl = $id > 0 ? "../realisation_form.php?id=$id&" : "../realisation_form.php?";
    header("Location: " . $targetUrl . "message=" . urlencode(implode(' ', $errors)) . "&status=error");
    exit;
}

$baseDir = __DIR__ . '/../../';
$photosDir = $baseDir . 'uploads/realisations/photos/';
$videosDir = $baseDir . 'uploads/realisations/videos/';
$audiosDir = $baseDir . 'uploads/realisations/audios/';

if (!is_dir($photosDir)) mkdir($photosDir, 0755, true);
if (!is_dir($videosDir)) mkdir($videosDir, 0755, true);
if (!is_dir($audiosDir)) mkdir($audiosDir, 0755, true);

// 1. Récupération des données existantes si mise à jour
$existing = null;
if ($id > 0) {
    $existing = get_realisation_by_id($pdo, $id);
}

$coverImagePath = $existing['cover_image'] ?? null;
$testimonialMediaUrl = $existing['testimonial_media_url'] ?? null;

// 2. Gestion de l'image de couverture
if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['cover_image_file'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    if (isset($allowedMimes[$mime])) {
        $ext = $allowedMimes[$mime];
        $filename = 'cover_' . bin2hex(random_bytes(12)) . '.' . $ext;
        $dest = $photosDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Supprimer l'ancienne image locale si existante
            if ($coverImagePath && !filter_var($coverImagePath, FILTER_VALIDATE_URL)) {
                $oldPath = $baseDir . ltrim($coverImagePath, '/');
                if (file_exists($oldPath) && is_file($oldPath)) @unlink($oldPath);
            }
            $coverImagePath = 'uploads/realisations/photos/' . $filename;
        }
    }
} elseif (!empty($_POST['cover_image_url'])) {
    $coverImagePath = trim($_POST['cover_image_url']);
}

// 3. Gestion du média de témoignage client (Vidéo ou Audio)
if ($testimonialType === 'none') {
    $testimonialMediaUrl = null;
} else {
    // Si un fichier est uploadé
    if (isset($_FILES['testimonial_file']) && $_FILES['testimonial_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['testimonial_file'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($testimonialType === 'video') {
            $allowedVideo = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-matroska' => 'mkv'];
            if (isset($allowedVideo[$mime])) {
                $ext = $allowedVideo[$mime];
                $filename = 'testimonial_' . bin2hex(random_bytes(12)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $videosDir . $filename)) {
                    $testimonialMediaUrl = 'uploads/realisations/videos/' . $filename;
                }
            }
        } elseif ($testimonialType === 'audio') {
            $allowedAudio = [
                'audio/mpeg' => 'mp3',
                'audio/mp3' => 'mp3',
                'audio/wav' => 'wav',
                'audio/x-wav' => 'wav',
                'audio/ogg' => 'ogg',
                'audio/aac' => 'aac',
                'audio/x-m4a' => 'm4a',
                'audio/mp4' => 'm4a',
                'video/webm' => 'webm'
            ];
            if (isset($allowedAudio[$mime]) || strpos($mime, 'audio/') === 0) {
                $ext = $allowedAudio[$mime] ?? 'mp3';
                $filename = 'audio_' . bin2hex(random_bytes(12)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $audiosDir . $filename)) {
                    $testimonialMediaUrl = 'uploads/realisations/audios/' . $filename;
                }
            }
        }
    } elseif (!empty($testimonialUrlExternal)) {
        $testimonialMediaUrl = $testimonialUrlExternal;
    }
}

// 4. Génération du slug unique
$baseSlug = slugify_realisation($title);
$slug = get_unique_realisation_slug($pdo, $baseSlug, $id ?: null);

// 5. Sauvegarde en Base de Données
try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE realisations SET 
            title = ?, slug = ?, category = ?, client_name = ?, location = ?, 
            completion_date = ?, short_description = ?, description = ?, 
            cover_image = ?, client_quote = ?, testimonial_type = ?, 
            testimonial_media_url = ?, testimonial_author = ?, status = ?, 
            is_featured = ?, display_order = ? 
            WHERE id = ?");
        $stmt->execute([
            $title, $slug, $category, $clientName, $location,
            $completionDate, $shortDescription, $description,
            $coverImagePath, $clientQuote, $testimonialType,
            $testimonialMediaUrl, $testimonialAuthor, $status,
            $isFeatured, $displayOrder, $id
        ]);
        $realisationId = $id;
        log_admin_activity($pdo, 'UPDATE', "Mise à jour de la réalisation '$title' (ID #$id)", $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? 'Admin');
    } else {
        $stmt = $pdo->prepare("INSERT INTO realisations 
            (title, slug, category, client_name, location, completion_date, short_description, description, cover_image, client_quote, testimonial_type, testimonial_media_url, testimonial_author, status, is_featured, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title, $slug, $category, $clientName, $location,
            $completionDate, $shortDescription, $description,
            $coverImagePath, $clientQuote, $testimonialType,
            $testimonialMediaUrl, $testimonialAuthor, $status,
            $isFeatured, $displayOrder
        ]);
        $realisationId = (int)$pdo->lastInsertId();
        log_admin_activity($pdo, 'CREATE', "Création de la réalisation '$title' (ID #$realisationId)", $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? 'Admin');
    }

    // 6. Gestion des photos multiples de l'intervention (l'équipe au travail / chantier)
    if (isset($_FILES['work_photos']) && is_array($_FILES['work_photos']['name'])) {
        $photoFiles = $_FILES['work_photos'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $allowedImageMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

        for ($i = 0; $i < count($photoFiles['name']); $i++) {
            if ($photoFiles['error'][$i] === UPLOAD_ERR_OK) {
                $mime = $finfo->file($photoFiles['tmp_name'][$i]);
                if (isset($allowedImageMimes[$mime])) {
                    $ext = $allowedImageMimes[$mime];
                    $pFilename = 'work_' . bin2hex(random_bytes(10)) . '.' . $ext;
                    $pDest = $photosDir . $pFilename;
                    if (move_uploaded_file($photoFiles['tmp_name'][$i], $pDest)) {
                        $pPath = 'uploads/realisations/photos/' . $pFilename;
                        $mediaStmt = $pdo->prepare("INSERT INTO realisation_media (realisation_id, media_type, media_role, file_path, display_order) VALUES (?, 'photo', 'work', ?, ?)");
                        $mediaStmt->execute([$realisationId, $pPath, $i]);
                    }
                }
            }
        }
    }

    // 7. Gestion de la vidéo d'intervention (L'équipe au travail)
    // A. Via upload fichier vidéo
    if (isset($_FILES['work_video_file']) && $_FILES['work_video_file']['error'] === UPLOAD_ERR_OK) {
        $vFile = $_FILES['work_video_file'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($vFile['tmp_name']);
        $allowedVideoMimes = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-matroska' => 'mkv'];

        if (isset($allowedVideoMimes[$mime])) {
            $ext = $allowedVideoMimes[$mime];
            $vFilename = 'work_vid_' . bin2hex(random_bytes(10)) . '.' . $ext;
            if (move_uploaded_file($vFile['tmp_name'], $videosDir . $vFilename)) {
                $vPath = 'uploads/realisations/videos/' . $vFilename;
                $vStmt = $pdo->prepare("INSERT INTO realisation_media (realisation_id, media_type, media_role, file_path, caption) VALUES (?, 'video', 'work', ?, 'Équipe en intervention')");
                $vStmt->execute([$realisationId, $vPath]);
            }
        }
    }
    // B. Via lien vidéo externe (YouTube, etc.)
    $workVideoUrl = trim($_POST['work_video_url'] ?? '');
    if (!empty($workVideoUrl)) {
        $vStmt = $pdo->prepare("INSERT INTO realisation_media (realisation_id, media_type, media_role, file_path, caption) VALUES (?, 'video', 'work', ?, 'Vidéo d\'intervention (externe)')");
        $vStmt->execute([$realisationId, $workVideoUrl]);
    }

    $msg = $id > 0 ? "Réalisation mise à jour avec succès." : "Réalisation ajoutée avec succès.";
    header("Location: ../realisations.php?message=" . urlencode($msg) . "&status=success");
    exit;

} catch (PDOException $e) {
    error_log("Erreur save_realisation: " . $e->getMessage());
    $targetUrl = $id > 0 ? "../realisation_form.php?id=$id&" : "../realisation_form.php?";
    header("Location: " . $targetUrl . "message=" . urlencode("Erreur base de données : " . $e->getMessage()) . "&status=error");
    exit;
}
