<?php
session_start();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/blog.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
$selectedCat = trim((string)($_GET['category'] ?? ''));
$page = isset($_GET['blog_page']) ? max(1, (int) $_GET['blog_page']) : 1;
$perPage = 3;
$offset = ($page - 1) * $perPage;

try {
    ensure_blog_table($pdo);
    $totalPosts = count_all_blog_posts($pdo, $selectedCat ?: null, $q ?: null);
    $totalPages = max(1, (int) ceil($totalPosts / $perPage));
    $posts = get_all_blog_posts($pdo, $perPage, $selectedCat ?: null, $q ?: null, $offset);

    ob_start(); // Démarrer la capture du HTML

if (!empty($posts)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-in">
        <?php foreach ($posts as $postItem) : ?>
            <article class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative h-48 overflow-hidden">
                    <img src="<?php echo htmlspecialchars($postItem['featured_image'] ?: 'https://images.unsplash.com/photo-1526628953301-3e589a6a8b74?auto=format&fit=crop&w=1200', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($postItem['title'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-all duration-700" loading="lazy">
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <span class="text-xs text-gray-400"><?php echo date('d F Y', strtotime($postItem['created_at'])); ?></span>
                        <span class="text-xs text-primary font-bold bg-[#E6F7E6] px-3 py-1 rounded-full"><?php echo htmlspecialchars($postItem['category'] ?: 'Actualité', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <h3 class="font-title text-lg font-bold mb-2 leading-snug"><?php echo highlight_search_term($postItem['title'], $q); ?></h3>
                    <p class="text-gray-500 text-sm mb-4 line-clamp-2 leading-relaxed">
                        <?php 
                            $previewText = mb_substr(strip_tags($postItem['excerpt'] ?: $postItem['content']), 0, 150, 'UTF-8');
                            echo highlight_search_term($previewText, $q); 
                        ?>
                    </p>
                    <a href="blog_post.php?slug=<?php echo urlencode($postItem['slug']); ?>" class="text-primary font-bold inline-flex items-center hover:text-secondary transition">Lire la suite <i class="fas fa-arrow-right ml-2 text-sm"></i></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1) : ?>
        <nav class="mt-8 flex justify-center items-center space-x-2 animate-in" style="animation-delay: 150ms">
            <?php if ($page > 1): ?>
                <a href="index.php?<?php echo http_build_query(array_merge($_GET, ['blog_page' => $page - 1])); ?>#page-blog" 
                   class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-primary hover:text-white transition-all shadow-sm">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)): ?>
                    <a href="index.php?<?php echo http_build_query(array_merge($_GET, ['blog_page' => $p])); ?>#page-blog" 
                       class="w-10 h-10 flex items-center justify-center rounded-full text-sm font-bold transition-all <?php echo ($p === $page) ? 'bg-primary text-white shadow-lg' : 'bg-white border border-gray-100 text-gray-600'; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="index.php?<?php echo http_build_query(array_merge($_GET, ['blog_page' => $page + 1])); ?>#page-blog" 
                   class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-primary hover:text-white transition-all shadow-sm">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else : ?>
    <div class="md:col-span-3 py-20 px-6 bg-white rounded-[3rem] border border-slate-100 text-center shadow-sm ajax-no-results">
        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
            <i class="fas fa-search text-slate-200 text-4xl"></i>
        </div>
        <h3 class="font-title text-3xl font-black text-slate-900 mb-4 uppercase tracking-tight">Aucun résultat</h3>
        <p class="text-gray-500 max-w-lg mx-auto">Désolé, nous n'avons trouvé aucun article correspondant à votre recherche.</p>
    </div>
<?php endif; ?>
<?php
    $htmlContent = ob_get_clean(); // Récupère le HTML capturé

    echo json_encode(['status' => 'success', 'html' => $htmlContent, 'count' => $totalPosts]);

} catch (PDOException $e) {
    error_log('AJAX Blog Search PDO Error: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur de base de données est survenue. Veuillez réessayer plus tard.']);
} catch (Exception $e) {
    error_log('AJAX Blog Search General Error: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.']);
}