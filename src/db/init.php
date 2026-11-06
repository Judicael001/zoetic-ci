<?php



/**

 * Charge les variables d'environnement depuis le fichier .env

 */

function load_env(string $path): void {

    if (!file_exists($path)) return;



    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        if (strpos(trim($line), '#') === 0) continue;

        

        list($name, $value) = explode('=', $line, 2);

        $name = trim($name);

        $value = trim($value);



        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {

            putenv("{$name}={$value}");

            $_ENV[$name] = $value;

            $_SERVER[$name] = $value;

        }

    }

}



// Chargement des variables d'environnement
load_env(__DIR__ . '/../../.env');
load_env(__DIR__ . '/.env');

// Paramètres de connexion
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'zoeticci_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Tentative de connexion avec PDO (Production ou Local)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $pdoOptions);
} catch (PDOException $e) {
    // Si la connexion de production échoue (ex: environnement local Laragon), fallback automatique sur root
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", 'root', '', $pdoOptions);
    } catch (PDOException $eLocal) {
        error_log('DB connection error: ' . $e->getMessage());
        http_response_code(500);
        echo 'Erreur de connexion à la base de données.';
        exit;
    }
}



// Initialisation des tables avancées

function ensure_system_tables(PDO $pdo): void {

    // Table pour les utilisateurs (étudiants)

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        name VARCHAR(255) NOT NULL,

        email VARCHAR(255) UNIQUE NOT NULL,

        password VARCHAR(255) NOT NULL,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Table pour les administrateurs

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        fullname VARCHAR(255) NOT NULL,

        email VARCHAR(255) UNIQUE NOT NULL,

        phone VARCHAR(50) DEFAULT NULL,

        role ENUM('admin', 'editor', 'super_admin') DEFAULT 'admin',

        password VARCHAR(255) NOT NULL,

        is_active TINYINT(1) DEFAULT 1,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Migration : s'assurer que 'notification_sound' existe dans 'admins'

    try {

        $pdo->query("SELECT notification_sound FROM admins LIMIT 1");

    } catch (PDOException $e) {

        $pdo->exec("ALTER TABLE admins ADD COLUMN notification_sound TINYINT(1) DEFAULT 1 AFTER is_active");

    }



    // Table pour les catégories

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        name VARCHAR(255) NOT NULL UNIQUE,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Migration : s'assurer que la colonne 'color' existe dans la table 'categories'

    try {

        $pdo->query("SELECT color FROM categories LIMIT 1");

    } catch (PDOException $e) {

        $pdo->exec("ALTER TABLE categories ADD COLUMN color VARCHAR(7) DEFAULT '#3F8F29' AFTER name");

    }



    // Table pour les formations

    $pdo->exec("CREATE TABLE IF NOT EXISTS formations (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        category_id INT UNSIGNED DEFAULT NULL,

        instructor_id INT UNSIGNED DEFAULT NULL,

        title VARCHAR(255) NOT NULL,

        slug VARCHAR(255) NOT NULL UNIQUE,

        short_description TEXT DEFAULT NULL,

        description LONGTEXT NOT NULL,

        level VARCHAR(50) DEFAULT 'Débutant',

        duration VARCHAR(100) DEFAULT NULL,

        price DECIMAL(15, 2) DEFAULT 0.00,

        image VARCHAR(500) DEFAULT NULL,

        status TINYINT(1) DEFAULT 1,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,

        FOREIGN KEY (instructor_id) REFERENCES admins(id) ON DELETE SET NULL

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Migration : s'assurer que instructor_id existe dans formations

    try {

        $pdo->query("SELECT instructor_id FROM formations LIMIT 1");

    } catch (PDOException $e) {

        $pdo->exec("ALTER TABLE formations ADD COLUMN instructor_id INT UNSIGNED DEFAULT NULL AFTER category_id");

        try {

            $pdo->exec("ALTER TABLE formations ADD CONSTRAINT fk_formations_instructor FOREIGN KEY (instructor_id) REFERENCES admins(id) ON DELETE SET NULL");

        } catch (PDOException $ex) {

            // La contrainte existe peut-être déjà

        }

    }



    // Table pour les messages de contact

    $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        name VARCHAR(255) NOT NULL,

        email VARCHAR(255) NOT NULL,

        subject VARCHAR(255) DEFAULT NULL,

        message TEXT NOT NULL,

        is_read TINYINT(1) DEFAULT 0,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



    // Vérification de sécurité : Ajoute la colonne is_read si elle n'existe pas encore

    try {

        $pdo->query("SELECT is_read FROM contacts LIMIT 1");

    } catch (PDOException $e) {

        $pdo->exec("ALTER TABLE contacts ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message");

    }



    // Vérification de sécurité : Ajoute la colonne is_starred si elle n'existe pas

    try {

        $pdo->query("SELECT is_starred FROM contacts LIMIT 1");

    } catch (PDOException $e) {

        $pdo->exec("ALTER TABLE contacts ADD COLUMN is_starred TINYINT(1) DEFAULT 0 AFTER is_read");

    }



    // Table pour les abonnés à la newsletter

    $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        email VARCHAR(255) UNIQUE NOT NULL,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



    // Table pour les témoignages

    $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        author_name VARCHAR(255) NOT NULL,

        author_role VARCHAR(255) DEFAULT NULL,

        content TEXT NOT NULL,

        is_active TINYINT(1) DEFAULT 1,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



    // Insertion de témoignages par défaut si la table est vide

    $count = $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();

    if ($count == 0) {

        $pdo->exec("INSERT INTO testimonials (author_name, author_role, content) VALUES 

            ('Amélie', 'Développeuse Front-end', 'Grâce à ZoeTIC, j\'ai trouvé un poste en développement web en moins de 3 mois. Le programme était clair et le support constant.'),

            ('Jean-Luc', 'Administrateur Systèmes', 'La formation en cybersécurité a changé ma vision de l\'infrastructure. Les cas pratiques sont d\'une valeur inestimable.')");

    }



    // Insertion de catégories par défaut si vide

    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

    if ($catCount == 0) {

        $pdo->exec("INSERT INTO categories (name, color) VALUES 

            ('Cybersécurité', '#F4A321'),

            ('Développement', '#3F8F29'),

            ('Productivité', '#007bff'),

            ('Outils Pro', '#6c757d')");

    }



    // Table pour le journal d'activité admin

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_logs (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        admin_id INT UNSIGNED NOT NULL,

        admin_name VARCHAR(255) NOT NULL,

        action_type VARCHAR(50) NOT NULL,

        description TEXT NOT NULL,

        ip_address VARCHAR(45) DEFAULT NULL,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



    // Table pour les conversations du chatbot

    $pdo->exec("CREATE TABLE IF NOT EXISTS chatbot_logs (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        session_id VARCHAR(255) NOT NULL,

        user_message TEXT NOT NULL,

        bot_reply TEXT NOT NULL,

        ip_address VARCHAR(45) DEFAULT NULL,

        intent VARCHAR(50) DEFAULT NULL,

        conversation_category VARCHAR(50) DEFAULT NULL,

        rating TINYINT(1) DEFAULT NULL,

        user_preferences JSON DEFAULT NULL,

        helpful_feedback TINYINT(1) DEFAULT NULL,

        is_escalated TINYINT(1) DEFAULT 0,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_session (session_id),

        INDEX idx_intent (intent),

        INDEX idx_category (conversation_category),

        INDEX idx_created (created_at)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Table pour les commentaires de feedback détaillés du chatbot

    $pdo->exec("CREATE TABLE IF NOT EXISTS chatbot_feedback (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        message_id INT UNSIGNED NOT NULL,

        feedback_text TEXT NOT NULL,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_message_id (message_id),

        FOREIGN KEY (message_id) REFERENCES chatbot_logs(id) ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Ajouter les colonnes manquantes si la table existe

    try {

        $checkColumns = $pdo->query("SHOW COLUMNS FROM chatbot_logs LIKE 'intent'")->fetch();

        if (!$checkColumns) {

            $pdo->exec("ALTER TABLE chatbot_logs ADD COLUMN intent VARCHAR(50) DEFAULT NULL AFTER ip_address");

            $pdo->exec("ALTER TABLE chatbot_logs ADD COLUMN conversation_category VARCHAR(50) DEFAULT NULL AFTER intent");

            $pdo->exec("ALTER TABLE chatbot_logs ADD COLUMN rating TINYINT(1) DEFAULT NULL AFTER conversation_category");

            $pdo->exec("ALTER TABLE chatbot_logs ADD COLUMN user_preferences JSON DEFAULT NULL AFTER rating");

            $pdo->exec("ALTER TABLE chatbot_logs ADD COLUMN helpful_feedback TINYINT(1) DEFAULT NULL AFTER user_preferences");

            $pdo->exec("ALTER TABLE chatbot_logs ADD COLUMN is_escalated TINYINT(1) DEFAULT 0 AFTER helpful_feedback");

            $pdo->exec("ALTER TABLE chatbot_logs ADD INDEX idx_session (session_id)");

            $pdo->exec("ALTER TABLE chatbot_logs ADD INDEX idx_intent (intent)");

            $pdo->exec("ALTER TABLE chatbot_logs ADD INDEX idx_category (conversation_category)");

            $pdo->exec("ALTER TABLE chatbot_logs ADD INDEX idx_created (created_at)");

        }

    } catch (Exception $e) { }



    // Table pour les documents légaux versionnés

    $pdo->exec("CREATE TABLE IF NOT EXISTS legal_documents (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        doc_type ENUM('terms', 'privacy', 'legal') NOT NULL,

        lang VARCHAR(5) DEFAULT 'fr',

        content LONGTEXT NOT NULL,

        version INT DEFAULT 1,

        is_active TINYINT(1) DEFAULT 1,

        updated_by INT UNSIGNED NULL,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Migration de secours : s'assurer que lang existe

    try { $pdo->query("SELECT lang FROM legal_documents LIMIT 1"); } 

    catch (PDOException $e) { $pdo->exec("ALTER TABLE legal_documents ADD COLUMN lang VARCHAR(5) DEFAULT 'fr' AFTER doc_type"); }



    // Seeding initial si vide

    $count = $pdo->query("SELECT COUNT(*) FROM legal_documents")->fetchColumn();

    if ($count == 0) {

        $stmt = $pdo->prepare("INSERT INTO legal_documents (doc_type, lang, content, version, is_active) VALUES (?, ?, ?, 1, 1)");

        

        // Contenu par défaut pour CGU

        $stmt->execute(['terms', 'fr', '<h3>1. Objet</h3><p>Le présent site a pour objet de fournir des informations sur les parcours certifiants...</p>']);

        // Contenu par défaut pour Politique de Confidentialité

        $stmt->execute(['privacy', 'fr', '<h3>Collecte des données</h3><p>Nous collectons les données strictement nécessaires au traitement...</p>']);

        // Contenu par défaut pour Mentions Légales

        $stmt->execute(['legal', 'fr', '<h3>Éditeur du site</h3><p>ZoeTIC Côte d\'Ivoire, Cocody Angré Abidjan.</p>']);

    }



    // Table pour les paramètres du site

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (

        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

        setting_key VARCHAR(64) UNIQUE NOT NULL,

        setting_value TEXT NULL,

        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Initialisation des paramètres par défaut si la table est vide

    $count = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();

    if ($count == 0) {

        $defaultSettings = [

            'site_name' => 'ZoeTIC',

            'contact_email' => 'contact@zoetic.ci',

            'contact_phone' => '+225 05 74 67 72 46',

            'contact_whatsapp' => '2250574677246',

            'contact_address' => 'Cocody Angré, 7e Tranche, Abidjan, Côte d\'Ivoire',

            'social_facebook' => '#',

            'social_linkedin' => '#',

            'social_instagram' => '#',

            'social_twitter' => '#',

            'social_tiktok' => '#',

            'social_youtube' => '#',

            'social_whatsapp' => '#',

            'hero_title' => 'Propulsez votre carrière au Cœur du Numérique',

            'hero_subtitle' => 'ZoeTIC forme les leaders technologiques de demain en Côte d\'Ivoire. Des formations certifiantes adaptées au marché de l\'emploi.',

            'theme_primary' => '#3F8F29',

            'theme_secondary' => '#F4A321',

            'auto_night_mode' => '0',

            'auto_night_start' => '18',

            'auto_night_end' => '6',

            'night_mode_primary_color' => '#86efac', // Vert clair pour le mode nuit

            'night_mode_secondary_color' => '#fcd34d', // Jaune clair pour le mode nuit

            'show_site_name' => '0',

            'logo_height' => '40',

            'logo_shape' => 'original',

            'google_gemini_api_key' => NULL

        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");

        foreach ($defaultSettings as $key => $value) {

            $stmt->execute([$key, $value]);

        }

    } else {

        // Migration : s'assurer que les clés de thème existent pour les installations existantes

        try {

            // Force l'unicité pour que ON DUPLICATE KEY UPDATE fonctionne sur les anciennes tables

            $pdo->exec("ALTER TABLE settings MODIFY COLUMN setting_key VARCHAR(64)");

            $pdo->exec("ALTER TABLE settings ADD UNIQUE INDEX idx_unique_setting_key (setting_key)");

        } catch (PDOException $e) {

            // L'index existe déjà ou la colonne est déjà modifiée, on ignore

        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('theme_primary', '#3F8F29'), ('theme_secondary', '#F4A321'), ('auto_night_mode', '0'), ('auto_night_start', '18'), ('auto_night_end', '6'), ('night_mode_primary_color', '#86efac'), ('night_mode_secondary_color', '#fcd34d'), ('logo_image', NULL), ('show_site_name', '0'), ('logo_height', '40'), ('logo_shape', 'original'), ('google_gemini_api_key', NULL)");

        $stmt->execute();

    }

}



/**

 * Récupère tous les paramètres du site sous forme de tableau associatif

 */

function get_site_settings(PDO $pdo): array {

    return $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

}



/**

 * Génère un nom de fichier aléatoire et sécurisé

 */

function generate_secure_filename(string $originalName, string $extension = 'webp'): string {

    // On utilise 16 octets (32 caractères) pour une unicité quasi-absolue

    $randomName = bin2hex(random_bytes(16));

    return $randomName . '.' . $extension;

}



/**

 * Chiffre une donnée sensible (ex: Clé API)

 */

function encrypt_data(string $value): string {

    // Utilisez une clé de 32 caractères. Idéalement définie dans un fichier .env ou une variable système.

    $encryption_key = getenv('APP_ENC_KEY') ?: 'Zoetic2_Secret_Master_Key_2024_#!!';

    $method = 'aes-256-cbc';

    $iv_length = openssl_cipher_iv_length($method);

    $iv = openssl_random_pseudo_bytes($iv_length);

    

    $encrypted = openssl_encrypt($value, $method, hash('sha256', $encryption_key), 0, $iv);

    return base64_encode($iv . $encrypted);

}



/**

 * Déchiffre une donnée

 */

function decrypt_data(?string $value): string {

    if (empty($value)) return '';

    $encryption_key = getenv('APP_ENC_KEY') ?: 'Zoetic2_Secret_Master_Key_2024_#!!';

    $method = 'aes-256-cbc';

    $data = base64_decode($value);

    $iv_length = openssl_cipher_iv_length($method);

    $iv = substr($data, 0, $iv_length);

    $encrypted = substr($data, $iv_length);

    

    return openssl_decrypt($encrypted, $method, hash('sha256', $encryption_key), 0, $iv) ?: $value;

}



/**

 * Redimensionne et optimise une image pour économiser de l'espace disque

 */

function process_image_upload(string $sourcePath, string $destPath, int $maxWidth = 1200, int $quality = 80): bool {

    if (!extension_loaded('gd')) return move_uploaded_file($sourcePath, $destPath);



    $info = @getimagesize($sourcePath);

    if (!$info) return false;



    list($width, $height, $type) = $info;

    

    // Calcul des nouvelles dimensions proportionnelles

    $newWidth = $width;

    $newHeight = $height;



    if ($width > $maxWidth) {

        $newWidth = $maxWidth;

        $newHeight = (int)($height * ($maxWidth / $width));

    }



    // Chargement de la source

    switch ($type) {

        case IMAGETYPE_JPEG: $srcImage = imagecreatefromjpeg($sourcePath); break;

        case IMAGETYPE_PNG:  $srcImage = imagecreatefrompng($sourcePath); break;

        case IMAGETYPE_WEBP: $srcImage = imagecreatefromwebp($sourcePath); break;

        default: return move_uploaded_file($sourcePath, $destPath);

    }



    if (!$srcImage) return false;



    // Création de la nouvelle image

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);



    // Gestion de la transparence pour PNG et WebP

    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {

        imagealphablending($dstImage, false);

        imagesavealpha($dstImage, true);

        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);

        imagefill($dstImage, 0, 0, $transparent);

    }



    // Redimensionnement

    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);



    // Sauvegarde forcée au format WebP pour un gain d'espace maximal

    $result = imagewebp($dstImage, $destPath, $quality);



    return $result;
}



/**
 * Génère automatiquement les favicons (ICO, PNG 32x32, 16x16, 180x180) à partir d'une image de logo
 */
function generate_favicon_from_image(string $sourceImagePath): bool {
    if (!file_exists($sourceImagePath) || !extension_loaded('gd')) return false;

    $data = @file_get_contents($sourceImagePath);
    if (!$data) return false;

    $src = @imagecreatefromstring($data);
    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);

    $makeSquare = function(int $size) use ($src, $w, $h) {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
        imagealphablending($canvas, true);

        $pad = (int)($size * 0.08);
        $availW = $size - 2 * $pad;
        $availH = $size - 2 * $pad;
        $scale = min($availW / $w, $availH / $h);
        $targetW = (int)($w * $scale);
        $targetH = (int)($h * $scale);
        $dstX = (int)(($size - $targetW) / 2);
        $dstY = (int)(($size - $targetH) / 2);

        imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $targetW, $targetH, $w, $h);
        return $canvas;
    };

    $baseDir = __DIR__ . '/../../';
    $logosDir = $baseDir . 'uploads/logos/';
    if (!is_dir($logosDir)) @mkdir($logosDir, 0755, true);

    $fav512 = $makeSquare(512);
    imagepng($fav512, $logosDir . 'favicon-512x512.png');
    imagedestroy($fav512);

    $fav180 = $makeSquare(180);
    imagepng($fav180, $logosDir . 'apple-touch-icon.png');
    imagepng($fav180, $baseDir . 'apple-touch-icon.png');
    imagedestroy($fav180);

    $fav48 = $makeSquare(48);
    imagepng($fav48, $logosDir . 'favicon-48x48.png');
    imagedestroy($fav48);

    $fav32 = $makeSquare(32);
    imagepng($fav32, $logosDir . 'favicon-32x32.png');
    imagepng($fav32, $baseDir . 'favicon.png');
    imagedestroy($fav32);

    $fav16 = $makeSquare(16);
    imagepng($fav16, $logosDir . 'favicon-16x16.png');
    imagedestroy($fav16);

    imagedestroy($src);

    $pngFiles = [
        $logosDir . 'favicon-16x16.png',
        $logosDir . 'favicon-32x32.png',
        $logosDir . 'favicon-48x48.png'
    ];
    $images = [];
    foreach ($pngFiles as $file) {
        if (!file_exists($file)) continue;
        $imgData = @file_get_contents($file);
        $imgSize = @getimagesize($file);
        if (!$imgSize || !$imgData) continue;
        $images[] = [
            'width' => ($imgSize[0] >= 256) ? 0 : $imgSize[0],
            'height' => ($imgSize[1] >= 256) ? 0 : $imgSize[1],
            'data' => $imgData,
            'size' => strlen($imgData)
        ];
    }
    if (!empty($images)) {
        $count = count($images);
        $header = pack('vvv', 0, 1, $count);
        $offset = 6 + (16 * $count);
        $directory = '';
        $imageData = '';
        foreach ($images as $img) {
            $directory .= pack('CCCCvvVV', $img['width'], $img['height'], 0, 0, 1, 32, $img['size'], $offset);
            $imageData .= $img['data'];
            $offset += $img['size'];
        }
        @file_put_contents($baseDir . 'favicon.ico', $header . $directory . $imageData);
        @copy($baseDir . 'favicon.ico', $logosDir . 'favicon.ico');
    }

    return true;
}

/**
 * Applique un filigrane (watermark) graphique sur une image
 */
function apply_watermark(string $sourcePath, string $logoPath = 'src/img/logo.png'): bool {

    if (!extension_loaded('gd') || !file_exists($sourcePath)) return false;



    // Chemin vers le logo (doit être accessible depuis ce fichier)

    $fullLogoPath = __DIR__ . '/../../' . $logoPath;

    if (!file_exists($fullLogoPath)) return false;



    $info = @getimagesize($sourcePath);

    if (!$info) return false;



    $mime = $info['mime'];

    $image = null;

    

    if ($mime === 'image/jpeg') $image = @imagecreatefromjpeg($sourcePath);

    elseif ($mime === 'image/png') $image = @imagecreatefrompng($sourcePath);

    elseif ($mime === 'image/webp') $image = @imagecreatefromwebp($sourcePath);



    if (!$image) return false;



    // Chargement du logo

    $logoInfo = @getimagesize($fullLogoPath);

    if (!$logoInfo) { return false; }

    $logoMime = $logoInfo['mime'];

    $logo = null;

    

    if ($logoMime === 'image/jpeg') $logo = @imagecreatefromjpeg($fullLogoPath);

    elseif ($logoMime === 'image/png') $logo = @imagecreatefrompng($fullLogoPath);

    elseif ($logoMime === 'image/webp') $logo = @imagecreatefromwebp($fullLogoPath);

    

    if (!$logo) { return false; }



    // Activer la gestion de la transparence pour l'overlay

    imagealphablending($image, true);

    imagesavealpha($image, true);



    // Dimensions

    $width = imagesx($image);

    $height = imagesy($image);

    $logoW = imagesx($logo);

    $logoH = imagesy($logo);



    // Redimensionnement automatique si le logo est trop grand (max 20% de l'image)

    $maxW = $width * 0.20;

    $maxH = $height * 0.20;



    if ($logoW > $maxW || $logoH > $maxH) {

        $scale = min($maxW / $logoW, $maxH / $logoH);

        $newW = (int)($logoW * $scale);

        $newH = (int)($logoH * $scale);



        $resizedLogo = imagecreatetruecolor($newW, $newH);

        // Préserver la transparence du nouveau logo

        imagealphablending($resizedLogo, false);

        imagesavealpha($resizedLogo, true);

        imagecopyresampled($resizedLogo, $logo, 0, 0, 0, 0, $newW, $newH, $logoW, $logoH);

        

        $logo = $resizedLogo;

        $logoW = $newW;

        $logoH = $newH;

    }

    

    // Positionnement : Bas à droite (20px de marge)

    $posX = $width - $logoW - 20;

    $posY = $height - $logoH - 20;



    // Fusion du logo sur l'image source

    imagecopy($image, $logo, $posX, $posY, 0, 0, $logoW, $logoH);



    // Sauvegarde

    $result = false;

    if ($mime === 'image/jpeg') $result = imagejpeg($image, $sourcePath, 90);

    elseif ($mime === 'image/png') $result = imagepng($image, $sourcePath);

    elseif ($mime === 'image/webp') $result = imagewebp($image, $sourcePath);



    return $result;
}



/**

 * Centralise la journalisation des actions dans admin_logs

 */

function log_admin_activity(PDO $pdo, string $type, string $description, ?int $adminId = null, string $adminName = 'SYSTEM'): void {

    try {

        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, ?, ?, ?)");

        $stmt->execute([

            $adminId ?? 0,

            $adminName,

            $type,

            $description,

            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'

        ]);

    } catch (Exception $e) {

        error_log("Logging Error: " . $e->getMessage());

    }

}

?>

