<?php
// DEBUG : Activer temporairement pour voir les erreurs fatales à l'écran
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../_auth.php';
require_admin_login();

require_once '../../src/db/init.php';
require_once '../../src/db/blog.php';

ensure_blog_table($pdo);
ensure_blog_media_table($pdo);

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$content = trim($_POST['content'] ?? '');

$featured_image = trim($_POST['featured_image'] ?? '');
$featured_image_file = $_FILES['featured_image_file'] ?? null;
$cropped_image_data = $_POST['cropped_image_data'] ?? '';
$media_files = $_FILES['media_files'] ?? null;
$status = trim($_POST['status'] ?? 'draft');
$status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';

$category = $category !== '' ? $category : 'Actualités';

$uploadedPath = null;
// Handle cropped image data (Base64 from Cropper.js)
if (!empty($cropped_image_data) && preg_match('/^data:image\/(\w+);base64,/', $cropped_image_data, $matches)) {
    $type = strtolower($matches[1]); // jpg, png, etc
    $allowed = ['jpg' => 'jpeg', 'jpeg' => 'jpeg', 'png' => 'png', 'webp' => 'webp'];
    
    if (isset($allowed[$type])) {
        $data = substr($cropped_image_data, strpos($cropped_image_data, ',') + 1);
        $data = base64_decode($data);
        
        if ($data !== false) {
            $uploads = __DIR__ . '/../../uploads/blog';
            if (!is_dir($uploads)) {
                mkdir($uploads, 0755, true);
            }
            
            // Forcer l'extension webp
            $name = bin2hex(random_bytes(16)) . '.webp';
            $dest = $uploads . '/' . $name;
            
            if (file_put_contents($dest, $data)) {
                $featured_image = 'uploads/blog/' . $name;
                apply_watermark($dest);
                $uploadedPath = $featured_image; // Mark as uploaded to skip standard file check
            }
        }
    }
}

// Handle uploaded featured image (preferred over URL)
if (!$uploadedPath && $featured_image_file && isset($featured_image_file['error']) && $featured_image_file['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($featured_image_file['error'] === UPLOAD_ERR_OK) {
        $tmp = $featured_image_file['tmp_name'];
        
        // Vérification de la signature réelle (MIME type)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmp);
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (in_array($mimeType, $allowed) && $featured_image_file['size'] <= 5 * 1024 * 1024) {
            $uploads = __DIR__ . '/../../uploads/blog';
            if (!is_dir($uploads)) {
                mkdir($uploads, 0755, true);
            }
            // Nom aléatoire forcé en .webp
            $name = generate_secure_filename($featured_image_file['name'], 'webp');
            $dest = $uploads . '/' . $name;
            if (process_image_upload($tmp, $dest, 1200, 80)) {
                $uploadedPath = 'uploads/blog/' . $name;
                // Appliquer le filigrane sur l'image à la une
                apply_watermark($dest);
            }
        }
    }
}

if ($uploadedPath) {
    $featured_image = $uploadedPath;
}

