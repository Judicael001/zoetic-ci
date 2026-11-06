<?php
require_once '../src/db/init.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Incrémentation simple du like
        $stmt = $pdo->prepare("UPDATE blog_posts SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$id]);

        // Récupérer le nouveau total
        $res = $pdo->prepare("SELECT likes FROM blog_posts WHERE id = ?");
        $res->execute([$id]);
        $count = $res->fetchColumn();

        echo json_encode(['status' => 'success', 'likes' => $count]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error']);
    }
} else {
    echo json_encode(['status' => 'error']);
}