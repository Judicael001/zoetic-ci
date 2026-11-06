<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';

// Récupération des paramètres de filtrage
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$monthNamesFr = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$monthName = $monthNamesFr[$selectedMonth - 1];

// Récupération des statistiques
try {
    $statsStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users) as users,
            (SELECT COUNT(*) FROM formations) as formations,
            (SELECT COUNT(*) FROM enrollments) as enrollments,
            (SELECT COUNT(*) FROM enrollments WHERE YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ?) as period_enrollments,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'pending' AND YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ?) as pending,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'approved' AND YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ?) as approved,
            (SELECT COUNT(*) FROM blog_posts) as blog,
            (SELECT COUNT(*) FROM newsletter_subscribers) as newsletter
    ");
    $statsStmt->execute([$selectedYear, $selectedMonth, $selectedYear, $selectedMonth, $selectedYear, $selectedMonth]);
    $stats = $statsStmt->fetch();
} catch (PDOException $e) {
    die("Erreur base de données");
}

$userCount = (int) $stats['users'];
$formationCount = (int) $stats['formations'];
$enrollmentCount = (int) $stats['period_enrollments'];
$pendingCount = (int) $stats['pending'];
$approvedCount = (int) $stats['approved'];
$blogCount = (int) $stats['blog'];
$newsletterCount = (int) $stats['newsletter'];

// Calcul des indicateurs
$convRate = $userCount > 0 ? round(($enrollmentCount / $userCount) * 100, 1) : 0;
$newsRate = $userCount > 0 ? round(($newsletterCount / $userCount) * 100, 1) : 0;
$blogDensity = $formationCount > 0 ? round(($blogCount / $formationCount) * 10, 1) : 0;

include('../src/include/head.php');
?>

<body class="bg-white">
    <div class="max-w-4xl mx-auto p-12" id="printable-report">
        <!-- Header du Rapport -->
        <div class="flex justify-between items-start border-b-4 border-primary pb-8 mb-10">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center text-white font-bold text-2xl">Z</div>
                    <span class="font-title text-3xl font-black text-slate-900 uppercase tracking-tighter">Zoe<span class="text-primary">TIC</span></span>
                </div>
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-widest">Rapport de Performance : <?php echo "$monthName $selectedYear"; ?></h1>
                <p class="text-slate-500 font-bold mt-1">Généré le <?php echo date('d/m/Y à H:i'); ?> par <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
            </div>
            <div class="text-right no-print">
                <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-full font-black text-xs uppercase tracking-widest shadow-xl shadow-slate-900/20 hover:bg-primary transition-all">
                    <i class="fas fa-file-pdf mr-2"></i> Télécharger en PDF
                </button>
            </div>
        </div>

        <!-- Section Statistiques Globales -->
        <div class="grid grid-cols-3 gap-6 mb-12">
            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Étudiants</p>
                <p class="text-3xl font-black text-slate-900"><?php echo $userCount; ?></p>
            </div>
            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Formations</p>
                <p class="text-3xl font-black text-slate-900"><?php echo $formationCount; ?></p>
            </div>
            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Inscriptions</p>
                <p class="text-3xl font-black text-slate-900"><?php echo $enrollmentCount; ?></p>
            </div>
        </div>

        <!-- Indicateurs de Performance (KPIs) -->
        <div class="mb-12">
            <h2 class="text-sm font-black uppercase tracking-[0.4em] text-primary mb-8 border-l-4 border-primary pl-4">Indicateurs de Performance (KPIs)</h2>
            
            <div class="space-y-10">
                <div>
                    <div class="flex justify-between items-end mb-3">
                        <div>
                            <p class="text-lg font-black text-slate-900">Taux de Conversion</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Inscriptions / Utilisateurs uniques</p>
                        </div>
                        <span class="text-2xl font-black text-primary"><?php echo $convRate; ?>%</span>
                    </div>
                    <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary" style="width: <?php echo $convRate; ?>%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-end mb-3">
                        <div>
                            <p class="text-lg font-black text-slate-900">Engagement Newsletter</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Abonnés / Base Utilisateurs</p>
                        </div>
                        <span class="text-2xl font-black text-blue-600"><?php echo $newsRate; ?>%</span>
                    </div>
                    <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600" style="width: <?php echo $newsRate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Focus Stratégique -->
        <div class="p-10 bg-slate-900 rounded-[2.5rem] text-white">
            <h2 class="text-xs font-black uppercase tracking-[0.4em] text-primary/80 mb-8">Analyse des inscriptions</h2>
            <div class="flex justify-around items-center text-center">
                <div>
                    <p class="text-4xl font-black text-green-400"><?php echo $approvedCount; ?></p>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mt-2">Demandes Validées</p>
                </div>
                <div class="h-16 w-px bg-white/10"></div>
                <div>
                    <p class="text-4xl font-black text-orange-400"><?php echo $pendingCount; ?></p>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mt-2">En attente de traitement</p>
                </div>
            </div>
        </div>

        <div class="mt-20 pt-8 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400 font-medium italic">Document confidentiel à usage interne - ZoeTIC Tech Team</p>
        </div>
    </div>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            #printable-report { padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</body>
</html>