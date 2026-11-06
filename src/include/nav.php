
    <header class="fixed w-full z-50 bg-white shadow-md">
        <div class="container mx-auto px-4 md:px-6 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2 cursor-pointer" onclick="showPage('home')">
                <?php 
                global $assetPrefix, $siteSettings, $pdo;
                if (!isset($siteSettings) && isset($pdo)) $siteSettings = get_site_settings($pdo);
                // Afficher le logo personnalisé s'il existe, sinon afficher le logo par défaut
                $logoPath = $siteSettings['logo_image'] ?? null;
                $logoHeight = $siteSettings['logo_height'] ?? '40';
                $logoShape = $siteSettings['logo_shape'] ?? 'original';
                $showName = ($siteSettings['show_site_name'] ?? '0') === '1';

                $logoStyle = "height: " . (int)$logoHeight . "px;";
                if ($logoShape === 'rounded') $logoStyle .= " border-radius: 0.75rem;";
                elseif ($logoShape === 'circle') $logoStyle .= " border-radius: 9999px; aspect-ratio: 1/1; object-fit: cover;";
                
                if ($logoPath && file_exists(__DIR__ . '/../../' . $logoPath)): 
                ?>
                    <img src="<?php echo htmlspecialchars($assetPrefix . $logoPath . '?v=' . time()); ?>" alt="Logo" class="w-auto object-contain" style="<?php echo $logoStyle; ?>">
                <?php else: ?>
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white font-bold text-xl">Z</div>
                <?php endif; ?>

                <?php if ($showName || (!$logoPath || !file_exists(__DIR__ . '/../../' . $logoPath))): ?>
                    <span class="font-title text-2xl font-extrabold tracking-tight"><?php 
                        $name = $siteSettings['site_name'] ?? 'ZoeTIC';
                        if (stripos($name, 'ZoeTIC') !== false) {
                            echo 'Zoe<span class="text-primary">TIC</span>';
                        } else {
                            echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                        }
                    ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button type="button" class="md:hidden text-gray-600 focus:outline-none" id="mobile-menu-btn" aria-controls="mobile-menu" aria-expanded="false" aria-label="Afficher le menu mobile">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center space-x-8 font-semibold" aria-label="Navigation principale">
                <button type="button" data-page="home" onclick="showPage('home')" class="nav-btn nav-active hover:text-primary transition-colors" aria-label="<?php echo t('nav_home'); ?>"><?php echo t('nav_home'); ?></button>
                <button type="button" data-page="formations" onclick="showPage('formations')" class="nav-btn hover:text-primary transition-colors" aria-label="<?php echo t('nav_formations'); ?>"><?php echo t('nav_formations'); ?></button>
                <button type="button" data-page="realisations" onclick="showPage('realisations')" class="nav-btn hover:text-primary transition-colors" aria-label="<?php echo t('nav_realisations'); ?>"><?php echo t('nav_realisations'); ?></button>
                <button type="button" data-page="blog" onclick="showPage('blog')" class="nav-btn hover:text-primary transition-colors" aria-label="<?php echo t('nav_blog'); ?>"><?php echo t('nav_blog'); ?></button>
                <button type="button" data-page="contact" onclick="showPage('contact')" class="nav-btn hover:text-primary transition-colors" aria-label="<?php echo t('nav_contact'); ?>"><?php echo t('nav_contact'); ?></button>
                <button type="button" onclick="showPage('register')" class="bg-secondary text-white px-6 py-2 rounded-full hover:bg-orange-600 transition-all shadow-md" aria-label="<?php echo t('nav_register'); ?>"><?php echo t('nav_register'); ?></button>
                
                <!-- Dark Mode Toggle Desktop -->
                <button onclick="toggleTheme()" class="p-2 rounded-full hover:bg-gray-100 transition-colors text-slate-400 hover:text-primary" title="<?php echo t('mode_night'); ?>">
                    <i id="theme-toggle-icon" class="fas fa-moon"></i>
                </button>

                <div class="flex items-center space-x-2 border-l pl-4">
                    <?php
                    // Helper to build current URL with lang param
                    function build_lang_url($lang_code) {
                        $uri = $_SERVER['REQUEST_URI'] ?? '/';
                        $parts = parse_url($uri);
                        $path = $parts['path'] ?? '/';
                        parse_str($parts['query'] ?? '', $qs);
                        $qs['lang'] = $lang_code;
                        return $path . '?' . http_build_query($qs);
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars(build_lang_url('fr')); ?>" class="text-xs font-bold <?php echo ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'text-primary' : 'text-gray-300 hover:text-gray-500'; ?>">FR</a>
                    <a href="<?php echo htmlspecialchars(build_lang_url('en')); ?>" class="text-xs font-bold <?php echo ($_SESSION['lang'] ?? 'fr') === 'en' ? 'text-primary' : 'text-gray-300 hover:text-gray-500'; ?>">EN</a>
                </div>
            </nav>
        </div>
        
        <!-- Mobile Nav Menu (Hidden by default) -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t p-4 space-y-4 shadow-lg">
            <button onclick="showPage('home')" class="block w-full text-left font-bold py-2"><?php echo t('nav_home'); ?></button>
            <button onclick="showPage('formations')" class="block w-full text-left font-bold py-2"><?php echo t('nav_formations'); ?></button>
            <button onclick="showPage('realisations')" class="block w-full text-left font-bold py-2"><?php echo t('nav_realisations'); ?></button>
            <button onclick="showPage('blog')" class="block w-full text-left font-bold py-2"><?php echo t('nav_blog'); ?></button>
            <button onclick="showPage('contact')" class="block w-full text-left font-bold py-2"><?php echo t('nav_contact'); ?></button>
            <button onclick="showPage('register')" class="block w-full text-center bg-secondary text-white py-3 rounded-lg mb-2"><?php echo t('nav_register'); ?></button>
            
            <button onclick="toggleTheme()" class="flex items-center justify-between w-full font-bold py-3 border-t border-gray-50 text-slate-600">
                <span><?php echo t('mode_night'); ?></span>
                <i class="theme-toggle-icon-mobile fas fa-moon"></i>
            </button>
            <div class="flex items-center gap-3 pt-4">
                <a href="<?php echo htmlspecialchars(build_lang_url('fr')); ?>" class="font-bold <?php echo ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'text-primary' : 'text-gray-400'; ?>">FR</a>
                <a href="<?php echo htmlspecialchars(build_lang_url('en')); ?>" class="font-bold <?php echo ($_SESSION['lang'] ?? 'fr') === 'en' ? 'text-primary' : 'text-gray-400'; ?>">EN</a>
            </div>
        </div>
    </header>