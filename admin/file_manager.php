<?php
require_once '_auth.php';
require_admin_login();

$uploadsDir = __DIR__ . '/../uploads/tinymce';
$images = [];

if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match("/\.(gif|jpe?g|png|webp)$/i", $file)) {
            $images[] = [
                'name' => $file,
                'url' => 'uploads/tinymce/' . $file
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestionnaire de fichiers</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .img-card:hover .select-overlay { opacity: 1; }
    </style>
</head>
<body class="bg-white p-4">
    <!-- Barre de recherche -->
    <div class="sticky top-0 z-10 bg-white pb-6">
        <div class="relative">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" id="imgSearch" placeholder="Rechercher une image..." 
                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-medium outline-none focus:ring-4 focus:ring-primary/5 focus:bg-white focus:border-primary/20 transition-all shadow-sm">
        </div>
    </div>

    <div id="imageGrid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4">
        <?php foreach ($images as $img): ?>
            <div class="img-card group relative aspect-square rounded-2xl overflow-hidden border border-slate-100 bg-slate-50 cursor-pointer transition-all hover:shadow-xl hover:-translate-y-1"
                 onclick="selectImage('<?php echo $img['url']; ?>')" data-name="<?php echo htmlspecialchars(strtolower($img['name'])); ?>">
                <img src="../<?php echo $img['url']; ?>" class="w-full h-full object-cover">
                <div class="select-overlay absolute inset-0 bg-primary/40 opacity-0 transition-opacity flex items-center justify-center backdrop-blur-[2px]">
                    <div class="bg-white p-3 rounded-full shadow-2xl text-primary transform scale-0 group-hover:scale-100 transition-transform">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/80 to-transparent p-2 truncate text-[10px] text-white font-bold"><?php echo $img['name']; ?></div>
            </div>
        <?php endforeach; ?>
        
        <!-- Message Aucun résultat -->
        <div id="noResults" class="<?php echo empty($images) ? '' : 'hidden'; ?> col-span-full py-24 text-center">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
            <h3 id="noResultsTitle" class="text-slate-900 font-bold"><?php echo empty($images) ? 'Bibliothèque vide' : 'Aucun résultat'; ?></h3>
            <p id="noResultsText" class="text-slate-400 text-sm mt-2"><?php echo empty($images) ? 'Uploadez des images pour les voir ici.' : 'Aucune image ne correspond à votre recherche.'; ?></p>
            </div>
    </div>

    <script>
        function selectImage(url) {
            window.parent.postMessage({
                mceAction: 'insertValue',
                value: url
            }, '*');
        }

        const cleanStr = (str) => {
            if (!str) return "";
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
        };

        document.getElementById('imgSearch').addEventListener('input', function(e) {
            const term = cleanStr(e.target.value);
            const cards = document.querySelectorAll('.img-card');
            const noResults = document.getElementById('noResults');
            const noResultsTitle = document.getElementById('noResultsTitle');
            const noResultsText = document.getElementById('noResultsText');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = cleanStr(card.getAttribute('data-name') || '');
                if (name.includes(term)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            noResults.classList.toggle('hidden', visibleCount > 0);
            if (term !== "") {
                noResultsTitle.textContent = "Aucun résultat";
                noResultsText.textContent = "Aucune image ne correspond à votre recherche.";
            } else {
                noResultsTitle.textContent = "Bibliothèque vide";
                noResultsText.textContent = "Uploadez des images pour les voir ici.";
            }
        });
    </script>
</body>
</html>