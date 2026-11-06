<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/_layout.php';

ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';
$initials = strtoupper(substr($adminName, 0, 1));

// Récupération des paramètres actuels
$settings = get_site_settings($pdo);

if (!empty($settings['google_gemini_api_key'])) {
    $settings['google_gemini_api_key'] = decrypt_data($settings['google_gemini_api_key']);
}

/**
 * Masque partiellement une clé API
 */
function maskApiKey(?string $key): string {
    if (empty($key)) return '';
    if (strlen($key) < 12) return '********';
    return substr($key, 0, 4) . '...' . substr($key, -4);
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('settings'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Configuration du Site', 'Gérez les informations globales et les textes de la page d\'accueil.'); ?>

    <main class="p-8">
        <div class="mb-10">
            <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Paramètres</p>
            <h2 class="text-4xl font-title font-black text-slate-900">Configuration Générale</h2>
            <p class="text-slate-400 mt-2">Mettez à jour les coordonnées, les réseaux sociaux et le contenu du site.</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl border <?php echo $status === 'success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700'; ?> font-bold text-sm animate-in">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="connect/save_settings.php" method="POST" class="space-y-8">
            <!-- Section: Logo du Site -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Logo du Site</h3>
                </div>

                <div class="space-y-6">
                    <?php 
                     $currentLogo = $settings['logo_image'] ?? null;
                    ?>
                    
                    <?php if ($currentLogo && file_exists(__DIR__ . '/../' . $currentLogo)): ?>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-black uppercase text-slate-500">Logo actuel</p>
                                <button type="button" onclick="deleteLogo()" class="text-xs font-black uppercase px-4 py-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                    <i data-lucide="trash-2" class="w-3 h-3 inline mr-1"></i> Supprimer
                                </button>
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 flex items-center justify-center min-h-32">
                                <img src="../<?php echo htmlspecialchars($currentLogo); ?>?v=<?php echo time(); ?>" alt="Logo actuel" class="max-h-32 object-contain" loading="lazy">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <p class="text-xs font-black uppercase text-slate-500">Aucun logo téléchargé</p>
                            <p class="text-sm text-slate-600">Un logo par défaut (lettre "Z") est actuellement affiché.</p>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-4 border-t border-slate-100 pt-6">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Télécharger un nouveau logo</label>
                        <div id="logo-upload-area" class="relative border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-all group">
                            <input 
                                type="file" 
                                id="logo-file-input" 
                                accept="image/jpeg,image/png,image/webp,image/svg+xml" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            >
                            <div class="pointer-events-none">
                                <i data-lucide="upload-cloud" class="w-12 h-12 mx-auto mb-3 text-slate-400 group-hover:text-primary transition-colors"></i>
                                <p class="text-sm font-bold text-slate-900">Glissez votre logo ici</p>
                                <p class="text-xs text-slate-500 mt-1">ou cliquez pour parcourir (PNG, JPEG, WebP, SVG - Max 5MB)</p>
                            </div>
                        </div>

                        <div id="logo-preview-container" class="hidden space-y-3">
                            <p class="text-xs font-black uppercase text-slate-500">Aperçu</p>
                            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 flex items-center justify-center min-h-32">
                                <img id="logo-preview" class="max-h-32 object-contain" alt="Aperçu du logo">
                            </div>
                        </div>

                        <div id="logo-upload-status" class="hidden p-4 rounded-2xl text-sm font-medium"></div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-50 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer group mb-2">
                                <input type="hidden" name="show_site_name" value="0">
                                <input type="checkbox" name="show_site_name" value="1" <?php echo ($settings['show_site_name'] ?? '0') === '1' ? 'checked' : ''; ?> class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary transition-all">
                                <span class="text-sm font-bold text-slate-700 group-hover:text-primary transition-colors">Afficher le nom du site</span>
                            </label>
                            <p class="text-xs text-slate-400 ml-8">Affiche le nom à côté de l'image.</p>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase text-slate-500 ml-2">Hauteur du logo (pixels)</label>
                            <div class="flex items-center gap-3 mt-2">
                                <input type="number" name="logo_height" value="<?php echo htmlspecialchars($settings['logo_height'] ?? '40'); ?>" min="20" max="150" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                                <span class="text-xs font-bold text-slate-400">px</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase text-slate-500 ml-2">Forme du logo</label>
                            <select name="logo_shape" class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                                <option value="original" <?php echo ($settings['logo_shape'] ?? 'original') === 'original' ? 'selected' : ''; ?>>Original (Carré)</option>
                                <option value="rounded" <?php echo ($settings['logo_shape'] ?? '') === 'rounded' ? 'selected' : ''; ?>>Bords arrondis</option>
                                <option value="circle" <?php echo ($settings['logo_shape'] ?? '') === 'circle' ? 'selected' : ''; ?>>Cercle parfait</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Intelligence Artificielle -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="bot" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Intelligence Artificielle (Chatbot)</h3>
                </div>
                
                <div class="space-y-4">
                    <div class="flex flex-col md:flex-row md:items-end gap-4">
                        <div class="flex-grow space-y-2">
                            <label class="text-xs font-black uppercase text-slate-500 ml-2">Clé API Google Gemini</label>
                            <input 
                                type="text" 
                                id="gemini-key-input" 
                                name="google_gemini_api_key" 
                                value="<?php echo htmlspecialchars(maskApiKey($settings['google_gemini_api_key'] ?? '')); ?>" 
                                placeholder="AIza..." 
                                class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-mono text-sm"
                                onfocus="if(this.value.includes('...')) this.value=''"
                            >
                        </div>
                        <button type="button" onclick="testGeminiKey()" class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center gap-2">
                            <i data-lucide="zap" class="w-4 h-4"></i> Tester la clé
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 ml-2">Utilisée par l'assistant virtuel pour répondre aux visiteurs. Obtenez une clé gratuite sur <a href="https://aistudio.google.com/" target="_blank" class="text-primary underline">Google AI Studio</a>.</p>
                    <div id="gemini-test-status" class="hidden p-4 rounded-2xl text-xs font-bold animate-in"></div>
                </div>
            </div>

            <!-- Section: Identité & Contact -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <i data-lucide="info" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Identité & Contact</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Nom du site</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'ZoeTIC'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Email de contact</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Téléphone</label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">WhatsApp (ID sans +)</label>
                        <input type="text" name="contact_whatsapp" value="<?php echo htmlspecialchars($settings['contact_whatsapp'] ?? ''); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm" placeholder="ex: 2250574677246">
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Adresse physique</label>
                        <input type="text" name="contact_address" value="<?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                </div>
            </div>

            <!-- Section: Design & Thème -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                        <i data-lucide="palette" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Design & Thème</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Couleur Primaire (Jour)</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="theme_primary" value="<?php echo htmlspecialchars($settings['theme_primary'] ?? '#3F8F29'); ?>" class="w-20 h-20 rounded-2xl border-none p-1 cursor-pointer bg-slate-50">
                            <input type="text" value="<?php echo htmlspecialchars($settings['theme_primary'] ?? '#3F8F29'); ?>" class="flex-grow bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-mono text-sm" readonly>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Couleur Secondaire (Jour)</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="theme_secondary" value="<?php echo htmlspecialchars($settings['theme_secondary'] ?? '#F4A321'); ?>" class="w-20 h-20 rounded-2xl border-none p-1 cursor-pointer bg-slate-50">
                            <input type="text" value="<?php echo htmlspecialchars($settings['theme_secondary'] ?? '#F4A321'); ?>" class="flex-grow bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-mono text-sm" readonly>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-slate-50 space-y-6">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="hidden" name="auto_night_mode" value="0">
                        <input type="checkbox" name="auto_night_mode" value="1" <?php echo ($settings['auto_night_mode'] ?? '0') === '1' ? 'checked' : ''; ?> class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary transition-all">
                        <span class="text-sm font-bold text-slate-700 group-hover:text-primary transition-colors">Activer le mode nuit automatique</span>
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 ml-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400">Heure de début (0-23)</label>
                            <input type="number" name="auto_night_start" min="0" max="23" value="<?php echo htmlspecialchars($settings['auto_night_start'] ?? '18'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400">Heure de fin (0-23)</label>
                            <input type="number" name="auto_night_end" min="0" max="23" value="<?php echo htmlspecialchars($settings['auto_night_end'] ?? '6'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                        </div>
                    </div>

                    <div class="mt-8 pt-8 border-t border-slate-50 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="text-xs font-black uppercase text-slate-500 ml-2">Couleur Primaire (Nuit)</label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="night_mode_primary_color" value="<?php echo htmlspecialchars($settings['night_mode_primary_color'] ?? '#86efac'); ?>" class="w-20 h-20 rounded-2xl border-none p-1 cursor-pointer bg-slate-50">
                                <input type="text" value="<?php echo htmlspecialchars($settings['night_mode_primary_color'] ?? '#86efac'); ?>" class="flex-grow bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-mono text-sm" readonly>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="text-xs font-black uppercase text-slate-500 ml-2">Couleur Secondaire (Nuit)</label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="night_mode_secondary_color" value="<?php echo htmlspecialchars($settings['night_mode_secondary_color'] ?? '#fcd34d'); ?>" class="w-20 h-20 rounded-2xl border-none p-1 cursor-pointer bg-slate-50">
                                <input type="text" value="<?php echo htmlspecialchars($settings['night_mode_secondary_color'] ?? '#fcd34d'); ?>" class="flex-grow bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-mono text-sm" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Réseaux Sociaux -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                        <i data-lucide="share-2" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Réseaux Sociaux</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Facebook</label>
                        <input type="text" name="social_facebook" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? '#'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">LinkedIn</label>
                        <input type="text" name="social_linkedin" value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? '#'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Instagram</label>
                        <input type="text" name="social_instagram" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? '#'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">X / Twitter</label>
                        <input type="text" name="social_twitter" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? '#'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">TikTok</label>
                        <input type="text" name="social_tiktok" value="<?php echo htmlspecialchars($settings['social_tiktok'] ?? '#'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">YouTube</label>
                        <input type="text" name="social_youtube" value="<?php echo htmlspecialchars($settings['social_youtube'] ?? '#'); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                </div>
            </div>

            <!-- Section: Hero Accueil -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                        <i data-lucide="layout" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Contenu Hero (Page d'accueil)</h3>
                </div>
                
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Titre Principal</label>
                        <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title'] ?? ''); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-500 ml-2">Sous-titre / Description</label>
                        <textarea name="hero_subtitle" rows="3" class="w-full bg-slate-50 border-none rounded-2xl p-5 focus:ring-4 focus:ring-primary/5 outline-none font-bold text-sm resize-none"><?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-slate-900 text-white px-12 py-4 rounded-full font-black text-xs uppercase tracking-widest hover:bg-primary transition-all shadow-2xl shadow-slate-900/20">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
<script>
function toggleNightPreview() {
    document.body.classList.toggle('night-mode');
}

// Gestion du téléchargement du logo
document.addEventListener('DOMContentLoaded', () => {
    const uploadArea = document.getElementById('logo-upload-area');
    const fileInput = document.getElementById('logo-file-input');
    const previewContainer = document.getElementById('logo-preview-container');
    const preview = document.getElementById('logo-preview');
    const statusDiv = document.getElementById('logo-upload-status');

    // Gestion du drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        if (uploadArea) {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        }
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    if (uploadArea) {
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.add('border-primary', 'bg-primary/10');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.remove('border-primary', 'bg-primary/10');
            }, false);
        });

        uploadArea.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (fileInput) fileInput.files = files;
            handleFileSelect();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }

    function handleFileSelect() {
        if (!fileInput || fileInput.files.length === 0) return;
        const file = fileInput.files[0];
        if (!file) return;

        // Vérification du type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        if (!allowedTypes.includes(file.type)) {
            showStatus('Format de fichier non autorisé. Formats acceptés: PNG, JPEG, WebP, SVG', 'error');
            return;
        }

        // Vérification de la taille (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showStatus('La taille du fichier dépasse 5MB', 'error');
            return;
        }

        // Affichage de l'aperçu
        const reader = new FileReader();
        reader.onload = (e) => {
            if (preview) preview.src = e.target.result;
            if (previewContainer) previewContainer.classList.remove('hidden');
            showStatus('Fichier valide. Cliquez sur "Enregistrer les modifications" pour télécharger.', 'info');
        };
        reader.readAsDataURL(file);
    }

    function showStatus(message, type) {
        if (!statusDiv) return;
        statusDiv.textContent = message;
        statusDiv.classList.remove('hidden', 'bg-green-50', 'text-green-700', 'border', 'border-green-100', 'bg-red-50', 'text-red-700', 'border-red-100', 'bg-blue-50', 'text-blue-700', 'border-blue-100');
        
        if (type === 'success') {
            statusDiv.classList.add('bg-green-50', 'text-green-700', 'border', 'border-green-100');
        } else if (type === 'error') {
            statusDiv.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-100');
        } else {
            statusDiv.classList.add('bg-blue-50', 'text-blue-700', 'border', 'border-blue-100');
        }
        statusDiv.classList.add('block');
    }
});

