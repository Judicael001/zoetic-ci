<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/blog.php';

ensure_blog_table($pdo);
ensure_blog_media_table($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = 'Article introuvable.';
$status = 'error';

if ($id > 0) {
    // 1. Récupérer les chemins des fichiers avant la suppression pour le nettoyage physique
    $stmt = $pdo->prepare('SELECT featured_image FROM blog_posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    $postMedia = get_media_for_post($pdo, $id);

    // 2. Supprimer l'article (MySQL se charge de blog_media via ON DELETE CASCADE)
    $stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = ?');
    if ($stmt->execute([$id])) {
        // 3. Supprimer physiquement l'image à la une si elle est locale
        if ($post && !empty($post['featured_image']) && strpos($post['featured_image'], 'uploads/') !== false) {
            @unlink(__DIR__ . '/../' . $post['featured_image']);
        }

        // 4. Supprimer physiquement les médias de la galerie
        foreach ($postMedia as $media) {
            if (!empty($media['filename'])) {
                @unlink(__DIR__ . '/../' . $media['filename']);
            }
        }

        $message = 'Article supprimé avec succès.';
        $status = 'success';
    } else {
        $message = 'Impossible de supprimer l’article.';
        $status = 'error';
    }
}

header('Location: blog.php?message=' . urlencode($message) . '&status=' . $status);
exit;
