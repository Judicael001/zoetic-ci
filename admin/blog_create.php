<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/../src/db/blog.php';
require_once '_layout.php';

ensure_blog_table($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'success';

$post = [
    'title' => '',
    'category' => '',
    'excerpt' => '',
    'content' => '',
    'featured_image' => '',
    'status' => 'draft',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC) ?: $post;
}

// load existing media attachments for preview when editing
$postMedia = [];
if ($id > 0) {
    $postMedia = get_media_for_post($pdo, $id);
}

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

<!-- Cropper.js Integration -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<style>
    .cropper-container { max-height: 500px; width: 100%; }
    #cropper-image { max-width: 100%; display: block; }
</style>

<!-- TinyMCE Integration -->
<script src="https://cdn.tiny.cloud/1/ntjmkq5d5c2etrcxoc5qeiakfywakjlc9rvlnrvt7gqqarc9/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#blog-content, #blog-excerpt',
    height: 400,
    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons help',
    toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image fontawesome | forecolor backcolor | help',
    menubar: 'file edit view insert format tools table help',
    images_upload_url: 'connect/upload_tinymce_image.php',
    automatic_uploads: true,
    file_picker_types: 'image',
    file_picker_callback: (callback, value, meta) => {
      if (meta.filetype === 'image') {
        tinymce.activeEditor.windowManager.openUrl({
          title: 'Bibliothèque d\'images ZoeTIC',
          url: 'file_manager.php',
          onMessage: (api, data) => {
            if (data.mceAction === 'insertValue') {
              callback(data.value);
              api.close();
            }
          }
        });
      }
    },
    content_css: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    content_style: `
      body { font-family: "Inter", Helvetica, Arial, sans-serif; font-size: 16px; color: #334155; line-height: 1.6; padding: 20px; }
      h1, h2, h3, h4, h5, h6 { color: #111827; font-weight: 800; margin-bottom: 0.5em; }
      h2 { border-left: 4px solid #3F8F29; padding-left: 15px; margin-top: 30px; }
      h3 { color: #3F8F29; margin-top: 25px; }
      a { color: #3F8F29; text-decoration: none; font-weight: 600; }
      blockquote { 
        border-left: 5px solid #F4A321; 
        padding: 15px 25px; 
        background: #FFF8ED; 
        font-style: italic; 
        color: #92400E; 
        margin: 25px 0; 
        border-radius: 0 1.5rem 1.5rem 0; 
      }
      .text-primary { color: #3F8F29; }
      .text-secondary { color: #F4A321; }
    `,
    color_map: [
      '3F8F29', 'Vert ZoeTIC',
      'F4A321', 'Orange ZoeTIC',
      '111827', 'Noir Profond',
      '334155', 'Gris Ardoise',
      '64748B', 'Gris Moyen'
    ],
    style_formats: [
      { title: 'Titres ZoeTIC', items: [
        { title: 'Titre de Section (H2)', block: 'h2' },
        { title: 'Sous-titre Vert (H3)', block: 'h3' }
      ]},
      { title: 'Blocs Spéciaux', items: [
        { title: 'Citation Orange', block: 'blockquote' },
        { title: 'Encadré Info Vert', block: 'p', styles: { padding: '15px', background: '#F0FDF4', 'border-radius': '1rem', border: '1px solid #DCF3D6', color: '#166534' } }
      ]},
      { title: 'Inline', items: [
        { title: 'Texte Vert Primaire', inline: 'span', classes: 'text-primary' },
        { title: 'Texte Orange Secondaire', inline: 'span', classes: 'text-secondary' }
      ]}
    ],
    border_radius: '1.5rem',
    setup: function (editor) { 
      editor.on('change keyup', function () { 
        if(typeof updatePreview === 'function') updatePreview(); 
      });

      // Ajout du bouton personnalisé pour FontAwesome
      editor.ui.registry.addButton('fontawesome', {
        icon: 'emoji',
        tooltip: 'Insérer une icône FontAwesome',
        onAction: function (_) {
          editor.windowManager.open({
            title: 'Icône FontAwesome',
            body: {
              type: 'panel',
              items: [
                {
                  type: 'input',
                  name: 'icon_class',
                  label: 'Classe de l\'icône (ex: fas fa-rocket)',
                  placeholder: 'fas fa-star'
                }
              ]
            },
            buttons: [
              { type: 'cancel', text: 'Annuler' },
              { type: 'submit', text: 'Insérer', primary: true }
            ],
            onSubmit: function (api) {
              const data = api.getData();
              if(data.icon_class) editor.insertContent('<i class="' + data.icon_class + '"></i>&nbsp;');
              api.close();
            }
          });
        }
      });
    }
  });
