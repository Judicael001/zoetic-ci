<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '_layout.php';

// S'assurer que les tables système existent
ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$message_status = $_GET['message_status'] ?? '';
$status_type = $_GET['status'] ?? 'info';

$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

$unreadCount = (int) $pdo->query('SELECT COUNT(*) FROM contacts WHERE is_read = 0')->fetchColumn();
$starredCount = (int) $pdo->query('SELECT COUNT(*) FROM contacts WHERE is_starred = 1')->fetchColumn();

$view = $_GET['view'] ?? 'all';
$totalMessages = 0;
$messages = [];

try {
    $whereClause = $view === 'starred' ? "WHERE is_starred = 1" : "";
    
    $countStmt = $pdo->query("SELECT COUNT(*) FROM contacts $whereClause");
    $totalMessages = (int) $countStmt->fetchColumn();

    $messages = $pdo->query("SELECT *, 
        CASE 
            WHEN DATE(created_at) = CURDATE() THEN DATE_FORMAT(created_at, '%H:%i')
            WHEN YEAR(created_at) = YEAR(CURDATE()) THEN DATE_FORMAT(created_at, '%d %b')
            ELSE DATE_FORMAT(created_at, '%d/%m/%Y')
        END AS display_date
        FROM contacts 
        $whereClause
        ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('messages'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Messages', 'Gérez les prises de contact des visiteurs.'); ?>

    <main class="p-8">
        <div class="mb-8">
            <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Communication</p>
            <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Messages Reçus</h2>
            <p class="mt-2 max-w-2xl text-sm text-gray-500">Consultez les demandes envoyées via le formulaire de contact du site.</p>
        </div>

        <?php if (!empty($message_status)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status_type === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message_status, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="connect/bulk_messages.php" method="POST" id="bulkForm" class="rounded-[2.5rem] border border-gray-200 bg-white shadow-xl overflow-hidden flex flex-col lg:flex-row min-h-[600px]">
            <!-- Mailbox Sidebar -->
            <aside class="w-full lg:w-72 bg-slate-50 border-r border-gray-100 p-6 shrink-0">
                <div class="mb-8">
                    <button class="w-full bg-primary text-white rounded-2xl py-4 font-bold shadow-lg shadow-green-900/20 hover:bg-green-700 transition-all flex items-center justify-center gap-3">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        Nouveau message
                    </button>
                </div>
                <nav class="space-y-2">
                    <a href="messages.php?view=all" class="flex items-center gap-4 px-5 py-3.5 <?php echo $view !== 'starred' ? 'bg-primary/10 text-primary' : 'text-slate-500 hover:bg-white'; ?> rounded-2xl font-bold text-sm transition-all">
                        <i data-lucide="inbox" class="w-5 h-5"></i>
                        Boîte de réception
                        <span class="ml-auto bg-primary text-white text-[10px] px-2.5 py-0.5 rounded-full font-bold"><?php echo $unreadCount; ?></span>
                    </a>
                    <a href="messages.php?view=starred" class="flex items-center gap-4 px-5 py-3.5 <?php echo $view === 'starred' ? 'bg-primary/10 text-primary' : 'text-slate-500 hover:bg-white'; ?> rounded-2xl font-medium text-sm transition-all group">
                        <i data-lucide="star" class="w-5 h-5 <?php echo $view === 'starred' ? 'text-yellow-500 fill-yellow-400' : 'text-slate-300 group-hover:text-yellow-400'; ?>"></i>
                        Favoris
                        <span class="ml-auto text-[10px] font-bold opacity-60"><?php echo $starredCount; ?></span>
                    </a>
                    <a href="#" class="flex items-center gap-4 px-5 py-3.5 text-slate-500 hover:bg-white hover:shadow-sm rounded-2xl font-medium text-sm transition-all">
                        <i data-lucide="send" class="w-5 h-5 text-slate-300"></i>
                        Envoyés
                    </a>
                    <a href="#" class="flex items-center gap-4 px-5 py-3.5 text-slate-500 hover:bg-white hover:shadow-sm rounded-2xl font-medium text-sm transition-all group">
                        <i data-lucide="trash-2" class="w-5 h-5 text-slate-300 group-hover:text-red-400"></i>
                        Corbeille
                    </a>
                </nav>
            </aside>

            <!-- Inbox Main Section -->
            <div class="flex-grow flex flex-col min-w-0 bg-white">
                <!-- Inbox Toolbar -->
                <div class="flex items-center justify-between border-b border-gray-100 px-8 py-5 sticky top-0 bg-white/80 backdrop-blur-md z-10">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="selectAll" class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer transition-all">
                        </div>
                        <div class="relative hidden sm:block ml-4">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" id="messageSearch" placeholder="Rechercher un message..." class="pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-xs outline-none focus:ring-2 focus:ring-primary/20 w-48 lg:w-64 transition-all">
                        </div>

                        <div id="bulkActions" class="flex items-center gap-2 opacity-0 pointer-events-none transition-all">
                            <button class="p-2.5 hover:bg-slate-100 rounded-xl text-slate-400 transition-colors" title="Actualiser" onclick="window.location.reload()">
                                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                            </button>
                            <button type="submit" name="action" value="mark_read" class="p-2.5 hover:bg-slate-100 rounded-xl text-slate-600 transition-colors" title="Marquer comme lu">
                                <i data-lucide="mail-open" class="w-5 h-5"></i>
                            </button>
                            <button type="submit" name="action" value="delete" onclick="return confirm('Supprimer les messages sélectionnés ?')" class="p-2.5 hover:bg-red-50 rounded-xl text-red-500 transition-colors" title="Supprimer la sélection">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                            1 - <?php echo count($messages); ?> / <?php echo $totalMessages; ?>
                        </span>
                    </div>
                </div>

                <!-- Inbox List View -->
                <div id="messagesContainer" class="divide-y divide-gray-100 overflow-y-auto max-h-[700px]">
                    <?php if (!empty($messages)) : ?>
                        <?php foreach ($messages as $msg) : ?>
                            <div class="message-row group flex items-center px-8 py-5 <?php echo $msg['is_read'] ? 'bg-white opacity-80' : 'bg-slate-50/40 font-bold'; ?> hover:bg-slate-50 cursor-pointer transition-all relative border-l-4 border-transparent hover:border-primary" 
                                 onclick="if(!event.target.closest('input') && !event.target.closest('button') && !event.target.closest('a')) window.location='message_view.php?id=<?php echo $msg['id']; ?>'">
                                
                                <!-- Status Icons -->
                                <div class="flex items-center gap-6 mr-8 shrink-0">
                                    <input type="checkbox" name="ids[]" value="<?php echo $msg['id']; ?>" class="message-checkbox h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer transition-all">
                                    <button type="button" class="star-toggle-btn transition-colors <?php echo $msg['is_starred'] ? 'text-yellow-400' : 'text-slate-200 hover:text-yellow-200'; ?>" data-id="<?php echo $msg['id']; ?>">
                                        <i data-lucide="star" class="w-5 h-5 <?php echo $msg['is_starred'] ? 'fill-yellow-400' : ''; ?>"></i>
                                    </button>
                                </div>

                                <!-- Sender -->
                                <div class="w-48 md:w-64 shrink-0 truncate">
                                    <span class="text-sm <?php echo $msg['is_read'] ? 'font-medium' : 'font-extrabold'; ?> text-slate-900 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <!-- Content Snippet -->
                                <div class="flex-grow flex items-baseline gap-3 min-w-0 mr-6">
                                    <span class="text-sm <?php echo $msg['is_read'] ? 'text-slate-600' : 'text-slate-900'; ?> truncate shrink-0"><?php echo htmlspecialchars($msg['subject'] ?: '(Sans sujet)', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-xs <?php echo $msg['is_read'] ? 'text-slate-400' : 'text-slate-500'; ?> truncate font-medium">— <?php echo htmlspecialchars(preg_replace('/\s+/', ' ', strip_tags($msg['message'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <!-- Date / Hover Actions -->
                                <div class="w-24 shrink-0 text-right relative">
                                    <span class="text-[11px] <?php echo $msg['is_read'] ? 'font-medium' : 'font-bold text-primary'; ?> text-slate-400 group-hover:hidden transition-all"><?php echo $msg['display_date']; ?></span>
                                    
                                    <!-- Overlay actions on hover -->
                                    <div class="hidden group-hover:flex items-center justify-end gap-1 absolute inset-y-0 right-0 bg-slate-50 group-hover:bg-slate-50/0 pl-6">
                                        <a href="message_view.php?id=<?php echo $msg['id']; ?>" class="p-2.5 hover:bg-white hover:shadow-sm rounded-xl text-blue-500 transition-all border border-transparent hover:border-blue-100" title="Lire le message">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </a>
                                        <a href="connect/delete_message.php?id=<?php echo $msg['id']; ?>" onclick="return confirm('Supprimer ce message ?')" class="p-2.5 hover:bg-white hover:shadow-sm rounded-xl text-red-500 transition-all border border-transparent hover:border-red-100" title="Supprimer">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="flex flex-col items-center justify-center py-32 px-6 text-center bg-slate-50/30">
                            <div class="w-24 h-24 bg-white rounded-[2.5rem] flex items-center justify-center mb-8 shadow-xl shadow-slate-200/50 border border-slate-100">
                                <i data-lucide="mail" class="w-10 h-10 text-slate-200"></i>
                            </div>
                            <h3 class="text-xl font-title font-bold text-slate-900">Boîte de réception vide</h3>
                            <p class="text-sm text-slate-400 mt-3 max-w-sm leading-relaxed">Dès qu'un visiteur vous enverra un message depuis le site, il apparaîtra ici avec tous ses détails.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.message-checkbox');
        const bulkActions = document.getElementById('bulkActions');

        // Star Toggle Logic
        document.querySelectorAll('.star-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.dataset.id;
                fetch(`connect/toggle_star.php?id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.classList.toggle('text-yellow-400');
                            this.classList.toggle('text-slate-200');
                            const icon = this.querySelector('svg');
                            icon.classList.toggle('fill-yellow-400');
                        }
                    });
            });
        });

        const updateToolbar = () => {
            const checkedCount = document.querySelectorAll('.message-checkbox:checked').length;
            if (checkedCount > 0) {
                bulkActions.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                bulkActions.classList.add('opacity-0', 'pointer-events-none');
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateToolbar();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateToolbar);
        });
    });
</script>
</body>
</html>