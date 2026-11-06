<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/realisations.php';
require_once __DIR__ . '/_layout.php';

ensure_realisations_tables($pdo);

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

// Statistiques
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM realisations")->fetchColumn();
$publishedCount = (int)$pdo->query("SELECT COUNT(*) FROM realisations WHERE status = 'published'")->fetchColumn();
$videoCount = (int)$pdo->query("SELECT COUNT(DISTINCT realisation_id) FROM realisation_media WHERE media_type = 'video'")->fetchColumn();
$testimonialCount = (int)$pdo->query("SELECT COUNT(*) FROM realisations WHERE testimonial_type IN ('video', 'audio')")->fetchColumn();

// Liste de toutes les réalisations
$realisations = get_all_realisations($pdo, null);
$categories = $pdo->query("SELECT DISTINCT category FROM realisations WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

include('../src/include/head.php');
?>

<?php render_admin_sidebar('realisations'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Nos Réalisations', 'Gérez les chantiers, interventions techniques et confessions clients.'); ?>

    <main class="tab-content active p-8">
        <!-- En-tête et Bouton d'action -->
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-extrabold mb-1">Prestations & Terrain</p>
                <h2 class="mt-2 text-4xl font-title font-bold text-slate-900">Espace Réalisations</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-500 font-medium">Présentez vos interventions en direct, vidéos d'installation et témoignages de clients satisfaits.</p>
            </div>
            <a href="realisation_form.php" class="inline-flex items-center gap-2 rounded-2xl bg-[#3F8F29] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-green-900/20 transition-all hover:bg-green-700 hover:scale-[1.02]">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                Nouvelle Réalisation
            </a>
        </div>

        <!-- Cartes Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="briefcase" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Chantiers</p>
                    <p class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $totalCount; ?></p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">En Ligne</p>
                    <p class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $publishedCount; ?></p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i data-lucide="video" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Vidéos de Travail</p>
                    <p class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $videoCount; ?></p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <i data-lucide="heart-handshake" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Confessions Clients</p>
                    <p class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $testimonialCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Notification Flash -->
        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-2xl border px-5 py-4 text-sm font-semibold shadow-sm flex items-center justify-between <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'; ?>">
                <div class="flex items-center gap-3">
                    <i data-lucide="<?php echo $status === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                    <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Barre de recherche et filtres -->
        <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input id="realisationSearch" type="text" placeholder="Rechercher par titre, client, lieu..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:border-primary focus:bg-white transition-all">
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Filtrer :</span>
                <select id="categoryFilter" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:border-primary">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Tableau des réalisations -->
        <div class="rounded-3xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="realisationsTable">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-6">Projet & Service</th>
                            <th class="py-4 px-6">Client & Lieu</th>
                            <th class="py-4 px-6">Médias Collectés</th>
                            <th class="py-4 px-6">Confession Client</th>
                            <th class="py-4 px-6 text-center">À la une</th>
                            <th class="py-4 px-6 text-center">Statut</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($realisations)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                            <i data-lucide="briefcase" class="w-8 h-8"></i>
                                        </div>
                                        <p class="font-bold text-slate-700 text-base mb-1">Aucune réalisation pour l'instant</p>
                                        <p class="text-xs text-slate-400 mb-4">Commencez par ajouter votre premier chantier (photos, vidéo d'équipe et retour client).</p>
                                        <a href="realisation_form.php" class="inline-flex items-center gap-2 rounded-xl bg-primary text-white px-5 py-2.5 text-xs font-bold shadow-md hover:bg-green-700 transition">
                                            <i data-lucide="plus" class="w-4 h-4"></i> Ajouter un projet
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($realisations as $r): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors realisation-row" data-title="<?php echo htmlspecialchars(strtolower($r['title'])); ?>" data-client="<?php echo htmlspecialchars(strtolower($r['client_name'] ?? '')); ?>" data-location="<?php echo htmlspecialchars(strtolower($r['location'] ?? '')); ?>" data-category="<?php echo htmlspecialchars($r['category']); ?>">
                                    <!-- Image + Titre + Catégorie -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-16 h-12 rounded-xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200 flex items-center justify-center">
                                                <?php if (!empty($r['cover_image'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($r['cover_image']); ?>" alt="Couverture" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <i data-lucide="camera" class="w-6 h-6 text-slate-300"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="realisation_form.php?id=<?php echo $r['id']; ?>" class="font-bold text-slate-900 hover:text-primary transition line-clamp-1">
                                                    <?php echo htmlspecialchars($r['title']); ?>
                                                </a>
                                                <span class="inline-block mt-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary">
                                                    <?php echo htmlspecialchars($r['category']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Client & Lieu -->
                                    <td class="py-4 px-6">
                                        <div class="font-semibold text-slate-800">
                                            <?php echo !empty($r['client_name']) ? htmlspecialchars($r['client_name']) : '<span class="text-slate-400 italic">Non spécifié</span>'; ?>
                                        </div>
                                        <div class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                                            <span><?php echo !empty($r['location']) ? htmlspecialchars($r['location']) : 'Abidjan'; ?></span>
                                        </div>
                                    </td>

                                    <!-- Médias collectés -->
                                    <td class="py-4 px-6">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700" title="<?php echo $r['photo_count']; ?> photo(s)">
                                                <i data-lucide="image" class="w-3.5 h-3.5 text-slate-500"></i>
                                                <?php echo (int)$r['photo_count']; ?>
                                            </span>

                                            <?php if ($r['video_count'] > 0): ?>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700" title="Vidéo d'équipe disponible">
                                                    <i data-lucide="video" class="w-3.5 h-3.5 text-blue-600"></i>
                                                    <?php echo (int)$r['video_count']; ?> vidéo<?php echo $r['video_count'] > 1 ? 's' : ''; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Confession Client -->
                                    <td class="py-4 px-6">
                                        <?php if ($r['testimonial_type'] === 'video'): ?>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                <i data-lucide="film" class="w-3.5 h-3.5"></i>
                                                Vidéo Client
                                            </span>
                                        <?php elseif ($r['testimonial_type'] === 'audio'): ?>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                                                <i data-lucide="mic" class="w-3.5 h-3.5"></i>
                                                Note Vocale
                                            </span>
                                        <?php elseif (!empty($r['client_quote'])): ?>
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600">
                                                <i data-lucide="quote" class="w-3 h-3 text-slate-400"></i>
                                                Texte seul
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-300 italic">Aucun</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- À la une (Favori) -->
                                    <td class="py-4 px-6 text-center">
                                        <a href="connect/toggle_realisation_featured.php?id=<?php echo $r['id']; ?>" class="inline-flex p-1.5 rounded-xl transition <?php echo $r['is_featured'] ? 'text-amber-500 bg-amber-50 hover:bg-amber-100' : 'text-slate-300 hover:text-slate-400 hover:bg-slate-100'; ?>" title="Mettre en avant sur l'accueil">
                                            <i data-lucide="star" class="w-5 h-5 <?php echo $r['is_featured'] ? 'fill-current' : ''; ?>"></i>
                                        </a>
                                    </td>

                                    <!-- Statut Publié / Brouillon -->
                                    <td class="py-4 px-6 text-center">
                                        <a href="connect/toggle_realisation_status.php?id=<?php echo $r['id']; ?>" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition <?php echo $r['status'] === 'published' ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo $r['status'] === 'published' ? 'bg-green-600' : 'bg-slate-400'; ?>"></span>
                                            <?php echo $r['status'] === 'published' ? 'En ligne' : 'Brouillon'; ?>
                                        </a>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="realisation_form.php?id=<?php echo $r['id']; ?>" class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-primary transition" title="Modifier">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars(addslashes($r['title'])); ?>')" class="p-2 rounded-xl text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Supprimer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Confirmation de suppression -->
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full mx-4 shadow-2xl border border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mb-4">
            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
        </div>
        <h3 class="text-xl font-title font-bold text-slate-900 mb-2">Confirmer la suppression</h3>
        <p class="text-sm text-slate-500 mb-6">Êtes-vous sûr de vouloir supprimer définitivement la réalisation <span id="deleteItemTitle" class="font-bold text-slate-800"></span> ainsi que toutes ses photos et vidéos ? Cette action est irréversible.</p>
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Annuler</button>
            <a id="confirmDeleteBtn" href="#" class="px-5 py-2.5 rounded-xl bg-red-600 text-white font-bold text-sm hover:bg-red-700 transition shadow-lg shadow-red-600/20">Supprimer</a>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, title) {
        document.getElementById('deleteItemTitle').textContent = `« ${title} »`;
        document.getElementById('confirmDeleteBtn').href = `connect/delete_realisation.php?id=${id}`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Filtrage et recherche en temps réel
    const searchInput = document.getElementById('realisationSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const rows = document.querySelectorAll('.realisation-row');

    function applyFilter() {
        const query = searchInput.value.toLowerCase().trim();
        const selectedCat = categoryFilter.value;

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const client = row.getAttribute('data-client') || '';
            const location = row.getAttribute('data-location') || '';
            const cat = row.getAttribute('data-category') || '';

            const matchesQuery = !query || title.includes(query) || client.includes(query) || location.includes(query);
            const matchesCat = !selectedCat || cat === selectedCat;

            if (matchesQuery && matchesCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilter);

    // Initialisation Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
