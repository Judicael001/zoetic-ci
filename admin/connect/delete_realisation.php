<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';
require_once '../../src/db/realisations.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id > 0) {
    $realisation = get_realisation_by_id($pdo, $id);

    if ($realisation) {
        $baseDir = __DIR__ . '/../../';

        // Supprimer l'image de couverture
        if (!empty($realisation['cover_image']) && !filter_var($realisation['cover_image'], FILTER_VALIDATE_URL)) {
            $coverPath = $baseDir . ltrim($realisation['cover_image'], '/');
            if (file_exists($coverPath) && is_file($coverPath)) {
                @unlink($coverPath);
            }
        }

        // Supprimer le média de témoignage client (vidéo ou audio)
        if (!empty($realisation['testimonial_media_url']) && !filter_var($realisation['testimonial_media_url'], FILTER_VALIDATE_URL)) {
            $testiPath = $baseDir . ltrim($realisation['testimonial_media_url'], '/');
            if (file_exists($testiPath) && is_file($testiPath)) {
                @unlink($testiPath);
            }
        }

        // Supprimer tous les médias associés (photos & vidéos d'intervention)
        $mediaList = get_realisation_media($pdo, $id);
        foreach ($mediaList as $media) {
            if (!empty($media['file_path']) && !filter_var($media['file_path'], FILTER_VALIDATE_URL)) {
                $mPath = $baseDir . ltrim($media['file_path'], '/');
                if (file_exists($mPath) && is_file($mPath)) {
                    @unlink($mPath);
                }
            }
        }

        // Supprimer de la base de données
        $delStmt = $pdo->prepare("DELETE FROM realisations WHERE id = ?");
        $delStmt->execute([$id]);

        log_admin_activity($pdo, 'DELETE', "Suppression de la réalisation '{$realisation['title']}' (ID #$id)", $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? 'Admin');

        header("Location: ../realisations.php?message=" . urlencode("La réalisation a été supprimée avec succès.") . "&status=success");
        exit;
    }
}

header("Location: ../realisations.php?message=" . urlencode("Réalisation introuvable.") . "&status=error");
exit;
