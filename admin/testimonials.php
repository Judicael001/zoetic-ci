<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '_layout.php';

ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) $initials .= strtoupper(substr($segment, 0, 1));
}

$testimonials = [];
try {
    $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonials = [];
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('testimonials'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Témoignages', 'Gérez les avis et retours de vos étudiants.'); ?>

    <main class="p-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Social Proof</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Témoignages</h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Ajoutez des témoignages pour renforcer la confiance des futurs étudiants.</p>
            </div>
            <button id="toggleAddTestimonial" class="inline-flex items-center gap-2 rounded-full bg-[#3F8F29] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Ajouter un avis
            </button>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'ajout (caché par défaut) -->
        <section id="addTestimonialPanel" class="mb-8 rounded-3xl border border-gray-200 bg-white/95 p-8 shadow-sm hidden">
            <form action="connect/save_testimonial.php" method="POST" class="space-y-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-gray-700">Nom de l'étudiant</label>
                        <input type="text" name="author_name" required class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Ex: Marie Koné">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700">Rôle / Profession</label>
                        <input type="text" name="author_role" class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Ex: Étudiante en Cybersécurité">
                    </div>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Message</label>
                    <textarea name="content" required rows="4" class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 transition-all resize-none" placeholder="Contenu du témoignage..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addTestimonialPanel').classList.add('hidden')" class="px-6 py-3 rounded-full border border-gray-200 text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all">Annuler</button>
                    <button type="submit" class="bg-primary text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-green-900/20 hover:bg-green-700 transition-all">Enregistrer</button>
                </div>
            </form>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php if (!empty($testimonials)) : ?>
                <?php foreach ($testimonials as $t) : ?>
                    <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm flex flex-col hover:shadow-md transition-all relative group">
                        <div class="absolute top-6 right-6 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="connect/toggle_testimonial_status.php?id=<?php echo $t['id']; ?>" class="p-2 rounded-xl bg-slate-50 text-slate-400 hover:text-primary transition-colors" title="Activer/Désactiver">
                                <i data-lucide="<?php echo $t['is_active'] ? 'eye' : 'eye-off'; ?>" class="w-4 h-4"></i>
                            </a>
                            <a href="connect/delete_testimonial.php?id=<?php echo $t['id']; ?>" onclick="return confirm('Supprimer ce témoignage ?')" class="p-2 rounded-xl bg-red-50 text-red-400 hover:text-red-600 transition-colors" title="Supprimer">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>

                        <div class="mb-4">
                            <?php if (!$t['is_active']): ?>
                                <span class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-tighter">Masqué</span>
                            <?php else: ?>
                                <span class="bg-green-100 text-green-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-tighter">Actif</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex-grow">
                            <i class="fas fa-quote-left text-primary/20 text-3xl mb-4"></i>
                            <p class="text-slate-700 italic leading-relaxed mb-6 line-clamp-4">"<?php echo htmlspecialchars($t['content'], ENT_QUOTES, 'UTF-8'); ?>"</p>
                        </div>

                        <div class="flex items-center gap-4 pt-6 border-t border-gray-50 mt-auto">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 font-bold">
                                <?php echo strtoupper(substr($t['author_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($t['author_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($t['author_role'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="col-span-full py-20 text-center">
                    <p class="text-gray-400">Aucun témoignage enregistré pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
<script>
    document.getElementById('toggleAddTestimonial').addEventListener('click', () => {
        const panel = document.getElementById('addTestimonialPanel');
        panel.classList.toggle('hidden');
        if(!panel.classList.contains('hidden')) panel.scrollIntoView({ behavior: 'smooth' });
    });
</script>
</body>
</html>