<?php
session_start();
require_once __DIR__ . '/src/include/i18n.php';
require_once 'src/db/init.php';
require_once 'src/db/blog.php';
require_once 'src/db/enrollments.php';

ensure_blog_table($pdo);
ensure_blog_media_table($pdo);
ensure_blog_comments_table($pdo);
//Ajoute un système de "réponse" aux commentaires pour permettre des fils de discussion.
//Crée une page de modération des commentaires dans l'espace administration.
$slug = trim($_GET['slug'] ?? '');
$post = null;
$recentPosts = [];
$adjacent = ['prev' => null, 'next' => null];
$availableFormations = [];
$comments = [];
$relatedPosts = [];
$readingTime = 1;
$shareUrl = '';
$shareTitle = '';
$pageDescription = '';
$pageImage = '';

if ($slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE slug = ?');
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    // Incrémenter les vues
    $updateViews = $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
    $updateViews->execute([$post['id']]);
}

// fetch media attachments
$postMedia = [];
if ($post && isset($post['id'])) {
    $postMedia = get_media_for_post($pdo, $post['id']);

    // Récupérer les commentaires approuvés
    $commentStmt = $pdo->prepare("SELECT * FROM blog_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $commentStmt->execute([$post['id']]);
    $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    $adjacent = get_adjacent_posts($pdo, (int)$post['id']);
    $relatedPosts = get_related_posts($pdo, (int)$post['id'], $post['category'] ?: 'Actualités');

    // Calcul du temps de lecture (moyenne 200 mots/min)
    $wordCount = str_word_count(strip_tags($post['content']));
    $readingTime = max(1, ceil($wordCount / 200));

    // URL actuelle pour le partage
    $shareUrl = urlencode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $shareTitle = urlencode($post['title']);

    // SEO Data (à utiliser dans head.php si possible)
    $pageTitle = htmlspecialchars($post['title']) . " | Blog ZoeTIC";
    $pageDescription = htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160));
    $pageImage = $post['featured_image'] ?: 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200';
}

try {
    $recentStmt = $pdo->prepare('SELECT id, title, slug, created_at, featured_image FROM blog_posts WHERE slug <> ? AND status = "published" ORDER BY created_at DESC LIMIT 3');
    $recentStmt->execute([$slug]);
    $recentPosts = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentPosts = [];
}

$availableFormations = get_available_formations($pdo);

// S'assurer que $availableFormations est toujours un tableau avant d'inclure les vues
if (!isset($availableFormations) || !is_array($availableFormations)) {
    $availableFormations = [];
}

include 'src/include/head.php';
include 'src/include/nav.php';
?>

