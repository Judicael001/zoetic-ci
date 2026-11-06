<?php
require_once __DIR__ . '/src/db/init.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $settings = get_site_settings($pdo);
    
    $socials = [];
    $keys = [
        'social_facebook' => 'Facebook',
        'social_linkedin' => 'LinkedIn',
        'social_instagram' => 'Instagram',
        'social_twitter' => 'Twitter',
        'social_tiktok' => 'TikTok',
        'social_youtube' => 'YouTube'
    ];
    
    foreach ($keys as $key => $name) {
        $val = $settings[$key] ?? '';
        // Uniquement renvoyer les liens configurés et non-vides/différents du placeholder '#'
        if ($val !== '' && $val !== '#') {
            if (filter_var($val, FILTER_VALIDATE_URL)) {
                $socials[] = [
                    'name' => $name,
                    'key' => str_replace('social_', '', $key),
                    'url' => $val
                ];
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $socials
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;
