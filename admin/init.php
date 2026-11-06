
<?php


// Paramètres de connexion
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'zoetic';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

// Tentative de connexion avec PDO
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erreur de connexion à la base de données.';
    exit;
}

// Initialisation des tables avancées
function ensure_system_tables(PDO $pdo) {
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
}

/**
 * Génère un nom de fichier aléatoire et sécurisé
 */
function generate_secure_filename(string $originalName): string {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    // On utilise 16 octets (32 caractères) pour une unicité quasi-absolue
    $randomName = bin2hex(random_bytes(16));
    return $randomName . '.' . $extension;
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
?>
