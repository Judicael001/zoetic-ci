<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '_layout.php';

ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$type = $_GET['type'] ?? 'terms';
$lang_filter = $_GET['lang'] ?? 'fr';
$message = $_GET['message'] ?? '';

// Récupérer la version active pour édition
$stmt = $pdo->prepare("SELECT * FROM legal_documents WHERE doc_type = ? AND lang = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$type, $lang_filter]);
$currentDoc = $stmt->fetch(PDO::FETCH_ASSOC);

include('../src/include/head.php');
?>

<!-- TinyMCE Integration -->
<script src="https://cdn.tiny.cloud/1/ntjmkq5d5c2etrcxoc5qeiakfywakjlc9rvlnrvt7gqqarc9/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
 
<script>
  tinymce.init({
    selector: 'textarea[name="content"]',
    height: 500,
    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons help',
    toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | forecolor backcolor | help',
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
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
    border_radius: '1.5rem'
  });
</script>

<?php render_admin_sidebar('legal'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, 'AD', 'Gestion Légale', 'Mise à jour des documents juridiques.'); ?>

    <main class="p-8">
        <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-3xl font-title font-black text-slate-900">Documents Juridiques</h2>
                <p class="text-slate-400 mt-1">Créez de nouvelles versions de vos pages légales.</p>
            </div>
            <div class="flex bg-white rounded-2xl p-1 border border-slate-100 shadow-sm">
                <a href="?type=terms" class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest <?php echo $type === 'terms' ? 'bg-primary text-white' : 'text-slate-400 hover:bg-slate-50'; ?>">CGU</a>
                <a href="?type=privacy" class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest <?php echo $type === 'privacy' ? 'bg-primary text-white' : 'text-slate-400 hover:bg-slate-50'; ?>">Privacy</a>
                <a href="?type=legal" class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest <?php echo $type === 'legal' ? 'bg-primary text-white' : 'text-slate-400 hover:bg-slate-50'; ?>">Mentions</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-sm"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-[1.5fr_1fr] gap-8">
            <form action="connect/save_legal.php" method="POST" class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40">
                <input type="hidden" name="doc_type" value="<?php echo $type; ?>">
                <input type="hidden" name="lang" value="<?php echo $lang_filter; ?>">
                
                <div class="mb-6 flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-primary">Éditeur de version v<?php echo ($currentDoc['version'] ?? 0) + 1; ?></span>
                    <select name="lang" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-4 py-2 outline-none">
                        <option value="fr" <?php echo $lang_filter === 'fr' ? 'selected' : ''; ?>>Français</option>
                        <option value="en" <?php echo $lang_filter === 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>

                <textarea name="content" rows="15" class="w-full bg-slate-50 border-2 border-transparent focus:border-primary/20 focus:bg-white rounded-3xl p-6 outline-none transition-all font-mono text-sm leading-relaxed"><?php echo htmlspecialchars($currentDoc['content'] ?? ''); ?></textarea>
                
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="bg-slate-900 text-white px-10 py-4 rounded-full font-black text-xs uppercase tracking-widest hover:bg-primary shadow-xl shadow-slate-900/20 transition-all">Publier la nouvelle version</button>
                </div>
            </form>

            <aside class="space-y-6">
                <div class="bg-slate-900 text-white rounded-[2.5rem] p-8 shadow-xl">
                    <h3 class="text-lg font-black mb-4">Historique des versions</h3>
                    <div class="space-y-4">
                        <?php
                        $history = $pdo->prepare("SELECT id, version, created_at, is_active FROM legal_documents WHERE doc_type = ? AND lang = ? ORDER BY version DESC LIMIT 5");
                        $history->execute([$type, $lang_filter]);
                        while($h = $history->fetch()):
                        ?>
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-white/5 border border-white/10">
                            <div>
                                <p class="font-black text-sm text-white">Version <?php echo $h['version']; ?></p>
                                <p class="text-[10px] text-white/40 uppercase font-bold"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if($h['is_active']): ?>
                                    <span class="bg-primary text-white text-[9px] font-black px-3 py-1 rounded-full uppercase">Actif</span>
                                <?php else: ?>
                                    <a href="connect/restore_legal.php?id=<?php echo $h['id']; ?>" class="text-[9px] font-black uppercase text-white/60 hover:text-primary transition-all">Restaurer</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="info" class="w-6 h-6"></i>
                    </div>
                    <h4 class="font-black text-slate-900 mb-2">Conseil de rédaction</h4>
                    <p class="text-sm text-slate-400 leading-relaxed">Utilisez des balises HTML simples (h3, p, strong) pour structurer vos documents. Chaque publication désactive automatiquement la version précédente.</p>
                </div>
            </aside>
        </div>
    </main>
</div>

<?php render_admin_footer_scripts(); ?>
</body>
</html>