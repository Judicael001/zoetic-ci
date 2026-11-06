<?php
/**
 * Système d'internationalisation pour ZoeTIC
 */

$allowed_langs = ['fr', 'en'];
$lang = $_SESSION['lang'] ?? 'fr';

// Changement de langue via URL
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}

/**
 * Chargement des dictionnaires avec fallback
 */

// 1. On charge d'abord la langue par défaut (fr) comme base de sécurité
$defaultFile = __DIR__ . "/fr.json";
$translations = is_file($defaultFile) ? (json_decode(file_get_contents($defaultFile), true) ?? []) : [];

// 2. Si la langue actuelle n'est pas le français, on fusionne les traductions spécifiques
if ($lang !== 'fr') {
    $localeFile = __DIR__ . "/{$lang}.json";
    if (is_file($localeFile)) {
        $specificTranslations = json_decode(file_get_contents($localeFile), true) ?? [];
        // array_merge écrase les valeurs françaises par les valeurs de la langue cible
        $translations = array_merge($translations, $specificTranslations);
    }
}

/**
 * Fonction de traduction
 */
function t(string $key): string {
    global $translations;

    // Sécurité supplémentaire si la variable globale n'est pas initialisée
    if (!is_array($translations)) {
        return $key;
    }

    return $translations[$key] ?? $key;
}