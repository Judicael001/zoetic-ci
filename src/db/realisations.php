<?php

/**
 * Gestion de la table et des requêtes pour les Réalisations & Prestations ZoeTIC
 */

function ensure_realisations_tables(PDO $pdo): void {
    // Table principale des réalisations
    $pdo->exec("CREATE TABLE IF NOT EXISTS realisations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        category VARCHAR(100) NOT NULL,
        client_name VARCHAR(255) DEFAULT NULL,
        location VARCHAR(255) DEFAULT NULL,
        completion_date DATE DEFAULT NULL,
        short_description VARCHAR(500) DEFAULT NULL,
        description LONGTEXT NOT NULL,
        cover_image VARCHAR(500) DEFAULT NULL,
        client_quote TEXT DEFAULT NULL,
        testimonial_type ENUM('none', 'video', 'audio') DEFAULT 'none',
        testimonial_media_url VARCHAR(500) DEFAULT NULL,
        testimonial_author VARCHAR(255) DEFAULT NULL,
        status ENUM('published', 'draft') DEFAULT 'published',
        is_featured TINYINT(1) DEFAULT 0,
        display_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Table des médias associés (photos et vidéos d'intervention)
    $pdo->exec("CREATE TABLE IF NOT EXISTS realisation_media (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        realisation_id INT UNSIGNED NOT NULL,
        media_type ENUM('photo', 'video') NOT NULL,
        media_role ENUM('work', 'gallery') DEFAULT 'work',
        file_path VARCHAR(500) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        display_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_realisation (realisation_id),
        CONSTRAINT fk_realisation_media FOREIGN KEY (realisation_id) REFERENCES realisations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function slugify_realisation(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'projet-' . time() : $text;
}

function get_unique_realisation_slug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string {
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $sql = "SELECT id FROM realisations WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

function get_all_realisations(PDO $pdo, ?string $status = 'published', ?string $category = null, ?int $limit = null, int $offset = 0): array {
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM realisation_media rm WHERE rm.realisation_id = r.id AND rm.media_type = 'photo') as photo_count,
            (SELECT COUNT(*) FROM realisation_media rm WHERE rm.realisation_id = r.id AND rm.media_type = 'video') as video_count
            FROM realisations r WHERE 1=1";
    $params = [];

    if ($status !== null) {
        $sql .= " AND r.status = ?";
        $params[] = $status;
    }

    if ($category !== null && $category !== '' && $category !== 'Tous') {
        $sql .= " AND r.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY r.is_featured DESC, r.display_order ASC, r.created_at DESC";

    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $realisations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Charger les médias pour chaque réalisation
    foreach ($realisations as &$r) {
        $r['media'] = get_realisation_media($pdo, (int)$r['id']);
    }
    unset($r);

    return $realisations;
}

function get_featured_realisations(PDO $pdo, int $limit = 3): array {
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM realisation_media rm WHERE rm.realisation_id = r.id AND rm.media_type = 'photo') as photo_count,
            (SELECT COUNT(*) FROM realisation_media rm WHERE rm.realisation_id = r.id AND rm.media_type = 'video') as video_count
            FROM realisations r 
            WHERE r.status = 'published' AND r.is_featured = 1
            ORDER BY r.display_order ASC, r.created_at DESC
            LIMIT " . (int)$limit;
    $stmt = $pdo->query($sql);
    $realisations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si on a moins que la limite, compléter avec les plus récentes publiées
    if (count($realisations) < $limit) {
        $needed = $limit - count($realisations);
        $ids = !empty($realisations) ? implode(',', array_column($realisations, 'id')) : '0';
        $fallbackSql = "SELECT r.*, 
                (SELECT COUNT(*) FROM realisation_media rm WHERE rm.realisation_id = r.id AND rm.media_type = 'photo') as photo_count,
                (SELECT COUNT(*) FROM realisation_media rm WHERE rm.realisation_id = r.id AND rm.media_type = 'video') as video_count
                FROM realisations r 
                WHERE r.status = 'published' AND r.id NOT IN ($ids)
                ORDER BY r.display_order ASC, r.created_at DESC
                LIMIT " . (int)$needed;
        $fallback = $pdo->query($fallbackSql)->fetchAll(PDO::FETCH_ASSOC);
        $realisations = array_merge($realisations, $fallback);
    }

    foreach ($realisations as &$r) {
        $r['media'] = get_realisation_media($pdo, (int)$r['id']);
    }
    unset($r);

    return $realisations;
}

function get_realisation_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM realisations WHERE id = ?");
    $stmt->execute([$id]);
    $realisation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$realisation) return null;

    $realisation['media'] = get_realisation_media($pdo, $id);
    return $realisation;
}

function get_realisation_by_slug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("SELECT * FROM realisations WHERE slug = ?");
    $stmt->execute([$slug]);
    $realisation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$realisation) return null;

    $realisation['media'] = get_realisation_media($pdo, (int)$realisation['id']);
    return $realisation;
}

function get_realisation_media(PDO $pdo, int $realisationId): array {
    $stmt = $pdo->prepare("SELECT * FROM realisation_media WHERE realisation_id = ? ORDER BY display_order ASC, id ASC");
    $stmt->execute([$realisationId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_realisation_categories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT category FROM realisations WHERE status = 'published' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function count_realisations(PDO $pdo, ?string $status = 'published', ?string $category = null): int {
    $sql = "SELECT COUNT(*) FROM realisations WHERE 1=1";
    $params = [];
    if ($status !== null) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    if ($category !== null && $category !== '' && $category !== 'Tous') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
