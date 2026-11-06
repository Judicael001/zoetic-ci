<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/src/include/i18n.php';
require_once __DIR__ . '/src/db/init.php'; // This includes PDO connection and ensure_system_tables
require_once __DIR__ . '/src/db/blog.php'; // This includes ensure_blog_table
require_once __DIR__ . '/src/db/enrollments.php'; // This includes ensure_enrollments_table and get_available_formations
require_once __DIR__ . '/src/db/realisations.php'; // Gestion des réalisations et prestations

$studentCount = null;
$formationCount = null;
$enrollmentCount = null;
$publishedBlogCount = null;
$filteredBlogCount = 0;
$totalBlogPages = 1;
$blogCount = null;
$allBlogPosts = [];
$blogCategories = [];
$availableFormations = [];
$allRealisations = [];
$realisationCategories = [];
$featuredRealisations = [];
$testimonials = [];
$legalDocs = [];
$registrationStatus = $_GET['registration_status'] ?? '';
$registrationMessage = $_GET['message'] ?? '';

$registrationStatus = in_array($registrationStatus, ['success', 'error'], true) ? $registrationStatus : '';
$registrationMessage = trim((string)$registrationMessage);

try {
    // Paramètres de pagination pour le blog
    $blogPage = isset($_GET['blog_page']) ? max(1, (int) $_GET['blog_page']) : 1;
    $blogPerPage = 3; // On garde 3 articles par vue
    $blogOffset = ($blogPage - 1) * $blogPerPage;

    $searchQuery = $_GET['q'] ?? null;
    $selectedCategory = $_GET['category'] ?? null;

    // Récupération des paramètres du site
    $siteSettings = get_site_settings($pdo);

    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users) as students,
            (SELECT COUNT(*) FROM formations) as formations,
            (SELECT COUNT(*) FROM enrollments) as enrollments,
            (SELECT COUNT(*) FROM blog_posts WHERE status = 'published') as blog_published
    ")->fetch();

    $studentCount = (int) ($stats['students'] ?? 0);
    $formationCount = (int) ($stats['formations'] ?? 0);
    $enrollmentCount = (int) ($stats['enrollments'] ?? 0);
    $publishedBlogCount = (int) ($stats['blog_published'] ?? 0);

    // Calcul du nombre d'articles filtrés pour la pagination
    $filteredBlogCount = count_all_blog_posts($pdo, $selectedCategory, $searchQuery);
    $totalBlogPages = max(1, (int) ceil($filteredBlogCount / $blogPerPage));

    // Récupération des articles paginés
    $allBlogPosts = get_all_blog_posts($pdo, $blogPerPage, $selectedCategory, $searchQuery, $blogOffset);
    $blogCategories = get_blog_categories($pdo);

    $testimonials = $pdo->query("SELECT author_name, author_role, content FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 2")->fetchAll();

    // Récupération des documents légaux actifs pour la langue courante
    $lstmt = $pdo->prepare("SELECT doc_type, content FROM legal_documents WHERE is_active = 1 AND lang = ?");
    $lstmt->execute([$_SESSION['lang'] ?? 'fr']);
    $legalDocs = $lstmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $availableFormations = get_available_formations($pdo);

    // Initialisation et chargement des réalisations et prestations
    ensure_realisations_tables($pdo);
    $allRealisations = get_all_realisations($pdo, 'published');
    $realisationCategories = get_realisation_categories($pdo);
    $featuredRealisations = get_featured_realisations($pdo, 3);
} catch (PDOException $e) {
    error_log('index.php PDOException: ' . $e->getMessage());
    // En cas d'erreur, s'assurer que les tableaux sont vides pour éviter des erreurs dans la vue
    $allBlogPosts = [];
    $filteredBlogCount = 0;
    $totalBlogPages = 1;
    $availableFormations = [];
    $allRealisations = [];
    $realisationCategories = [];
    $featuredRealisations = [];
}
if (!isset($allBlogPosts) || !is_array($allBlogPosts)) {
    $allBlogPosts = [];
}

$registrationMessage = trim($registrationMessage);

include __DIR__ . '/src/include/head.php';
include __DIR__ . '/src/include/nav.php';
include __DIR__ . '/src/views/index.view.php';
include __DIR__ . '/src/include/footer.php';
