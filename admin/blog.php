<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/blog.php';
require_once __DIR__ . '/_layout.php';

ensure_blog_table($pdo);

$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'success';

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
$publishedCount = (int) $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
$draftCount = (int) $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'")->fetchColumn();
$posts = $pdo->query('SELECT * FROM blog_posts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

include('../src/include/head.php');
?>

<?php render_admin_sidebar('blog'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Blog & Articles', 'Gérez les contenus et les publications.'); ?>

    <main class="tab-content active p-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Contenu</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Blog & Articles</h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Publiez des actualités, gérez vos articles et suivez l'activité du blog.</p>
            </div>
            <a href="blog_create.php" class="inline-flex items-center gap-2 rounded-full bg-[#3F8F29] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Ajouter un article
            </a>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-[1fr_auto] items-end">
            <div class="flex items-center gap-2 rounded-3xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                <input id="blogSearch" type="text" placeholder="Rechercher un article..." class="w-full border-0 outline-none text-sm text-gray-700" />
            </div>
            <div class="text-right text-xs text-gray-500">Filtrer par titre, catégorie ou statut</div>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <div class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.35em] text-gray-400 font-semibold">Articles publiés</p>
                <h3 class="mt-4 text-4xl font-bold text-slate-900"><?php echo $publishedCount; ?></h3>
                <p class="mt-3 text-sm text-gray-500">Articles visibles sur le site.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.35em] text-gray-400 font-semibold">Brouillons</p>
                <h3 class="mt-4 text-4xl font-bold text-slate-900"><?php echo $draftCount; ?></h3>
                <p class="mt-3 text-sm text-gray-500">Articles encore en préparation.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.35em] text-gray-400 font-semibold">Total</p>
                <h3 class="mt-4 text-4xl font-bold text-slate-900"><?php echo $totalPosts; ?></h3>
                <p class="mt-3 text-sm text-gray-500">Articles gérés sur la plateforme.</p>
            </div>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 tracking-widest font-semibold">
                        <th class="px-6 py-4">Titre</th>
                        <th class="px-6 py-4">Catégorie</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4">Créé le</th>
                        <th class="px-6 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($posts)) : ?>
                        <?php foreach ($posts as $post) : ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700"><?php echo htmlspecialchars($post['category'] ?: 'Aucune', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-bold uppercase <?php echo $post['status'] === 'published' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($post['status']), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400 font-medium"><?php echo date('d M Y', strtotime($post['created_at'])); ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="blog_create.php?id=<?php echo $post['id']; ?>" class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">Modifier</a>
                                    <a href="blog_delete.php?id=<?php echo $post['id']; ?>" class="inline-flex delete-article items-center gap-2 rounded-full bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 transition">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="px-10 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-200">
                                        <i data-lucide="newspaper" class="w-10 h-10"></i>
                                    </div>
                                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest">Aucun article créé pour le moment.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
</body>
</html>