// Process multiple media files (images or videos)
// Accept files from input name="media_files[]"
$uploadedMedia = [];
if ($media_files && isset($media_files['name']) && is_array($media_files['name'])) {
    $uploadsDir = __DIR__ . '/../../uploads/blog_media';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $count = count($media_files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($media_files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = $media_files['tmp_name'][$i];
        
        // Vérification de la signature réelle (MIME type)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);

        $allowedImages = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $allowedVideos = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

        $isImage = isset($allowedImages[$mime]);
        $isVideo = isset($allowedVideos[$mime]);

        if ($isImage) {
            if ($media_files['size'][$i] > 5 * 1024 * 1024) continue;
            $ext = $allowedImages[$mime];
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
        } elseif ($isVideo) {
            if ($media_files['size'][$i] > 50 * 1024 * 1024) continue;
            $ext = $allowedVideos[$mime];
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
        } else {
            continue;
        }

        $dest = $uploadsDir . '/' . $name;
        if (move_uploaded_file($tmp, $dest)) {
            $rel = 'uploads/blog_media/' . $name;
            // Appliquer le filigrane si c'est une image
            if ($isImage) apply_watermark($dest);
            // If post exists (we will attach after inserting new post below), store temporarily in array
            $uploadedMedia[] = ['path' => $rel, 'mime' => $mime, 'type' => $isImage ? 'image' : 'video'];
        }
    }
}


if (empty($title) || empty($content)) {
    $redirect = '../blog_create.php?message=' . urlencode('Le titre et le contenu sont requis.') . '&status=error';
    if ($id > 0) {
        $redirect .= '&id=' . $id;
    }
    header('Location: ' . $redirect);
    exit;
}

// Accept either a remote URL or a local uploads path
if (!empty($featured_image) && !filter_var($featured_image, FILTER_VALIDATE_URL) && strpos($featured_image, 'uploads/') === false) {
    $featured_image = '';
}

if ($excerpt === '') {
    $cleanText = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
    $excerpt = mb_substr($cleanText, 0, 160, 'UTF-8');
    if (mb_strlen($cleanText, 'UTF-8') > 160) {
        $excerpt = mb_substr($excerpt, 0, mb_strrpos($excerpt, ' ', 0, 'UTF-8') ?: 160, 'UTF-8') . '...';
    }
}

$slug = slugify($title);
$slug = get_unique_blog_slug($pdo, $slug, $id ?: null);

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE blog_posts SET title = ?, slug = ?, category = ?, excerpt = ?, content = ?, featured_image = ?, status = ? WHERE id = ?');
        $stmt->execute([$title, $slug, $category, $excerpt, $content, $featured_image, $status, $id]);
        $postId = $id;
        $message = 'Article mis à jour avec succès.';
        $redirect = '../blog.php?message=' . urlencode($message) . '&status=success';

        // Audit Log
        $adminId = $_SESSION['admin_id'] ?? 0;
        $adminName = $_SESSION['admin_name'] ?? 'Admin/Système';
        try {
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'UPDATE', ?, ?)");
            $logStmt->execute([
                $adminId, $adminName,
                "Mise à jour de l'article : " . $title, $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Throwable $logError) {
            error_log('save_blog.php update log failed: ' . $logError->getMessage());
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO blog_posts (title, slug, category, excerpt, content, featured_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $slug, $category, $excerpt, $content, $featured_image, $status]);
        $postId = (int)$pdo->lastInsertId();
        $message = 'Article créé avec succès.';
        $redirect = '../blog.php?message=' . urlencode($message) . '&status=success';

        // Audit Log for Creation
        try {
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'CREATE', ?, ?)");
            $logStmt->execute([
                $_SESSION['admin_id'], $_SESSION['admin_name'],
                "Création de l'article : " . $title, $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Throwable $logError) {
            error_log('save_blog.php create log failed: ' . $logError->getMessage());
        }
    }

    // Attach any uploaded media to the post
    if (!empty($uploadedMedia) && isset($postId) && $postId > 0) {
        $pos = 0;
        foreach ($uploadedMedia as $m) {
            try {
                attach_media_to_post($pdo, $postId, $m['path'], $m['mime'], $m['type'], $pos);
                $pos++;
            } catch (Exception $ex) {
                // continue on failure but log
                error_log('attach_media failed: ' . $ex->getMessage());
            }
        }

        // If no featured image set, and first media is an image, set it as featured_image
        if (empty($featured_image)) {
            foreach ($uploadedMedia as $m) {
                if ($m['type'] === 'image') {
                    $featured_image_path = $m['path'];
                    $ustmt = $pdo->prepare('UPDATE blog_posts SET featured_image = ? WHERE id = ?');
                    $ustmt->execute([$featured_image_path, $postId]);
                    break;
                }
            }
        }
    }
    header('Location: ' . $redirect);
    exit;
} catch (PDOException $e) {
    error_log('save_blog.php PDOException: ' . $e->getMessage());
    $redirect = '../blog_create.php?message=' . urlencode('Erreur SQL : ' . $e->getMessage()) . '&status=error';
    if ($id > 0) {
        $redirect .= '&id=' . $id;
    }
    header('Location: ' . $redirect);
    exit;
}
