<?php
require_once __DIR__ . '/src/db/init.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users) as students,
            (SELECT COUNT(*) FROM formations) as formations,
            (SELECT COUNT(*) FROM enrollments) as enrollments,
            (SELECT COUNT(*) FROM blog_posts WHERE status = 'published') as blog_published
    ")->fetch();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'students' => (int)($stats['students'] ?? 0),
            'formations' => (int)($stats['formations'] ?? 0),
            'blog_published' => (int)($stats['blog_published'] ?? 0),
            'enrollments' => (int)($stats['enrollments'] ?? 0)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}