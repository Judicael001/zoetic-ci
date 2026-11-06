<?php
require_once '_auth.php';
require_super_admin();
require_once '../src/db/init.php';
require_once '_layout.php';

ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Super Admin', ENT_QUOTES, 'UTF-8');

// Récupération du message de statut
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';

// Récupération des filtres
$filterAction = $_GET['action_type'] ?? '';
$filterDate = $_GET['date'] ?? '';

// Construction de la requête avec filtres
$params = [];
$whereClauses = [];

if ($filterAction) {
    $whereClauses[] = "action_type = ?";
    $params[] = $filterAction;
}
if ($filterDate) {
    $whereClauses[] = "DATE(created_at) = ?";
    $params[] = $filterDate;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Récupération des types d'actions uniques pour le filtre
$types = $pdo->query("SELECT DISTINCT action_type FROM admin_logs ORDER BY action_type ASC")->fetchAll(PDO::FETCH_COLUMN);

// Récupération des logs
$stmt = $pdo->prepare("SELECT * FROM admin_logs $whereSql ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$logs = $stmt->fetchAll();

include('../src/include/head.php');
?>

<?php render_admin_sidebar('logs'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, 'AD', 'Journal d\'audit', 'Historique complet des actions effectuées par les administrateurs.'); ?>

    <main class="p-8">
        <div class="mb-10 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Sécurité & Traçabilité</p>
                <h2 class="text-4xl font-title font-black text-slate-900">Logs d'activité</h2>
                <p class="text-slate-400 mt-2">Suivi en temps réel des modifications de la plateforme.</p>
            </div>
            
            <!-- Filtres -->
            <form method="GET" class="flex flex-wrap gap-3 bg-white p-2 rounded-[2rem] border border-slate-100 shadow-sm">
                <div class="relative">
                    <select name="action_type" class="pl-4 pr-10 py-3 bg-slate-50 border-none rounded-2xl text-xs font-black uppercase outline-none focus:ring-2 focus:ring-primary/20 appearance-none cursor-pointer">
                        <option value="">Toutes les actions</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $filterAction === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="date" name="date" value="<?php echo $filterDate; ?>" class="px-4 py-3 bg-slate-50 border-none rounded-2xl text-xs font-black uppercase outline-none focus:ring-2 focus:ring-primary/20">
                <button type="submit" class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary transition-all">Filtrer</button>
                <?php if($filterAction || $filterDate): ?>
                    <a href="logs.php" class="bg-slate-100 text-slate-400 px-4 py-3 rounded-2xl flex items-center justify-center hover:bg-slate-200 transition-all"><i data-lucide="x" class="w-4 h-4"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="rounded-[2.5rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/50 text-[11px] uppercase text-slate-400 tracking-[0.25em] font-black border-b border-slate-50">
                            <th class="px-10 py-6">Date & Heure</th>
                            <th class="px-6 py-6">Administrateur</th>
                            <th class="px-6 py-6">Action</th>
                            <th class="px-6 py-6">Description</th>
                            <th class="px-10 py-6 text-right">Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($logs)) : ?>
                            <?php foreach ($logs as $log) : ?>
                                <tr class="hover:bg-slate-50/30 transition-all group">
                                    <td class="px-10 py-6">
                                        <div class="text-sm font-black text-slate-900"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 font-black text-[10px]">
                                                <?php echo strtoupper(substr($log['admin_name'], 0, 1)); ?>
                                            </div>
                                            <span class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($log['admin_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <?php 
                                        $badgeColor = match($log['action_type']) {
                                            'CREATE_ADMIN', 'UPDATE' => 'bg-green-100 text-green-700',
                                            'DELETE_ADMIN' => 'bg-red-100 text-red-700',
                                            'ROLE_CHANGE' => 'bg-orange-100 text-orange-700',
                                            default => 'bg-slate-100 text-slate-600'
                                        };
                                        ?>
                                        <span class="px-3 py-1.5 rounded-xl <?php echo $badgeColor; ?> text-[9px] font-black uppercase tracking-wider">
                                            <?php echo htmlspecialchars($log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <p class="text-sm text-slate-500 font-medium leading-relaxed max-w-md"><?php echo htmlspecialchars($log['description']); ?></p>
                                    </td>
                                    <td class="px-10 py-6 text-right">
                                        <span class="text-[10px] font-black text-slate-300 font-mono"><?php echo $log['ip_address'] ?? '127.0.0.1'; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="px-10 py-20 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-200">
                                            <i data-lucide="history" class="w-10 h-10"></i>
                                        </div>
                                        <p class="text-sm text-slate-400 font-bold uppercase tracking-widest">Aucun log trouvé</p>
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