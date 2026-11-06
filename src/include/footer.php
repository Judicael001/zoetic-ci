
    <footer class="bg-dark text-white pt-20 pb-10 mt-20">
        <div class="container mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12 border-b border-gray-800 pb-12 mb-8">
            <div class="col-span-1 md:col-span-1">
                <?php global $assetPrefix, $siteSettings, $pdo; 
                if (!isset($siteSettings) && isset($pdo)) $siteSettings = get_site_settings($pdo); ?>
                <div class="flex items-center space-x-2 mb-6 cursor-pointer" onclick="showPage('home')">
                    <?php 
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
                        <div class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold text-lg">Z</div>
                    <?php endif; ?>

                    <?php if ($showName || (!$logoPath || !file_exists(__DIR__ . '/../../' . $logoPath))): ?>
                        <span class="font-title text-xl font-extrabold"><?php 
                            $name = $siteSettings['site_name'] ?? 'ZoeTIC';
                            if (stripos($name, 'ZoeTIC') !== false) {
                                echo 'Zoe<span class="text-primary">TIC</span>';
                            } else {
                                echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                            }
                        ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed">ZoeTIC est le partenaire de votre transformation numérique en Côte d'Ivoire. Nous formons pour demain.</p>
            </div>
            
            <div>
                <h4 class="font-title font-bold text-lg mb-6"><?php echo t('plan_du_site'); ?></h4>
                <ul class="text-gray-400 text-sm space-y-3">
                    <li><a href="#" onclick="showPage('home')" class="hover:text-primary transition-all"><?php echo t('nav_home'); ?></a></li>
                    <li><a href="#" onclick="showPage('formations')" class="hover:text-primary transition-all"><?php echo t('nav_formations'); ?></a></li>
                    <li><a href="#" onclick="showPage('blog')" class="hover:text-primary transition-all"><?php echo t('nav_blog'); ?></a></li>
                    <li><a href="#" onclick="showPage('contact')" class="hover:text-primary transition-all"><?php echo t('nav_contact'); ?></a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-title font-bold text-lg mb-6">Légal</h4>
                <ul class="text-gray-400 text-sm space-y-3">
                    <li><a href="#" onclick="showPage('terms')" class="hover:text-primary transition-all"><?php echo t('nav_terms'); ?></a></li>
                    <li><a href="#" onclick="showPage('privacy')" class="hover:text-primary transition-all"><?php echo t('nav_privacy'); ?></a></li>
                    <li><a href="#" onclick="showPage('legal')" class="hover:text-primary transition-all"><?php echo t('nav_legal'); ?></a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-title font-bold text-lg mb-6"><?php echo t('newsletter_title'); ?></h4>
                <p class="text-gray-400 text-xs mb-4"><?php echo t('newsletter_sub'); ?></p>
                <form onsubmit="handleNewsletter(event)" class="flex">
                    <input type="email" id="newsletterEmail" required placeholder="<?php echo t('newsletter_title'); ?>" class="bg-gray-800 border-0 rounded-l-lg px-4 py-2 text-sm w-full outline-none">
                    <button type="submit" class="bg-primary px-4 py-2 rounded-r-lg hover:bg-green-700 transition-all"><?php echo t('newsletter_ok'); ?></button>
                </form>
            </div>
        </div>
        <div class="container mx-auto px-6 text-center text-gray-500 text-xs">
            <?php echo str_replace('{year}', date('Y'), htmlspecialchars(t('site_copyright'))); ?>
        </div>
    </footer>

    <!-- WhatsApp Floating Button -->
    <?php
    $whatsappRaw = $siteSettings['contact_whatsapp'] ?? '2250574677246';
    $whatsappClean = preg_replace('/[^0-9]/', '', $whatsappRaw);
    if (strlen($whatsappClean) === 10 && $whatsappClean[0] === '0') {
        $whatsappClean = '225' . substr($whatsappClean, 1);
    } elseif (strlen($whatsappClean) === 8) {
        $whatsappClean = '225' . $whatsappClean;
    }
    ?>
    <a href="https://wa.me/<?php echo htmlspecialchars($whatsappClean, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="fixed bottom-8 right-8 z-50 bg-[#25D366] text-white w-16 h-16 rounded-full flex items-center justify-center text-3xl shadow-2xl whatsapp-pulse hover:scale-110 transition-all cursor-pointer">
        <i class="fab fa-whatsapp"></i>
    </a>

    <script>
        // PAGE NAVIGATION LOGIC
        function showPage(pageId) {
            const targetPage = document.getElementById(`page-${pageId}`);
            if (!targetPage) return;

            document.querySelectorAll('.page-content').forEach(p => p.classList.add('hidden'));
            document.querySelectorAll('.nav-btn').forEach(b => {
                b.classList.remove('nav-active');
                b.removeAttribute('aria-current');
            });

            targetPage.classList.remove('hidden');
            targetPage.scrollIntoView({ behavior: 'smooth', block: 'start' });

            const activeBtn = document.querySelector(`.nav-btn[data-page="${pageId}"]`);
            if (activeBtn) {
                activeBtn.classList.add('nav-active');
                activeBtn.setAttribute('aria-current', 'page');
            }

            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }

            targetPage.classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => {
                targetPage.classList.remove('opacity-0', 'translate-y-4');
                targetPage.classList.add('page-transition');
            }, 10);

            history.replaceState(null, '', `#${pageId}`);
        }

        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                const isOpen = mobileMenu.classList.contains('hidden');
                mobileMenu.classList.toggle('hidden');
                mobileMenuBtn.setAttribute('aria-expanded', String(isOpen));
            });
        }


        function handleNewsletter(e) {
            e.preventDefault();
            const emailInput = document.getElementById('newsletterEmail');
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;

            const formData = new FormData();
            formData.append('email', emailInput.value);

            btn.disabled = true;
            btn.innerText = '...';

            fetch('api_newsletter.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    alert(data.message);
                    emailInput.value = '';
                    btn.disabled = false;
                    btn.innerText = originalText;
                });
        }

        function loadSocialLinks() {
            const container = document.getElementById('social-links-container');
            if (!container) return;
            fetch('api_social.php')
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success' && res.data.length > 0) {
                        container.innerHTML = '';
                        res.data.forEach(item => {
                            let iconClass = 'fas fa-share-alt';
                            if (item.key === 'facebook') iconClass = 'fab fa-facebook-f';
                            else if (item.key === 'linkedin') iconClass = 'fab fa-linkedin-in';
                            else if (item.key === 'instagram') iconClass = 'fab fa-instagram';
                            else if (item.key === 'twitter') iconClass = 'fab fa-twitter';
                            else if (item.key === 'tiktok') iconClass = 'fab fa-tiktok';
                            else if (item.key === 'youtube') iconClass = 'fab fa-youtube';
                            else if (item.key === 'whatsapp') iconClass = 'fab fa-whatsapp';
                            
                            const a = document.createElement('a');
                            a.href = item.url;
                            a.target = '_blank';
                            a.rel = 'noopener noreferrer';
                            a.className = 'w-12 h-12 bg-white text-slate-400 border border-slate-100 rounded-2xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-sm';
                            a.innerHTML = `<i class="${iconClass}"></i>`;
                            container.appendChild(a);
                        });
                    } else {
                        container.classList.add('hidden');
                    }
                })
                .catch(err => {
                    console.error("Error loading social links:", err);
                    container.classList.add('hidden');
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash) {
                const hash = window.location.hash.substring(1);
                if (document.getElementById(`page-${hash}`)) {
                    showPage(hash);
                }
            }
            const formationSelect = document.getElementById('formationSelect');
            const formationTitleField = document.querySelector('input[name="formation_title"]');
            if (formationSelect && formationTitleField) {
                formationSelect.addEventListener('change', () => {
                    formationTitleField.value = formationSelect.options[formationSelect.selectedIndex].text;
                });
            }
            loadSocialLinks();
        });

        function openRegisterForm(selectedTitle) {
            const formationSelect = document.getElementById('formationSelect');
            const formationTitleField = document.querySelector('input[name="formation_title"]');
            let selectedOptionText = selectedTitle;

            if (formationSelect) {
                const option = Array.from(formationSelect.options).find(opt => opt.textContent.trim().toLowerCase() === selectedTitle.trim().toLowerCase());
                if (option) {
                    formationSelect.value = option.value;
                    selectedOptionText = option.textContent.trim();
                }
            }

            if (formationTitleField) {
                formationTitleField.value = selectedOptionText;
            }

            showPage('register');
        }

        window.onload = () => {
            const searchInput = document.getElementById('formationCatalogueSearch');
            if (searchInput) {
                searchInput.addEventListener('input', applyFormationFilters);
            }
        };
    </script>

    <!-- Cookie Consent Banner -->
    <div id="cookie-banner" class="fixed bottom-0 left-0 right-0 bg-dark text-white p-4 shadow-lg z-[999] hidden">
        <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-cookie-bite text-2xl text-secondary"></i>
                <div>
                    <p class="font-bold text-sm mb-1"><?php echo t('cookie_title'); ?></p>
                    <p class="text-xs text-gray-300"><?php echo t('cookie_message'); ?></p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button id="accept-cookies" class="bg-primary text-white px-5 py-2 rounded-full text-sm font-semibold hover:bg-green-700 transition-all"><?php echo t('cookie_accept'); ?></button>
                <a href="#" onclick="showPage('privacy')" class="text-white border border-gray-600 px-5 py-2 rounded-full text-sm font-semibold hover:bg-gray-700 transition-all"><?php echo t('cookie_learn_more'); ?></a>
            </div>
        </div>
    </div>
</body>
</html>