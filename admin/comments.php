<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/blog.php'; // Contains ensure_blog_comments_table
require_once __DIR__ . '/_layout.php';

ensure_blog_comments_table($pdo); // Ensure the table exists

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');

// Récupération de l'email avec fallback en base de données si manquant dans la session
$adminEmail = $_SESSION['admin_email'] ?? null;
if (empty($adminEmail) && !empty($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $dbEmail = $stmt->fetchColumn();
    if ($dbEmail) {
        $adminEmail = $dbEmail;
        $_SESSION['admin_email'] = $dbEmail;
    }
}
$adminEmail = $adminEmail ?? '';
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

$view = $_GET['view'] ?? 'all'; // Filter by 'all', 'pending', 'approved', 'spam'
$comments = [];
$totalComments = 0; // Initialisation
$pendingCount = 0; // Initialisation
$approvedCount = 0; // Initialisation
$spamCount = 0; // Initialisation
$trashedCount = 0; // Initialisation

try {
    // Get counts for filters
    $pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'pending' AND deleted_at IS NULL")->fetchColumn();
    $approvedCount = (int) $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'approved' AND deleted_at IS NULL")->fetchColumn();
    $spamCount = (int) $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'spam' AND deleted_at IS NULL")->fetchColumn();
    $trashedCount = (int) $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE deleted_at IS NOT NULL")->fetchColumn();
    $totalComments = (int) $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE deleted_at IS NULL")->fetchColumn();

    $conditions = [];
    $params = [];
    if ($view === 'trashed') {
        $conditions[] = "bc.deleted_at IS NOT NULL";
    } else {
        $conditions[] = "bc.deleted_at IS NULL";
        if ($view === 'pending') {
            $conditions[] = "bc.status = 'pending'";
        } elseif ($view === 'approved') {
            $conditions[] = "bc.status = 'approved'";
        } elseif ($view === 'spam') {
            $conditions[] = "bc.status = 'spam'";
        }
    }
    $whereClause = "WHERE " . implode(' AND ', $conditions);
    
    $sql = "SELECT bc.*, bp.title AS post_title, bp.slug AS post_slug, pbc.name AS parent_name
            FROM blog_comments bc
            JOIN blog_posts bp ON bc.post_id = bp.id
            LEFT JOIN blog_comments pbc ON bc.parent_id = pbc.id
            $whereClause
            ORDER BY bc.created_at DESC";
    $comments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Admin Comments Error: ' . $e->getMessage());
    $comments = [];
    $message = 'Erreur lors du chargement des commentaires.';
    $status = 'error';
    // En cas d'erreur, s'assurer que tous les compteurs sont à zéro
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('comments'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Modération Commentaires', 'Gérez les réactions des lecteurs sur vos articles.'); ?>

    <main class="p-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Blog</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Modération Commentaires</h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Valisez, rejetez ou supprimez les commentaires de vos articles.</p>
            </div>
            <div class="flex gap-4">
                <div class="rounded-3xl border border-slate-100 bg-white p-5 pr-10 shadow-sm flex items-center gap-5 transition-all hover:shadow-md">
                    <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                        <i data-lucide="clock" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase font-bold text-slate-400 tracking-widest">En attente</p>
                        <p id="stat-pending-count" class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $pendingCount; ?></p>
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-100 bg-white p-5 pr-10 shadow-sm flex items-center gap-5 transition-all hover:shadow-md">
                    <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase font-bold text-slate-400 tracking-widest">Approuvés</p>
                        <p id="stat-approved-count" class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $approvedCount; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="connect/bulk_comments.php" method="POST" id="bulkCommentsForm" class="rounded-[2.5rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/50 overflow-hidden">
            <!-- Filter Navigation -->
            <div class="flex items-center justify-between border-b border-gray-100 px-8 py-5 sticky top-0 bg-white/80 backdrop-blur-md z-10">
                <nav class="flex gap-4">
                    <a href="comments.php?view=all" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $view === 'all' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-100'; ?>">Tous (<span id="nav-total-count"><?php echo $totalComments; ?></span>)</a>
                    <a href="comments.php?view=pending" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $view === 'pending' ? 'bg-orange-500 text-white' : 'text-slate-500 hover:bg-slate-100'; ?>">En attente (<span id="nav-pending-count"><?php echo $pendingCount; ?></span>)</a>
                    <a href="comments.php?view=approved" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $view === 'approved' ? 'bg-green-500 text-white' : 'text-slate-500 hover:bg-slate-100'; ?>">Approuvés (<span id="nav-approved-count"><?php echo $approvedCount; ?></span>)</a>
                    <a href="comments.php?view=spam" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $view === 'spam' ? 'bg-red-500 text-white' : 'text-slate-500 hover:bg-slate-100'; ?>">Spam (<span id="nav-spam-count"><?php echo $spamCount; ?></span>)</a>
                    <a href="comments.php?view=trashed" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $view === 'trashed' ? 'bg-slate-500 text-white' : 'text-slate-500 hover:bg-slate-100'; ?>">Corbeille (<span id="nav-trashed-count"><?php echo $trashedCount; ?></span>)</a>
                </nav>
                <div class="flex items-center gap-4">
                    <div id="bulkActions" class="flex items-center gap-2 opacity-0 pointer-events-none transition-all">
                        <button type="submit" name="action" value="approve" class="p-2.5 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm" title="Approuver la sélection"><i data-lucide="check-circle" class="w-4 h-4"></i></button>
                        <button type="submit" name="action" value="spam" class="p-2.5 bg-orange-50 text-orange-600 rounded-xl hover:bg-orange-600 hover:text-white transition-all shadow-sm" title="Marquer comme spam"><i data-lucide="shield-alert" class="w-4 h-4"></i></button>
                        <button type="submit" name="action" value="delete" onclick="return confirm('Supprimer les commentaires sélectionnés ?')" class="p-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Supprimer la sélection"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                    <div class="h-8 w-px bg-slate-100"></div>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" id="commentSearch" placeholder="Rechercher..." class="pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-xs outline-none focus:ring-2 focus:ring-primary/20 w-40 transition-all">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead>
                        <tr class="bg-slate-50/50 text-[11px] uppercase text-slate-400 tracking-[0.25em] font-black border-b border-slate-50">
                            <th class="px-8 py-5 w-10">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="selectAll" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer transition-all">
                                </div>
                            </th>
                            <th class="px-8 py-5">Commentaire</th>
                            <th class="px-6 py-5">Article</th>
                            <th class="px-6 py-5">Statut</th>
                            <th class="px-6 py-5">Date</th>
                            <th class="px-8 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($comments)) : ?>
                            <?php foreach ($comments as $comment) : ?>
                                <?php $commentStatus = $comment['status'] ?? 'pending'; ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group comment-row" data-comment-id="<?php echo $comment['id']; ?>" data-status="<?php echo $commentStatus; ?>">
                                    <td class="px-8 py-5">
                                        <input type="checkbox" name="ids[]" value="<?php echo $comment['id']; ?>" class="comment-checkbox h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer transition-all">
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-sm">
                                                <?php echo strtoupper(substr($comment['name'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($comment['name'] ?: 'Anonyme', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($comment['email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if ($comment['parent_id']): ?>
                                                    <div class="mt-1 flex items-center gap-1.5 text-[10px] font-bold text-primary bg-primary/5 px-2 py-0.5 rounded-lg w-fit">
                                                        <i data-lucide="corner-down-right" class="w-3 h-3"></i> En réponse à <?php echo htmlspecialchars($comment['parent_name'] ?? ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <p class="text-sm text-slate-600 mt-2 line-clamp-2"><?php echo htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <a href="../blog_post.php?slug=<?php echo urlencode($comment['post_slug']); ?>" target="_blank" class="text-sm text-primary font-semibold hover:underline line-clamp-2">
                                            <?php echo htmlspecialchars($comment['post_title'] ?: 'Article supprimé', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-[10px] font-black uppercase tracking-widest 
                                            <?php echo $commentStatus === 'approved' ? 'bg-green-100 text-green-700' : ($commentStatus === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'); ?>">
                                            <span class="w-1.5 h-1.5 rounded-full 
                                                <?php echo $commentStatus === 'approved' ? 'bg-green-500' : ($commentStatus === 'pending' ? 'bg-orange-500' : 'bg-red-500'); ?>"></span>
                                            <?php echo htmlspecialchars(ucfirst($commentStatus), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-sm text-slate-600 font-medium"><?php echo date('d/m/Y', strtotime($comment['created_at'])); ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter"><?php echo date('H:i', strtotime($comment['created_at'])); ?></div>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity translate-x-2 group-hover:translate-x-0">
                                            <button type="button" onclick="openAdminReplyModal(<?php echo $comment['post_id']; ?>, <?php echo $comment['id']; ?>, '<?php echo addslashes($comment['name']); ?>')" class="p-2 rounded-xl bg-slate-50 text-slate-400 hover:text-primary transition-all shadow-sm" title="Répondre">
                                                <i data-lucide="reply" class="w-4 h-4"></i>
                                            </button>
                                            <?php if ($view === 'trashed') : ?>
                                                <button type="button" onclick="restoreComment(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars($commentStatus); ?>')" class="p-2 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Restaurer">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                </button>
                                                <button type="button" onclick="deleteCommentPermanently(<?php echo $comment['id']; ?>)" class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Supprimer définitivement">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            <?php else : ?>
                                            <?php if ($commentStatus !== 'approved') : ?>
                                                <button type="button" onclick="updateCommentStatus(<?php echo $comment['id']; ?>, 'approved')" class="p-2 rounded-xl bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all shadow-sm" title="Approuver">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($commentStatus !== 'spam') : ?>
                                                <button type="button" onclick="updateCommentStatus(<?php echo $comment['id']; ?>, 'spam')" class="p-2 rounded-xl bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white transition-all shadow-sm" title="Marquer comme spam">
                                                    <i data-lucide="flag" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" onclick="deleteComment(<?php echo $comment['id']; ?>)" class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Supprimer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="px-8 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-200">
                                            <i data-lucide="message-square" class="w-8 h-8"></i>
                                        </div>
                                        <p class="text-sm text-slate-500 font-medium">Aucun commentaire à afficher pour le moment.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </main>
</div>

<!-- Modal Réponse Admin -->
<div id="replyModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden animate-in">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900">Répondre à <span id="replyTargetName" class="text-primary"></span></h3>
            <button onclick="closeAdminReplyModal()" class="p-2 hover:bg-slate-100 rounded-full text-slate-400"><i data-lucide="x"></i></button>
        </div>
        <form id="adminReplyForm" onsubmit="submitAdminReply(event)" class="p-8 space-y-4">
            <input type="hidden" name="post_id" id="replyPostId">
            <input type="hidden" name="parent_id" id="replyParentId">
            <input type="hidden" name="name" value="Équipe ZoeTIC (Admin)">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($adminEmail); ?>">

            <!-- Barre de progression de l'envoi -->
            <div id="reply-upload-progress-container" class="mt-4 hidden p-4 bg-slate-50 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                        <i class="fas fa-cloud-upload-alt animate-bounce"></i> Envoi en cours...
                    </span>
                    <span id="reply-upload-percent" class="text-[10px] font-black text-slate-900 tracking-tighter">0%</span>
                </div>
                <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                    <div id="reply-upload-progress-bar" class="h-full bg-primary transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
            
            <textarea name="content" required rows="5" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-medium outline-none focus:ring-4 focus:ring-primary/5 transition-all resize-none" placeholder="Votre réponse officielle..."></textarea>
            
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeAdminReplyModal()" class="px-6 py-3 rounded-full text-sm font-bold text-slate-400 hover:bg-slate-50 transition-all">Annuler</button>
                <button type="submit" class="bg-primary text-white px-8 py-3 rounded-full text-sm font-black uppercase tracking-widest hover:bg-green-700 shadow-xl shadow-green-900/20 transition-all flex items-center gap-2">
                    Envoyer la réponse <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.comment-checkbox');
        const bulkActions = document.getElementById('bulkActions');

        /**
         * Met à jour les compteurs de l'interface sans rechargement
         */
        const updateCounters = (oldStatus, newStatus, isDelete = false, isRestore = false) => {
            const elements = {
                all: document.getElementById('nav-total-count'),
                pending: [document.getElementById('stat-pending-count'), document.getElementById('nav-pending-count')],
                approved: [document.getElementById('stat-approved-count'), document.getElementById('nav-approved-count')],
                trashed: [document.getElementById('nav-trashed-count')],
                spam: [document.getElementById('nav-spam-count')]
            };

            const adjust = (key, delta) => {
                const els = Array.isArray(elements[key]) ? elements[key] : [elements[key]];
                els.forEach(el => {
                    if (el) {
                        const current = parseInt(el.textContent) || 0;
                        el.textContent = Math.max(0, current + delta);
                    }
                });
            };

            if (isDelete) {
                adjust('all', -1);
                adjust('trashed', 1); // Move to trash
                adjust(oldStatus, -1);
            } else if (isRestore) {
                adjust('all', 1);
                adjust(newStatus, 1);
                adjust('trashed', -1); // Remove from trash
            } else if (oldStatus !== newStatus) {
                adjust(oldStatus, -1);
                adjust(newStatus, 1);
            }
        };

        /**
         * Affiche une notification visuelle temporaire (Toast)
         */
        window.showToast = function(message, status = 'success', action = null) {
            const toast = document.createElement('div');
            const bgColor = status === 'success' ? 'bg-green-600' : 'bg-red-600';
            const icon = status === 'success' ? 'check-circle' : 'alert-triangle';
            
            toast.className = `fixed bottom-8 left-1/2 -translate-x-1/2 z-[110] flex items-center gap-3 px-6 py-4 rounded-2xl text-white shadow-2xl transition-all duration-500 opacity-0 translate-y-4 ${bgColor}`;
            toast.innerHTML = `
                <i data-lucide="${icon}" class="w-5 h-5"></i>
                <span class="font-bold text-sm">${message}</span>
            `;
            
            if (action) {
                const actionBtn = document.createElement('button');
                actionBtn.className = 'ml-4 underline font-black text-xs uppercase tracking-widest hover:text-white/80 transition-colors';
                actionBtn.innerText = action.label;
                actionBtn.onclick = action.callback;
                toast.appendChild(actionBtn);
            }

            document.body.appendChild(toast);
            if (window.lucide) window.lucide.createIcons();
            
            requestAnimationFrame(() => toast.classList.remove('opacity-0', 'translate-y-4'));
            
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-4');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        };

        // Bulk Selection Logic
        const updateBulkToolbar = () => {
            const checkedCount = document.querySelectorAll('.comment-checkbox:checked').length;
            if (bulkActions) {
                bulkActions.classList.toggle('opacity-0', checkedCount === 0);
                bulkActions.classList.toggle('pointer-events-none', checkedCount === 0);
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateBulkToolbar();
            });
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkToolbar));

        // Admin Reply Modal Logic
        window.openAdminReplyModal = function(postId, parentId, name) {
            document.getElementById('replyPostId').value = postId;
            document.getElementById('replyParentId').value = parentId;
            document.getElementById('replyTargetName').innerText = name;
            const modal = document.getElementById('replyModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        };

        window.closeAdminReplyModal = function() {
            const modal = document.getElementById('replyModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.getElementById('adminReplyForm').reset();
        };

        window.submitAdminReply = function(e) {
            e.preventDefault();
            const form = e.target;

            if (!form.email.value.trim()) {
                showToast('Erreur : Votre adresse email est manquante. Veuillez vous reconnecter.', 'error');
                return;
            }

            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const originalBtnText = btn.innerHTML;

            const progressContainer = document.getElementById('reply-upload-progress-container');
            const progressBar = document.getElementById('reply-upload-progress-bar');
            const progressPercent = document.getElementById('reply-upload-percent');

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i> Envoi...';
            if (window.lucide) window.lucide.createIcons(); // Pour afficher l'icône de chargement

            if (progressContainer) {
                progressContainer.classList.remove('hidden');
                if (progressBar) progressBar.style.width = '0%';
                if (progressPercent) progressPercent.textContent = '0%';
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../save_comment.php', true);

            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percent = Math.round((event.loaded / event.total) * 100);
                    if (progressBar) progressBar.style.width = percent + '%';
                    if (progressPercent) progressPercent.textContent = percent + '%';
                    btn.innerHTML = `<i data-lucide="cloud-upload" class="w-4 h-4 animate-bounce mr-2"></i> Envoi (${percent}%)`;
                    if (window.lucide) window.lucide.createIcons();
                }
            };

            xhr.onload = function() {
                if (progressContainer) progressContainer.classList.add('hidden');
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
                if (window.lucide) window.lucide.createIcons();

                if (xhr.status >= 200 && xhr.status < 400) {
                    const data = JSON.parse(xhr.responseText);
                    if (data.status === 'success') {
                        closeAdminReplyModal();
                        showToast('Réponse publiée avec succès.');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Erreur lors de la publication de la réponse.', 'error');
                    }
                } else {
                    showToast('Erreur serveur lors de l\'envoi de la réponse.', 'error');
                }
            };

            xhr.onerror = function() {
                if (progressContainer) progressContainer.classList.add('hidden');
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
                if (window.lucide) window.lucide.createIcons();
                showToast('Erreur réseau lors de l\'envoi de la réponse.', 'error');
            };

            xhr.send(formData);
        };

        // AJAX for updating comment status
        window.updateCommentStatus = function(commentId, newStatus) {
            fetch(`connect/update_comment_status.php?id=${commentId}&status=${newStatus}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.querySelector(`.comment-row[data-comment-id="${commentId}"]`);
                        const oldStatus = row.dataset.status;

                        updateCounters(oldStatus, newStatus);
                        row.classList.add('opacity-0', 'scale-95', 'pointer-events-none', 'transition-all', 'duration-500');
                        setTimeout(() => row.remove(), 500);

                        showToast(`Commentaire ${newStatus === 'approved' ? 'approuvé' : 'marqué comme spam'}.`);
                    } else {
                        showToast(data.message || 'Erreur lors de la mise à jour.', 'error');
                    }
                })
                .catch(error => console.error('Error updating comment status:', error));
        };

        // AJAX for deleting comment
        window.deleteComment = function(commentId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
                return;
            }
            fetch(`connect/delete_comment.php?id=${commentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.querySelector(`.comment-row[data-comment-id="${commentId}"]`);
                        const oldStatus = row.dataset.status;

                        updateCounters(oldStatus, null, true);
                        row.classList.add('hidden'); // On cache immédiatement pour l'UX
                        
                        showToast('Commentaire supprimé.', 'success', {
                            label: 'Annuler',
                            callback: () => {
                                fetch(`connect/restore_comment.php?id=${commentId}`)
                                    .then(r => r.json())
                                    .then(res => {
                                        if (res.status === 'success') {
                                            row.classList.remove('hidden');
                                            updateCounters(null, oldStatus, false, true);
                                            showToast('Commentaire restauré.');
                                        }
                                    });
                            }
                        });
                    } else {
                        showToast(data.message || 'Erreur lors de la suppression.', 'error');
                    }
                })
                .catch(error => console.error('Error deleting comment:', error));
        };

        // AJAX for permanently deleting comment (from trash)
        window.deleteCommentPermanently = function(commentId) {
            if (!confirm('Êtes-vous sûr de vouloir SUPPRIMER DÉFINITIVEMENT ce commentaire ? Cette action est irréversible.')) {
                return;
            }
            fetch(`connect/delete_comment_permanently.php?id=${commentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.querySelector(`.comment-row[data-comment-id="${commentId}"]`);
                        updateCounters('trashed', null, true, false); // Decrement trashed count
                        row.classList.add('opacity-0', 'scale-95', 'pointer-events-none', 'transition-all', 'duration-500');
                        setTimeout(() => row.remove(), 500);
                        showToast('Commentaire supprimé définitivement.');
                    } else {
                        showToast(data.message || 'Erreur lors de la suppression définitive.', 'error');
                    }
                })
                .catch(error => console.error('Error permanently deleting comment:', error));
        };

        // AJAX for restoring comment from trash
        window.restoreComment = function(commentId, originalStatus) {
            fetch(`connect/restore_comment.php?id=${commentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.querySelector(`.comment-row[data-comment-id="${commentId}"]`);
                        updateCounters('trashed', originalStatus, false, true); // Decrement trashed, increment original status
                        row.classList.add('opacity-0', 'scale-95', 'pointer-events-none', 'transition-all', 'duration-500');
                        setTimeout(() => row.remove(), 500);
                        showToast('Commentaire restauré avec succès.');
                    } else {
                        showToast(data.message || 'Erreur lors de la restauration.', 'error');
                    }
                })
                .catch(error => console.error('Error restoring comment:', error));
        };
    });
</script>
<?php render_admin_footer_scripts(); ?>
</body>
</html>