<?php

function ensure_enrollments_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_email VARCHAR(255) NOT NULL,
            student_phone VARCHAR(50) DEFAULT NULL,
            formation_id INT NULL,
            formation_title VARCHAR(255) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            enrollment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function get_available_formations(PDO $pdo): array
{
    try {
        $stmt = $pdo->query(
            'SELECT f.id, f.title, f.short_description, f.description, f.level, f.duration, f.price, f.image, f.status, c.name AS category, c.color AS category_color, a.fullname AS instructor
             FROM formations f
             LEFT JOIN categories c ON c.id = f.category_id
             LEFT JOIN admins a ON a.id = f.instructor_id
             WHERE f.status = 1
             ORDER BY f.created_at DESC'
        );
        $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($formations)) {
            return $formations;
        }
    } catch (PDOException $e) {
        // Table may not exist yet, fallback to hardcoded options.
    }

    return [
        [
            'id' => 1,
            'title' => 'Cybersécurité (Niveau 1)',
            'short_description' => 'Apprenez à identifier les vulnérabilités et à sécuriser les réseaux.',
            'description' => 'Maîtrisez les fondamentaux de la cybersécurité et les bonnes pratiques pour protéger les infrastructures.',
            'level' => 'Intermédiaire',
            'duration' => '40 Heures',
            'price' => '120000.00',
            'image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=800',
            'status' => 1,
            'category' => 'Cybersécurité',
            'category_color' => '#F4A321',
            'instructor' => 'Expert ZoeTIC'
        ],
        [
            'id' => 2,
            'title' => 'Développement Web Fullstack',
            'short_description' => 'Maîtrisez HTML, CSS, JavaScript et les frameworks modernes.',
            'description' => 'Créez des applications web modernes du front-end au back-end avec les outils du marché.',
            'level' => 'Débutant',
            'duration' => '60 Heures',
            'price' => '150000.00',
            'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800',
            'status' => 1,
            'category' => 'Développement',
            'category_color' => '#3F8F29',
            'instructor' => 'Équipe Dev'
        ],
        [
            'id' => 3,
            'title' => 'Maîtrise Office 365 Pro',
            'short_description' => 'Exploitez le potentiel d Excel, Teams et SharePoint.',
            'description' => 'Devenez un expert Office 365 pour optimiser votre productivité et vos processus collaboratifs.',
            'level' => 'Tous niveaux',
            'duration' => '30 Heures',
            'price' => '90000.00',
            'image' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=800',
            'status' => 1,
            'category' => 'Productivité',
            'category_color' => '#007bff',
            'instructor' => 'Coach Pro'
        ],
        [
            'id' => 4,
            'title' => 'Outils Collaboratifs & Agile',
            'short_description' => 'Améliorez votre travail d équipe avec Agile et les outils collaboratifs.',
            'description' => 'Adoptez les méthodes de travail modernes pour mieux piloter vos projets en équipe.',
            'level' => 'Tous niveaux',
            'duration' => '35 Heures',
            'price' => '100000.00',
            'image' => 'https://images.unsplash.com/photo-1498079022511-d15614cb1c02?auto=format&fit=crop&w=800',
            'status' => 1,
            'category' => 'Outils Pro',
            'category_color' => '#6c757d',
            'instructor' => 'Consultant Agile'
        ],
    ];
}

function get_formation_title(PDO $pdo, int $formationId): ?string
{
    if ($formationId <= 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT title FROM formations WHERE id = ? LIMIT 1');
        $stmt->execute([$formationId]);
        $title = $stmt->fetchColumn();
        return $title !== false ? $title : null;
    } catch (PDOException $e) {
        error_log('get_formation_title() error: ' . $e->getMessage());
        return null;
    }
}
