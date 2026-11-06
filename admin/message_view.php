<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '_layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    // Mark as read automatically
    $updateStmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $updateStmt->execute([$id]);

    // Navigation logic: get previous and next IDs
    $prevStmt = $pdo->prepare("SELECT id FROM contacts WHERE id < ? ORDER BY id DESC LIMIT 1");
    $prevStmt->execute([$id]);
    $prevId = $prevStmt->fetchColumn();

    $nextStmt = $pdo->prepare("SELECT id FROM contacts WHERE id > ? ORDER BY id ASC LIMIT 1");
    $nextStmt->execute([$id]);
    $nextId = $nextStmt->fetchColumn();
}

if (!$message) {
    header("Location: messages.php?message_status=" . urlencode("Message introuvable.") . "&status=error");
    exit;
}

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

include('../src/include/head.php');
?>

<style>
    @media print {
        #sidebar, header, .no-print {
            display: none !important;
        }
        #main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .message-container {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
    }
</style>

<?php render_admin_sidebar('messages'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Lecture du message', 'Consultez les détails du message reçu.'); ?>

    <main class="p-8">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
            <a href="messages.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-[#3F8F29] transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Retour à la liste
            </a>
            
            <div class="flex items-center gap-3">
                <!-- Navigation Between Messages -->
                <div class="flex items-center bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mr-2">
                    <?php if ($prevId): ?>
                        <a href="message_view.php?id=<?php echo $prevId; ?>" class="p-2.5 hover:bg-gray-50 text-gray-500 transition-colors border-r border-gray-100" title="Message précédent">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </a>
                    <?php else: ?>
                        <span class="p-2.5 text-gray-200 cursor-not-allowed border-r border-gray-100"><i data-lucide="chevron-left" class="w-5 h-5"></i></span>
                    <?php endif; ?>
                    
                    <?php if ($nextId): ?>
                        <a href="message_view.php?id=<?php echo $nextId; ?>" class="p-2.5 hover:bg-gray-50 text-gray-500 transition-colors" title="Message suivant">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </a>
                    <?php else: ?>
                        <span class="p-2.5 text-gray-200 cursor-not-allowed"><i data-lucide="chevron-right" class="w-5 h-5"></i></span>
                    <?php endif; ?>
                </div>

                <button onclick="window.print()" class="p-2.5 hover:bg-gray-100 rounded-xl text-gray-500 transition-all border border-gray-200 bg-white shadow-sm" title="Imprimer">
                    <i data-lucide="printer" class="w-5 h-5"></i>
                </button>

                <a href="connect/mark_unread.php?id=<?php echo $message['id']; ?>" class="p-2.5 hover:bg-gray-100 rounded-xl text-gray-500 transition-all border border-gray-200 bg-white shadow-sm" title="Marquer comme non lu">
                    <i data-lucide="mail" class="w-5 h-5"></i>
                </a>

                <a href="mailto:<?php echo htmlspecialchars($message['email'], ENT_QUOTES, 'UTF-8'); ?>?subject=Re: <?php echo urlencode($message['subject'] ?: 'Votre message à ZoeTIC'); ?>" class="inline-flex items-center gap-2 rounded-full bg-[#3F8F29] px-6 py-2.5 text-sm font-semibold text-white hover:bg-green-600 transition shadow-lg shadow-green-900/10">
                    <i data-lucide="reply" class="w-4 h-4"></i>
                    Répondre
                </a>

                <a href="connect/delete_message.php?id=<?php echo $message['id']; ?>" onclick="return confirm('Supprimer définitivement ce message ?')" class="inline-flex items-center gap-2 rounded-full bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100 transition shadow-sm border border-red-100">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <div class="message-container rounded-[2.5rem] border border-gray-200 bg-white shadow-xl overflow-hidden">
            <!-- En-tête du message -->
            <div class="p-8 border-b border-gray-100 bg-slate-50/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-title font-bold text-slate-900 mb-4 leading-tight"><?php echo htmlspecialchars($message['subject'] ?: '(Sans sujet)', ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-[#3F8F29]/10 flex items-center justify-center text-[#3F8F29] text-xl font-bold shadow-sm">
                                <?php echo strtoupper(substr($message['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($message['name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($message['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="md:text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-1">Reçu le</p>
                        <p class="text-base font-semibold text-slate-700"><?php echo date('d F Y', strtotime($message['created_at'])); ?></p>
                        <p class="text-xs text-gray-400"><?php echo date('H:i', strtotime($message['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Corps du message -->
            <div class="p-10 min-h-[400px] bg-white">
                <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed whitespace-pre-wrap text-lg">
                    <?php echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <!-- Pied de page du message -->
            <div class="p-8 bg-slate-50/30 border-t border-gray-50 text-center">
                <p class="text-xs text-gray-400 italic">Message sécurisé envoyé via le portail ZoeTIC Côte d'Ivoire.</p>
            </div>
        </div>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
</body>
</html>