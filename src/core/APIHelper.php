<?php
namespace App\Core;

/**
 * Helper centralisé pour les appels d'API externes
 */
class APIHelper {
    /**
     * Effectue une requête POST JSON
     * 
     * @param string $url L'URL de destination
     * @param array $body Le corps de la requête (sera encodé en JSON)
     * @param array $headers En-têtes additionnels
     * @param int $timeout Délai d'attente maximum en secondes (défaut: 15)
     * @return array Résultat contenant le code HTTP et les données
     */
    public static function post(string $url, array $body, array $headers = [], int $timeout = 15): array {
        $ch = curl_init($url);
        
        $defaultHeaders = ['Content-Type: application/json'];
        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        // Ajout des timeouts pour éviter de bloquer le thread PHP
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Temps max pour établir la connexion
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);    // Temps max pour l'exécution totale de la requête

        // Sécurité : Vérifier le SSL en production
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errorNo = curl_errno($ch);

        // Note : curl_close() est omis car obsolète/optionnel en PHP 8.x

        // Gestion explicite du timeout pour le retour
        if ($errorNo === CURLE_OPERATION_TIMEDOUT) {
            $error = "La requête vers l'API a expiré après $timeout secondes.";
        }

        $apiResult = [
            'success' => ($httpCode === 200),
            'status' => $httpCode,
            'data' => json_decode($result, true) ?: $result,
            'error' => $error
        ];

        // Journalisation automatique en cas d'erreur
        if (!$apiResult['success']) {
            self::logApiError($url, $error ?: 'HTTP Status ' . $httpCode, $httpCode);
        }

        return $apiResult;
    }

    /**
     * Enregistre l'erreur dans logs/api_errors.log
     */
    private static function logApiError(string $url, string $error, int $status): void {
        $logDir = __DIR__ . '/../../logs';
        
        // Créer le dossier logs s'il n'existe pas
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/api_errors.log';

        // Vérification de la taille pour la rotation (Limite à 2 Mo)
        if (file_exists($logFile) && filesize($logFile) > 2 * 1024 * 1024) {
            self::rotateLogs($logFile);
        }

        // Nettoyage automatique des vieux fichiers (1 chance sur 50 de s'exécuter pour économiser les ressources)
        if (rand(1, 50) === 1) {
            self::purgeOldLogs(190);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] URL: $url | Status: $status | Error: $error" . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Effectue une rotation des fichiers de log (garde 3 archives)
     */
    private static function rotateLogs(string $logFile): void {
        $maxArchives = 3;
        
        // On décale les archives existantes vers le haut
        for ($i = $maxArchives; $i > 0; $i--) {
            $current = ($i === 1) ? $logFile : $logFile . '.' . ($i - 1);
            $next = $logFile . '.' . $i;
            
            if (file_exists($current)) {
                if ($i === $maxArchives && file_exists($next)) {
                    unlink($next);
                }
                rename($current, $next);
            }
        }
    }

    /**
     * Supprime tous les fichiers du dossier logs vieux de $days jours
     * 
     * @param int $days Nombre de jours de rétention
     * @return int Nombre de fichiers supprimés
     */
    public static function purgeOldLogs(int $days = 190): int {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) return 0;

        $count = 0;
        $threshold = time() - ($days * 86400); // 86400 secondes par jour
        $files = scandir($logDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $path = $logDir . '/' . $file;
            if (is_file($path) && filemtime($path) < $threshold) {
                if (unlink($path)) $count++;
            }
        }

        return $count;
    }
}