// Ajouter un event listener au formulaire pour gérer l'upload du logo
let logoUploadInProgress = false;
const settingsForm = document.querySelector('form');
if (settingsForm) {
    settingsForm.addEventListener('submit', async (e) => {
        const fileInput = document.getElementById('logo-file-input');
        
        // S'il y a un fichier à uploader
        if (fileInput && fileInput.files.length > 0) {
            e.preventDefault();
            logoUploadInProgress = true;
            const success = await uploadLogo(fileInput.files[0]);
            logoUploadInProgress = false;
            
            if (success) {
                // Nettoyer le fichier de l'input pour éviter de le re-uploader
                fileInput.value = '';
                // Soumettre le reste du formulaire
                setTimeout(() => {
                    settingsForm.submit();
                }, 500);
            }
        }
    });
}

async function uploadLogo(file) {
    const statusDiv = document.getElementById('logo-upload-status');
    const formData = new FormData();
    formData.append('logo_file', file);

    try {
        if (statusDiv) {
            statusDiv.textContent = 'Téléchargement du logo en cours...';
            statusDiv.classList.remove('hidden', 'bg-green-50', 'text-green-700', 'border', 'border-green-100', 'bg-red-50', 'text-red-700', 'border-red-100', 'bg-blue-50', 'text-blue-700', 'border-blue-100');
            statusDiv.classList.add('bg-blue-50', 'text-blue-700', 'border', 'border-blue-100', 'block');
        }

        const response = await fetch('connect/save_logo.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.status === 'success') {
            if (statusDiv) {
                statusDiv.textContent = data.message;
                statusDiv.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-100');
                statusDiv.classList.add('bg-green-50', 'text-green-700', 'border', 'border-green-100');
            }
            return true;
        } else {
            if (statusDiv) {
                statusDiv.textContent = 'Erreur: ' + data.message;
                statusDiv.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-100');
                statusDiv.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-100');
            }
            return false;
        }
    } catch (error) {
        if (statusDiv) {
            statusDiv.textContent = (window.t ? window.t('server_connection_error') : 'Erreur de communication: ') + error.message;
            statusDiv.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-100');
            statusDiv.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-100');
        }
        console.error('Erreur upload:', error);
        return false;
    }
}

