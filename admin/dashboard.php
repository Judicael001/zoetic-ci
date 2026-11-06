<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/blog.php';
require_once __DIR__ . '/../src/db/enrollments.php';

ensure_blog_table($pdo);
ensure_enrollments_table($pdo);

// Paramètres de filtrage par mois
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$prevMonthDate = strtotime(strtotime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth) . " -1 month") !== false ? sprintf('%04d-%02d-01', $selectedYear, $selectedMonth) . " -1 month" : date('Y-m-d'));
$prevMonth = (int)date('m', $prevMonthDate);
$prevYear = (int)date('Y', $prevMonthDate);

$compareMonth = isset($_GET['compare_month']) && $_GET['compare_month'] !== '' ? (int)$_GET['compare_month'] : null;
$compareYear = isset($_GET['compare_year']) && $_GET['compare_year'] !== '' ? (int)$_GET['compare_year'] : null;
$isComparing = ($compareMonth !== null && $compareYear !== null);
$months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

include('../src/include/head.php');

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

$adminCount = 0;
$userCount = 0;
$formationCount = 0;
$enrollmentCount = 0;
$pendingEnrollmentCount = 0;
$approvedEnrollmentCount = 0;
$blogCount = 0;
$newsletterCount = 0;
$unreadMessagesCount = 0;
$recentUsers = [];
$pendingCommentCount = 0; // Initialisation de la variable
$recentFormations = [];
$recentEnrollments = [];
$recentMessages = [];
$activityLogs = [];
$chartLabels = [];
$chartValues = [];
$compareChartValues = [];
$chartDetails = array_fill(0, 31, []);
$compareChartDetails = array_fill(0, 31, []);
$periodCount = 0;
$growth = 0;

