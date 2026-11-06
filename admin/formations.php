<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '_layout.php';

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$initials = '';
foreach (explode(' ', $adminName) as $segment) {
    if (!empty($segment)) {
        $initials .= strtoupper(substr($segment, 0, 1));
    }
}

ensure_system_tables($pdo);

$categories = [];
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';
$formations = [];
$formationCount = 0;
$activeFormationCount = 0;
$admins = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentFormation = null;

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM formations WHERE id = ?");
        $stmt->execute([$id]);
        $currentFormation = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $categories = $pdo->query('SELECT id, name, color FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
    $admins = $pdo->query("SELECT id, fullname FROM admins WHERE is_active = 1 ORDER BY fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sql = 'SELECT f.id, f.title, f.image, COALESCE(c.name, "Non classée") AS category, c.color AS category_color, f.level, f.duration, f.price, f.status AS is_active, f.created_at, COALESCE(a.fullname, "Non assigné") AS instructor
            FROM formations f
            LEFT JOIN categories c ON c.id = f.category_id
            LEFT JOIN admins a ON a.id = f.instructor_id
            ORDER BY f.created_at DESC';
    $formations = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $formationCount = count($formations);
    foreach ($formations as $f) {
        if ($f['is_active']) $activeFormationCount++;
    }
} catch (PDOException $e) {
    error_log('Erreur Formations Admin : ' . $e->getMessage());
    $categories = [];
    $formations = [];
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('formations'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Formations', 'Gérez les parcours de formation disponibles.'); ?>

    <main class="tab-content active p-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-extrabold mb-1">Catalogue</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900"><?php echo $currentFormation ? 'Modifier la formation' : 'Formations'; ?></h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-400 font-medium">Gérez votre offre de formation et suivez les intervenants.</p>
            </div>

            <div class="flex gap-4">
                <div class="rounded-3xl border border-slate-100 bg-white p-5 pr-10 shadow-sm flex items-center gap-5 transition-all hover:shadow-md">
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <i data-lucide="layers" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase font-bold text-slate-400 tracking-widest">Total</p>
                        <p class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $formationCount; ?></p>
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-100 bg-white p-5 pr-10 shadow-sm flex items-center gap-5 transition-all hover:shadow-md">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="activity" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase font-bold text-slate-400 tracking-widest">En Ligne</p>
                        <p class="text-2xl font-black text-slate-900 leading-none mt-1"><?php echo $activeFormationCount; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <section id="addFormationPanel" class="mb-8 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm <?php echo $currentFormation ? '' : 'hidden'; ?>">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold"><?php echo $currentFormation ? 'Mise à jour' : 'Nouvelle formation'; ?></p>
                    <h3 class="mt-2 text-2xl font-title font-bold text-slate-900"><?php echo $currentFormation ? 'Modifier le parcours' : 'Ajouter un parcours'; ?></h3>
                </div>
                <p class="text-sm text-gray-500">Complétez les informations pour créer une nouvelle formation.</p>
            </div>
            <form action="connect/save_formation.php" method="post" enctype="multipart/form-data" class="grid gap-4">
                <?php if ($currentFormation): ?>
                    <input type="hidden" name="id" value="<?php echo $currentFormation['id']; ?>">
                <?php endif; ?>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Titre</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($currentFormation['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" placeholder="Ex : Cybersécurité avancée" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Catégorie</label>
                        <div class="relative mt-2 mb-2">
                            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300"></i>
                            <input type="text" id="categorySearch" placeholder="Filtrer les catégories..." class="w-full pl-11 pr-4 py-2 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-bold outline-none focus:ring-4 focus:ring-primary/5 transition-all">
                        </div>
                        <select name="category_id" id="categorySelect" class="w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20">
                            <option value="">Aucune catégorie</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo (int) $category['id']; ?>" <?php echo (isset($currentFormation['category_id']) && $currentFormation['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Couleur de la catégorie</label>
                        <input type="color" id="category_color" name="category_color" value="<?php echo htmlspecialchars($currentFormation['category_color'] ?? '#3F8F29', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full h-12 rounded-3xl border border-gray-200 px-2 py-1 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Instructeur</label>
                        <select name="instructor_id" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20">
                            <option value="">Non assigné</option>
                            <?php foreach ($admins as $admin) : ?>
                                <option value="<?php echo (int) $admin['id']; ?>" <?php echo (isset($currentFormation['instructor_id']) && $currentFormation['instructor_id'] == $admin['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['fullname'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Niveau</label>
                        <select name="level" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20">
                            <option value="Débutant" <?php echo ($currentFormation['level'] ?? '') === 'Débutant' ? 'selected' : ''; ?>>Débutant</option>
                            <option value="Intermédiaire" <?php echo ($currentFormation['level'] ?? '') === 'Intermédiaire' ? 'selected' : ''; ?>>Intermédiaire</option>
                            <option value="Avancé" <?php echo ($currentFormation['level'] ?? '') === 'Avancé' ? 'selected' : ''; ?>>Avancé</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Durée</label>
                        <input type="text" name="duration" value="<?php echo htmlspecialchars($currentFormation['duration'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" placeholder="Ex : 40 heures" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Prix</label>
                        <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($currentFormation['price'] ?? '0.00', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Image de formation (upload)</label>
                        <input type="file" id="image_file" name="image_file" accept="image/*" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" />
                        <p class="mt-2 text-xs text-gray-500">Vous pouvez uploader une image ou laisser ce champ vide si vous préférez fournir une URL ci-dessous.</p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Image ou vidéo (URL)</label>
                        <input type="url" id="image_url" name="image" value="<?php echo htmlspecialchars($currentFormation['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" placeholder="https://..." />
                        <p class="mt-2 text-xs text-gray-500">Si vous avez déjà une image en ligne, vous pouvez entrer son URL ici.</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Aperçu du visuel</label>
                        <div class="relative aspect-video rounded-3xl overflow-hidden border border-gray-100 bg-slate-50 flex items-center justify-center shadow-inner group">
                            <?php 
                                $initialImg = $currentFormation['image'] ?? '';
                                $displayImg = ($initialImg && strpos($initialImg, 'http') !== 0) ? '../' . $initialImg : $initialImg;
                            ?>
                            <img id="image_preview" src="<?php echo htmlspecialchars($displayImg); ?>" class="w-full h-full object-cover <?php echo empty($displayImg) ? 'hidden' : ''; ?>" alt="Preview">
                            <div id="preview_placeholder" class="text-slate-300 flex flex-col items-center <?php echo !empty($displayImg) ? 'hidden' : ''; ?>">
                                <i data-lucide="image" class="w-10 h-10 mb-2 opacity-20"></i>
                                <span class="text-[10px] font-bold uppercase tracking-widest opacity-40">Aucun visuel</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Résumé court</label>
                    <textarea name="short_description" rows="3" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" placeholder="Présentation courte de la formation"><?php echo htmlspecialchars($currentFormation['short_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Description complète</label>
                    <textarea name="description" rows="5" required class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 text-sm text-slate-900 outline-none focus:border-[#3F8F29] focus:ring-[#3F8F29]/20" placeholder="Détails du contenu, objectifs, prérequis..."><?php echo htmlspecialchars($currentFormation['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-2 items-end">
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="status" value="1" <?php echo ($currentFormation['status'] ?? 1) == 1 ? 'checked' : ''; ?> class="h-4 w-4 rounded border-gray-300 text-[#3F8F29] focus:ring-[#3F8F29]" />
                        Formation active
                    </label>
                    <div class="flex gap-2">
                        <?php if ($currentFormation): ?>
                            <a href="formations.php" class="inline-flex justify-center rounded-full bg-slate-100 px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-200">Annuler</a>
                        <?php endif; ?>
                        <button type="submit" class="flex-grow inline-flex justify-center rounded-full bg-[#3F8F29] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600">
                            <?php echo $currentFormation ? 'Mettre à jour' : 'Enregistrer la formation'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <div class="mb-8 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
            <div class="relative flex-grow max-w-xl">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300"></i>
                <input type="text" id="formationSearch" placeholder="Rechercher par titre, catégorie ou instructeur..." class="w-full pl-12 pr-4 py-4 bg-white border border-slate-100 rounded-[1.5rem] text-sm font-medium focus:ring-4 focus:ring-primary/5 focus:border-primary/30 outline-none transition-all shadow-sm">
            </div>
            <button id="toggleAddFormation" type="button" class="inline-flex items-center justify-center gap-3 rounded-2xl bg-primary px-8 py-4 text-sm font-bold text-white shadow-xl shadow-primary/20 hover:shadow-primary/30 hover:-translate-y-0.5 transition-all active:scale-95">
                <i data-lucide="<?php echo $currentFormation ? 'pen-line' : 'plus-circle'; ?>" class="w-5 h-5"></i>
                <span class="tracking-wide"><?php echo $currentFormation ? 'Modifier ce parcours' : 'Nouveau Parcours'; ?></span>
            </button>
        </div>

        <div class="rounded-[2.5rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/50 overflow-hidden">
            <table class="w-full min-w-[720px] text-left">
                <thead>
                    <tr class="bg-slate-50/50 text-[11px] uppercase text-slate-400 tracking-[0.25em] font-black border-b border-slate-50">
                        <th class="px-10 py-6">Détails du programme</th>
                        <th class="px-6 py-6">Catégorie</th>
                        <th class="px-6 py-6 text-center">Durée</th>
                        <th class="px-6 py-6">Tarif</th>
                        <th class="px-6 py-6">Statut</th>
                        <th class="px-10 py-6 text-right">Actions</th>
                    </tr>
                </thead>
                <!-- Skeleton Loader (Affiché pendant la recherche) -->
                <tbody id="skeleton-body" class="hidden divide-y divide-gray-100">
                    <?php for($i=0; $i<6; $i++): ?>
                        <tr class="skeleton-row">
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl skeleton"></div>
                                    <div class="space-y-2">
                                        <div class="h-4 w-32 skeleton"></div>
                                        <div class="h-3 w-20 skeleton opacity-50"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6"><div class="h-6 w-20 skeleton rounded-lg"></div></td>
                            <td class="px-6 py-6 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="h-4 w-12 skeleton rounded"></div>
                                    <div class="h-3 w-16 skeleton opacity-50 rounded"></div>
                                </div>
                            </td>
                            <td class="px-6 py-6"><div class="h-4 w-24 skeleton rounded"></div></td>
                            <td class="px-6 py-6"><div class="h-6 w-24 skeleton rounded-full"></div></td>
                            <td class="px-10 py-6 text-right">
                                <div class="flex justify-end gap-3">
                                    <div class="w-10 h-10 skeleton rounded-xl"></div>
                                    <div class="w-10 h-10 skeleton rounded-xl"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>

                <!-- État "Aucun résultat" -->
                <tbody id="no-results-body" class="hidden">
                    <tr>
                        <td colspan="6" class="px-10 py-24 text-center">
                            <div class="flex flex-col items-center justify-center max-w-xs mx-auto animate-fade-in">
                                <div class="w-24 h-24 bg-slate-50 rounded-[2.5rem] flex items-center justify-center mb-8 shadow-sm border border-slate-100/50">
                                    <i data-lucide="search-x" class="w-12 h-12 text-slate-200"></i>
                                </div>
                                <h3 class="text-xl font-title font-black text-slate-900 uppercase tracking-tight">Aucun résultat</h3>
                                <p class="text-sm text-slate-400 mt-3 font-medium leading-relaxed">Nous n'avons trouvé aucun programme correspondant à votre recherche "<strong><span id="search-term-display"></span></strong>".</p>
                                <button type="button" onclick="const inp = document.getElementById('formationSearch') || document.getElementById('globalSearch'); if(inp) { inp.value=''; filterData(''); inp.focus(); }" class="mt-8 px-8 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all">Réinitialiser la vue</button>
                            </div>
                        </td>
                    </tr>
                </tbody>

                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($formations)) : ?>
                        <?php foreach ($formations as $formation) : ?>
                            <tr class="hover:bg-slate-50/50 transition-all group border-b border-slate-50 last:border-0">
                                <td class="px-10 py-6">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($formation['image'])): ?>
                                            <div class="w-12 h-12 rounded-2xl overflow-hidden border border-slate-100 shadow-sm">
                                                <img src="<?php echo (strpos($formation['image'], 'http') === 0) ? htmlspecialchars($formation['image']) : '../' . htmlspecialchars($formation['image']); ?>" 
                                                     class="w-full h-full object-cover" alt="">
                                            </div>
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 font-black text-lg group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                                <?php echo mb_strtoupper(mb_substr($formation['title'] ?? 'F', 0, 1, 'UTF-8'), 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-black text-slate-900 mb-0.5 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($formation['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight flex items-center gap-1.5">
                                                <i data-lucide="user" class="w-3 h-3 text-slate-300"></i> <?php echo htmlspecialchars($formation['instructor'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="category-badge cursor-pointer px-3 py-1.5 rounded-lg border border-slate-100 text-[9px] font-black uppercase tracking-wider transition-all hover:scale-105 active:scale-95 shadow-sm hover:shadow-md" style="background-color: <?php echo htmlspecialchars($formation['category_color'] ?? '#E2E8F0', ENT_QUOTES, 'UTF-8'); ?>; color: <?php echo htmlspecialchars($formation['category_color'] ? (hexdec(substr($formation['category_color'], 1, 2)) + hexdec(substr($formation['category_color'], 3, 2)) + hexdec(substr($formation['category_color'], 5, 2)) > 382 ? '#333' : '#FFF') : '#333', ENT_QUOTES, 'UTF-8'); ?>;" title="Filtrer par cette catégorie">
                                        <?php echo htmlspecialchars($formation['category'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>   
                                <td class="px-6 py-6 text-center">
                                    <div class="text-sm font-black text-slate-700"><?php echo htmlspecialchars($formation['duration'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"><?php echo htmlspecialchars($formation['level'] ?: '', ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="text-sm font-black text-slate-900"><?php echo htmlspecialchars(number_format((float)$formation['price'], 0, ',', ' ')); ?> <span class="text-[9px] font-black text-slate-300">FCFA</span></div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-[10px] font-black uppercase tracking-widest <?php echo $formation['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $formation['is_active'] ? 'bg-green-500' : 'bg-slate-400'; ?>"></span>
                                        <?php echo $formation['is_active'] ? 'Active' : 'Désactivée'; ?>
                                    </span>
                                </td>
                                <td class="px-10 py-6 text-right">
                                    <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity translate-x-2 group-hover:translate-x-0">
                                        <a href="formations.php?id=<?php echo $formation['id']; ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary hover:border-primary/30 hover:shadow-lg hover:shadow-primary/10 transition-all" title="Modifier">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                        <a href="connect/delete_formation.php?id=<?php echo $formation['id']; ?>" onclick="return confirm('Confirmer la suppression ?')" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-100 hover:shadow-lg hover:shadow-red-500/10 transition-all" title="Supprimer">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">Aucune formation n'a encore été créée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleButton = document.getElementById('toggleAddFormation');
        var panel = document.getElementById('addFormationPanel');
        if (toggleButton && panel) {
            toggleButton.addEventListener('click', function() {
                panel.classList.toggle('hidden');
                if (!panel.classList.contains('hidden')) {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        // Gestion de la prévisualisation de l'image en temps réel
        const imageFile = document.getElementById('image_file');
        const imageUrl = document.getElementById('image_url');
        const imagePreview = document.getElementById('image_preview');
        const previewPlaceholder = document.getElementById('preview_placeholder');

        function updatePreview(src) {
            if (src && src.trim() !== '') {
                imagePreview.src = src;
                imagePreview.classList.remove('hidden');
                previewPlaceholder.classList.add('hidden');
            } else {
                imagePreview.src = '';
                imagePreview.classList.add('hidden');
                previewPlaceholder.classList.remove('hidden');
            }
        }

        if (imageFile) {
            imageFile.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5 Mo en octets

                    if (file.size > maxSize) {
                        alert(window.t ? window.t('file_too_large') : "Le fichier est trop volumineux. La taille maximale autorisée est de 5 Mo.");
                        this.value = ''; // Réinitialise le champ de fichier
                        updatePreview(''); // Vide l'aperçu
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        updatePreview(e.target.result);
                    };
                    reader.readAsDataURL(file);
                    // Vider le champ URL pour éviter les conflits visuels
                    if (imageUrl) imageUrl.value = '';
                }
            });
        }

        if (imageUrl) {
            imageUrl.addEventListener('input', function() {
                updatePreview(this.value);
            });
        }

        // Gestion de la couleur de la catégorie
        const categorySelect = document.getElementById('categorySelect');
        const categoryColorInput = document.getElementById('category_color');
        const categorySearchInput = document.getElementById('categorySearch');
        const categoriesData = <?php echo json_encode($categories); ?>;
        const originalOptions = categorySelect ? Array.from(categorySelect.options) : [];

        function updateCategoryColor() {
            if (!categorySelect) return;
            const selectedCategoryId = categorySelect.value;
            const selectedCategory = categoriesData.find(cat => cat.id == selectedCategoryId);
            if (selectedCategory && categoryColorInput) {
                categoryColorInput.value = selectedCategory.color || '#3F8F29'; // Default color if none is set
            }
        }

        if (categorySearchInput && categorySelect) {
            let categorySearchTimer = null;
            const debounceMs = 250;

            categorySearchInput.addEventListener('input', function() {
                if (categorySearchTimer) clearTimeout(categorySearchTimer);

                categorySearchTimer = setTimeout(() => {
                    const term = this.value.toLowerCase().trim();
                    originalOptions.forEach(opt => {
                        const isMatch = opt.text.toLowerCase().includes(term) || opt.value === "";
                        opt.hidden = !isMatch;
                        // Support pour les navigateurs plus anciens ou spécifiques (Safari)
                        opt.style.display = isMatch ? '' : 'none';
                    });
                }, debounceMs);
            });
        }


        if (categorySelect && categoryColorInput) {
            categorySelect.addEventListener('change', updateCategoryColor);
            updateCategoryColor(); // Set initial color on load
        }
    });
</script>
<?php render_admin_footer_scripts(); ?>
</body>
</html>
