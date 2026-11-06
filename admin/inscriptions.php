<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '../src/db/enrollments.php';
require_once '_layout.php';

ensure_enrollments_table($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

$enrollmentCount = 0;
$pendingEnrollmentCount = 0;
$enrollments = [];
try {
    $enrollmentCount = (int) $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
    $pendingEnrollmentCount = (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'")->fetchColumn();

    $sql = 'SELECT e.id,
                   COALESCE(u.name, e.student_name) AS student_name,
                   COALESCE(u.email, e.student_email) AS student_email,
                   e.student_phone AS student_phone,
                   COALESCE(f.title, e.formation_title) AS formation_title,
                   e.message,
                   e.status,
                   e.enrollment_date
            FROM enrollments e
            LEFT JOIN users u ON u.id = e.user_id
            LEFT JOIN formations f ON f.id = e.formation_id
            ORDER BY e.enrollment_date DESC';
    $enrollments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $enrollments = [];
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('inscriptions'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Inscriptions', 'Consultez et gérez toutes les demandes d’inscription.'); ?>

    <main class="tab-content active p-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Suivi</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Inscriptions</h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Tableau de bord des inscriptions récentes sur la plateforme. Gérez chaque visiteur, validez ou refusez facilement.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-green-100 text-[#3F8F29] flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total</p>
                        <p class="text-xl font-bold text-slate-900"><?php echo $enrollmentCount; ?></p>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">En attente</p>
                        <p class="text-xl font-bold text-slate-900"><?php echo $pendingEnrollmentCount; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="relative w-full sm:w-96">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <input type="text" id="enrollmentSearch" placeholder="Rechercher un étudiant ou une formation..." class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary/20 outline-none transition-all">
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="export_inscriptions.php" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-gray-50">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Exporter
                </a>
                <a href="#" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-900/20 transition hover:bg-green-700">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Ajouter
                </a>
            </div>
        </div>

        <div class="rounded-[2.5rem] border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] uppercase text-slate-400 tracking-[0.2em] font-bold">
                            <th class="px-8 py-5">Étudiant</th>
                            <th class="px-6 py-5">Contact</th>
                            <th class="px-6 py-5">Formation</th>
                            <th class="px-6 py-5">Statut</th>
                            <th class="px-6 py-5">Date</th>
                            <th class="px-8 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                <!-- Skeleton Loader -->
                <tbody id="skeleton-body" class="hidden divide-y divide-gray-100">
                    <?php for($i=0; $i<6; $i++): ?>
                        <tr class="skeleton-row">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl skeleton"></div>
                                    <div class="space-y-2">
                                        <div class="h-4 w-24 skeleton"></div>
                                        <div class="h-3 w-32 skeleton opacity-50"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5"><div class="h-4 w-20 skeleton"></div></td>
                            <td class="px-6 py-5"><div class="h-4 w-40 skeleton"></div></td>
                            <td class="px-6 py-5"><div class="h-6 w-16 skeleton rounded-full"></div></td>
                            <td class="px-6 py-5">
                                <div class="h-4 w-16 skeleton mb-1"></div>
                                <div class="h-3 w-10 skeleton opacity-50"></div>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2"><div class="w-8 h-8 skeleton rounded-xl"></div><div class="w-8 h-8 skeleton rounded-xl"></div></div>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($enrollments)) : ?>
                            <?php foreach ($enrollments as $enrollment) : ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-sm">
                                                <?php echo strtoupper(substr($enrollment['student_name'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($enrollment['student_name'] ?: 'Inconnu', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($enrollment['student_email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-slate-600 font-medium"><?php echo htmlspecialchars($enrollment['student_phone'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if ($enrollment['message']): ?>
                                                <button title="<?php echo htmlspecialchars($enrollment['message'], ENT_QUOTES, 'UTF-8'); ?>" class="text-left text-[10px] text-primary font-bold hover:underline cursor-help">Voir message</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="text-sm text-slate-700 font-semibold line-clamp-1"><?php echo htmlspecialchars($enrollment['formation_title'] ?: 'Formation supprimée', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <?php $enrollmentStatus = $enrollment['status'] ?? 'pending'; ?>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider <?php echo in_array($enrollmentStatus, ['active', 'approved'], true) ? 'bg-green-100 text-green-700' : ($enrollmentStatus === 'completed' ? 'bg-blue-100 text-blue-700' : ($enrollmentStatus === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700')); ?>">
                                            <?php echo htmlspecialchars(ucfirst($enrollmentStatus), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-sm text-slate-600 font-medium"><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter"><?php echo date('H:i', strtotime($enrollment['enrollment_date'])); ?></div>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <?php if ($enrollmentStatus === 'pending') : ?>
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="connect/update_enrollment.php?id=<?php echo (int) $enrollment['id']; ?>&action=approve" class="p-2 rounded-xl bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all shadow-sm" title="Approuver">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </a>
                                                <a href="connect/update_enrollment.php?id=<?php echo (int) $enrollment['id']; ?>&action=cancel" class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Refuser">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </a>
                                            </div>
                                        <?php else : ?>
                                            <span class="text-xs text-slate-300 font-bold italic">Traité</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="px-8 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-200">
                                            <i data-lucide="users" class="w-8 h-8"></i>
                                        </div>
                                        <p class="text-sm text-slate-500 font-medium">Aucune inscription disponible pour le moment.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
</body>
</html>