async function testGeminiKey() {
    const input = document.getElementById('gemini-key-input');
    const statusDiv = document.getElementById('gemini-test-status');
    if (!input) return;
    const key = input.value.trim();

    if (!key) {
        alert(window.t ? window.t('enter_a_key') : 'Veuillez saisir une clé avant de tester.');
        return;
    }

    if (statusDiv) {
        statusDiv.classList.remove('hidden', 'bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700');
        statusDiv.classList.add('bg-blue-50', 'text-blue-700', 'block');
        statusDiv.textContent = (window.t ? window.t('verifying_key') : 'Vérification en cours auprès de Google AI...');
    }

    const formData = new FormData();
    formData.append('api_key', key);

    try {
        const response = await fetch('connect/test_gemini_key.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (statusDiv) {
            statusDiv.classList.remove('bg-blue-50', 'text-blue-700');
            statusDiv.classList.add(data.status === 'success' ? 'bg-green-50' : 'bg-red-50');
            statusDiv.classList.add(data.status === 'success' ? 'text-green-700' : 'text-red-700');
            statusDiv.textContent = data.message;
        }
    } catch (error) {
        if (statusDiv) {
            statusDiv.classList.replace('bg-blue-50', 'bg-red-50');
            statusDiv.textContent = 'Erreur de communication avec le serveur.';
        }
    }
}

async function deleteLogo() {
    if (!confirm(window.t ? window.t('confirm_delete_logo') : 'Êtes-vous sûr de vouloir supprimer le logo?')) return;

    try {
        const response = await fetch('connect/delete_logo.php', {
            method: 'POST',
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.status === 'success') {
            alert(data.message);
            location.reload();
        } else {
            alert((window.t ? window.t('error_occurred') : 'Erreur') + ': ' + data.message);
        }
    } catch (error) {
        alert((window.t ? window.t('delete_error_prefix') : 'Erreur lors de la suppression: {error}').replace('{error}', error.message));
    }
}
</script>
</body>
</html>