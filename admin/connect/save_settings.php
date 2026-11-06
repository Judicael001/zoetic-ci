<?php
require_once __DIR__ . '/../_auth.php';
require_admin_login();
require_once __DIR__ . '/../../src/db/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../settings.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Préparation de la requête avec gestion de l'update sur clé dupliquée
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    foreach ($_POST as $key => $value) {
        $cleanKey = preg_replace('/[^a-z0-9_]/', '', $key);
        $trimmedValue = trim($value);

        if ($cleanKey !== '') {
            // Sécurité : Ne pas écraser la clé API si elle est envoyée sous sa forme masquée (contient "...")
            if ($cleanKey === 'google_gemini_api_key' && strpos($trimmedValue, '...') !== false) {
                continue;
            }
            
            $val = $trimmedValue;
            if ($cleanKey === 'google_gemini_api_key') {
                $val = encrypt_data($val);
            }

            // Validation et normalisation des réseaux sociaux
            if (strpos($cleanKey, 'social_') === 0) {
                if ($val !== '' && $val !== '#') {
                    if ($cleanKey === 'social_whatsapp') {
                        // Normalisation si c'est un numéro WhatsApp (chiffres, espaces, +)
                        // Si le format est purement numérique ou contient des caractères de numéro de téléphone (+, -, espace)
                        $phoneDigits = preg_replace('/[^0-9]/', '', $val);
                        // On vérifie si c'est un numéro de téléphone ou une URL
                        if (preg_match('/^[0-9+\s\-()]{6,20}$/', $val) || (strlen($phoneDigits) >= 8 && strlen($phoneDigits) <= 15 && !preg_match('/[a-zA-Z]/', $val))) {
                            if (!empty($phoneDigits)) {
                                if (strlen($phoneDigits) === 10 && $phoneDigits[0] === '0') {
                                    $phoneDigits = '225' . substr($phoneDigits, 1);
                                } elseif (strlen($phoneDigits) === 8) {
                                    $phoneDigits = '225' . $phoneDigits;
                                }
                                $val = 'https://wa.me/' . $phoneDigits;
                            }
                        } else {
                            if (strpos($val, 'wa.me/') === 0) {
                                $val = 'https://' . $val;
                            }
                            if (!filter_var($val, FILTER_VALIDATE_URL)) {
                                throw new Exception("Le lien ou numéro WhatsApp n'est pas valide.");
                            }
                        }
                    } else {
                        if (strpos($val, 'http://') !== 0 && strpos($val, 'https://') !== 0) {
                            $val = 'https://' . $val;
                        }
                        if (!filter_var($val, FILTER_VALIDATE_URL)) {
                            $networkName = ucfirst(str_replace('social_', '', $cleanKey));
                            throw new Exception("L'URL pour $networkName n'est pas valide. Exemple: https://facebook.com/votrepage");
                        }
                    }
                }
            }

            $stmt->execute([$cleanKey, $val]);
        }
    }

    // Journalisation
    $adminId = $_SESSION['admin_id'] ?? 0;
    $adminName = $_SESSION['admin_name'] ?? 'Admin/Système';

    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'UPDATE', ?, ?)");
    $logStmt->execute([
        $adminId,
        $adminName,
        "Mise à jour des paramètres du site",
        $_SERVER['REMOTE_ADDR']
    ]);

    $pdo->commit();
    header("Location: ../settings.php?status=success&message=" . urlencode("Paramètres mis à jour avec succès."));
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    header("Location: ../settings.php?status=error&message=" . urlencode("Erreur: " . $e->getMessage()));
}
exit;