<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/realisations.php';
require_once __DIR__ . '/_layout.php';

ensure_realisations_tables($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'success';

$realisation = [
    'id' => 0,
    'title' => '',
    'category' => 'Vidéosurveillance & Télésurveillance IP',
    'client_name' => '',
    'location' => 'Abidjan',
    'completion_date' => date('Y-m-d'),
    'short_description' => '',
    'description' => '',
    'cover_image' => '',
    'client_quote' => '',
    'testimonial_type' => 'none',
    'testimonial_media_url' => '',
    'testimonial_author' => '',
    'status' => 'published',
    'is_featured' => 0,
    'display_order' => 0,
    'media' => []
];

if ($id > 0) {
    $existing = get_realisation_by_id($pdo, $id);
    if ($existing) {
        $realisation = $existing;
    } else {
        header("Location: realisations.php?message=" . urlencode("Réalisation introuvable.") . "&status=error");
        exit;
    }
}

$standardCategories = [
    'Vidéosurveillance & Télésurveillance IP',
    'Câblage Réseau & Fibre Optique',
    'Sécurité Électronique & Alarmes',
    'Contrôle d\'Accès & Pointage Biométrique',
    'Maintenance & Support Informatique',
    'Installation Onduleurs & Énergie',
    'Développement & Solutions Logicielles',
    'Autre Prestation'
];

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

<?php render_admin_sidebar('realisations'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, $id > 0 ? 'Modifier la réalisation' : 'Nouvelle réalisation', 'Gérez les détails de l\'intervention et les témoignages clients.'); ?>

    <main class="tab-content active p-8">
        <!-- En-tête -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="realisations.php" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary transition mb-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Retour aux réalisations
                </a>
                <h2 class="text-3xl font-title font-bold text-slate-900">
                    <?php echo $id > 0 ? 'Modifier : ' . htmlspecialchars($realisation['title']) : 'Ajouter une réalisation sur le terrain'; ?>
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Documentez votre intervention technique (photos, vidéos au travail) et la confession de satisfaction du client.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="realisations.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">
                    Annuler
                </a>
                <button type="submit" form="realisationForm" class="inline-flex items-center gap-2 rounded-xl bg-primary text-white px-6 py-2.5 text-sm font-bold shadow-lg shadow-green-900/20 hover:bg-green-700 transition">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <?php echo $id > 0 ? 'Mettre à jour' : 'Publier le projet'; ?>
                </button>
            </div>
        </div>

        <!-- Notification Message -->
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

        <form id="realisationForm" action="connect/save_realisation.php" method="POST" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="id" value="<?php echo (int)$realisation['id']; ?>">

            <!-- BLOC 1 : Informations Générales -->
            <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <i data-lucide="info" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-title font-bold text-slate-900">1. Présentation de la prestation</h3>
                        <p class="text-xs text-slate-400">Titre, catégorie de métier, client et localisation de l'intervention.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Titre de la réalisation <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($realisation['title']); ?>" placeholder="Ex : Installation de 16 caméras IP 4K avec vision nocturne infrarouge" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold outline-none focus:border-primary focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Catégorie de service <span class="text-red-500">*</span></label>
                        <select name="category_select" id="categorySelect" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold outline-none focus:border-primary focus:bg-white transition-all">
                            <?php foreach ($standardCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $realisation['category'] === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="custom">-- Saisir une autre catégorie --</option>
                        </select>
                        <input type="text" name="category_custom" id="categoryCustom" placeholder="Précisez votre catégorie personnalisée..." class="w-full mt-2 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:border-primary hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Nom du Client / Entreprise (Optionnel)</label>
                        <input type="text" name="client_name" value="<?php echo htmlspecialchars($realisation['client_name'] ?? ''); ?>" placeholder="Ex : Résidence Privée, Société BTP Ivoire, Particulier..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold outline-none focus:border-primary focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Lieu / Quartier</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($realisation['location'] ?? ''); ?>" placeholder="Ex : Abidjan - Cocody Angré" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold outline-none focus:border-primary focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Date de l'intervention</label>
                        <input type="date" name="completion_date" value="<?php echo htmlspecialchars($realisation['completion_date'] ?? date('Y-m-d')); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold outline-none focus:border-primary focus:bg-white transition-all">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Description courte (Accroche)</label>
                        <input type="text" name="short_description" value="<?php echo htmlspecialchars($realisation['short_description'] ?? ''); ?>" placeholder="Résumé en 1 phrase pour la vignette sur le site (Ex : Sécurisation complète d'une villa avec accès à distance sur smartphone)" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold outline-none focus:border-primary focus:bg-white transition-all">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Détails complets de l'intervention <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="5" required placeholder="Détaillez le travail effectué : le besoin initial du client, le matériel déployé (ex: caméras dômes, NVR 4K, disque 4To, câbles CAT6), les défis rencontrés et la solution apportée..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium outline-none focus:border-primary focus:bg-white transition-all leading-relaxed"><?php echo htmlspecialchars($realisation['description']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- BLOC 2 : Photos & Visuels du Chantier -->
            <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <i data-lucide="camera" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-title font-bold text-slate-900">2. Photos de l'intervention</h3>
                        <p class="text-xs text-slate-400">Image principale de couverture et galerie de photos du chantier.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Photo de couverture -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Photo de couverture principale</label>
                        <div class="flex flex-col gap-3">
                            <?php if (!empty($realisation['cover_image'])): ?>
                                <div class="relative w-full h-44 rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 group">
                                    <img src="../<?php echo htmlspecialchars($realisation['cover_image']); ?>" alt="Couverture" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                                        Image actuelle
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-600 outline-none">
                            <input type="text" name="cover_image_url" value="<?php echo (!empty($realisation['cover_image']) && filter_var($realisation['cover_image'], FILTER_VALIDATE_URL)) ? htmlspecialchars($realisation['cover_image']) : ''; ?>" placeholder="Ou coller une URL d'image externe..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none">
                        </div>
                    </div>

                    <!-- Galerie photos additionnelles -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Ajouter des photos du chantier / matériel</label>
                        <p class="text-xs text-slate-400 mb-3">Sélectionnez plusieurs photos prises pendant l'installation (câblage, pose de caméra, rack réseau, résultat fini).</p>
                        <input type="file" name="work_photos[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="w-full px-4 py-3 bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl text-xs font-medium text-slate-600 cursor-pointer hover:border-primary transition">

                        <!-- Liste des photos existantes -->
                        <?php 
                        $existingPhotos = array_filter($realisation['media'], function($m) { return $m['media_type'] === 'photo'; });
                        if (!empty($existingPhotos)):
                        ?>
                            <div class="mt-4">
                                <p class="text-xs font-bold text-slate-500 mb-2">Photos déjà enregistrées (<?php echo count($existingPhotos); ?>) :</p>
                                <div class="grid grid-cols-4 gap-2">
                                    <?php foreach ($existingPhotos as $photo): ?>
                                        <div class="relative group h-16 rounded-xl overflow-hidden border border-slate-200">
                                            <img src="../<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Photo chantier" class="w-full h-full object-cover">
                                            <a href="connect/delete_realisation_media.php?id=<?php echo $photo['id']; ?>&realisation_id=<?php echo $realisation['id']; ?>" onclick="return confirm('Supprimer cette photo ?')" class="absolute inset-0 bg-red-600/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold" title="Supprimer la photo">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- BLOC 3 : Vidéo de l'équipe au travail -->
            <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        <i data-lucide="video" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-title font-bold text-slate-900">3. Vidéo de l'équipe en plein travail</h3>
                        <p class="text-xs text-slate-400">Montrer nos techniciens en action pendant l'installation (câblage, configuration, tests).</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Téléverser une vidéo d'intervention (MP4, WebM, MOV)</label>
                        <input type="file" name="work_video_file" accept="video/mp4,video/webm,video/quicktime,video/x-matroska" class="w-full px-4 py-3 bg-slate-50 border-2 border-dashed border-purple-200 rounded-2xl text-xs font-medium text-slate-600 cursor-pointer hover:border-purple-500 transition mb-3">
                        
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Ou coller un lien vidéo (YouTube, Vimeo, etc.)</label>
                        <input type="text" name="work_video_url" placeholder="https://www.youtube.com/watch?v=... ou https://youtu.be/..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:border-purple-500">
                    </div>

                    <!-- Lecteur des vidéos existantes de l'équipe -->
                    <div>
                        <p class="text-xs font-bold text-slate-500 mb-2">Vidéos de chantier déjà associées :</p>
                        <?php 
                        $existingVideos = array_filter($realisation['media'], function($m) { return $m['media_type'] === 'video'; });
                        if (empty($existingVideos)):
                        ?>
                            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 text-center text-slate-400 text-xs">
                                Aucune vidéo de travail pour le moment.
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($existingVideos as $vid): ?>
                                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col gap-2">
                                        <?php if (filter_var($vid['file_path'], FILTER_VALIDATE_URL)): ?>
                                            <div class="text-xs font-semibold text-purple-700 truncate">
                                                Lien : <?php echo htmlspecialchars($vid['file_path']); ?>
                                            </div>
                                        <?php else: ?>
                                            <video controls class="w-full h-36 rounded-xl bg-black object-contain">
                                                <source src="../<?php echo htmlspecialchars($vid['file_path']); ?>" type="video/mp4">
                                                Votre navigateur ne supporte pas la balise vidéo.
                                            </video>
                                        <?php endif; ?>
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-slate-500"><?php echo htmlspecialchars($vid['caption'] ?: 'Vidéo de travail'); ?></span>
                                            <a href="connect/delete_realisation_media.php?id=<?php echo $vid['id']; ?>&realisation_id=<?php echo $realisation['id']; ?>" onclick="return confirm('Supprimer cette vidéo ?')" class="text-red-500 hover:text-red-700 text-xs font-bold inline-flex items-center gap-1">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Supprimer
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- BLOC 4 : Confession & Témoignage Client (Vidéos & Notes Vocales) -->
            <div class="bg-gradient-to-br from-amber-50/50 to-orange-50/50 rounded-3xl p-6 md:p-8 border border-amber-200/60 shadow-sm">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-amber-200/50">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-md shadow-amber-500/20">
                        <i data-lucide="heart-handshake" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-title font-bold text-slate-900">4. Confession & Témoignage du Client Satisfait</h3>
                        <p class="text-xs text-amber-900/70">Mettez en valeur les retours oraux (note vocale audio) ou vidéo filmée sur place au smartphone.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Auteur du témoignage</label>
                        <input type="text" name="testimonial_author" value="<?php echo htmlspecialchars($realisation['testimonial_author'] ?? ''); ?>" placeholder="Ex : M. Kouamé, Gérant de supermarché" class="w-full px-4 py-3 bg-white border border-amber-200 rounded-2xl text-sm font-semibold outline-none focus:border-amber-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Format de la confession client</label>
                        <select name="testimonial_type" id="testimonialTypeSelect" class="w-full px-4 py-3 bg-white border border-amber-200 rounded-2xl text-sm font-semibold outline-none focus:border-amber-500 transition-all">
                            <option value="none" <?php echo $realisation['testimonial_type'] === 'none' ? 'selected' : ''; ?>>Aucun média (texte seul ou pas de retour)</option>
                            <option value="video" <?php echo $realisation['testimonial_type'] === 'video' ? 'selected' : ''; ?>>🎥 Vidéo du client (Smartphone sur place)</option>
                            <option value="audio" <?php echo $realisation['testimonial_type'] === 'audio' ? 'selected' : ''; ?>>🎙️ Note vocale / Audio (Confession orale)</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Citation ou extrait clé du client</label>
                        <textarea name="client_quote" rows="3" placeholder="Ex : « L'équipe de ZoeTIC a été ponctuelle, le travail est très propre et les caméras fonctionnent parfaitement sur mon téléphone même la nuit ! Je les recommande vivement. »" class="w-full px-4 py-3 bg-white border border-amber-200 rounded-2xl text-sm font-medium outline-none focus:border-amber-500 transition-all"><?php echo htmlspecialchars($realisation['client_quote'] ?? ''); ?></textarea>
                    </div>

                    <!-- Zone de téléversement média du témoignage -->
                    <div id="testimonialMediaBox" class="md:col-span-2 p-5 bg-white rounded-2xl border border-amber-200 <?php echo $realisation['testimonial_type'] === 'none' ? 'hidden' : ''; ?>">
                        <div class="flex items-center justify-between mb-3">
                            <h4 id="testimonialMediaTitle" class="text-sm font-bold text-slate-800">
                                <?php echo $realisation['testimonial_type'] === 'video' ? 'Fichier Vidéo du Client (Smartphone)' : 'Note Vocale / Fichier Audio du Client'; ?>
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Téléverser le fichier (vidéo ou audio)</label>
                                <input type="file" name="testimonial_file" id="testimonialFileInput" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium">
                                
                                <label class="block text-xs font-bold text-slate-600 mt-2 mb-1">Ou renseigner un lien externe</label>
                                <input type="text" name="testimonial_url_external" value="<?php echo (!empty($realisation['testimonial_media_url']) && filter_var($realisation['testimonial_media_url'], FILTER_VALIDATE_URL)) ? htmlspecialchars($realisation['testimonial_media_url']) : ''; ?>" placeholder="https://..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            </div>

                            <!-- Prévisualisation / Lecteur direct -->
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="block text-xs font-bold text-slate-500 mb-2">Aperçu du média actuel :</span>
                                <?php if (!empty($realisation['testimonial_media_url'])): ?>
                                    <?php if ($realisation['testimonial_type'] === 'video'): ?>
                                        <video controls class="w-full h-32 rounded-lg bg-black object-contain">
                                            <source src="../<?php echo htmlspecialchars($realisation['testimonial_media_url']); ?>" type="video/mp4">
                                            Votre navigateur ne supporte pas la vidéo.
                                        </video>
                                    <?php elseif ($realisation['testimonial_type'] === 'audio'): ?>
                                        <div class="flex flex-col gap-2">
                                            <audio controls class="w-full">
                                                <source src="../<?php echo htmlspecialchars($realisation['testimonial_media_url']); ?>">
                                                Votre navigateur ne supporte pas l'audio.
                                            </audio>
                                            <span class="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                                                <i data-lucide="mic" class="w-3.5 h-3.5"></i> Note vocale enregistrée
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Aucun fichier téléversé pour le moment.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOC 5 : Options de Publication -->
            <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-6">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">Statut de diffusion</label>
                        <select name="status" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold outline-none focus:border-primary">
                            <option value="published" <?php echo $realisation['status'] === 'published' ? 'selected' : ''; ?>>🟢 Publié (Visible sur le site)</option>
                            <option value="draft" <?php echo $realisation['status'] === 'draft' ? 'selected' : ''; ?>>🟡 Brouillon (Caché aux visiteurs)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3 pt-6">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo !empty($realisation['is_featured']) ? 'checked' : ''; ?> class="w-5 h-5 rounded-lg text-primary focus:ring-primary border-slate-300">
                        <label for="is_featured" class="text-sm font-bold text-slate-800 cursor-pointer flex items-center gap-1">
                            <i data-lucide="star" class="w-4 h-4 text-amber-500 fill-current"></i>
                            Mettre à la une sur l'accueil
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-4 w-full md:w-auto justify-end">
                    <a href="realisations.php" class="px-6 py-3 rounded-2xl border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">
                        Annuler
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-primary text-white px-8 py-3.5 text-sm font-bold shadow-lg shadow-green-900/20 hover:bg-green-700 transition scale-100 hover:scale-[1.02]">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        <?php echo $id > 0 ? 'Enregistrer les modifications' : 'Créer et Publier'; ?>
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
    // Gestion de la catégorie personnalisée
    const catSelect = document.getElementById('categorySelect');
    const catCustom = document.getElementById('categoryCustom');

    if (catSelect && catCustom) {
        catSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                catCustom.classList.remove('hidden');
                catCustom.focus();
            } else {
                catCustom.classList.add('hidden');
            }
        });
    }

    // Gestion de l'affichage du box confession client
    const testTypeSelect = document.getElementById('testimonialTypeSelect');
    const testBox = document.getElementById('testimonialMediaBox');
    const testTitle = document.getElementById('testimonialMediaTitle');
    const testFileInput = document.getElementById('testimonialFileInput');

    if (testTypeSelect && testBox) {
        testTypeSelect.addEventListener('change', function() {
            if (this.value === 'none') {
                testBox.classList.add('hidden');
            } else {
                testBox.classList.remove('hidden');
                if (this.value === 'video') {
                    testTitle.textContent = "Fichier Vidéo du Client (Smartphone)";
                    testFileInput.setAttribute('accept', 'video/mp4,video/webm,video/quicktime');
                } else if (this.value === 'audio') {
                    testTitle.textContent = "Note Vocale / Fichier Audio du Client";
                    testFileInput.setAttribute('accept', 'audio/*,video/webm');
                }
            }
        });
    }

    // Initialisation Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