<style>
    /* Reading Mode Transitions */
    body, main, article, aside, .bg-white, .bg-light {
        transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease;
    }

    /* Dark Mode Overrides */
    body.reading-mode-dark {
        background-color: #0f172a !important; /* slate-900 */
        color: #cbd5e1 !important; /* slate-300 */
    }
    body.reading-mode-dark main.bg-light { background-color: #0f172a !important; }
    body.reading-mode-dark .bg-white {
        background-color: #1e293b !important; /* slate-800 */
        border-color: #334155 !important; /* slate-700 */
    }
    body.reading-mode-dark .text-dark, 
    body.reading-mode-dark .text-slate-900,
    body.reading-mode-dark .font-bold.text-dark { color: #f8fafc !important; }
    
    body.reading-mode-dark .prose { color: #cbd5e1 !important; }
    body.reading-mode-dark .prose h1, 
    body.reading-mode-dark .prose h2, 
    body.reading-mode-dark .prose h3 { color: #f8fafc !important; }
    
    body.reading-mode-dark .dark-mode-toggle {
        background-color: #334155 !important;
        border-color: #475569 !important;
        color: #fbbf24 !important; /* yellow-400 */
    }

    @keyframes pulse-badge {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(63, 143, 41, 0.4); }
        50% { transform: scale(1.08); box-shadow: 0 0 20px 5px rgba(63, 143, 41, 0.2); border-color: #3F8F29; }
    }
    .animate-pulse-reading {
        animation: pulse-badge 1.8s infinite ease-in-out;
        background-color: white !important;
    }

    /* ScrollSpy Active State */
    .toc-link.active {
        color: var(--color-primary);
        font-weight: 800;
        border-left: 3px solid var(--color-primary);
        padding-left: 1rem;
    }
</style>

<!-- Reading Progress Bar -->
<div id="reading-progress" class="fixed top-0 left-0 h-1.5 bg-gradient-to-r from-primary to-secondary z-[100] transition-all duration-75 shadow-[0_0_10px_rgba(63,143,41,0.3)]" style="width: 0%"></div>
<div id="progress-indicator" class="fixed top-6 right-6 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-gray-100 shadow-sm text-[10px] font-black text-primary uppercase tracking-[0.2em] z-[100] opacity-0 transition-opacity duration-300 pointer-events-none">0%</div>

<!-- Schema.org Structured Data -->
<?php if ($post): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "<?php echo addslashes($post['title']); ?>",
  "image": "<?php echo $pageImage; ?>",
  "author": { "@type": "Organization", "name": "ZoeTIC" },
  "publisher": { "@type": "Organization", "name": "ZoeTIC", "logo": { "@type": "ImageObject", "url": "https://zoetic.ci/logo.png" } },
  "datePublished": "<?php echo $post['created_at']; ?>",
  "description": "<?php echo addslashes($pageDescription); ?>"
}
</script>
<?php endif; ?>

<main class="pt-24 bg-light min-h-screen">
    <div class="container mx-auto px-6 py-16">
        <?php if ($post) : ?>
            <!-- Breadcrumbs -->
            <nav class="flex mb-8 text-sm text-gray-500 font-medium" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="index.php" class="hover:text-primary transition-colors">Accueil</a></li>
                    <li><i class="fas fa-chevron-right text-[10px] mx-2"></i></li>
                    <li><a href="index.php#page-blog" class="hover:text-primary transition-colors">Blog</a></li>
                    <li><i class="fas fa-chevron-right text-[10px] mx-2"></i></li>
                    <li class="text-gray-400 truncate max-w-[200px]"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></li>
                </ol>
            </nav>

            <div class="grid gap-10 lg:grid-cols-[2.2fr_1fr]">
                <article class="space-y-8">
                    <div class="rounded-3xl overflow-hidden shadow-2xl">
                        <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1600', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-96 object-cover" loading="lazy">
                    </div>

                    <?php if (!empty($postMedia)) : ?>
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php foreach ($postMedia as $m) : ?>
                                <?php if ($m['media_type'] === 'image') : ?>
                                    <div class="rounded-2xl overflow-hidden bg-slate-100">
                                        <img src="<?php echo htmlspecialchars($m['filename'], ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-full h-48 object-cover" loading="lazy">
                                    </div>
                                <?php else : ?>
                                    <div class="rounded-2xl overflow-hidden bg-slate-100">
                                        <video controls class="w-full h-48 object-cover" preload="metadata">
                                            <source src="<?php echo htmlspecialchars($m['filename'], ENT_QUOTES, 'UTF-8'); ?>" type="<?php echo htmlspecialchars($m['mime'], ENT_QUOTES, 'UTF-8'); ?>">
                                            Votre navigateur ne supporte pas la lecture de la vidéo.
                                        </video>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 uppercase tracking-[0.3em] font-bold">
                            <span><?php echo htmlspecialchars($post['category'] ?: 'Actualités', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>&bull;</span>
                            <span><?php echo date('d F Y', strtotime($post['created_at'])); ?></span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1"><i class="far fa-clock"></i> <?php echo $readingTime; ?> min de lecture</span>
                        </div>
                        
                        <div class="flex items-center justify-between gap-4 mb-2">
                            <div class="flex items-center gap-4 text-xs text-gray-400 font-bold uppercase tracking-wider">
                                <span class="flex items-center gap-1.5"><i class="far fa-eye text-primary"></i> <?php echo number_format($post['views']); ?> vues</span>
                                <button id="like-btn" onclick="toggleLike(<?php echo $post['id']; ?>)" class="flex items-center gap-1.5 hover:text-red-500 transition-colors">
                                    <i class="far fa-heart"></i> <span id="like-count"><?php echo number_format($post['likes']); ?></span> j'aime
                                </button>
                            </div>
                            <button onclick="toggleReadingMode()" class="dark-mode-toggle p-2.5 rounded-xl bg-white border border-gray-100 shadow-sm text-slate-400 hover:text-primary transition-all" title="Mode lecture">
                                <i class="fas fa-moon"></i>
                            </button>
                        </div>

                        <h1 class="font-title text-4xl font-bold text-dark"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

                        <div class="max-w-3xl text-lg text-gray-600 leading-relaxed">
                            <?php echo $post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 220, 'UTF-8'); ?>
                        </div>
                        <div class="prose prose-lg max-w-none text-gray-700">
                            <?php echo $post['content']; ?>
                        </div>
                    </div>

                    <!-- Author Bio -->
                    <div class="flex flex-col sm:flex-row items-center gap-6 p-8 rounded-3xl bg-white border border-gray-100 shadow-sm mt-12">
                        <div class="w-20 h-20 rounded-2xl bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold shrink-0">ZT</div>
                        <div class="text-center sm:text-left">
                            <h4 class="font-bold text-dark text-lg">Équipe de rédaction ZoeTIC</h4>
                            <p class="text-gray-500 text-sm mt-1 leading-relaxed">Experts en formation numérique et cybersécurité, nous partageons les meilleures pratiques et actualités du secteur en Côte d'Ivoire.</p>
                        </div>
                    </div>

                    <!-- Related Posts -->
                    <?php if (!empty($relatedPosts)): ?>
                    <div class="pt-12">
                        <h3 class="font-title text-2xl font-bold text-dark mb-8">Articles Similaires</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($relatedPosts as $related): ?>
                                <a href="blog_post.php?slug=<?php echo urlencode($related['slug']); ?>" class="group flex gap-4 p-4 rounded-3xl bg-white border border-transparent hover:border-primary/20 hover:shadow-xl transition-all">
                                    <img src="<?php echo htmlspecialchars($related['featured_image'] ?: 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=300', ENT_QUOTES, 'UTF-8'); ?>" class="w-24 h-24 rounded-2xl object-cover shrink-0" loading="lazy">
                                    <div class="flex flex-col justify-center">
                                        <span class="text-[10px] text-primary font-bold uppercase tracking-wider"><?php echo htmlspecialchars($related['category']); ?></span>
                                        <h4 class="font-bold text-dark group-hover:text-primary transition-colors text-sm line-clamp-2 mt-1"><?php echo htmlspecialchars($related['title']); ?></h4>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo date('d M Y', strtotime($related['created_at'])); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Partage Social -->
                    <div class="flex flex-wrap items-center gap-4 py-8 border-t border-gray-100">
                        <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Partager :</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#1DA1F2] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-twitter"></i></a>
                        <a href="https://api.whatsapp.com/send?text=<?php echo $shareTitle . '%20' . $shareUrl; ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#25D366] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#0A66C2] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-linkedin-in"></i></a>
                        <button onclick="copyToClipboard()" class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-primary hover:text-white transition" title="Copier le lien"><i class="fas fa-link"></i></button>
                    </div>

                    <!-- Section Commentaires Threadés -->
                    <div class="pt-12" id="comments-section">
                        <h3 class="font-title text-2xl font-bold text-dark mb-8 flex items-center gap-3">
                            Réactions <span class="bg-slate-100 text-slate-400 text-sm px-3 py-1 rounded-full" id="comment-count-badge"><?php echo count($comments); ?></span>
                        </h3>

                        <!-- Formulaire de commentaire -->
                        <div id="comment-form-container" class="bg-slate-50 rounded-[2rem] p-8 mb-12 border border-slate-100 transition-all">
                            <div id="reply-indicator" class="hidden mb-4 flex items-center justify-between bg-primary/10 px-4 py-2 rounded-xl border border-primary/20">
                                <span class="text-xs font-bold text-primary">Réponse à <span id="reply-target-name"></span></span>
                                <button onclick="cancelReply()" class="text-xs font-black uppercase text-primary hover:text-dark">Annuler</button>
                            </div>
                            <h4 class="font-bold text-slate-800 mb-6 text-lg">Laissez un message</h4>
                            <form id="comment-form" onsubmit="submitComment(event)" class="space-y-4">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <input type="hidden" name="parent_id" id="form-parent-id" value="">
                                <!-- Anti-spam HoneyPot (invisible pour les humains) -->
                                <div class="hidden" aria-hidden="true">
                                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input type="text" name="name" required placeholder="Votre nom" class="w-full px-6 py-4 bg-white border-0 rounded-2xl outline-none focus:ring-4 focus:ring-primary/5 transition-all text-sm font-medium">
                                    <input type="email" name="email" required placeholder="votre@email.com" class="w-full px-6 py-4 bg-white border-0 rounded-2xl outline-none focus:ring-4 focus:ring-primary/5 transition-all text-sm font-medium">
                                </div>
                                <textarea name="content" required rows="4" placeholder="Votre commentaire..." class="w-full px-6 py-4 bg-white border-0 rounded-2xl outline-none focus:ring-4 focus:ring-primary/5 transition-all text-sm font-medium resize-none"></textarea>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="notify_replies" id="notify_replies" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                                    <label for="notify_replies" class="text-sm text-slate-600">
                                        M'informer des nouvelles réponses par email
                                    </label>
                                </div>

                                <button type="submit" class="bg-primary text-white px-10 py-4 rounded-2xl font-bold hover:bg-green-700 transition-all shadow-lg shadow-green-900/10 flex items-center gap-3">
                                    <span>Publier le commentaire</span>
                                    <i class="fas fa-paper-plane text-xs"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Liste des commentaires -->
                        <div class="space-y-8" id="comments-list">
                            <?php 
                            $parents = array_filter($comments, fn($c) => is_null($c['parent_id']));
                            if (!empty($parents)): 
                                foreach ($parents as $comment): 
                                    $replies = array_filter($comments, fn($c) => (int)$c['parent_id'] === (int)$comment['id']);
                            ?>
                                <div class="comment-thread space-y-6" id="comment-<?php echo $comment['id']; ?>">
                                    <!-- Parent Comment -->
                                    <div class="flex gap-6 animate-in">
                                        <div class="w-14 h-14 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center text-primary font-black text-xl shrink-0">
                                            <?php echo strtoupper(substr($comment['name'], 0, 1)); ?>
                                        </div>
                                        <div class="space-y-2 flex-grow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <h5 class="font-bold text-slate-900 flex items-center gap-2">
                                                        <?php 
                                                            $isAdmin = strpos($comment['name'], '(Admin)') !== false;
                                                            echo htmlspecialchars(trim(str_replace('(Admin)', '', $comment['name'])));
                                                        ?>
                                                        <?php if ($isAdmin): ?><span class="bg-primary text-white text-[8px] px-1.5 py-0.5 rounded font-black uppercase tracking-tighter">Admin</span><?php endif; ?>
                                                    </h5>
                                                    <span class="text-[10px] font-black uppercase text-slate-300 tracking-widest"><?php echo date('d M Y', strtotime($comment['created_at'])); ?></span>
                                                </div>
                                                <button onclick="setReply(<?php echo $comment['id']; ?>, '<?php echo addslashes($comment['name']); ?>')" class="text-[10px] font-black uppercase text-primary hover:text-secondary transition-colors">Répondre</button>
                                            </div>
                                            <p class="text-slate-600 leading-relaxed text-sm"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        </div>
                                    </div>

                                    <!-- Replies -->
                                    <?php if (!empty($replies)): ?>
                                        <div class="ml-14 space-y-6 border-l-2 border-slate-50 pl-8">
                                            <?php foreach ($replies as $reply): ?>
                                                <div class="flex gap-4 animate-in">
                                                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-sm shrink-0">
                                                        <?php echo strtoupper(substr($reply['name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <h5 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                                            <?php 
                                                                $isReplyAdmin = strpos($reply['name'], '(Admin)') !== false;
                                                                echo htmlspecialchars(trim(str_replace('(Admin)', '', $reply['name'])));
                                                            ?>
                                                            <?php if ($isReplyAdmin): ?><span class="bg-primary text-white text-[8px] px-1.5 py-0.5 rounded font-black uppercase tracking-tighter">Admin</span><?php endif; ?>
                                                        </h5>
                                                        <p class="text-slate-600 text-xs leading-relaxed"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="reply-container-<?php echo $comment['id']; ?>"></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-10" id="no-comments-msg">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                        <i class="fas fa-comments text-2xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium">Soyez le premier à réagir à cet article !</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Navigation entre articles -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-8 border-t border-gray-100">
                        <?php if ($adjacent['prev']) : ?>
                            <a href="blog_post.php?slug=<?php echo urlencode($adjacent['prev']['slug']); ?>" class="group p-6 rounded-3xl border border-gray-100 hover:border-primary transition-all text-left">
                                <p class="text-xs text-gray-400 uppercase font-bold mb-2">Précédent</p>
                                <h4 class="font-bold text-dark group-hover:text-primary transition-colors"><?php echo htmlspecialchars($adjacent['prev']['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            </a>
                        <?php else: ?><div></div><?php endif; ?>

                        <?php if ($adjacent['next']) : ?>
                            <a href="blog_post.php?slug=<?php echo urlencode($adjacent['next']['slug']); ?>" class="group p-6 rounded-3xl border border-gray-100 hover:border-primary transition-all text-right">
                                <p class="text-xs text-gray-400 uppercase font-bold mb-2">Suivant</p>
                                <h4 class="font-bold text-dark group-hover:text-primary transition-colors"><?php echo htmlspecialchars($adjacent['next']['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>

                <aside class="space-y-6 lg:sticky lg:top-28 self-start">
                    <!-- Sommaire Automatique (Déplacé dans la sidebar pour desktop) -->
                    <div id="toc-container" class="hidden rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                <i class="fas fa-list-ul text-xs"></i>
                            </div>
                            <span class="font-title font-bold text-slate-800 text-sm uppercase tracking-wider">Sommaire</span>
                        </div>
                        <div id="table-of-contents" class="transition-all duration-300"></div>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="font-title text-xl font-bold text-dark mb-4">Articles récents</h2>
                        <div class="space-y-4">
                            <?php if (!empty($recentPosts)) : ?>
                                <?php foreach ($recentPosts as $recent) : ?>
                                    <a href="blog_post.php?slug=<?php echo urlencode($recent['slug']); ?>" class="group flex gap-3 items-center hover:bg-gray-50 p-2 rounded-2xl transition-all">
                                        <img src="<?php echo htmlspecialchars($recent['featured_image'] ?: 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=150', ENT_QUOTES, 'UTF-8'); ?>" class="w-16 h-16 rounded-xl object-cover shrink-0" loading="lazy">
                                        <h3 class="font-semibold text-sm text-dark"><?php echo htmlspecialchars($recent['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    </a>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="text-sm text-gray-500">Aucun article récent pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Newsletter Sidebar -->
                    <div class="rounded-3xl border border-gray-200 bg-slate-900 p-6 shadow-sm text-white">
                        <h2 class="font-title text-lg font-bold mb-2">Restez informé</h2>
                        <p class="text-xs text-slate-400 mb-4 leading-relaxed">Recevez nos analyses et conseils Tech directement par email.</p>
                        <form id="sidebar-newsletter" class="space-y-3">
                            <input type="email" name="email" required placeholder="votre@email.com" class="w-full px-4 py-3 bg-slate-800 border-none rounded-xl text-sm text-white outline-none focus:ring-2 focus:ring-primary/50 transition-all">
                            <button type="submit" class="w-full bg-primary text-white text-sm font-bold py-3 rounded-xl hover:bg-green-600 transition-all">S'abonner</button>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="font-title text-xl font-bold text-dark mb-4">Navigation rapide</h2>
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li><a href="index.php#formations" class="flex items-center gap-2 hover:text-primary transition-all"><i class="fas fa-graduation-cap"></i> Nos formations</a></li>
                            <li><a href="index.php#page-blog" class="flex items-center gap-2 hover:text-primary transition-all"><i class="fas fa-newspaper"></i> Retour au blog</a></li>
                            <li><a href="index.php#contact" class="flex items-center gap-2 hover:text-primary transition-all"><i class="fas fa-envelope"></i> Nous contacter</a></li>
                        </ul>
                    </div>

                    <div class="rounded-3xl bg-primary p-8 text-white shadow-xl shadow-green-200">
                        <h3 class="font-bold text-xl mb-4">Prêt à passer au niveau supérieur ?</h3>
                        <p class="text-green-50 text-sm mb-6">Découvrez nos formations certifiantes en numérique et cybersécurité.</p>
                        <a href="index.php#register" class="inline-block w-full text-center bg-white text-primary font-bold py-3 rounded-2xl hover:bg-secondary hover:text-white transition-all">S'inscrire maintenant</a>
                    </div>
                </aside>
            </div>
        <?php else : ?>
            <div class="rounded-3xl border border-gray-200 bg-white p-12 text-center shadow-sm">
                <h1 class="font-title text-3xl font-bold text-dark mb-4">Article introuvable</h1>
                <p class="text-gray-600 mb-6">Le contenu demandé est introuvable ou n’a pas encore été publié.</p>
                <a href="index.php#blog" class="inline-flex items-center justify-center rounded-full bg-[#3F8F29] px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-green-600 transition-all">Retour au blog</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Exit Intent Modal -->
    <?php if ($post && !empty($availableFormations)): 
        $promoFormation = $availableFormations[array_rand($availableFormations)];
    ?>
    <div id="exit-intent-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-500">
        <div class="bg-white rounded-[3rem] max-w-lg w-full overflow-hidden shadow-2xl relative transform scale-95 transition-transform duration-500">
            <button onclick="closeExitModal()" class="absolute top-6 right-6 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-slate-400 hover:text-primary transition-colors z-10 shadow-sm">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="relative h-48">
                <img src="<?php echo htmlspecialchars($promoFormation['image'] ?? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800'); ?>" alt="Promotion" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent"></div>
                <div class="absolute bottom-4 left-8">
                    <span class="bg-secondary text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg animate-bounce">Offre Spéciale</span>
                </div>
            </div>

            <div class="p-8 pt-2 text-center">
                <h3 class="font-title text-2xl font-bold text-slate-900 mb-2">Attendez ! Ne partez pas si vite...</h3>
                
                <!-- Countdown Timer -->
                <div id="modal-countdown" class="inline-flex items-center gap-2 mb-4 px-4 py-2 bg-red-50 rounded-2xl border border-red-100 text-red-600 font-black text-[10px] uppercase tracking-widest shadow-sm">
                    <i class="fas fa-clock animate-pulse"></i>
                    Expire dans <span id="modal-timer-display">15:00</span>
                </div>

                <p class="text-gray-500 text-sm mb-6 leading-relaxed">Boostez votre carrière avec notre formation <span class="text-primary font-bold"><?php echo htmlspecialchars($promoFormation['title']); ?></span>. Rejoignez nos experts dès la prochaine session !</p>
                
                <div class="flex flex-col gap-3">
                    <a href="index.php?formation=<?php echo urlencode($promoFormation['title']); ?>#register" 
                       onclick="trackExitIntentClick('<?php echo addslashes($promoFormation['title']); ?>', '<?php echo addslashes($slug); ?>')"
                       class="bg-primary text-white py-4 rounded-2xl font-bold hover:bg-green-700 transition-all shadow-lg shadow-green-900/20 text-center">M'inscrire maintenant</a>
                    <button onclick="closeExitModal()" class="text-slate-400 text-xs font-bold uppercase tracking-widest hover:text-slate-600 transition-colors">Non merci, je continue ma lecture</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- Back to top -->
<button id="back-to-top" class="fixed bottom-8 right-8 w-12 h-12 rounded-full bg-white border border-gray-100 shadow-xl text-primary flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 hover:bg-primary hover:text-white z-50">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const progress = document.getElementById('reading-progress');
    const indicator = document.getElementById('progress-indicator');
    const backToTop = document.getElementById('back-to-top');
    const articleElement = document.querySelector('article');

    // Reading Mode Logic
    window.toggleReadingMode = function() {
        const body = document.body;
        const icon = document.querySelector('.dark-mode-toggle i');
        body.classList.toggle('reading-mode-dark');
        
        const isDark = body.classList.contains('reading-mode-dark');
        localStorage.setItem('readingMode', isDark ? 'dark' : 'light');
        
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    };

    // Load reading mode preference
    if (localStorage.getItem('readingMode') === 'dark') {
        document.body.classList.add('reading-mode-dark');
        const icon = document.querySelector('.dark-mode-toggle i');
        if (icon) icon.className = 'fas fa-sun';
    }

    // Génération automatique du sommaire
    const postContent = document.querySelector('.prose');
    const toc = document.getElementById('table-of-contents');
    if (postContent && toc) {
        const headings = postContent.querySelectorAll('h2, h3');
        if (headings.length >= 2) {
            const list = document.createElement('ul');
            list.className = 'space-y-2';
            headings.forEach((h, i) => {
                const id = 'section-' + i;
                h.id = id;
                const li = document.createElement('li');
                li.className = h.tagName === 'H3' ? 'ml-4' : '';
                li.innerHTML = `<a href="#${id}" class="toc-link block py-1 text-sm text-slate-500 hover:text-primary transition-all font-medium">${h.innerText}</a>`;
                list.appendChild(li);
            });
            toc.appendChild(list);
            document.getElementById('toc-container').classList.remove('hidden');

            // Intersection Observer pour le ScrollSpy
            const observerOptions = { rootMargin: '-100px 0px -70% 0px' };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        document.querySelectorAll('.toc-link').forEach(link => {
                            link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id);
                        });
                    }
                });
            }, observerOptions);
            headings.forEach(h => observer.observe(h));
        }
    }

    // Newsletter Sidebar Ajax
    const newsletterForm = document.getElementById('sidebar-newsletter');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = newsletterForm.querySelector('button');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = (window.t ? window.t('loading') : 'Chargement...');
            
            try {
                const response = await fetch('api_newsletter.php', {
                    method: 'POST',
                    body: new FormData(newsletterForm)
                });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') newsletterForm.reset();
            } catch (err) {
                alert(window.t ? window.t('error_occurred') : 'Une erreur est survenue.');
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        });
    }

    // Fonction Like AJAX
    window.toggleLike = function(postId) {
        const btn = document.getElementById('like-btn');
        if (btn.classList.contains('text-red-500')) return; // Déjà liké (simplifié)

        fetch('ajax/like_post.php?id=' + postId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('like-count').innerText = data.likes;
                    btn.classList.add('text-red-500');
                    btn.querySelector('i').className = 'fas fa-heart animate-pulse';
                }
            });
    };

    // Copier le lien
    window.copyToClipboard = function() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            const btn = document.querySelector('[title="Copier le lien"]');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-link"></i>'; }, 2000);
        });
    };

    // Logic pour les fils de discussion
    window.setReply = function(commentId, name) {
        document.getElementById('form-parent-id').value = commentId;
        document.getElementById('reply-target-name').innerText = name;
        document.getElementById('reply-indicator').classList.remove('hidden');
        
        const formContainer = document.getElementById('comment-form-container');
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        formContainer.classList.add('ring-4', 'ring-primary/10');
        setTimeout(() => formContainer.classList.remove('ring-4', 'ring-primary/10'), 1500);
    };

    window.cancelReply = function() {
        document.getElementById('form-parent-id').value = '';
        document.getElementById('reply-indicator').classList.add('hidden');
    };

    // Gestion des commentaires AJAX
    window.submitComment = function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> ' + (window.t ? window.t('sending') : 'Envoi...');

        fetch('save_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Créer dynamiquement le nouveau commentaire
                const list = document.getElementById('comments-list');
                const noMsg = document.getElementById('no-comments-msg');
                if (noMsg) noMsg.remove();

                if (data.is_reply) {
                    // Ajouter la réponse au fil
                    const parentId = document.getElementById('form-parent-id').value;
                    const nameVal = form.name.value;
                    const isAdmin = nameVal.includes('(Admin)');
                    const displayName = nameVal.replace('(Admin)', '').trim();
                    const badgeHtml = isAdmin ? '<span class="bg-primary text-white text-[8px] px-1.5 py-0.5 rounded font-black uppercase tracking-tighter ml-2">Admin</span>' : '';
                    
                    const replyHtml = `
                        <div class="ml-14 mt-6 flex gap-4 animate-in border-l-2 border-slate-50 pl-8">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-sm shrink-0">
                                ${data.initial}
                            </div>
                            <div class="space-y-1">
                                <h5 class="text-sm font-bold text-slate-900 flex items-center">${displayName}${badgeHtml}</h5>
                                <p class="text-slate-600 text-xs leading-relaxed">${form.content.value.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                    `;
                    document.getElementById('comment-' + parentId).insertAdjacentHTML('beforeend', replyHtml);
                    cancelReply();
                } else {
                    // Ajouter un nouveau commentaire parent
                    const nameVal = form.name.value;
                    const isAdmin = nameVal.includes('(Admin)');
                    const displayName = nameVal.replace('(Admin)', '').trim();
                    const badgeHtml = isAdmin ? '<span class="bg-primary text-white text-[8px] px-1.5 py-0.5 rounded font-black uppercase tracking-tighter ml-2">Admin</span>' : '';

                    const commentHtml = `
                        <div class="flex gap-6 animate-in">
                            <div class="w-14 h-14 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center text-primary font-black text-xl shrink-0">${data.initial}</div>
                            <div class="space-y-2 flex-grow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <h5 class="font-bold text-slate-900 flex items-center">${displayName}${badgeHtml}</h5>
                                        <span class="text-[10px] font-black uppercase text-slate-300 tracking-widest">${data.date}</span>
                                    </div>
                                </div>
                                <p class="text-slate-600 leading-relaxed text-sm">${form.content.value.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                    `;
                    list.insertAdjacentHTML('afterbegin', commentHtml);
                }
                form.reset();
                // Mettre à jour le badge
                const badge = document.getElementById('comment-count-badge');
                badge.innerText = parseInt(badge.innerText) + 1;
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert(window.t ? window.t('error_occurred') : 'Une erreur est survenue.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };

    window.addEventListener('scroll', () => {
        // Reading progress logic
        const winScroll = window.scrollY || document.documentElement.scrollTop;
        
        if (progress && articleElement) {
            // On cible spécifiquement la hauteur de l'article pour plus de précision
            // Cela évite que la barre ne soit faussée par la longueur des commentaires ou du footer
            const articleTop = articleElement.offsetTop;
            const articleHeight = articleElement.offsetHeight;
            const windowHeight = window.innerHeight;
            
            // Calcul : progression de 0 à 100% à travers le contenu de l'article uniquement
            let scrolledPercent = ((winScroll - articleTop + (windowHeight / 3)) / articleHeight) * 100;
            scrolledPercent = Math.max(0, Math.min(100, scrolledPercent));
            progress.style.width = scrolledPercent + "%";

            // Mise à jour du texte indicateur
            if (indicator) {
                const roundedPercent = Math.round(scrolledPercent);
                // Calcul du temps restant basé sur la progression
                const remainingTime = Math.max(1, Math.ceil(<?php echo (int)($readingTime ?? 1); ?> * (1 - (scrolledPercent / 100))));
                
                indicator.innerText = roundedPercent + "% • ~" + remainingTime + " MIN";
                indicator.style.opacity = (scrolledPercent > 1 && scrolledPercent < 100) ? "1" : "0";

                // Animation de pulsation quand la lecture est presque finie (> 90%)
                if (scrolledPercent > 90 && scrolledPercent < 100) {
                    indicator.classList.add('animate-pulse-reading');
                } else {
                    indicator.classList.remove('animate-pulse-reading');
                }
            }
        }

        // Back to top visibility
        if (winScroll > 500) {
            backToTop.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            backToTop.classList.add('opacity-0', 'pointer-events-none');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>

<?php include 'src/include/footer.php'; ?>
