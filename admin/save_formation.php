<?php
session_start();
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

function slugify(string $text): string
{
    $text = preg_replace('/[^a-z0-9]+/i', '-', trim($text));
    $text = trim($text, '-');
    return strtolower($text) ?: 'formation-' . time();
}

function get_unique_formation_slug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string {
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $sql = "SELECT id FROM formations WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) return $slug;
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = trim($_POST['title'] ?? '');
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
$instructorId = isset($_POST['instructor_id']) && $_POST['instructor_id'] !== '' ? (int) $_POST['instructor_id'] : null;
$shortDescription = trim($_POST['short_description'] ?? '');
$description = trim($_POST['description'] ?? '');
$level = $_POST['level'] ?? 'Débutant';
$duration = trim($_POST['duration'] ?? '');
$priceRaw = str_replace(',', '.', trim($_POST['price'] ?? '0.00'));
$categoryColor = trim($_POST['category_color'] ?? '');
$price = is_numeric($priceRaw) ? number_format((float) $priceRaw, 2, '.', '') : null;
$image = trim($_POST['image'] ?? '');
$imageFile = $_FILES['image_file'] ?? null;
$status = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;

$errors = [];
if ($title === '') {
    $errors[] = 'Le titre de la formation est requis.';
}
if ($description === '') {
    $errors[] = 'La description complète est requise.';
}
if ($price === null) {
    $errors[] = 'Le prix doit être un nombre valide.';
}
if (!in_array($level, ['Débutant', 'Intermédiaire', 'Avancé'], true)) {
    $errors[] = 'Le niveau sélectionné est invalide.';
}

$uploadedImagePath = null;
if ($imageFile && isset($imageFile['error']) && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($imageFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors du téléchargement de l’image.';
    } else {
        $tmpName = $imageFile['tmp_name'];
        
        // Vérification de la signature réelle du fichier (MIME-type)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Le fichier téléchargé doit être une image JPG, PNG, WEBP ou GIF.';
        } elseif ($imageFile['size'] > 5 * 1024 * 1024) {
            $errors[] = 'La taille de l’image ne doit pas dépasser 5 Mo.';
        } else {
            $uploadsDir = __DIR__ . '/../../uploads/formations';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // On génère un nom aléatoire et on force l'extension basée sur le MIME type détecté
            $extension = $allowedTypes[$mimeType];
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            $destination = $uploadsDir . '/' . $fileName;

            if (move_uploaded_file($tmpName, $destination)) {
                $uploadedImagePath = 'uploads/formations/' . $fileName;
            } else {
                $errors[] = 'Le fichier n’a pas pu être enregistré sur le serveur.';
            }
        }
    }
}

if ($uploadedImagePath) {
    $image = $uploadedImagePath;
} elseif ($image !== '' && !filter_var($image, FILTER_VALIDATE_URL)) {
    $errors[] = 'L’URL de l’image ou de la vidéo est invalide.';
}

if (!empty($errors)) {
    $message = implode(' ', $errors);
    header('Location: ../formations.php?message=' . urlencode($message) . '&status=error');
    exit;
}

// Mettre à jour la couleur de la catégorie si elle est fournie
if ($categoryId && !empty($categoryColor)) {
    $stmt = $pdo->prepare('UPDATE categories SET color = ? WHERE id = ?');
    $stmt->execute([$categoryColor, $categoryId]);
}


$slug = slugify($title);
$slug = get_unique_formation_slug($pdo, $slug, $id ?: null);

try {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE formations SET category_id = ?, instructor_id = ?, title = ?, slug = ?, short_description = ?, description = ?, level = ?, duration = ?, price = ?, image = ?, status = ? WHERE id = ?'
        );
        $params = [$categoryId, $instructorId, $title, $slug, $shortDescription ?: null, $description, $level, $duration ?: null, $price, $image ?: null, $status, $id];
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO formations (category_id, instructor_id, title, slug, short_description, description, level, duration, price, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $params = [$categoryId, $instructorId, $title, $slug, $shortDescription ?: null, $description, $level, $duration ?: null, $price, $image ?: null, $status];
    }
    
    $stmt->execute($params);
    $msg = $id > 0 ? 'Formation mise à jour avec succès.' : 'Formation ajoutée avec succès.';
    header('Location: ../formations.php?message=' . urlencode($msg) . '&status=success');
    exit;
} catch (PDOException $e) {
    header('Location: ../formations.php?message=' . urlencode('Erreur lors de l’ajout de la formation.') . '&status=error');
    exit;
}