try {
        $statsStmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM users) as users,
                (SELECT COUNT(*) FROM formations) as formations,
                (SELECT COUNT(*) FROM enrollments) as enrollments,
                (SELECT COUNT(*) FROM enrollments WHERE status = 'pending') as pending,
                (SELECT COUNT(*) FROM enrollments WHERE status = 'approved') as approved,
                (SELECT COUNT(*) FROM blog_posts) as blog,
                (SELECT COUNT(*) FROM newsletter_subscribers) as newsletter,
                (SELECT COUNT(*) FROM contacts WHERE is_read = 0) as unread_messages,
                (SELECT COUNT(*) FROM blog_comments WHERE status = 'pending' AND deleted_at IS NULL) as pending_comments,
                (SELECT COUNT(*) FROM enrollments WHERE YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ?) as period_count,
                (SELECT COUNT(*) FROM enrollments WHERE YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ?) as prev_period_count
        ");
        $statsStmt->execute([$selectedYear, $selectedMonth, $prevYear, $prevMonth]);
        $stats = $statsStmt->fetch();

        $userCount = (int) $stats['users'];
        $formationCount = (int) $stats['formations'];
        $enrollmentCount = (int) $stats['enrollments'];
        $pendingEnrollmentCount = (int) $stats['pending'];
        $approvedEnrollmentCount = (int) $stats['approved'];
        $blogCount = (int) $stats['blog'];
        $newsletterCount = (int) $stats['newsletter'];
        $unreadMessagesCount = (int) $stats['unread_messages'];
        $pendingCommentCount = (int) $stats['pending_comments'];
        $periodCount = (int) $stats['period_count'];
        
        // Calcul de la croissance
        $prevPeriodCount = (int) $stats['prev_period_count'];
        $growth = ($prevPeriodCount > 0) ? round((($periodCount - $prevPeriodCount) / $prevPeriodCount) * 100, 1) : ($periodCount > 0 ? 100 : 0);

        $recentUsers = $pdo->query('SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
        $recentFormations = $pdo->query('SELECT title, created_at FROM formations ORDER BY created_at DESC LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
        $recentEnrollments = $pdo->query('SELECT id, student_name, student_email, student_phone, formation_title, status, enrollment_date FROM enrollments ORDER BY enrollment_date DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
        $recentMessages = $pdo->query('SELECT id, name, subject, is_read, created_at FROM contacts ORDER BY created_at DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
        $activityLogs = $pdo->query('SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

        // Standardisation des données quotidiennes pour le graphique (1-31) avec détail par statut
        $dailyStmt = $pdo->prepare("SELECT DAY(enrollment_date) as day, status, COUNT(*) as total FROM enrollments WHERE YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ? GROUP BY day, status ORDER BY day ASC");
        $dailyStmt->execute([$selectedYear, $selectedMonth]);
        $dailyData = $dailyStmt->fetchAll();
        
        $chartLabels = range(1, 31);
        $chartValues = array_fill(0, 31, 0);
        $chartDetails = array_fill(0, 31, []);
        foreach($dailyData as $row) {
            $dayIdx = (int)$row['day'] - 1;
            $chartValues[$dayIdx] += (int)$row['total'];
            $chartDetails[$dayIdx][$row['status']] = (int)$row['total'];
        }

        if ($isComparing) {
            $dailyStmt->execute([$compareYear, $compareMonth]);
            $compareDailyData = $dailyStmt->fetchAll();
            $compareChartValues = array_fill(0, 31, 0);
            $compareChartDetails = array_fill(0, 31, []);
            foreach($compareDailyData as $row) {
                $dayIdx = (int)$row['day'] - 1;
                $compareChartValues[$dayIdx] += (int)$row['total'];
                $compareChartDetails[$dayIdx][$row['status']] = (int)$row['total'];
            }
        }

    } catch (PDOException $e) {
        // si la base n'est pas disponible, on conserve des valeurs par défaut
    }
$convRate = $userCount > 0 ? round(($enrollmentCount / $userCount) * 100, 1) : 0;
$newsRate = $userCount > 0 ? round(($newsletterCount / $userCount) * 100, 1) : 0;
$blogDensity = $formationCount > 0 ? round(($blogCount / $formationCount) * 10, 1) : 0;


$newActivityCount = $unreadMessagesCount + $pendingEnrollmentCount + $pendingCommentCount;

require_once '_layout.php';
?>

<?php render_admin_sidebar('dashboard'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Vue d\'ensemble', 'Un espace fluide pour piloter votre plateforme.', $newActivityCount, $_SESSION['admin_id'] ?? 0); ?>

    <main id="tab-dashboard" class="tab-content active p-8"> 
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Dashboard Pro</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Tableau de bord ZoeTIC</h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Un espace fluide pour piloter les inscriptions, les formations et le suivi de vos administrateurs.</p>
            </div>
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                <!-- Filtre par mois et année -->
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 bg-white p-1 rounded-full border border-gray-200 shadow-sm">
                        <select name="month" class="bg-transparent border-none text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer pl-4 py-2">
                        <?php
                        foreach ($months as $i => $m) {
                            $val = $i + 1;
                            $selected = $val === $selectedMonth ? 'selected' : '';
                            echo "<option value=\"$val\" $selected>$m</option>";
                        }
                        ?>
                    </select>
                    <select name="year" class="bg-transparent border-none text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer py-2">
                        <?php
                        $currentY = (int)date('Y');
                        for ($y = $currentY; $y >= $currentY - 2; $y--) {
                            $selected = $y === $selectedYear ? 'selected' : '';
                            echo "<option value=\"$y\" $selected>$y</option>";
                        }
                        ?>
                    </select>
                    </div>

                    <!-- Comparaison -->
                    <div id="comparison-toggle" class="flex items-center gap-2 bg-white p-1 rounded-full border border-orange-200 shadow-sm <?php echo $isComparing ? '' : 'hidden'; ?>">
                        <span class="pl-4 text-[10px] font-black uppercase text-orange-400">vs</span>
                        <select name="compare_month" class="bg-transparent border-none text-[10px] font-bold text-slate-700 focus:ring-0 cursor-pointer pl-2 py-2">
                            <option value="">Comparer...</option>
                            <?php foreach ($months as $i => $m): ?>
                                <option value="<?php echo $i+1; ?>" <?php echo ($i+1) === $compareMonth ? 'selected' : ''; ?>><?php echo $m; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="compare_year" class="bg-transparent border-none text-[10px] font-bold text-slate-700 focus:ring-0 cursor-pointer py-2 pr-4">
                             <?php for ($y = $currentY; $y >= $currentY - 2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y === $compareYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('comparison-toggle').classList.toggle('hidden')" class="p-3 bg-white border border-gray-200 rounded-full hover:bg-slate-50 transition-all text-slate-400" title="Activer la comparaison">
                        <i data-lucide="git-compare" class="w-4 h-4"></i>
                    </button>
                    <?php if (count($_GET) > 0): ?>
                        <a href="dashboard.php" class="p-3 bg-white border border-gray-200 rounded-full hover:bg-slate-50 transition-all text-slate-500" title="Réinitialiser la vue">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="p-3 bg-primary text-white rounded-full hover:bg-green-700 transition-all shadow-lg shadow-green-900/20" title="Actualiser">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                    </div>
                </form>

                <a href="export_month_enrollments.php?month=<?php echo $selectedMonth; ?>&year=<?php echo $selectedYear; ?>" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-gray-50 shadow-sm">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Détail CSV
                </a>
                <a href="report_performance.php?month=<?php echo $selectedMonth; ?>&year=<?php echo $selectedYear; ?>" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-[#3F8F29] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    Exporter Rapport PDF
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-6 mb-10">
            <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-blue-100 p-3 text-blue-600">
                        <i data-lucide="mail-warning" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.35em] text-slate-400 font-semibold">Messages</span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-900" data-target="<?php echo $unreadMessagesCount; ?>">0</p>
                <p class="mt-3 text-sm text-gray-500">Nouveaux messages non lus reçus.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-green-100 p-3 text-[#3F8F29]">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.35em] text-slate-400 font-semibold">Étudiants</span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-900" data-target="<?php echo $userCount; ?>">0</p>
                <p class="mt-3 text-sm text-gray-500">Utilisateurs inscrits et suivis.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-orange-100 p-3 text-[#F4A321]">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.35em] text-slate-400 font-semibold">Formations</span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-900" data-target="<?php echo $formationCount; ?>">0</p>
                <p class="mt-3 text-sm text-gray-500">Programmes publiés et prêts à la vente.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-purple-100 p-3 text-purple-600">
                        <i data-lucide="trending-up" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.35em] text-slate-400 font-semibold">Inscriptions</span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-900" data-target="<?php echo $enrollmentCount; ?>">0</p>
                <p class="mt-3 text-sm text-gray-500">Demandes enregistrées sur la plateforme.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-teal-100 p-3 text-teal-600">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.35em] text-slate-400 font-semibold">Newsletter</span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-900" data-target="<?php echo $newsletterCount; ?>">0</p>
                <p class="mt-3 text-sm text-gray-500">Abonnés enregistrés pour les sessions.</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-red-100 p-3 text-red-600">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.35em] text-slate-400 font-semibold">En attente</span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-900"><?php echo $pendingEnrollmentCount; ?></p>
                <p class="mt-3 text-sm text-gray-500">Inscriptions à valider par l'équipe.</p>
            </div>
        </div>

        <section class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm mt-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Inscriptions récentes</p>
                    <h3 class="mt-2 text-2xl font-title font-bold text-slate-900">Visiteurs inscrits à une formation</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600"><?php echo $approvedEnrollmentCount; ?> approuvées</span>
            </div>
            <?php if (!empty($recentEnrollments)) : ?>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-xs uppercase text-gray-500 tracking-widest font-semibold">
                                <th class="px-6 py-4">Nom</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Téléphone</th>
                                <th class="px-6 py-4">Formation</th>
                                <th class="px-6 py-4">Statut</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($recentEnrollments as $enrollment) : ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($enrollment['student_name'] ?: 'Inconnu', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo htmlspecialchars($enrollment['student_email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo htmlspecialchars($enrollment['student_phone'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo htmlspecialchars($enrollment['formation_title'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4">
                                        <?php $statusLabel = $enrollment['status'] ?? 'pending'; ?>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-bold uppercase <?php echo $statusLabel === 'approved' ? 'bg-green-100 text-green-700' : ($statusLabel === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($statusLabel), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-400 font-medium"><?php echo date('d M Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                    <td class="px-6 py-4 flex flex-wrap gap-2">
                                        <?php if ($statusLabel === 'pending') : ?>
                                            <a href="connect/update_enrollment.php?id=<?php echo (int) $enrollment['id']; ?>&action=approve" class="inline-flex items-center rounded-full bg-[#3F8F29] px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-green-600">Valider</a>
                                            <a href="connect/update_enrollment.php?id=<?php echo (int) $enrollment['id']; ?>&action=cancel" class="inline-flex items-center rounded-full bg-red-100 px-3 py-2 text-[11px] font-semibold text-red-700 transition hover:bg-red-200">Refuser</a>
                                        <?php else : ?>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-[11px] font-semibold text-slate-700"><?php echo htmlspecialchars(ucfirst($statusLabel), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="rounded-3xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">Aucune inscription récente disponible.</div>
            <?php endif; ?>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-1">Analyses KPI</p>
                        <h3 class="text-2xl font-title font-black text-slate-900 flex items-center gap-3">
                            Performance ZoeTIC
                            <?php if($growth !== 0): ?>
                                <span class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] <?php echo $growth >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'; ?>">
                                    <i data-lucide="<?php echo $growth >= 0 ? 'trending-up' : 'trending-down'; ?>" class="w-3 h-3"></i>
                                    <?php echo ($growth > 0 ? '+' : '') . $growth; ?>%
                                </span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Live Data</span>
                    </div>
                </div>

                <div class="mt-10 grid lg:grid-cols-2 gap-12">
                    <!-- Graphique -->
                    <div class="h-[300px] relative">
                        <canvas id="enrollmentChart"></canvas>
                    </div>

                    <!-- Barres de progression -->
                    <div class="flex flex-col justify-center gap-8">
                    <div>
                        <div class="flex items-center justify-between text-xs font-black uppercase tracking-widest text-slate-500 mb-3">
                            <span class="flex items-center gap-2"><i data-lucide="target" class="w-4 h-4 text-primary"></i> Conversion Étudiants</span>
                            <span class="text-slate-900"><?php echo $convRate; ?>%</span>
                        </div>
                        <div class="h-4 rounded-full bg-slate-100 overflow-hidden p-1 border border-slate-50">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary to-green-400 shadow-sm transition-all duration-1000" style="width: <?php echo min($convRate, 100); ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs font-black uppercase tracking-widest text-slate-500 mb-3">
                            <span class="flex items-center gap-2"><i data-lucide="mail-check" class="w-4 h-4 text-blue-500"></i> Pénétration Newsletter</span>
                            <span class="text-slate-900"><?php echo $newsRate; ?>%</span>
                        </div>
                        <div class="h-4 rounded-full bg-slate-100 overflow-hidden p-1 border border-slate-50">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-400 shadow-sm transition-all duration-1000" style="width: <?php echo min($newsRate, 100); ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs font-black uppercase tracking-widest text-slate-500 mb-3">
                            <span class="flex items-center gap-2"><i data-lucide="newspaper" class="w-4 h-4 text-orange-500"></i> Densité de contenu</span>
                            <span class="text-slate-900"><?php echo $blogDensity; ?> / 10</span>
                        </div>
                        <div class="h-4 rounded-full bg-slate-100 overflow-hidden p-1 border border-slate-50">
                            <div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-yellow-400 shadow-sm transition-all duration-1000" style="width: <?php echo min($blogDensity * 10, 100); ?>%"></div>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-2">
                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-3">Dernière action système</p>
                        <?php if(!empty($activityLogs)): ?>
                            <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($activityLogs[0]['description']); ?></p>
                            <p class="mt-2 text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Par <?php echo htmlspecialchars($activityLogs[0]['admin_name']); ?> • <?php echo date('H:i', strtotime($activityLogs[0]['created_at'])); ?></p>
                        <?php else: ?>
                            <p class="text-sm text-slate-400 italic">Aucune activité récente.</p>
                        <?php endif; ?>
                    </div>
                    <div class="rounded-3xl border border-blue-100 bg-blue-50/30 p-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 mb-3">Focus stratégique</p>
                        <p class="text-sm font-bold text-slate-700 leading-relaxed">Prioriser la validation des <span class="text-blue-600"><?php echo $pendingEnrollmentCount; ?> inscriptions</span> en attente pour optimiser le CA.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Activité récente</p>
                        <h3 class="mt-2 text-xl font-title font-bold text-slate-900">Nouveaux comptes</h3>
                    </div>
                    <button class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Voir tout</button>
                </div>

                <?php if (!empty($recentUsers)) : ?>
                    <ul class="space-y-4">
                        <?php foreach ($recentUsers as $user) : ?>
                            <li class="rounded-3xl border border-gray-100 p-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <span class="text-[11px] uppercase tracking-[0.28em] text-slate-400"><?php echo date('d M', strtotime($user['created_at'])); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="rounded-3xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">Aucun nouvel utilisateur enregistré récemment.</div>
                <?php endif; ?>

                <div class="mt-8">
                    <p class="text-sm uppercase tracking-[0.35em] text-slate-400 font-bold">Dernières formations</p>
                    <div class="mt-4 space-y-3">
                        <?php if (!empty($recentFormations)) : ?>
                            <?php foreach ($recentFormations as $formation) : ?>
                                <div class="rounded-3xl border border-gray-100 p-4 hover:border-[#3F8F29] transition">
                                    <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($formation['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="text-[11px] text-gray-400 mt-1"><?php echo date('d M Y', strtotime($formation['created_at'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="rounded-3xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">Aucune formation récente trouvée.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(section => section.classList.remove('active'));
        const target = document.getElementById(`tab-${tab}`);
        if (target) target.classList.add('active');
        document.getElementById('page-title').innerText = tab === 'dashboard' ? 'Vue d\'ensemble' : tab.charAt(0).toUpperCase() + tab.slice(1);
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main-content');
        sidebar.classList.toggle('-translate-x-full');
        main.classList.toggle('ml-0');
    }

    function animateCounters() {
        document.querySelectorAll('[data-target]').forEach(element => {
            const target = Number(element.getAttribute('data-target')) || 0;
            const duration = 1200;
            const stepTime = Math.max(Math.floor(duration / (target || 1)), 20);
            let current = 0;

            const counter = setInterval(() => {
                current += Math.max(1, Math.floor(target / 15));
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(counter);
                } else {
                    element.textContent = current;
                }
            }, stepTime);
        });
    }

    function initChart() {
        const ctx = document.getElementById('enrollmentChart');
        if (!ctx) return;

        const mainDetails = <?php echo json_encode($chartDetails); ?>;
        const compareDetails = <?php echo json_encode($compareChartDetails); ?>;
        const statusLabels = {
            'pending': 'En attente',
            'approved': 'Approuvée',
            'cancelled': 'Annulée',
            'completed': 'Terminée',
            'active': 'Active'
        };

        const datasets = [{
            label: '<?php echo $months[$selectedMonth-1] . " " . $selectedYear; ?>',
            data: <?php echo json_encode($chartValues); ?>,
            borderColor: '#3F8F29',
            backgroundColor: 'rgba(63, 143, 41, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2
        }];

        <?php if($isComparing): ?>
        datasets.push({
            label: '<?php echo $months[$compareMonth-1] . " " . $compareYear; ?>',
            data: <?php echo json_encode($compareChartValues); ?>,
            borderColor: '#F4A321',
            backgroundColor: 'rgba(244, 163, 33, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2
        });
        <?php endif; ?>

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: <?php echo $isComparing ? 'true' : 'false'; ?>,
                        position: 'top',
                        labels: { font: { weight: 'bold', size: 10 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        cornerRadius: 12,
                        callbacks: {
                            afterLabel: function(context) {
                                const dayIdx = context.dataIndex;
                                const details = context.datasetIndex === 1 ? compareDetails[dayIdx] : mainDetails[dayIdx];
                                if (!details || Object.keys(details).length === 0) return '';
                                
                                let lines = [''];
                                for (const [status, count] of Object.entries(details)) {
                                    const label = statusLabels[status] || status;
                                    lines.push(`${label}: ${count}`);
                                }
                                return lines;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.03)' },
                        ticks: { stepSize: 1, font: { weight: 'bold' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' } }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        animateCounters();
        initChart();

        document.querySelectorAll('.nav-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active-link'));
                button.classList.add('active-link');
            });
        });
    });
</script>

</body>
</html>
