<?php
function ensure_blog_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        category VARCHAR(150) DEFAULT NULL,
        excerpt TEXT DEFAULT NULL,
        content LONGTEXT NOT NULL,
        featured_image VARCHAR(500) DEFAULT NULL,
        status ENUM('draft','published') DEFAULT 'draft',
        views INT UNSIGNED DEFAULT 0,
        likes INT UNSIGNED DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensure_blog_media_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        filename VARCHAR(500) NOT NULL,
        mime VARCHAR(100) NOT NULL,
        media_type ENUM('image','video') NOT NULL,
        position INT DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensure_blog_comments_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        parent_id INT DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
        notify_replies TINYINT(1) DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES blog_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $requiredColumns = [
        'parent_id' => "ALTER TABLE blog_comments ADD COLUMN parent_id INT DEFAULT NULL AFTER post_id",
        'status' => "ALTER TABLE blog_comments ADD COLUMN status ENUM('pending', 'approved', 'spam') DEFAULT 'approved' AFTER content",
        'notify_replies' => "ALTER TABLE blog_comments ADD COLUMN notify_replies TINYINT(1) DEFAULT 0 AFTER status",
        'created_at' => "ALTER TABLE blog_comments ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER notify_replies",
        'deleted_at' => "ALTER TABLE blog_comments ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER created_at",
    ];

    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog_comments'");
    $existingColumns = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

    foreach ($requiredColumns as $column => $sql) {
        if (!isset($existingColumns[$column])) {
            $pdo->exec($sql);
        }
    }
}

function attach_media_to_post(PDO $pdo, int $postId, string $filename, string $mime, string $mediaType, int $position = 0): string|int {
    $stmt = $pdo->prepare('INSERT INTO blog_media (post_id, filename, mime, media_type, position) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$postId, $filename, $mime, $mediaType, $position]);
    return $pdo->lastInsertId();
}

function get_media_for_post(PDO $pdo, int $postId): array {
    $stmt = $pdo->prepare('SELECT * FROM blog_media WHERE post_id = ? ORDER BY position ASC, id ASC');
    $stmt->execute([$postId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function delete_media_by_id(PDO $pdo, int $mediaId): ?string {
    $stmt = $pdo->prepare('SELECT filename FROM blog_media WHERE id = ?');
    $stmt->execute([$mediaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $filename = $row['filename'];
    $dstmt = $pdo->prepare('DELETE FROM blog_media WHERE id = ?');
    $dstmt->execute([$mediaId]);
    return $filename;
}

function slugify(string $text): string {
    $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^-\\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    return empty($text) ? 'article-' . time() : $text;
}

function get_unique_blog_slug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string {
    $slug = $baseSlug;
    $counter = 1;

    while (true) {
        $sql = 'SELECT id FROM blog_posts WHERE slug = ?';
        $params = [$slug];

        if ($excludeId) {
            $sql .= ' AND id <> ?';
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

function get_adjacent_posts(PDO $pdo, ?int $currentId): array {
    if (!$currentId) return ['prev' => null, 'next' => null];
    $prevStmt = $pdo->prepare("SELECT title, slug FROM blog_posts WHERE id < ? AND status = 'published' ORDER BY id DESC LIMIT 1");
    $prevStmt->execute([$currentId]);
    $nextStmt = $pdo->prepare("SELECT title, slug FROM blog_posts WHERE id > ? AND status = 'published' ORDER BY id ASC LIMIT 1");
    $nextStmt->execute([$currentId]);
    return [
        'prev' => $prevStmt->fetch(PDO::FETCH_ASSOC) ?: null,
        'next' => $nextStmt->fetch(PDO::FETCH_ASSOC) ?: null
    ];
}

function get_related_posts(PDO $pdo, ?int $currentId, string $category, int $limit = 3): array {
    if (!$currentId) return [];
    $stmt = $pdo->prepare("SELECT id, title, slug, featured_image, category, created_at FROM blog_posts WHERE id <> ? AND category = ? AND status = 'published' ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$currentId, $category, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_blog_posts(PDO $pdo, ?int $limit = null, ?string $category = null, ?string $search = null, int $offset = 0): array
{
    $sql = "SELECT * FROM blog_posts WHERE status = 'published'";
    $params = [];
    $searchTerm = null;
    
    if ($search) {
        $searchTerm = "%$search%";
    }

    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    if ($searchTerm) {
        $sql .= " AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    
    $orderBy = " ORDER BY ";
    if ($searchTerm) {
        $orderBy .= "(CASE WHEN title LIKE ? THEN 3 ELSE 0 END) + " .
                    "(CASE WHEN excerpt LIKE ? THEN 2 ELSE 0 END) + " .
                    "(CASE WHEN content LIKE ? THEN 1 ELSE 0 END) DESC, ";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    $orderBy .= "created_at DESC"; // Fallback for ties or no search

    $sql .= $orderBy;
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit;
        if ($offset > 0) {
            $sql .= " OFFSET " . (int)$offset;
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_blog_categories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE status = 'published' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function count_all_blog_posts(PDO $pdo, ?string $category = null, ?string $search = null): int {
    $sql = "SELECT COUNT(*) FROM blog_posts WHERE status = 'published'";
    $params = [];
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    if ($search) {
        $sql .= " AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
        $searchTerm = "%$search%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Met en évidence un terme de recherche dans un texte en utilisant la balise <mark>
 */
function highlight_search_term(string $text, ?string $term): string {
    $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    if (empty($term)) {
        return $escapedText;
    }
    $escapedTerm = preg_quote(htmlspecialchars($term, ENT_QUOTES, 'UTF-8'), '/');
    return preg_replace('/(' . $escapedTerm . ')/i', '<mark class="bg-yellow-200 text-slate-900 px-0.5 rounded-sm">$1</mark>', $escapedText);
}
