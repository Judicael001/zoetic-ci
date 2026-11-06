<?php
require_once '_auth.php';
require_super_admin();
require_once '../src/db/init.php';
require_once '_layout.php';

ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';

$id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingAdmin = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $editingAdmin = $stmt->fetch();
}

$admins = $pdo->query("SELECT * FROM admins ORDER BY role DESC, fullname ASC")->fetchAll();
$superCount = array_reduce($admins, fn($c, $a) => $c + ($a['role'] === 'super_admin' ? 1 : 0), 0);

include('../src/include/head.php');
?>

<?php render_admin_sidebar('admins'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, 'AD', 'Équipe Admin', 'Gérez les accès et les rôles des collaborateurs.'); ?>

    <main class="p-8">
        <div class="mb-10 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Sécurité & Accès</p>
                <h2 class="text-4xl font-title font-black text-slate-900">Membres de l'équipe</h2>
                <p class="text-slate-400 mt-2">Contrôlez qui peut accéder au panneau d'administration.</p>
            </div>
            <div class="flex gap-4">
                <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center gap-5">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Super Admins</p>
                        <p class="text-2xl font-black text-slate-900"><?php echo $superCount; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl border <?php echo $status === 'success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700'; ?> font-bold text-sm">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire Panel -->
        <section id="addAdminPanel" class="mb-10 rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-xl shadow-slate-200/40 <?php echo $editingAdmin ? '' : 'hidden'; ?>">
            <div class="mb-8">
                <h3 class="text-xl font-black text-slate-900"><?php echo $editingAdmin ? 'Modifier le compte' : 'Créer un accès admin'; ?></h3>
                <p class="text-sm text-slate-400">Définissez les identifiants et le niveau de privilège.</p>
            </div>
            <form action="connect/save_admin.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if($editingAdmin): ?><input type="hidden" name="id" value="<?php echo $editingAdmin['id']; ?>"><?php endif; ?>
                
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase text-slate-500 ml-2">Nom Complet</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($editingAdmin['fullname'] ?? ''); ?>" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                </div>
                
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase text-slate-500 ml-2">Email Professionnel</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($editingAdmin['email'] ?? ''); ?>" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black uppercase text-slate-500 ml-2">Rôle</label>
                    <select name="role" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                        <option value="admin" <?php echo ($editingAdmin['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                        <option value="editor" <?php echo ($editingAdmin['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Éditeur (Blog uniquement)</option>
                        <option value="super_admin" <?php echo ($editingAdmin['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Administrateur</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black uppercase text-slate-500 ml-2">Mot de passe <?php echo $editingAdmin ? '(Laisser vide pour ne pas changer)' : ''; ?></label>
                    <input type="password" name="password" <?php echo $editingAdmin ? '' : 'required'; ?> class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                </div>

                <div class="space-y-2 flex items-center pt-8">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="notification_sound" value="1" <?php echo ($editingAdmin['notification_sound'] ?? 1) ? 'checked' : ''; ?> class="w-5 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="text-sm font-bold text-slate-700">Activer le son des notifications</span>
                    </label>
                </div>

                <div class="lg:col-span-2 flex items-end justify-end gap-3">
                    <button type="button" onclick="window.location='admins.php'" class="px-8 py-4 rounded-full font-black text-xs uppercase tracking-widest text-slate-400 hover:bg-slate-50 transition-all">Annuler</button>
                    <button type="submit" class="bg-primary text-white px-10 py-4 rounded-full font-black text-xs uppercase tracking-widest hover:bg-green-700 shadow-xl shadow-primary/20 transition-all">Enregistrer le compte</button>
                </div>
            </form>
        </section>

        <!-- Search and Action Toolbar -->
        <div class="mb-8 flex flex-col md:flex-row justify-between gap-4">
            <div class="relative max-w-md w-full">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300"></i>
                <input type="text" id="adminSearch" placeholder="Rechercher par nom ou email..." class="w-full pl-12 pr-4 py-4 bg-white border border-slate-100 rounded-2xl text-sm font-medium outline-none focus:ring-4 focus:ring-primary/5 shadow-sm">
            </div>
            <button onclick="document.getElementById('addAdminPanel').classList.toggle('hidden')" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary transition-all flex items-center gap-3">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                Ajouter un membre
            </button>
        </div>

        <!-- List -->
        <div id="admins-list" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($admins as $a): ?>
            <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group relative overflow-hidden">
                <div class="absolute top-0 right-0 p-6 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                    <a href="?edit_id=<?php echo $a['id']; ?>" class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                    <a href="connect/delete_admin.php?id=<?php echo $a['id']; ?>" onclick="return confirm('Supprimer ce compte ?')" class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                </div>

                <div class="flex items-center gap-5 mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-50 flex items-center justify-center text-slate-400 font-black text-xl border border-slate-50">
                        <?php echo strtoupper(substr($a['fullname'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 leading-tight"><?php echo htmlspecialchars($a['fullname']); ?></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 rounded-lg <?php echo $a['role'] === 'super_admin' ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-500'; ?> text-[9px] font-black uppercase tracking-wider"><?php echo $a['role']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-sm text-slate-500">
                        <i data-lucide="mail" class="w-4 h-4 opacity-40"></i>
                        <span class="font-medium truncate"><?php echo htmlspecialchars($a['email']); ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-500">
                        <i data-lucide="calendar" class="w-4 h-4 opacity-40"></i>
                        <span class="font-medium">Inscrit le <?php echo date('d/m/Y', strtotime($a['created_at'])); ?></span>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full <?php echo $a['is_active'] ? 'bg-green-500' : 'bg-slate-300'; ?>"></div>
                        <span class="text-[10px] font-black uppercase text-slate-400"><?php echo $a['is_active'] ? 'Actif' : 'Désactivé'; ?></span>
                    </div>
                    <a href="connect/toggle_admin_status.php?id=<?php echo $a['id']; ?>" class="text-[10px] font-black uppercase tracking-widest text-primary hover:underline">
                        <?php echo $a['is_active'] ? 'Désactiver' : 'Activer'; ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
</body>
</html>