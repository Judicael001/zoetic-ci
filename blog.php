<?php
session_start();
require_once __DIR__ . '/src/include/i18n.php';
require_once __DIR__ . '/src/db/init.php';
require_once __DIR__ . '/src/db/blog.php';

// Récupération de la catégorie filtrée depuis l'URL
$selectedCategory = trim($_GET['category'] ?? '');
$searchQuery = trim((string)($_GET['q'] ?? ''));

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

try {
    ensure_blog_table($pdo);
    
    // Récupération des données nécessaires
    $categories = get_blog_categories($pdo);
    
    $totalPosts = count_all_blog_posts($pdo, $selectedCategory ?: null, $searchQuery ?: null);
    $totalPages = max(1, (int) ceil($totalPosts / $perPage));
    
    $posts = get_all_blog_posts($pdo, $perPage, $selectedCategory ?: null, $searchQuery ?: null, $offset);
    
} catch (PDOException $e) {
    error_log('blog.php Error: ' . $e->getMessage());
    $posts = [];
    $categories = [];
}

include __DIR__ . '/src/include/head.php';
include __DIR__ . '/src/include/nav.php';
include __DIR__ . '/src/views/blog.view.php';
include __DIR__ . '/src/include/footer.php';