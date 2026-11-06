<?php
session_start();
use App\Core\APIHelper;

require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Script de test pour valider la clé API Gemini
 */

// On accepte une clé envoyée par POST (pour tester avant de sauvegarder) 
// ou on récupère celle en base de données.
$apiKey = $_POST['api_key'] ?? null;
// Si la clé est vide ou masquée, on va chercher la vraie en base
if (!$apiKey || strpos($apiKey, '...') !== false) {
    $settings = get_site_settings($pdo);
    $apiKey = $settings['google_gemini_api_key'] ?? null;
    if ($apiKey) $apiKey = decrypt_data($apiKey);
}

if (!$apiKey) {
    echo json_encode(['status' => 'error', 'message' => 'Aucune clé API à tester.']);
    exit;
}

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
$testData = ["contents" => [["parts" => [["text" => "dis bonjour"]]]]];

$apiResult = APIHelper::post($apiUrl, $testData);

if ($apiResult['success']) {
    echo json_encode(['status' => 'success', 'message' => 'La clé API est valide et opérationnelle !']);
} else {
    $errorDetail = $apiResult['data']['error']['message'] ?? 'Erreur inconnue (Code: ' . $apiResult['status'] . ')';
    echo json_encode([
        'status' => 'error', 
        'message' => 'La clé est invalide : ' . $errorDetail
    ]);
}