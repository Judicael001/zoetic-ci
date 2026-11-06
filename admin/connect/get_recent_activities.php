<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            action_type, 
            description, 
            admin_name, 
            created_at 
        FROM admin_logs 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater la date pour un affichage plus convivial
    foreach ($activities as &$activity) {
        $activity['time_ago'] = time_ago_in_words($activity['created_at']);
    }

    echo json_encode(['status' => 'success', 'activities' => $activities]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la récupération des activités.']);
}

function time_ago_in_words($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return $diff . ' sec ago';
    if ($diff < 3600) return round($diff / 60) . ' min ago';
    if ($diff < 86400) return round($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return round($diff / 86400) . ' days ago';
    if ($diff < 31536000) return round($diff / 2592000) . ' months ago';
    return round($diff / 31536000) . ' years ago';
}