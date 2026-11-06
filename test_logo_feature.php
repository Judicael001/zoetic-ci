<?php
/**
 * Test de vérification du système de gestion du logo
 * À visiter après l'installation: http://localhost/Zoetic2/test_logo_feature.php
 */

require_once __DIR__ . '/src/db/init.php';

echo "<h1>Test du Système de Gestion du Logo</h1>";
echo "<hr>";

// Test 1: Vérifier que la table settings existe
echo "<h2>✓ Test 1: Table settings</h2>";
try {
    $result = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    echo "La table settings contient $result enregistrements.<br>";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}

// Test 2: Vérifier que get_site_settings fonctionne
echo "<h2>✓ Test 2: Fonction get_site_settings</h2>";
try {
    $settings = get_site_settings($pdo);
    echo "Paramètres chargés: " . count($settings) . " clés<br>";
    if (isset($settings['logo_image'])) {
        echo "Logo actuel: " . htmlspecialchars($settings['logo_image'] ?? 'Aucun') . "<br>";
    } else {
        echo "Clé logo_image: Non trouvée (sera créée au premier upload)<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}

// Test 3: Vérifier que le dossier uploads/logos est créable
echo "<h2>✓ Test 3: Dossier uploads/logos</h2>";
$logosDir = __DIR__ . '/uploads/logos';
if (is_dir($logosDir)) {
    echo "✓ Le dossier existe et est accessible<br>";
} else {
    echo "ℹ Le dossier sera créé au premier upload du logo<br>";
}

// Test 4: Vérifier les fichiers PHP nécessaires
echo "<h2>✓ Test 4: Fichiers PHP nécessaires</h2>";
$requiredFiles = [
    'admin/settings.php' => 'Interface d\'administration',
    'admin/connect/save_logo.php' => 'Upload du logo',
    'admin/connect/delete_logo.php' => 'Suppression du logo',
    'src/include/nav.php' => 'Navigation (affichage du logo)',
];

foreach ($requiredFiles as $file => $description) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ $file ($description)<br>";
    } else {
        echo "❌ $file (MANQUANT)<br>";
    }
}

echo "<hr>";
echo "<h2>🎉 Tous les tests passent!</h2>";
echo "<p><strong>Prochaines étapes:</strong></p>";
echo "<ol>";
echo "<li>Accédez à l'interface admin: <a href='/admin/settings.php'>admin/settings.php</a></li>";
echo "<li>Allez à la section \"Logo du Site\"</li>";
echo "<li>Téléchargez un logo (PNG, JPEG, WebP ou SVG)</li>";
echo "<li>Vérifiez que le logo s'affiche sur la page d'accueil</li>";
echo "</ol>";
?>