</script>

<?php render_admin_sidebar('blog'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, $id ? 'Modifier l’article' : 'Ajouter un article', $id ? 'Mettez à jour le contenu de votre blog.' : 'Créez un nouvel article pour le blog.'); ?>

    <main class="tab-content active p-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Article</p>
                <h2 class="mt-3 text-4xl font-title font-bold text-slate-900"><?php echo $id ? 'Modifier l’article' : 'Créer un article'; ?></h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Renseignez les informations nécessaires pour publier votre contenu.</p>
            </div>
            <a href="blog.php" class="inline-flex items-center gap-2 rounded-full bg-white border border-gray-200 px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-gray-50">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Retour à la liste
            </a>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="grid gap-8 xl:grid-cols-[1.7fr_1fr]">
            <div class="rounded-3xl border border-gray-200 bg-white/95 p-8 shadow-sm">
                <form id="blogForm" action="connect/save_blog.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php if ($id): ?>
                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                    <?php endif; ?>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <!-- Hidden field for cropped image data -->
                    <input type="hidden" name="cropped_image_data" id="cropped_image_data">

                    <div class="grid gap-6 lg:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Titre</span>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Titre de l'article" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20" />
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Catégorie</span>
                            <input list="blog-categories" type="text" name="category" value="<?php echo htmlspecialchars($post['category'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ex : Tech" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20" />
                            <datalist id="blog-categories">
                                <option value="Actualités"></option>
                                <option value="Tech"></option>
                                <option value="Formation"></option>
                                <option value="Cyber sécurité"></option>
                                <option value="Événements"></option>
                            </datalist>
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Résumé</span>
                        <textarea id="blog-excerpt" name="excerpt" rows="4" placeholder="Une présentation courte pour le listing..." class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"><?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Contenu</span>
                        <textarea id="blog-content" name="content" rows="10" placeholder="Rédigez votre article ici..." class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"><?php echo htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Image de couverture (URL ou upload)</span>
                            <input type="url" name="featured_image" value="<?php echo htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://..." class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20" />
                            <input type="file" name="featured_image_file" accept="image/*" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-2 text-sm shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20" />
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Statut</span>
                            <select name="status" class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                                <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Publié</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:justify-end">
                        <a href="blog.php" class="inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-gray-50">Annuler</a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-[#3F8F29] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600"><?php echo $id ? 'Mettre à jour' : "Publier l'article"; ?></button>
                    </div>
                </form>
            </div>

            <aside class="rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Aperçu rapide</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Votre article</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openFullScreenPreview()" class="p-2 rounded-xl bg-slate-100 text-slate-400 hover:text-primary transition-all" title="Plein écran">
                            <i data-lucide="maximize"></i>
                        </button>
                        <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"><?php echo $post['status'] === 'published' ? 'Publié' : 'Brouillon'; ?></span>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="overflow-hidden rounded-3xl bg-slate-100 h-56 flex items-center justify-center" id="featured-preview-container">
                        <img id="featured-preview" src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200', ENT_QUOTES, 'UTF-8'); ?>" alt="Aperçu image article" class="h-full w-full object-cover" />
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Médias (images ou vidéos) — multiple</label>
                    <input type="file" name="media_files[]" id="media-files-input" accept="image/*,video/*" multiple class="w-full rounded-3xl border border-gray-200 px-4 py-2 text-sm shadow-sm" />

                    <!-- Barre de progression de l'upload -->
                    <div id="upload-progress-container" class="mt-4 hidden p-4 bg-slate-50 rounded-2xl border border-slate-100 shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                                <i class="fas fa-cloud-upload-alt animate-bounce"></i> Envoi des fichiers...
                            </span>
                            <span id="upload-percent" class="text-[10px] font-black text-slate-900 tracking-tighter">0%</span>
                        </div>
                        <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                            <div id="upload-progress-bar" class="h-full bg-primary transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <div id="media-preview-grid" class="mt-4 grid grid-cols-3 gap-3"></div>
                    <?php if (!empty($postMedia)) : ?>
                        <p class="mt-3 text-xs text-gray-500">Médias existants :</p>
                        <div class="mt-2 grid grid-cols-3 gap-3">
                            <?php foreach ($postMedia as $m) : ?>
                                <?php if ($m['media_type'] === 'image') : ?>
                                    <img src="<?php echo htmlspecialchars($m['filename'], ENT_QUOTES, 'UTF-8'); ?>" class="h-24 w-full object-cover rounded-2xl" alt="media">
                                <?php else : ?>
                                    <video controls class="h-24 w-full object-cover rounded-2xl">
                                        <source src="<?php echo htmlspecialchars($m['filename'], ENT_QUOTES, 'UTF-8'); ?>" type="<?php echo htmlspecialchars($m['mime'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Votre navigateur ne supporte pas la lecture de cette vidéo.
                                    </video>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="inline-flex rounded-full bg-[#3F8F29]/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.35em] text-[#166534]">
                            <?php echo htmlspecialchars($post['category'] ?: 'Actualités', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-slate-900"><?php echo htmlspecialchars($post['title'] ?: 'Titre de l’article', ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p class="mt-2 text-sm text-gray-500"><?php echo htmlspecialchars($post['excerpt'] ?: 'Résumé court de l’article pour capter l’attention des lecteurs.', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="grid gap-3 text-sm text-gray-500">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span>Auteur</span>
                            <span><?php echo $adminName; ?></span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span>Date prévue</span>
                            <span><?php echo date('d M Y'); ?></span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

<!-- Image Cropper Modal -->
<div id="cropper-modal" class="fixed inset-0 z-[120] hidden overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-4xl overflow-hidden animate-in">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-black text-slate-900">Recadrer l'image de couverture</h3>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Format recommandé : 16:9</p>
            </div>
            <button type="button" onclick="closeCropper()" class="p-2 hover:bg-slate-100 rounded-full text-slate-400 transition-colors">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="p-8">
            <div class="cropper-container bg-slate-50 rounded-2xl overflow-hidden">
                <img id="cropper-image" src="" alt="Source">
            </div>
        </div>
        <div class="p-6 bg-slate-50 flex justify-end gap-3">
            <button type="button" onclick="closeCropper()" class="px-8 py-3 rounded-full text-sm font-bold text-slate-500 hover:bg-slate-200 transition-all">Annuler</button>
            <button type="button" onclick="confirmCrop()" class="bg-primary text-white px-10 py-3 rounded-full text-sm font-black uppercase tracking-widest hover:bg-green-700 shadow-xl shadow-green-900/20 transition-all">Valider le cadrage</button>
        </div>
    </div>
</div>

<!-- Full Screen Preview Modal -->
<div id="full-preview-modal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-white">
    <div class="sticky top-0 z-[110] flex items-center justify-between bg-white/90 p-4 backdrop-blur-md border-b border-gray-100 px-8">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center font-bold text-white shadow-lg">Z</div>
            <span class="font-title font-bold text-slate-900">Aperçu du rendu final</span>
        </div>
        <button onclick="closeFullScreenPreview()" class="flex items-center gap-2 px-6 py-2.5 rounded-full bg-slate-900 text-white text-xs font-black uppercase tracking-widest hover:bg-red-600 transition-all shadow-xl shadow-slate-900/20">
            <i data-lucide="x" class="w-4 h-4"></i> Fermer l'aperçu
        </button>
    </div>
    <div class="bg-light min-h-screen py-12">
        <div class="container mx-auto px-6 max-w-5xl">
            <article class="space-y-10 bg-white p-10 md:p-16 rounded-[3rem] shadow-sm border border-gray-100">
                 <div class="rounded-[2rem] overflow-hidden shadow-2xl h-[450px]">
                    <img id="full-preview-image" src="" alt="" class="w-full h-full object-cover">
                </div>
                <div class="space-y-6">
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400 uppercase tracking-[0.3em] font-bold">
                        <span id="full-preview-category" class="text-primary"></span>
                        <span>&bull;</span>
                        <span><?php echo date('d F Y'); ?></span>
                    </div>
                    <h1 id="full-preview-title" class="font-title text-5xl font-black text-dark leading-tight"></h1>
                    <div id="full-preview-excerpt" class="max-w-4xl text-xl text-gray-500 leading-relaxed italic border-l-4 border-secondary pl-6 py-2"></div>
                    <div id="full-preview-content" class="prose prose-lg prose-slate max-w-none text-gray-700 pt-10 border-t border-gray-100"></div>
                </div>
            </article>
        </div>
    </div>
</div>

<script>
    const previewFields = {
        title: document.querySelector('input[name="title"]'),
        category: document.querySelector('input[name="category"]'),
        excerpt: document.querySelector('textarea[name="excerpt"]'),
        content: document.querySelector('textarea[name="content"]'),
        image: document.querySelector('input[name="featured_image"]'),
        imageFile: document.querySelector('input[name="featured_image_file"]'),
        mediaFiles: document.querySelector('input[name="media_files[]"]'),
        status: document.querySelector('select[name="status"]'),
    };

    const previewElements = {
        title: document.querySelector('aside h4'),
        category: document.querySelector('aside span.inline-flex'),
        excerpt: document.querySelector('aside p.text-gray-500'),
        image: document.querySelector('aside img'),
        status: document.querySelector('aside span.rounded-full'),
        mediaGrid: document.getElementById('media-preview-grid'),
    };

    let cropperInstance = null;

    window.openCropperModal = function(imageSrc) {
        const modal = document.getElementById('cropper-modal');
        const image = document.getElementById('cropper-image');
        image.src = imageSrc;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (cropperInstance) cropperInstance.destroy();
        
        cropperInstance = new Cropper(image, {
            aspectRatio: 16 / 9,
            viewMode: 2,
            dragMode: 'move',
            autoCropArea: 1,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    };

    window.closeCropper = function() {
        const modal = document.getElementById('cropper-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (cropperInstance) cropperInstance.destroy();
        previewFields.imageFile.value = ''; // Reset if cancelled
    };

    window.confirmCrop = function() {
        if (!cropperInstance) return;
        
        // Get cropped canvas
        const canvas = cropperInstance.getCroppedCanvas({
            width: 1280,
            height: 720,
        });

        const croppedData = canvas.toDataURL('image/jpeg', 0.9);
        document.getElementById('cropped_image_data').value = croppedData;
        previewElements.image.src = croppedData;
        
        document.getElementById('cropper-modal').classList.add('hidden');
        document.getElementById('cropper-modal').classList.remove('flex');
    };

    window.openFullScreenPreview = function() {
        const modal = document.getElementById('full-preview-modal');
        const title = previewFields.title.value || 'Titre de l’article';
        const category = previewFields.category.value || 'Actualités';
        
        let excerptVal = '';
        if (window.tinymce && tinymce.get('blog-excerpt')) {
            excerptVal = tinymce.get('blog-excerpt').getContent();
        } else {
            excerptVal = (previewFields.excerpt.value || '').replace(/\n/g, '<br>');
        }

        let contentVal = '';
        if (window.tinymce && tinymce.get('blog-content')) {
            contentVal = tinymce.get('blog-content').getContent();
        } else {
            contentVal = (previewFields.content.value || '').replace(/\n/g, '<br>');
        }

        document.getElementById('full-preview-title').textContent = title;
        document.getElementById('full-preview-category').textContent = category;
        document.getElementById('full-preview-excerpt').innerHTML = excerptVal;
        document.getElementById('full-preview-content').innerHTML = contentVal;
        document.getElementById('full-preview-image').src = previewElements.image.src;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeFullScreenPreview = function() {
        document.getElementById('full-preview-modal').classList.add('hidden');
        document.body.style.overflow = '';
    };

    function updatePreview() {
        if (!previewElements.title) return;

        previewElements.title.textContent = previewFields.title.value || 'Titre de l’article';
        previewElements.category.textContent = previewFields.category.value || 'Actualités';

        // Gestion de l'extrait (TinyMCE ou textarea classique)
        let excerptVal = '';
        if (window.tinymce && tinymce.get('blog-excerpt')) {
            excerptVal = tinymce.get('blog-excerpt').getContent({format: 'text'});
        } else {
            excerptVal = previewFields.excerpt.value;
        }
        previewElements.excerpt.textContent = excerptVal || 'Résumé court de l’article pour capter l’attention des lecteurs.';

        // Show cropped image if available, else standard logic
        const croppedData = document.getElementById('cropped_image_data').value;
        if (croppedData) {
            previewElements.image.src = croppedData;
        } else if (!previewFields.imageFile.files[0]) {
            previewElements.image.src = previewFields.image.value || 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200';
        }
        previewElements.status.textContent = previewFields.status.value === 'published' ? 'Publié' : 'Brouillon';
    }

    function updateMediaPreviewFiles(files) {
        const grid = previewElements.mediaGrid;
        if (!grid) return;
        grid.innerHTML = '';
        if (!files || files.length === 0) return;

        Array.from(files).forEach(file => {
            const type = file.type || '';
            const container = document.createElement('div');
            container.className = 'overflow-hidden rounded-2xl bg-slate-100 h-24 flex items-center justify-center';

            if (type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'h-24 w-full object-cover';
                const reader = new FileReader();
                reader.onload = e => { img.src = e.target.result; };
                reader.readAsDataURL(file);
                container.appendChild(img);
            } else if (type.startsWith('video/')) {
                const vid = document.createElement('video');
                vid.className = 'h-24 w-full object-cover';
                vid.controls = true;
                const reader = new FileReader();
                reader.onload = e => { vid.src = e.target.result; };
                reader.readAsDataURL(file);
                container.appendChild(vid);
            }

            grid.appendChild(container);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        Object.values(previewFields).forEach(field => {
            if (!field) return;
            field.addEventListener('input', updatePreview);
        });

        // Custom listener for the file input to trigger cropper
        if (previewFields.imageFile) {
            previewFields.imageFile.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => openCropperModal(e.target.result);
                    reader.readAsDataURL(this.files[0]);
                    previewFields.image.value = ''; // Clear URL if file selected
                }
            });
        }

        if (previewFields.mediaFiles) {
            previewFields.mediaFiles.addEventListener('change', (e) => updateMediaPreviewFiles(e.target.files));
        }

        // Validation du formulaire avant soumission (TinyMCE + Progression AJAX)
        const form = document.getElementById('blogForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Intercepter l'envoi standard pour gérer la progression

                if (window.tinymce) {
                    tinymce.triggerSave();
                    const content = tinymce.get('blog-content').getContent({format: 'text'}).trim();
                    if (content.length === 0) {
                        alert(window.t ? window.t('article_empty') : 'Le contenu de l\'article ne peut pas être vide.');
                        return;
                    }
                }
                
                const btn = this.querySelector('button[type="submit"]');
                const originalBtnText = btn.innerHTML;
                const progressContainer = document.getElementById('upload-progress-container');
                const progressBar = document.getElementById('upload-progress-bar');
                const progressPercent = document.getElementById('upload-percent');

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Préparation...';

                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();

                xhr.open('POST', this.action, true);

                // Afficher la barre de progression si des fichiers sont présents
                const hasFiles = (previewFields.imageFile && previewFields.imageFile.files.length > 0) || 
                                (previewFields.mediaFiles && previewFields.mediaFiles.files.length > 0);
                
                if (hasFiles && progressContainer) progressContainer.classList.remove('hidden');

                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        if (progressBar) progressBar.style.width = percent + '%';
                        if (progressPercent) progressPercent.textContent = percent + '%';
                        btn.innerHTML = '<i class="fas fa-cloud-upload-alt mr-2"></i> Envoi (' + percent + '%)';
                    }
                };

                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        // Redirection automatique vers la liste du blog (suit le redirect PHP)
                        window.location.href = xhr.responseURL || 'blog.php';
                    } else {
                        alert(window.t ? window.t('error_occurred') : 'Une erreur est survenue lors de l\'enregistrement.');
                        btn.disabled = false;
                        btn.innerHTML = originalBtnText;
                        if (progressContainer) progressContainer.classList.add('hidden');
                    }
                };

                xhr.onerror = function() {
                    alert(window.t ? window.t('network_error_transfer') : 'Erreur réseau lors du transfert.');
                    btn.disabled = false;
                    btn.innerHTML = originalBtnText;
                    if (progressContainer) progressContainer.classList.add('hidden');
                };

                xhr.send(formData);
            });
        }

        updatePreview();
        //// S'il existe déjà des médias, nous ne remplissons pas la grille d'aperçu ici (rendu côté serveur)
    });
</script>

<?php render_admin_footer_scripts(); ?>
</body>
</html>
