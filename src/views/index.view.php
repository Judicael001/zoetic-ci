<?php
$availableFormations = $availableFormations ?? [];
$testimonials = $testimonials ?? []; // Initialize $testimonials
$legalDocs = $legalDocs ?? [];
$registrationStatus = $registrationStatus ?? '';
$registrationMessage = $registrationMessage ?? '';
$studentCount = $studentCount ?? null;
$formationCount = $formationCount ?? null;
$enrollmentCount = $enrollmentCount ?? null;
$publishedBlogCount = $publishedBlogCount ?? null;
$filteredBlogCount = $filteredBlogCount ?? 0;
$totalBlogPages = $totalBlogPages ?? 1;
$siteSettings = $siteSettings ?? [];
$blogCategories = $blogCategories ?? [];
$allRealisations = $allRealisations ?? [];
$realisationCategories = $realisationCategories ?? [];
$featuredRealisations = $featuredRealisations ?? [];
?>

<style>
    @keyframes gentle-vibrate {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(3deg); }
        75% { transform: rotate(-3deg); }
    }
    .animate-vibrate {
        display: inline-block;
        animation: gentle-vibrate 4s infinite ease-in-out;
    }
    .formation-card-item {
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .formation-card-item.filtering {
        opacity: 0;
        transform: scale(0.95) translateY(20px);
    }
    .animate-in {
        animation: fadeIn 0.5s ease forwards;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .category-filter-btn .filter-count {
        transition: all 0.3s ease;
        background-color: #f1f5f9; /* slate-100 */
        color: #94a3b8; /* slate-400 */
    }
    .category-filter-btn.bg-primary .filter-count {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    #blog-results-container {
        position: relative; /* Nécessaire pour le positionnement absolu du spinner */
        min-height: 300px; /* Assure que le conteneur a une hauteur minimale pour le spinner */
    }
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .animate-marquee { animation: marquee 30s linear infinite; }
    /* Carousel for featured formations */
    .featured-carousel { position: relative; }
    .featured-carousel .slide { display: none; }
    .featured-carousel .slide.active { display: block; }
    .carousel-controls { position: absolute; top: 50%; transform: translateY(-50%); width: 100%; display:flex; justify-content:space-between; pointer-events:none; }
    .carousel-controls button { pointer-events:auto; }
    /* Counter style */
    .stat-counter { font-variant-numeric: tabular-nums; }
</style>

    <main class="pt-20">
        <!-- Announcement Bar 
        <div id="announcement-bar" class="hidden bg-secondary text-white py-3 px-6 text-center text-[10px] md:text-xs font-black uppercase tracking-[0.2em] relative z-[40] flex items-center justify-center gap-4 shadow-lg animate-in">
            <div class="flex items-center gap-3">
                <i class="fas fa-bolt animate-pulse text-yellow-300"></i>
                <span>Offre Flash : Profitez de <span class="text-white underline decoration-2 underline-offset-4 font-extrabold">-20%</span> sur votre prochaine formation ! Code : <span class="bg-white text-secondary px-3 py-1 rounded-lg ml-1 select-all shadow-sm">ZOE20</span></span>
            </div>
            <button onclick="closeAnnouncementBar()" class="absolute right-4 md:right-8 hover:rotate-90 transition-transform duration-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <script>
            function closeAnnouncementBar() { document.getElementById('announcement-bar').remove(); localStorage.setItem('announcement_closed', 'true'); }
            if (localStorage.getItem('announcement_closed') !== 'true') { document.getElementById('announcement-bar').classList.remove('hidden'); }
        </script>
        -->
        <!-- PAGE: HOME -->
        <section id="page-home" class="page-content">
            <!-- Hero -->
            <div class="relative h-[600px] flex items-center justify-center overflow-hidden">
                <div class="absolute inset-0 z-0">
                    <img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80" alt="Bureau Moderne" class="w-full h-full object-cover" fetchpriority="high">
                    <div class="absolute inset-0 hero-gradient"></div>
                </div>
                
                <div class="container mx-auto px-6 relative z-10 text-center">
                    <h1 class="font-title text-4xl md:text-6xl text-white font-extrabold mb-6 leading-tight">
                        <span id="hero-title" data-text="<?php echo htmlspecialchars($siteSettings['hero_title'] ?? 'Propulsez votre carrière au Cœur du Numérique', ENT_QUOTES, 'UTF-8'); ?>"></span>
                    </h1>
                    <p class="text-xl text-gray-200 mb-10 max-w-2xl mx-auto">
                        <?php echo htmlspecialchars($siteSettings['hero_subtitle'] ?? 'ZoeTIC forme les leaders technologiques de demain en Côte d\'Ivoire.'); ?>
                    </p>

                    <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-6">
                        <button onclick="showPage('formations')" class="bg-primary text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-green-700 transition-all shadow-xl">Découvrir nos formations</button>
                        <button onclick="showPage('contact')" class="bg-white text-dark px-10 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition-all border-2 border-transparent">Nous contacter</button>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-6 -mt-16 relative z-20">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div onclick="filterAndScroll('Cybersécurité')" class="bg-white p-6 rounded-2xl shadow-xl text-center card-hover transition-all cursor-pointer group">
                        <i class="fas fa-shield-alt text-4xl text-primary mb-4"></i>
                        <h3 class="font-title font-bold text-lg group-hover:text-primary transition-colors">Cybersécurité</h3>
                        <p class="text-sm text-gray-500 mt-2">Protégez les infrastructures critiques.</p>
                    </div>
                    <div onclick="filterAndScroll('Développement')" class="bg-white p-6 rounded-2xl shadow-xl text-center card-hover transition-all cursor-pointer group">
                        <i class="fas fa-code text-4xl text-secondary mb-4"></i>
                        <h3 class="font-title font-bold text-lg group-hover:text-secondary transition-colors">Programmation</h3>
                        <p class="text-sm text-gray-500 mt-2">Maîtrisez les langages du futur.</p>
                    </div>
                    <div onclick="filterAndScroll('Productivité')" class="bg-white p-6 rounded-2xl shadow-xl text-center card-hover transition-all cursor-pointer group">
                        <i class="fas fa-desktop text-4xl text-primary mb-4"></i>
                        <h3 class="font-title font-bold text-lg group-hover:text-primary transition-colors">Bureautique</h3>
                        <p class="text-sm text-gray-500 mt-2">Optimisez votre productivité.</p>
                    </div>
                    <div onclick="filterAndScroll('Outils Pro')" class="bg-white p-6 rounded-2xl shadow-xl text-center card-hover transition-all cursor-pointer group">
                        <i class="fas fa-users-cog text-4xl text-secondary mb-4"></i>
                        <h3 class="font-title font-bold text-lg group-hover:text-secondary transition-colors">Outils Pro</h3>
                        <p class="text-sm text-gray-500 mt-2">Collaboration et Agile.</p>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-6 mt-16">
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-10">
                    <div>
                        <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Nos formations à la une</p>
                        <h2 class="mt-3 text-4xl font-title font-bold text-slate-900">Formations sélectionnées pour vous</h2>
                        <p class="mt-3 max-w-2xl text-sm text-gray-500">Découvrez les parcours les plus demandés, construits pour booster votre employabilité.</p>
                    </div>
                    <button onclick="showPage('formations')" class="inline-flex items-center gap-2 rounded-full bg-[#3F8F29] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600">Voir toutes les formations</button>
                </div>
                <div class="mt-6">
                    <?php $featuredFormations = array_slice($availableFormations, 0, 3); ?>
                    <?php if (!empty($featuredFormations)) : ?>
                        <div id="featured-carousel" class="featured-carousel rounded-3xl overflow-hidden">
                            <?php foreach ($featuredFormations as $idx => $formation) : ?>
                                <div class="slide <?php echo $idx === 0 ? 'active' : ''; ?>">
                                    <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group transition-all hover:shadow-2xl">
                                        <div class="relative h-56 overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($formation['image'] ?: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($formation['title'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
                                            <?php if ((float)($formation['price'] ?? 0) <= 0) : ?>
                                                <div class="absolute top-4 right-4 bg-secondary text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-lg">Offert</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-6">
                                            <span onclick="filterAndScroll('<?php echo htmlspecialchars($formation['category'] ?: 'Formation', ENT_QUOTES, 'UTF-8'); ?>')" 
                                                  class="inline-flex items-center px-3 py-1 rounded-full bg-[#E6F7E6] text-sm font-semibold text-[#2B6A2B] mb-4 cursor-pointer hover:bg-primary hover:text-white transition-all">
                                                <?php echo htmlspecialchars($formation['category'] ?: 'Formation', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <h3 class="font-title font-bold text-xl mb-3"><?php echo htmlspecialchars($formation['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="text-gray-600 mb-4 text-sm leading-relaxed"><?php echo htmlspecialchars($formation['short_description'] ?: $formation['description'] ?: 'Une formation conçue pour développer vos compétences.', ENT_QUOTES, 'UTF-8'); ?></p>
                                            <div class="flex items-center justify-between text-sm text-gray-500 mb-6">
                                                <span><i class="far fa-clock mr-2 text-primary"></i><?php echo htmlspecialchars($formation['duration'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span><i class="fas fa-signal mr-2 text-secondary"></i><?php echo htmlspecialchars($formation['level'] ?: 'Tous niveaux', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-lg text-dark"><?php echo $formation['price'] !== null ? number_format((float)$formation['price'], 0, ',', ' ') . ' FCFA' : 'Gratuit'; ?></span>
                                                <button onclick="showPage('register')" class="text-sm font-semibold text-primary hover:text-secondary transition">S'inscrire</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="carousel-controls">
                                <div class="flex justify-between w-full px-4">
                                    <button id="carousel-prev" class="bg-white/90 p-3 rounded-full shadow-md"><i class="fas fa-chevron-left"></i></button>
                                    <button id="carousel-next" class="bg-white/90 p-3 rounded-full shadow-md"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="md:col-span-3 bg-white rounded-[2.5rem] border border-gray-100 p-16 text-center shadow-sm">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-50 rounded-full mb-6 text-slate-300">
                                <i class="fas fa-graduation-cap text-3xl"></i>
                            </div>
                            <h3 class="font-title text-2xl font-bold text-slate-900 mb-2">Catalogue en cours de mise à jour</h3>
                            <p class="text-gray-500 max-w-md mx-auto">Nous préparons de nouveaux programmes d'excellence. Revenez très bientôt pour découvrir nos prochaines sessions certifiantes.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container mx-auto px-6 mt-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-[#F0FDF4] rounded-3xl p-8 shadow-lg border border-[#DCF3D6]">
                        <h3 class="font-title font-bold text-2xl mb-4">Accompagnement personnalisé</h3>
                        <p class="text-gray-600">Un conseiller dédié vous guide dans le choix de la formation et le montage de votre dossier.</p>
                    </div>
                    <div class="bg-[#F7FAFC] rounded-3xl p-8 shadow-lg border border-[#E2E8F0]">
                        <h3 class="font-title font-bold text-2xl mb-4">Méthodes pragmatiques</h3>
                        <p class="text-gray-600">Apprentissage actif avec cas pratiques, projets réels et outils professionnels.</p>
                    </div>
                    <div class="bg-[#FFF8ED] rounded-3xl p-8 shadow-lg border border-[#F5DEB3]">
                        <h3 class="font-title font-bold text-2xl mb-4">Certification reconnue</h3>
                        <p class="text-gray-600">Obtenez un certificat valorisé par les employeurs et améliorez votre employabilité.</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="py-16 bg-white mt-10 relative">
            <div id="stats-display-section" class="py-16 bg-white mt-10 relative">
                <!-- Live Indicator -->
                <div class="absolute top-4 right-8 flex items-center gap-2 opacity-30 hover:opacity-100 transition-opacity">
                    <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span></span>
                    <span class="text-[8px] font-black uppercase tracking-[0.2em] text-slate-400">Live Data</span>
                </div>
                <div class="container mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-4xl font-extrabold text-primary mb-2"><span id="stat-students" class="stat-counter" data-target="<?php echo $studentCount !== null ? (int)$studentCount : 500; ?>">0</span></div>
                        <div class="text-gray-600 font-semibold uppercase text-xs tracking-widest">Étudiants formés</div>
                    </div>
                    <div>
                        <div class="text-4xl font-extrabold text-primary mb-2"><span id="stat-formations" class="stat-counter" data-target="<?php echo $formationCount !== null ? (int)$formationCount : 15; ?>">0</span></div>
                        <div class="text-gray-600 font-semibold uppercase text-xs tracking-widest">Formations</div>
                    </div>
                    <div>
                        <div class="text-4xl font-extrabold text-primary mb-2"><span id="stat-blog" class="stat-counter" data-target="<?php echo $publishedBlogCount !== null ? (int)$publishedBlogCount : 12; ?>">0</span></div>
                        <div class="text-gray-600 font-semibold uppercase text-xs tracking-widest">Articles publiés</div>
                    </div>
                    <div>
                        <div class="text-4xl font-extrabold text-primary mb-2"><span id="stat-enrollments" class="stat-counter" data-target="<?php echo $enrollmentCount !== null ? (int)$enrollmentCount : 120; ?>">0</span></div>
                        <div class="text-gray-600 font-semibold uppercase text-xs tracking-widest">Inscriptions</div>
                    </div>
                </div>
            </div>

            <!-- Trusted By Section -->
            <div class="py-12 bg-white border-y border-slate-50 overflow-hidden">
                <div class="container mx-auto px-6 text-center mb-8">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-300">Ils nous font confiance & Partenaires Tech</p>
                </div>
                <div class="flex whitespace-nowrap animate-marquee">
                    <div class="flex items-center space-x-16 px-8 grayscale opacity-40 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg" class="h-8 md:h-10" alt="AWS">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/08/Cisco_logo_blue_2016.svg" class="h-10 md:h-12" alt="Cisco">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" class="h-8 md:h-10" alt="Microsoft">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Slack_icon_2019.svg" class="h-8 md:h-10" alt="Slack">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_Cloud_Logo.svg" class="h-8 md:h-10" alt="Google Cloud">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/GitLab_logo.svg" class="h-8 md:h-10" alt="GitLab">
                    </div>
                    <!-- Duplication pour le défilement infini -->
                    <div class="flex items-center space-x-16 px-8 grayscale opacity-40 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg" class="h-8 md:h-10" alt="AWS">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/08/Cisco_logo_blue_2016.svg" class="h-10 md:h-12" alt="Cisco">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" class="h-8 md:h-10" alt="Microsoft">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Slack_icon_2019.svg" class="h-8 md:h-10" alt="Slack">
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-6 py-16">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <span class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Pourquoi choisir ZoeTIC ?</span>
                        <h2 class="mt-4 text-4xl font-title font-bold text-slate-900">Une formation pensée pour l'employabilité</h2>
                        <p class="mt-4 text-gray-600 text-lg leading-relaxed">Nos parcours sont conçus avec des experts métiers, des projets concrets et un accompagnement rapproché pour vous aider à décrocher un emploi durable.</p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-start gap-4 text-gray-600">
                                <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white">1</span>
                                <span>Curriculum aligné sur les besoins réels des entreprises.</span>
                            </li>
                            <li class="flex items-start gap-4 text-gray-600">
                                <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-white">2</span>
                                <span>Mentorat personnalisé et suivi de carrière.</span>
                            </li>
                            <li class="flex items-start gap-4 text-gray-600">
                                <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#F59E0B] text-white">3</span>
                                <span>Certificat reconnu et aide à la recherche d'emploi.</span>
                            </li>
                        </ul>
                        <div class="mt-10 flex flex-col sm:flex-row gap-4">
                            <button onclick="showPage('formations')" class="inline-flex items-center justify-center rounded-full bg-[#3F8F29] px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-green-200 transition hover:bg-green-600">Voir les formations</button>
                            <button onclick="showPage('contact')" class="inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-8 py-4 text-sm font-semibold text-slate-900 transition hover:bg-gray-100">Contactez un conseiller</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($testimonials as $t): ?>
                            <div class="rounded-3xl bg-[#F8FAFC] p-8 shadow-lg border border-[#E2E8F0]">
                                <span class="text-sm uppercase tracking-[0.35em] text-[#0F766E] font-bold">Témoignage</span>
                                <p class="mt-4 text-lg font-semibold text-slate-900">"<?php echo htmlspecialchars($t['content'], ENT_QUOTES, 'UTF-8'); ?>"</p>
                                <p class="mt-6 text-gray-500">- <?php echo htmlspecialchars($t['author_name'], ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars($t['author_role'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endforeach; ?>

                        <div class="rounded-3xl bg-white p-8 shadow-lg border border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Notre impact</h3>
                                    <p class="text-sm text-gray-500">Taux de réussite et insertion</p>
                                </div>
                                <div class="text-right">
                                    <span class="block text-3xl font-extrabold text-primary">88%</span>
                                    <span class="text-sm text-gray-500">de satisfaction</span>
                                </div>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full w-[88%] rounded-full bg-primary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HOW IT WORKS SECTION -->
            <div class="py-24 bg-slate-50/50">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Méthodologie</p>
                        <h2 class="text-3xl md:text-4xl font-title font-bold text-slate-900">3 Étapes vers votre Succès</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                        <!-- Connecting Line (Desktop) -->
                        <div class="hidden md:block absolute top-1/3 left-0 w-full h-0.5 border-t-2 border-dashed border-slate-200 -z-10"></div>
                        
                        <div class="text-center group">
                            <div class="w-20 h-20 bg-white rounded-3xl shadow-xl flex items-center justify-center mx-auto mb-6 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 transform group-hover:-rotate-6">
                                <i class="fas fa-id-card text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2">1. Inscription</h4>
                            <p class="text-gray-500 text-sm">Choisissez votre parcours et soumettez votre dossier en 2 minutes.</p>
                        </div>

                        <div class="text-center group">
                            <div class="w-20 h-20 bg-white rounded-3xl shadow-xl flex items-center justify-center mx-auto mb-6 text-secondary group-hover:bg-secondary group-hover:text-white transition-all duration-500 transform group-hover:rotate-6">
                                <i class="fas fa-laptop-code text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2">2. Formation Intensive</h4>
                            <p class="text-gray-500 text-sm">Apprenez avec des experts via des projets concrets et cas pratiques.</p>
                        </div>

                        <div class="text-center group">
                            <div class="w-20 h-20 bg-white rounded-3xl shadow-xl flex items-center justify-center mx-auto mb-6 text-primary group-hover:bg-[#0F766E] group-hover:text-white transition-all duration-500 transform group-hover:-rotate-6">
                                <i class="fas fa-certificate text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2">3. Certification & Job</h4>
                            <p class="text-gray-500 text-sm">Obtenez votre diplôme et bénéficiez de notre réseau de recrutement.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRESTATIONS & RÉALISATIONS DE TERRAIN (SHOWCASE ACCUEIL) -->
            <div class="py-24 bg-gradient-to-b from-slate-50 to-white relative overflow-hidden border-t border-slate-100">
                <div class="container mx-auto px-6">
                    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
                        <div>
                            <span class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.3em] text-[#3F8F29] px-3.5 py-1.5 rounded-full bg-green-50 border border-green-100">
                                <i class="fas fa-tools"></i> Prestations Techniques & Terrain
                            </span>
                            <h2 class="mt-4 text-3xl md:text-5xl font-title font-extrabold text-slate-900 leading-tight">
                                Nos Réalisations Récentes & <br class="hidden sm:inline">
                                <span class="text-[#3F8F29]">Confessions Clients</span>
                            </h2>
                            <p class="mt-4 max-w-2xl text-slate-600 text-base leading-relaxed">
                                Installation de caméras de surveillance, réseaux et sécurité : regardez notre équipe en plein travail et découvrez les retours oraux et vidéos de nos clients satisfaits.
                            </p>
                        </div>
                        <button onclick="showPage('realisations')" class="inline-flex items-center gap-3 rounded-full bg-[#3F8F29] px-8 py-4 text-sm font-bold text-white shadow-xl shadow-green-900/20 hover:bg-green-700 transition hover:scale-105 shrink-0">
                            <span>Voir toutes nos réalisations</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <?php if (!empty($featuredRealisations)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <?php foreach ($featuredRealisations as $item): ?>
                                <div onclick="openRealisationModal(<?php echo (int)$item['id']; ?>)" class="bg-white rounded-3xl overflow-hidden shadow-lg border border-slate-100 hover:shadow-2xl transition-all duration-300 flex flex-col group cursor-pointer hover:-translate-y-1">
                                    <div class="relative h-56 bg-slate-100 overflow-hidden">
                                        <?php if (!empty($item['cover_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-300">
                                                <i class="fas fa-camera text-4xl"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="absolute top-4 left-4">
                                            <span class="px-3.5 py-1.5 rounded-full bg-white/95 backdrop-blur-md text-[11px] font-black uppercase tracking-wider text-slate-900 shadow-sm">
                                                <?php echo htmlspecialchars($item['category']); ?>
                                            </span>
                                        </div>

                                        <div class="absolute bottom-4 right-4 flex items-center gap-2">
                                            <?php if ($item['video_count'] > 0): ?>
                                                <span class="px-3 py-1 rounded-full bg-slate-900/80 backdrop-blur-md text-white text-xs font-bold flex items-center gap-1.5 shadow">
                                                    <i class="fas fa-play text-[10px] text-green-400"></i> Vidéo au travail
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($item['testimonial_type'] === 'video'): ?>
                                                <span class="px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center gap-1.5 shadow">
                                                    <i class="fas fa-video text-[10px]"></i> Vidéo Client
                                                </span>
                                            <?php elseif ($item['testimonial_type'] === 'audio'): ?>
                                                <span class="px-3 py-1 rounded-full bg-amber-500 text-white text-xs font-bold flex items-center gap-1.5 shadow">
                                                    <i class="fas fa-microphone-alt text-[10px]"></i> Note Vocale
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="p-6 flex-1 flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-2">
                                                <i class="fas fa-map-marker-alt text-primary"></i>
                                                <span><?php echo htmlspecialchars($item['location'] ?: 'Abidjan'); ?></span>
                                                <?php if (!empty($item['client_name'])): ?>
                                                    <span>•</span>
                                                    <span><?php echo htmlspecialchars($item['client_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="font-title text-xl font-bold text-slate-900 group-hover:text-primary transition line-clamp-2">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </h3>
                                            <?php if (!empty($item['short_description'])): ?>
                                                <p class="text-slate-500 text-sm mt-2 line-clamp-2">
                                                    <?php echo htmlspecialchars($item['short_description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($item['client_quote'])): ?>
                                            <div class="mt-4 pt-4 border-t border-slate-100 flex items-start gap-3">
                                                <i class="fas fa-quote-left text-amber-500 text-sm mt-1 shrink-0"></i>
                                                <p class="text-xs italic text-slate-600 line-clamp-2">
                                                    « <?php echo htmlspecialchars($item['client_quote']); ?> »
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-white rounded-3xl border border-slate-100 p-8">
                            <p class="text-slate-500 font-medium">Nos prochaines réalisations et vidéos de chantiers seront publiées très prochainement.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- PAGE: FORMATIONS -->
        <?php
        function is_video_url(string $url): bool
        {
            return (bool) preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|vimeo\.com/|\.mp4|\.webm|\.ogg)#i', $url);
        }

        function get_embed_html(string $url, string $title): string
        {
            if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]+)#i', $url, $m)) {
                $videoId = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                return '<iframe class="absolute inset-0 w-full h-full" src="https://www.youtube.com/embed/' . $videoId . '" title="' . $title . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
            }
            if (preg_match('#vimeo\.com/(\d+)#i', $url, $m)) {
                $videoId = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                return '<iframe class="absolute inset-0 w-full h-full" src="https://player.vimeo.com/video/' . $videoId . '" title="' . $title . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
            }
            if (preg_match('#\.(mp4|webm|ogg)(\?.*)?$#i', $url)) {
                return '<video class="absolute inset-0 w-full h-full object-cover" src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" autoplay muted loop playsinline></video>';
            }
            return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $title . '" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">';
        }

        function card_classes_for_level(string $level): array
        {
            switch (mb_strtolower($level)) {
                case 'avancé':
                    return ['badge' => 'bg-dark', 'text' => 'text-dark'];
                case 'intermédiaire':
                    return ['badge' => 'bg-primary', 'text' => 'text-primary'];
                case 'débutant':
                default:
                    return ['badge' => 'bg-secondary', 'text' => 'text-secondary'];
            }
        }

        // Extraction unique des catégories présentes
        $allCategoryNames = array_map(fn($f) => $f['category'] ?: 'Formation', $availableFormations);
        $categories = array_unique($allCategoryNames);
        sort($categories);
        $categoryCounts = array_count_values($allCategoryNames);
        $totalAvailable = count($availableFormations);
        $freeAvailableCount = count(array_filter($availableFormations, fn($f) => (float)($f['price'] ?? 0) <= 0));

        $formationCards = array_map(function ($formation) {
            return [
                'title' => $formation['title'] ?? 'Formation',
                'category' => $formation['category'] ?: 'Formation',
                'description' => $formation['short_description'] ?: $formation['description'] ?: 'Découvrez une formation sur mesure pour développer vos compétences numériques.',
                'instructor' => $formation['instructor'] ?? 'Expert ZoeTIC',
                'duration' => $formation['duration'] ?: '30 Heures',
                'level' => $formation['level'] ?: 'Tous niveaux',
                'image' => $formation['image'] ?: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800',
                'media' => $formation['image'] ?? null,
                'price' => $formation['price'],
                'badgeClass' => card_classes_for_level($formation['level'] ?? 'Débutant')['badge'],
                'textClass' => card_classes_for_level($formation['level'] ?? 'Débutant')['text'],
            ];
        }, $availableFormations);
        ?>

        <section id="page-formations" class="page-content hidden py-24 bg-[#FAFBFF]">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <p class="text-sm uppercase tracking-[0.4em] text-primary font-bold mb-4">Parcours d'excellence</p>
                    <h2 class="font-title text-4xl md:text-5xl font-bold text-dark mb-6">Notre Catalogue de Formations</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto text-lg">Trouvez le programme qui propulsera votre carrière parmi nos <?php echo $formationCount !== null ? $formationCount : 'nombreux'; ?> parcours certifiants.</p>
                    
                    <!-- Dynamic Filters -->
                    <div class="mt-12 relative">
                        <!-- Mobile scroll indicators (Gradients) -->
                        <div class="md:hidden absolute left-0 top-0 bottom-4 w-12 bg-gradient-to-r from-[#FAFBFF] to-transparent pointer-events-none z-10 -ml-6"></div>
                        <div class="md:hidden absolute right-0 top-0 bottom-4 w-12 bg-gradient-to-l from-[#FAFBFF] to-transparent pointer-events-none z-10 -mr-6"></div>

                        <div class="flex flex-nowrap overflow-x-auto pb-4 gap-3 px-6 -mx-6 md:mx-0 md:px-0 md:flex-wrap md:justify-center md:pb-0 no-scrollbar">
                            <button onclick="filterFormationCategory('All')" class="category-filter-btn whitespace-nowrap shrink-0 px-8 py-3 bg-primary text-white rounded-full text-sm font-bold shadow-lg shadow-green-100 transition-all" data-category="All">Tout voir (<?php echo $totalAvailable; ?>)</button>
                            <button onclick="filterFormationCategory('Free')" 
                                    class="category-filter-btn whitespace-nowrap shrink-0 px-6 py-3 bg-white text-slate-600 border border-gray-100 rounded-full text-sm font-bold hover:bg-gray-50 transition-all shadow-sm" 
                                    data-category="Free">
                                Formations Gratuites (<?php echo $freeAvailableCount; ?>)
                            </button>
                            <?php foreach ($categories as $cat): ?>
                                <button onclick="filterFormationCategory('<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>')" 
                                        class="category-filter-btn whitespace-nowrap shrink-0 px-6 py-3 bg-white text-slate-600 border border-gray-100 rounded-full text-sm font-bold hover:bg-gray-50 transition-all shadow-sm" 
                                        data-category="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?> (<?php echo $categoryCounts[$cat] ?? 0; ?>)
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Keyword Search -->
                    <div class="mt-10 max-w-2xl mx-auto relative group px-4 md:px-0">
                        <input type="text" id="formationKeywordSearch" 
                               oninput="debouncedApplyFilters()"
                               onkeydown="if(event.key === 'Enter') applyFilters()"
                               placeholder="Rechercher une formation..." 
                               class="w-full pl-10 md:pl-14 pr-24 md:pr-44 py-4 md:py-5 bg-white border border-gray-100 rounded-full text-slate-800 font-semibold shadow-xl outline-none focus:ring-4 md:focus:ring-8 focus:ring-primary/10 transition-all placeholder:text-slate-400 text-sm md:text-base">
                        <i class="fas fa-search absolute left-4 md:left-6 top-1/2 -translate-y-1/2 text-primary text-lg md:text-xl"></i>
                        <button id="clearSearchBtn" type="button" onclick="clearFormationSearch()" class="hidden absolute right-16 md:right-36 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors p-2" title="Vider la recherche">
                            <i class="fas fa-times-circle text-base md:text-lg"></i>
                        </button>
                        <button onclick="applyFilters()" 
                                class="absolute right-1.5 md:right-2.5 top-1.5 md:top-2.5 bottom-1.5 md:bottom-2.5 bg-primary text-white px-4 md:px-8 rounded-full font-bold hover:bg-green-700 transition-all shadow-lg active:scale-95 text-xs md:text-sm">
                            <span class="hidden md:inline">Trouver</span>
                            <i class="fas fa-arrow-right md:hidden"></i>
                        </button>
                    </div>

                    <!-- Sorting Filter -->
                    <div class="mt-6 flex justify-center">
                        <div class="relative inline-block w-full max-w-xs">
                            <select onchange="sortFormations(this.value)" class="w-full pl-6 pr-10 py-3 bg-white border border-gray-100 rounded-full text-sm font-bold text-slate-600 focus:outline-none focus:ring-4 focus:ring-primary/5 shadow-sm appearance-none cursor-pointer">
                                <option value="none" disabled selected>Trier par prix...</option>
                                <option value="asc">Prix : Croissant</option>
                                <option value="desc">Prix : Décroissant</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-slate-300">
                                <i class="fas fa-sort-amount-down"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="formationsListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <!-- Message "Aucun résultat" dynamique -->
                    <div id="formationNoResults" class="hidden md:col-span-3 py-20 px-6 bg-white rounded-[3rem] border border-slate-50 text-center animate-in">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search-minus text-slate-200 text-3xl"></i>
                        </div>
                        <h3 class="font-title text-2xl font-black text-slate-900 mb-2 uppercase tracking-tight">Aucun programme trouvé</h3>
                        <p class="text-gray-400 max-w-sm mx-auto text-sm">Ajustez vos filtres ou votre recherche pour trouver la formation idéale.</p>
                        <button onclick="document.getElementById('formationKeywordSearch').value=''; filterFormationCategory('All');" class="mt-8 text-primary font-bold text-sm underline">Réinitialiser les filtres</button>
                    </div>

                    <?php if (!empty($formationCards)) : ?>
                        <?php foreach ($formationCards as $card) : ?>
                            <div class="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-gray-100 group transition-all hover:shadow-2xl hover:-translate-y-2 flex flex-col formation-card-item" 
                                 data-category="<?php echo htmlspecialchars($card['category'], ENT_QUOTES, 'UTF-8'); ?>"
                                 data-price="<?php echo (float)($card['price'] ?? 0); ?>"
                                 data-instructor="<?php echo htmlspecialchars($card['instructor'], ENT_QUOTES, 'UTF-8'); ?>"
                                 data-description="<?php echo htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="relative h-64 overflow-hidden">
                                    <?php if (!empty($card['media']) && is_video_url($card['media'])) : ?>
                                        <?php echo get_embed_html($card['media'], $card['title']); ?>
                                    <?php else : ?>
                                        <img src="<?php echo htmlspecialchars($card['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
                                    <?php endif; ?>
                                    <div onclick="filterFormationCategory('<?php echo htmlspecialchars($card['category'], ENT_QUOTES, 'UTF-8'); ?>')" 
                                         class="absolute top-6 left-6 <?php echo htmlspecialchars($card['badgeClass'], ENT_QUOTES, 'UTF-8'); ?> text-white text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-lg cursor-pointer hover:scale-110 transition-all">
                                        <?php echo htmlspecialchars($card['category'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if ((float)($card['price'] ?? 0) <= 0) : ?>
                                        <div class="absolute top-6 right-6 bg-secondary text-white text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-lg animate-pulse">Offert</div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-8 flex-grow flex flex-col">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
                                            <i class="far fa-calendar-alt text-primary"></i> Session 2024
                                        </div>
                                        <span class="font-bold text-lg text-primary"><?php echo $card['price'] !== null ? number_format((float)$card['price'], 0, ',', ' ') . ' <span class="text-xs uppercase">FCFA</span>' : 'Gratuit'; ?></span>
                                    </div>
                                    <h3 class="font-title font-bold text-xl mb-3"><?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="text-gray-600 text-sm mb-6 leading-relaxed"><?php echo htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="flex justify-between items-center text-xs font-bold text-gray-500 mb-8 border-t border-dashed pt-6 mt-auto">
                                        <span><i class="far fa-clock <?php echo htmlspecialchars($card['textClass'], ENT_QUOTES, 'UTF-8'); ?> mr-2"></i> <?php echo htmlspecialchars($card['duration'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><i class="fas fa-signal <?php echo htmlspecialchars($card['textClass'], ENT_QUOTES, 'UTF-8'); ?> mr-2"></i> <?php echo htmlspecialchars($card['level'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <button onclick="openRegisterForm('<?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?>')" class="w-full <?php echo $card['badgeClass'] === 'bg-dark' ? 'bg-dark/10 text-dark hover:bg-dark hover:text-white' : ($card['badgeClass'] === 'bg-secondary' ? 'bg-secondary/10 text-secondary hover:bg-secondary hover:text-white' : 'bg-primary/10 text-primary hover:bg-primary hover:text-white'); ?> py-3 rounded-xl font-bold transition-all"><?php echo htmlspecialchars('S\'inscrire à cette session', ENT_QUOTES, 'UTF-8'); ?></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="md:col-span-3 py-20 px-6 bg-white rounded-[3rem] border border-slate-100 text-center shadow-sm">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
                                <i class="fas fa-search text-slate-200 text-4xl animate-vibrate"></i>
                            </div>
                            <h3 class="font-title text-3xl font-black text-slate-900 mb-4 uppercase tracking-tight">Catalogue vide</h3>
                            <p class="text-gray-500 max-w-lg mx-auto leading-relaxed">Désolé, aucun programme n'est publié pour le moment. Nos équipes pédagogiques travaillent sur de nouveaux cursus adaptés au marché.</p>
                            <button onclick="showPage('contact')" class="mt-10 px-8 py-4 bg-primary text-white rounded-full font-bold hover:bg-green-700 transition-all">Être informé des ouvertures</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php
        // Utilisation des variables déjà préparées dans index.php
        // On renomme pour correspondre au code existant dans la vue
        $posts = $allBlogPosts ?? [];
        $totalPosts = $publishedBlogCount ?? 0;
        $perPage = 3; 
        $page = $blogPage ?? 1;
        $totalPages = $totalBlogPages ?? 1;
        $q = $_GET['q'] ?? '';
        $selectedCat = $_GET['category'] ?? '';
        ?>

        <!-- PAGE: REALISATIONS -->
        <section id="page-realisations" class="page-content hidden py-24 bg-[#FAFBFF]">
            <div class="container mx-auto px-6">
                <!-- En-tête -->
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.35em] text-[#3F8F29] px-4 py-1.5 rounded-full bg-green-50 border border-green-100 mb-4">
                        <i class="fas fa-shield-alt"></i> Prestations Techniques & Terrain
                    </span>
                    <h2 class="font-title text-4xl md:text-5xl font-extrabold text-slate-900 mb-6 leading-tight">
                        Nos Réalisations & <br>
                        <span class="text-[#3F8F29]">Confessions Clients</span>
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        Installation de caméras de surveillance, câblage réseau, sécurité électronique et maintenance : découvrez nos interventions en direct et écoutez les confessions orales ou vidéos de nos clients satisfaits.
                    </p>
                </div>

                <!-- Filtres par catégorie et Recherche -->
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12">
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2" id="realisation-filter-buttons">
                        <button type="button" onclick="filterRealisations('Tous', this)" class="realisation-filter-btn px-6 py-2.5 rounded-full text-xs font-bold transition-all shadow-sm bg-[#3F8F29] text-white">
                            Toutes nos prestations
                            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white"><?php echo count($allRealisations); ?></span>
                        </button>
                        <?php 
                        $distinctCategories = !empty($realisationCategories) ? $realisationCategories : array_unique(array_filter(array_column($allRealisations, 'category')));
                        foreach ($distinctCategories as $cat): 
                            $countInCat = count(array_filter($allRealisations, fn($r) => $r['category'] === $cat));
                        ?>
                            <button type="button" onclick="filterRealisations('<?php echo htmlspecialchars(addslashes($cat)); ?>', this)" class="realisation-filter-btn px-6 py-2.5 rounded-full text-xs font-bold transition-all bg-white text-slate-600 border border-slate-200 hover:border-[#3F8F29] hover:text-[#3F8F29]">
                                <?php echo htmlspecialchars($cat); ?>
                                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-500"><?php echo $countInCat; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="relative w-full md:w-72">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input id="realisationLiveSearch" type="text" placeholder="Rechercher une réalisation..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-full text-xs font-medium outline-none focus:border-[#3F8F29] shadow-sm">
                    </div>
                </div>

                <!-- Grille des Réalisations -->
                <div id="realisations-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (empty($allRealisations)): ?>
                        <div class="col-span-full text-center py-20 bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">
                            <div class="w-20 h-20 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4 text-3xl">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h3 class="font-title text-2xl font-bold text-slate-800 mb-2">Bientôt disponible</h3>
                            <p class="text-sm text-slate-500 max-w-md mx-auto">Nos techniciens documentent actuellement de nouvelles interventions sur le terrain. Les premières réalisations seront publiées sous peu.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allRealisations as $item): ?>
                            <div class="realisation-card bg-white rounded-3xl overflow-hidden shadow-lg border border-slate-100 hover:shadow-2xl transition-all duration-300 flex flex-col group cursor-pointer hover:-translate-y-1.5" 
                                 data-id="<?php echo (int)$item['id']; ?>"
                                 data-category="<?php echo htmlspecialchars($item['category']); ?>" 
                                 data-search="<?php echo htmlspecialchars(strtolower($item['title'] . ' ' . $item['category'] . ' ' . ($item['location'] ?? '') . ' ' . ($item['client_name'] ?? ''))); ?>"
                                 onclick="openRealisationModal(<?php echo (int)$item['id']; ?>)">
                                
                                <div class="relative h-60 bg-slate-100 overflow-hidden">
                                    <?php if (!empty($item['cover_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-300 bg-slate-50">
                                            <i class="fas fa-camera text-4xl"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Catégorie badge -->
                                    <div class="absolute top-4 left-4">
                                        <span class="px-3.5 py-1.5 rounded-full bg-white/95 backdrop-blur-md text-[11px] font-black uppercase tracking-wider text-slate-900 shadow-sm border border-white/50">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </span>
                                    </div>

                                    <!-- Badges médias en bas -->
                                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-3 py-1 rounded-full bg-slate-900/80 backdrop-blur-md text-white text-[11px] font-bold flex items-center gap-1 shadow">
                                                <i class="fas fa-camera text-[10px]"></i> <?php echo (int)$item['photo_count']; ?>
                                            </span>
                                            <?php if ($item['video_count'] > 0): ?>
                                                <span class="px-3 py-1 rounded-full bg-[#3F8F29]/90 backdrop-blur-md text-white text-[11px] font-bold flex items-center gap-1 shadow">
                                                    <i class="fas fa-play text-[9px]"></i> Vidéo au travail
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($item['testimonial_type'] === 'video'): ?>
                                            <span class="px-3 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-bold flex items-center gap-1 shadow">
                                                <i class="fas fa-video text-[9px]"></i> Vidéo Client
                                            </span>
                                        <?php elseif ($item['testimonial_type'] === 'audio'): ?>
                                            <span class="px-3 py-1 rounded-full bg-amber-500 text-white text-[11px] font-bold flex items-center gap-1 shadow">
                                                <i class="fas fa-microphone-alt text-[9px]"></i> Note Vocale
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-2">
                                            <i class="fas fa-map-marker-alt text-[#3F8F29]"></i>
                                            <span><?php echo htmlspecialchars($item['location'] ?: 'Abidjan'); ?></span>
                                            <?php if (!empty($item['client_name'])): ?>
                                                <span>•</span>
                                                <span><?php echo htmlspecialchars($item['client_name']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['completion_date'])): ?>
                                                <span>•</span>
                                                <span><?php echo date('m/Y', strtotime($item['completion_date'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="font-title text-xl font-bold text-slate-900 group-hover:text-[#3F8F29] transition line-clamp-2">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </h3>
                                        <?php if (!empty($item['short_description'])): ?>
                                            <p class="text-slate-500 text-sm mt-2 line-clamp-2 leading-relaxed">
                                                <?php echo htmlspecialchars($item['short_description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($item['client_quote'])): ?>
                                        <div class="mt-4 pt-4 border-t border-slate-100 bg-amber-50/50 -mx-6 -mb-6 p-5">
                                            <div class="flex items-start gap-2.5">
                                                <i class="fas fa-quote-left text-amber-500 text-sm mt-0.5 shrink-0"></i>
                                                <div>
                                                    <p class="text-xs italic text-slate-700 line-clamp-2">
                                                        « <?php echo htmlspecialchars($item['client_quote']); ?> »
                                                    </p>
                                                    <?php if (!empty($item['testimonial_author'])): ?>
                                                        <span class="block text-[10px] font-bold text-amber-800 mt-1 uppercase tracking-wider">
                                                            — <?php echo htmlspecialchars($item['testimonial_author']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- MODAL DÉTAILS RÉALISATION & LECTEURS MULTIMÉDIA -->
        <div id="realisationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-md hidden p-4 md:p-6 overflow-y-auto" onclick="if(event.target === this) closeRealisationModal()">
            <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100 flex flex-col my-auto relative animate-in">
                <!-- Bouton fermer -->
                <button onclick="closeRealisationModal()" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors z-30 shadow-sm">
                    <i class="fas fa-times"></i>
                </button>

                <!-- Contenu dynamique de la modale injecté par JavaScript -->
                <div id="realisationModalContent" class="p-6 md:p-10 space-y-8">
                    <!-- Dynamique -->
                </div>
            </div>
        </div>

        <!-- Données des réalisations pour affichage instantané dans la modale -->
        <script id="realisations-json-data" type="application/json">
            <?php echo json_encode($allRealisations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>
        </script>

<!-- PAGE: BLOG -->
<section id="page-blog" class="page-content hidden py-20 scroll-mt-28">
            
            <div class="container mx-auto px-6">
<div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <p class="text-sm uppercase tracking-[0.35em] text-[#3F8F29] font-bold">Notre Blog</p>
                            <span id="blog-active-filter-badge" class="<?php echo ($q || $selectedCat) ? '' : 'hidden'; ?> px-3 py-1 bg-primary/10 text-primary text-[9px] font-black rounded-full uppercase tracking-widest animate-in">Filtre Actif</span>
                        </div>
                        <h2 class="font-title text-4xl font-bold text-dark">Actualités & Tech</h2>
                        <div class="flex items-center gap-4 mt-4">
                            <p class="text-gray-500 text-sm">Les dernières tendances numériques en Afrique.</p>
                            <span id="blog-results-count" class="text-[10px] font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full uppercase tracking-tighter"><?php echo $filteredBlogCount; ?> articles</span>
                        </div>
                    </div>
                    <div class="relative">
                        <form id="blog-search-form" method="GET" action="index.php#page-blog" class="relative group">
                            <?php if (!empty($selectedCat)): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCat, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endif; ?>
                            
                            <input name="q" value="<?php echo htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                   type="text" placeholder="Rechercher un article..." 
                                   class="bg-white border border-gray-200 rounded-full pl-12 pr-10 py-3 w-full md:w-80 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all font-medium text-slate-700 shadow-sm">
                            
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                            
                            <?php if (!empty($q)): ?>
                                <a href="index.php#page-blog" onclick="window.location.href='index.php#page-blog'; window.location.reload();" class="absolute right-12 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-500 transition-colors p-1" title="Réinitialiser">
                                    <i class="fas fa-sync-alt text-xs"></i>
                                </a>
                            <?php endif; ?>
                            
                            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 bg-primary text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-green-700 transition-all shadow-md">
                                <i class="fas fa-arrow-right text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Filtres de Catégories Blog -->
                <div class="flex flex-wrap justify-center gap-3 mb-10" id="blog-category-filters">
                    <a href="index.php?q=<?php echo urlencode($q); ?>#page-blog" 
                       class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo empty($selectedCat) ? 'bg-primary text-white shadow-md' : 'bg-white text-slate-600 border border-gray-100 hover:bg-gray-50'; ?>"
                       data-category="">
                        Tous
                    </a>
                    <?php foreach ($blogCategories as $cat): ?>
                        <a href="index.php?category=<?php echo urlencode($cat); ?>&q=<?php echo urlencode($q); ?>#page-blog" 
                           class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo ($selectedCat === $cat) ? 'bg-primary text-white shadow-md' : 'bg-white text-slate-600 border border-gray-100 hover:bg-gray-50'; ?>"
                           data-category="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div id="blog-results-container" class="transition-all duration-300 relative min-h-[300px]">
                    <?php if (!empty($posts)) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <?php foreach ($posts as $postItem) : ?>
                            <article class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group transition-all hover:-translate-y-1 hover:shadow-2xl">
                                <div class="relative h-48 overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($postItem['featured_image'] ?: 'https://images.unsplash.com/photo-1526628953301-3e589a6a8b74?auto=format&fit=crop&w=1200', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($postItem['title'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-all duration-700" loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                </div>
                                <div class="p-6">
                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                        <span class="text-xs text-gray-400"><?php echo date('d F Y', strtotime($postItem['created_at'])); ?></span>
                                        <span class="text-xs text-primary font-bold bg-[#E6F7E6] px-3 py-1 rounded-full"><?php echo htmlspecialchars($postItem['category'] ?: 'Actualité', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <h3 class="font-title text-lg font-bold mb-2 leading-snug"><?php echo highlight_search_term($postItem['title'], $q); ?></h3>
                                    <p class="text-gray-500 text-sm mb-4 line-clamp-2 leading-relaxed">
                                        <?php 
                                            $previewText = mb_substr(strip_tags($postItem['excerpt'] ?: $postItem['content']), 0, 150, 'UTF-8');
                                            echo highlight_search_term($previewText, $q); 
                                        ?>
                                    </p>
                                    <a href="blog_post.php?slug=<?php echo urlencode($postItem['slug']); ?>" class="text-primary font-bold inline-flex items-center hover:text-secondary transition">Lire la suite <i class="fas fa-arrow-right ml-2 text-sm"></i></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>




                        <?php if (!empty($posts) && isset($totalPages) && $totalPages > 1) : ?>
                            <nav class="mt-8 flex justify-center items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="index.php?<?php echo http_build_query(array_merge($_GET, ['blog_page' => $page - 1])); ?>#page-blog" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $range = 2;
                                for ($p = 1; $p <= $totalPages; $p++) {
                                    if ($p == 1 || $p == $totalPages || ($p >= $page - $range && $p <= $page + $range)) {
                                        $pageParams = array_merge($_GET, ['blog_page' => $p]);
                                        $url = 'index.php?' . http_build_query($pageParams) . '#page-blog';
                                        $active = $p === $page;
                                        echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="w-10 h-10 flex items-center justify-center rounded-full text-sm font-bold transition-all ' . ($active ? 'bg-primary text-white shadow-lg shadow-green-200' : 'bg-white border border-gray-100 text-gray-600 hover:bg-gray-50') . '">' . $p . '</a>';
                                    } elseif ($p == $page - $range - 1 || $p == $page + $range + 1) {
                                        echo '<span class="text-gray-400">...</span>';
                                    }
                                }
                                ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="index.php?<?php echo http_build_query(array_merge($_GET, ['blog_page' => $page + 1])); ?>#page-blog" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="md:col-span-3 py-20 px-6 bg-white rounded-[3rem] border border-slate-100 text-center shadow-sm">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
                                <i class="fas <?php echo ($q !== '' || $selectedCat !== '') ? 'fa-search' : 'fa-newspaper'; ?> text-slate-200 text-4xl animate-vibrate"></i>
                            </div>
                            <?php if ($q !== '' || $selectedCat !== '') : ?>
                                <h3 class="font-title text-3xl font-black text-slate-900 mb-4 uppercase tracking-tight">Aucun résultat</h3>
                                <p class="text-gray-500 max-w-lg mx-auto leading-relaxed">Désolé, nous n'avons trouvé aucun article correspondant à votre recherche ou à cette catégorie.</p>
                                <a href="index.php?#page-blog" onclick="location.href='index.php?#page-blog'; location.reload();" class="mt-10 inline-block px-8 py-4 bg-primary text-white rounded-full font-bold hover:bg-green-700 transition-all">Voir tous les articles</a>
                            <?php else : ?>
                                <h3 class="font-title text-3xl font-black text-slate-900 mb-4 uppercase tracking-tight">Blog en construction</h3>
                                <p class="text-gray-500 max-w-lg mx-auto leading-relaxed">Nos experts rédigent actuellement de nouveaux articles passionnants. Revenez bientôt pour découvrir nos dernières publications !</p>
                                <button onclick="showPage('contact')" class="mt-10 px-8 py-4 bg-primary text-white rounded-full font-bold hover:bg-green-700 transition-all">Nous contacter</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const blogForm = document.getElementById('blog-search-form');
    const blogContainer = document.getElementById('blog-results-container');
    const categoryFilters = document.getElementById('blog-category-filters');
    const blogSection = document.getElementById('page-blog'); // Récupère la section blog pour le défilement

    /**
     * Génère le HTML pour l'effet de squelette
     */
    const getSkeletonHTML = () => {
        const skeletonCard = `
            <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 animate-pulse">
                <div class="h-48 bg-slate-200"></div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="h-3 w-20 bg-slate-100 rounded"></div>
                        <div class="h-5 w-16 bg-primary/10 rounded-full"></div>
                    </div>
                    <div class="h-6 w-3/4 bg-slate-200 rounded mb-2"></div>
                    <div class="space-y-2">
                        <div class="h-3 w-full bg-slate-50 rounded"></div>
                        <div class="h-3 w-2/3 bg-slate-50 rounded"></div>
                    </div>
                    <div class="h-4 w-24 bg-primary/5 rounded mt-6"></div>
                </div>
            </div>
        `;
        return '<div class="grid grid-cols-1 md:grid-cols-3 gap-8">' + skeletonCard.repeat(3) + '</div>';
    };

    // Initialisation de l'observateur pour les animations au scroll
    const initBlogReveal = () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100', 'translate-y-0');
                    entry.target.classList.remove('opacity-0', 'translate-y-10');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('#blog-results-container article').forEach(article => {
            article.classList.add('transition-all', 'duration-700', 'opacity-0', 'translate-y-10');
            observer.observe(article);
        });
    };

    // Fonction pour récupérer et mettre à jour le contenu du blog
    async function updateBlogContent(ajaxUrl) {
        // Injection d'un overlay avec spinner par-dessus les squelettes de chargement
        blogContainer.innerHTML = `
            <div class="absolute inset-0 z-20 flex items-center justify-center bg-white/60 backdrop-blur-[1px] rounded-[3rem] animate-in fade-in">
                <div class="flex flex-col items-center">
                    <i class="fas fa-circle-notch fa-spin text-5xl text-primary mb-4"></i>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-900">${window.t ? window.t('loading') : 'Chargement en cours'}</p>
                </div>
            </div>
        ` + getSkeletonHTML();
        blogContainer.style.pointerEvents = 'none'; // Désactive les interactions

        try {
            const response = await fetch(ajaxUrl);
            let data;
            
            try {
                data = await response.json();
            } catch (parseError) {
                if (!response.ok) {
                    throw new Error(`Erreur serveur (${response.status})`);
                }
                throw new Error("La réponse du serveur est invalide.");
            }

            if (data.status === 'success') {
                blogContainer.innerHTML = data.html;
                
                // Mise à jour du compteur
                const counter = document.getElementById('blog-results-count');
                if (counter && data.count !== undefined) {
                    counter.innerText = `${data.count} article${data.count > 1 ? 's' : ''}`;
                }

                // Mise à jour du badge de filtre actif
                const urlParams = new URLSearchParams(new URL(ajaxUrl, window.location.origin).search);
                const badge = document.getElementById('blog-active-filter-badge');
                if (badge) badge.classList.toggle('hidden', !urlParams.get('q') && !urlParams.get('category'));

                initBlogReveal();
            } else {
                // Gère les erreurs au niveau de l'application (ex: message d'erreur de la base de données depuis PHP)
                blogContainer.innerHTML = `
                    <div class="md:col-span-3 py-20 px-6 bg-red-50 rounded-[3rem] border border-red-200 text-center shadow-sm animate-in">
                        <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-8">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl"></i>
                        </div>
                        <h3 class="font-title text-3xl font-black text-red-800 mb-4 uppercase tracking-tight">Erreur serveur</h3>
                        <p class="text-red-700 max-w-lg mx-auto">${data.message || 'Une erreur inattendue est survenue.'}</p>
                    </div>
                `;
            }
            // Défilement fluide vers le haut de la section blog après le chargement
            blogSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            console.error('Erreur AJAX:', error);
            // Gère les erreurs réseau ou les erreurs de parsing JSON
            blogContainer.innerHTML = `
                <div class="md:col-span-3 py-20 px-6 bg-red-50 rounded-[3rem] border border-red-200 text-center shadow-sm animate-in">
                    <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl"></i>
                    </div>
                    <h3 class="font-title text-3xl font-black text-red-800 mb-4 uppercase tracking-tight">Erreur</h3>
                    <p class="text-red-700 max-w-lg mx-auto">${error.message}</p>
                </div>
            `;
        } finally {
            blogContainer.style.pointerEvents = 'auto'; // Réactive les interactions
        }
    }

    // Fonction pour effectuer une recherche AJAX et mettre à jour l'historique du navigateur
    function performAjaxSearch(ajaxUrl, browserUrl) {
        updateBlogContent(ajaxUrl);
        history.pushState(null, '', browserUrl); // Met à jour l'URL du navigateur
    }

    blogForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(blogForm);
        const params = new URLSearchParams(formData);
        const ajaxUrl = 'search_blog.php?' + params.toString();
        const browserUrl = 'index.php?' + params.toString() + '#page-blog'; // URL pour le navigateur (avec l'ancre)
        performAjaxSearch(ajaxUrl, browserUrl);
    });

    // Gestion des filtres de catégories
    if (categoryFilters) {
        categoryFilters.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link) {
                e.preventDefault();
                const url = new URL(link.href);
                const category = link.getAttribute('data-category');
                
                // Mettre à jour le champ caché du formulaire de recherche
                let catInput = blogForm.querySelector('input[name="category"]');
                if (category) {
                    if (!catInput) {
                        catInput = document.createElement('input');
                        catInput.type = 'hidden';
                        catInput.name = 'category';
                        blogForm.appendChild(catInput);
                    }
                    catInput.value = category;
                } else if (catInput) {
                    catInput.remove();
                }

                // Mise à jour visuelle des boutons
                categoryFilters.querySelectorAll('a').forEach(a => a.className = "px-5 py-2 rounded-full text-sm font-bold transition-all bg-white text-slate-600 border border-gray-100 hover:bg-gray-50");
                link.className = "px-5 py-2 rounded-full text-sm font-bold transition-all bg-primary text-white shadow-md";

                const ajaxUrl = 'search_blog.php' + url.search;
                performAjaxSearch(ajaxUrl, url.href);
            }
        });
    }

    // Gestion des clics sur les liens de pagination (délégation d'événements)
    blogContainer.addEventListener('click', async (e) => {
        const link = e.target.closest('a'); // Trouve le lien le plus proche
        // Vérifie si c'est un lien de pagination ou de catégorie dans la section blog
        if (link && link.href.includes('#page-blog') && blogContainer.contains(link)) { // Utilise blogContainer.contains pour une meilleure vérification
            e.preventDefault(); // Empêche le comportement par défaut du lien
            const url = new URL(link.href);
            const ajaxUrl = 'search_blog.php' + url.search; // URL pour la requête AJAX
            const browserUrl = url.href; // L'URL du lien est déjà la bonne pour le navigateur (avec l'ancre)
            performAjaxSearch(ajaxUrl, browserUrl);
        }
    });

    // Gérer les boutons "précédent" et "suivant" du navigateur
    window.addEventListener('popstate', (event) => {
        const currentUrl = new URL(window.location.href);
        // Si l'utilisateur revient sur la section blog via l'historique
        if (currentUrl.hash === '#page-blog') {
            const ajaxUrl = 'search_blog.php' + currentUrl.search;
            updateBlogContent(ajaxUrl); // Met à jour le contenu sans ajouter une nouvelle entrée à l'historique (car popstate gère déjà l'historique)
        }
    });

    // Premier lancement de l'animation
    initBlogReveal();
});
</script>

        <!-- PAGE: REGISTER -->
        <section id="page-register" class="page-content hidden py-16 bg-white">
            <div class="container mx-auto px-6 max-w-4xl">
                <div class="bg-light rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row">
                    <div class="bg-primary p-12 text-white md:w-1/3 flex flex-col justify-center">
                        <h2 class="font-title text-3xl font-bold mb-6">Rejoignez ZoeTIC</h2>
                        <p class="text-green-100 mb-8">Plus que quelques étapes pour valider votre inscription à la session d'automne 2024.</p>
                        <ul class="space-y-4 text-sm font-semibold">
                            <li class="flex items-center"><i class="fas fa-check-circle mr-3 text-secondary"></i> Dossier simplifié</li>
                            <li class="flex items-center"><i class="fas fa-check-circle mr-3 text-secondary"></i> Financement possible</li>
                            <li class="flex items-center"><i class="fas fa-check-circle mr-3 text-secondary"></i> Place garantie sous 48h</li>
                        </ul>
                    </div>
                    <div class="p-12 md:w-2/3 bg-white">
                        <?php if (!empty($registrationMessage)) : ?>
                            <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm <?php echo $registrationStatus === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                                <?php echo htmlspecialchars($registrationMessage, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <form id="registrationForm" onsubmit="handleRegistration(event)" class="space-y-6 <?php echo $registrationStatus === 'success' ? 'hidden' : ''; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Prénom</label>
                                    <input type="text" name="first_name" required class="w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Nom</label>
                                    <input type="text" name="last_name" required class="w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 transition-all">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Email</label>
                                    <input type="email" name="email" required class="w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Téléphone (WhatsApp)</label>
                                    <input type="tel" id="register-phone" name="phone" placeholder="+225 07 00 00 00 00" required class="w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 transition-all">
                                    <span id="phone-feedback" class="hidden text-[10px] font-bold mt-1"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Formation choisie</label>
                                <select id="formationSelect" name="formation_id" onchange="document.getElementById('formationTitleInput').value = this.options[this.selectedIndex].text;" class="w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 bg-transparent transition-all" required>
                                    <?php foreach ($availableFormations as $formation) : ?>
                                        <option value="<?php echo htmlspecialchars($formation['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($formation['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="formationTitleInput" name="formation_title" value="<?php echo !empty($availableFormations[0]['title']) ? htmlspecialchars($availableFormations[0]['title'], ENT_QUOTES, 'UTF-8') : 'Session de formation'; ?>">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Votre message / Attentes</label>
                                <textarea name="message" rows="3" class="w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 transition-all resize-none"></textarea>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="terms" required class="text-primary focus:ring-primary h-4 w-4 rounded">
                                <label for="terms" class="text-xs text-gray-500">J'accepte les conditions de traitement de mes données.</label>
                            </div>
                            <button type="submit" class="w-full bg-secondary text-white font-bold py-4 rounded-xl shadow-lg hover:bg-orange-600 transition-all flex items-center justify-center space-x-3">
                                <span>Envoyer ma candidature</span>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>

                        <div id="successMessage" class="text-center py-8 <?php echo $registrationStatus === 'success' ? '' : 'hidden'; ?>" aria-hidden="<?php echo $registrationStatus === 'success' ? 'false' : 'true'; ?>">
                            <div class="w-20 h-20 bg-green-100 text-primary rounded-full flex items-center justify-center mx-auto mb-4 text-3xl success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h3 class="font-title font-bold text-2xl mb-2">Demande Reçue !</h3>
                            <p class="text-gray-500">Un conseiller ZoeTIC vous contactera sous 24h sur WhatsApp.</p>
                            <button onclick="showPage('home')" class="mt-6 text-primary font-bold underline">Retour à l'accueil</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PAGE: CONTACT -->
        <section id="page-contact" class="page-content hidden py-24 bg-slate-50/50">
            <?php 
            // Logique de statut d'ouverture (Ex: Lun-Ven, 08h-18h)
            $currentHour = (int)date('G');
            $dayOfWeek = (int)date('w'); // 1 (Lun) à 5 (Ven)
            $isOfficeOpen = ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentHour >= 8 && $currentHour < 18);
            ?>
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-20">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-black uppercase tracking-widest mb-6">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo $isOfficeOpen ? 'bg-primary' : 'bg-red-500'; ?> opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 <?php echo $isOfficeOpen ? 'bg-primary' : 'bg-red-500'; ?>"></span>
                            </span>
                            <?php echo $isOfficeOpen ? 'Nos bureaux sont ouverts' : 'Bureaux fermés (Réponse sous 24h)'; ?>
                        </div>
                        <h2 class="font-title text-4xl font-bold text-dark mb-6">Contactez-nous</h2>
                        <p class="text-gray-600 mb-10 text-lg leading-relaxed">Nous sommes à votre écoute pour définir ensemble votre parcours de formation personnalisé. Passez nous voir ou écrivez-nous.</p>
                        
                        <div class="space-y-8">
                            <div class="flex items-start space-x-6 group">
                                <div class="w-12 h-12 bg-white rounded-2xl shadow-md flex items-center justify-center text-primary flex-shrink-0">
                                    <i class="fas fa-map-marker-alt text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">Notre Siège</h4>
                                    <p class="text-gray-500 text-sm leading-relaxed"><?php echo htmlspecialchars($siteSettings['contact_address'] ?? 'Cocody Angré, 7e Tranche, Abidjan, Côte d\'Ivoire', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-6 group">
                                <div class="w-12 h-12 bg-white rounded-2xl shadow-md flex items-center justify-center text-primary flex-shrink-0">
                                    <i class="fas fa-phone-alt text-xl"></i>
                                </div>
                                <div class="flex-grow">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-bold text-lg mb-1">Téléphone</h4>
                                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($siteSettings['contact_phone'] ?? '+2250574677246', ENT_QUOTES, 'UTF-8'); ?>', this)" class="text-[10px] font-black uppercase text-slate-300 hover:text-primary transition-colors">Copier</button>
                                    </div>
                                    <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($siteSettings['contact_phone'] ?? '+225 05 74 67 72 46', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-6 group">
                                <div class="w-12 h-12 bg-white rounded-2xl shadow-md flex items-center justify-center text-primary flex-shrink-0">
                                    <i class="fas fa-envelope text-xl"></i>
                                </div>
                                <div class="flex-grow">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-bold text-lg mb-1">Email</h4>
                                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($siteSettings['contact_email'] ?? 'contact@zoetic.ci', ENT_QUOTES, 'UTF-8'); ?>', this)" class="text-[10px] font-black uppercase text-slate-300 hover:text-primary transition-colors">Copier</button>
                                    </div>
                                    <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($siteSettings['contact_email'] ?? 'contact@zoetic.ci', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Carte interactive (Placeholder Google Maps stylisé) -->
                        <div class="mt-12 rounded-[2.5rem] overflow-hidden border border-slate-200 shadow-lg h-64 relative group">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3972.235613374241!2d-3.9877!3d5.3854!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMjMnMDcuNCJOIDPCsDU5JzEzLjIiVw!5e0!3m2!1sfr!2sci!4v1620000000000!5m2!1sfr!2sci" 
                                class="w-full h-full border-0 grayscale hover:grayscale-0 transition-all duration-700" 
                                allowfullscreen="" loading="lazy">
                            </iframe>
                            <div class="absolute bottom-4 left-4 right-4 bg-white/90 backdrop-blur-md p-4 rounded-2xl flex items-center justify-between border border-white/50 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity translate-y-2 group-hover:translate-y-0 duration-300">
                                <span class="text-xs font-bold text-slate-900">ZoeTIC • Cocody Angré</span>
                                <a href="https://maps.google.com" target="_blank" class="text-[10px] font-black uppercase text-primary">Itinéraire <i class="fas fa-external-link-alt ml-1"></i></a>
                            </div>
                        </div>

                        <!-- Social links -->
                        <div id="social-links-container" class="flex space-x-4 mt-12">
                        </div>
                    </div>
                    <div class="bg-white p-12 rounded-[3rem] shadow-2xl border border-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[5rem] -mr-10 -mt-10"></div>
                        
                        <h3 class="font-title text-2xl font-bold mb-2">Envoyez un message</h3>
                        <p class="text-gray-400 text-sm mb-8">Réponse garantie en moins de 24 heures ouvrées.</p>

                        <form id="contactForm" onsubmit="handleContact(event)" class="space-y-4">
                            <div class="relative group">
                                <i class="far fa-user absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" name="name" placeholder="Votre Nom Complet" class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-6 py-4 outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm font-bold" required>
                            </div>
                            <div class="relative group">
                                <i class="far fa-envelope absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="email" name="email" placeholder="votre@email.com" class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-6 py-4 outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm font-bold" required>
                            </div>
                            <div class="relative group">
                                <i class="fas fa-tag absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <select name="subject" class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-6 py-4 outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm font-bold appearance-none" required>
                                    <option value="" disabled selected>Objet de votre message</option>
                                    <option value="Information Formation">Information sur une formation</option>
                                    <option value="Inscription">Demande d'inscription</option>
                                    <option value="Partenariat">Partenariat</option>
                                    <option value="Autre">Autre demande</option>
                                </select>
                            </div>
                            <div class="relative group">
                                <textarea name="message" placeholder="Comment pouvons-nous vous aider ?" rows="4" class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 outline-none focus:ring-4 focus:ring-primary/10 transition-all resize-none text-sm font-bold" required></textarea>
                            </div>
                            
                            <button type="submit" class="w-full bg-primary text-white font-black uppercase tracking-widest text-xs py-5 rounded-2xl shadow-xl shadow-green-900/10 hover:bg-green-700 transition-all flex items-center justify-center gap-3 active:scale-95 disabled:opacity-50 disabled:pointer-events-none">
                                <span class="btn-text">Envoyer le message</span>
                                <i class="fas fa-paper-plane text-[10px]"></i>
                            </button>
                        </form>
                        <div id="contactSuccess" class="hidden text-center py-8" aria-hidden="true">
                            <div class="w-20 h-20 bg-green-100 text-primary rounded-full flex items-center justify-center mx-auto mb-4 text-3xl success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h3 class="font-title font-bold text-2xl mb-2">Message envoyé !</h3>
                            <p class="text-gray-500">Nous revenons vers vous sous 24h.</p>
                            <button onclick="showPage('home')" class="mt-6 text-primary font-bold underline">Retour à l'accueil</button>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="mt-32 max-w-4xl mx-auto">
                    <div class="text-center mb-12">
                        <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Assistance</p>
                        <h2 class="text-3xl font-title font-bold text-slate-900">Questions Fréquentes</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <?php 
                        $faqs = [
                            ["q" => "Quels sont les prérequis pour s'inscrire ?", "a" => "La plupart de nos formations débutantes ne nécessitent aucun prérequis technique, seulement une bonne maîtrise de l'outil informatique."],
                            ["q" => "Les formations sont-elles certifiantes ?", "a" => "Oui, ZoeTIC délivre des certificats de fin de formation reconnus par nos partenaires et valorisables sur votre CV."],
                            ["q" => "Proposez-vous des cours du soir ou en ligne ?", "a" => "Absolument. Nous avons des sessions flexibles (soir et week-end) ainsi que des modules en distanciel."],
                        ];
                        foreach($faqs as $index => $faq): 
                        ?>
                        <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
                            <button onclick="toggleFaq(<?php echo $index; ?>)" class="w-full px-8 py-6 text-left flex items-center justify-between group">
                                <span class="font-bold text-slate-700 group-hover:text-primary transition-colors"><?php echo $faq['q']; ?></span>
                                <i id="faq-icon-<?php echo $index; ?>" class="fas fa-plus text-xs text-slate-300 group-hover:text-primary transition-all"></i>
                            </button>
                            <div id="faq-ans-<?php echo $index; ?>" class="hidden px-8 pb-6 text-sm text-slate-500 leading-relaxed animate-in">
                                <?php echo $faq['a']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- PAGE: CONDITIONS GÉNÉRALES -->
        <section id="page-terms" class="page-content hidden py-24 bg-white">
            <div class="container mx-auto px-6 max-w-4xl">
                <h2 class="font-title text-4xl font-bold text-dark mb-8 text-center">Conditions Générales d'Utilisation</h2>
                <div class="prose prose-slate max-w-none text-gray-600 leading-relaxed space-y-6">
                    <?php echo $legalDocs['terms'] ?? 'Contenu en cours de rédaction...'; ?>
                </div>
            </div>
        </section>

        <!-- PAGE: POLITIQUE DE CONFIDENTIALITÉ -->
        <section id="page-privacy" class="page-content hidden py-24 bg-light">
            <div class="container mx-auto px-6 max-w-4xl">
                <h2 class="font-title text-4xl font-bold text-dark mb-8 text-center">Politique de Confidentialité</h2>
                <div class="prose prose-slate max-w-none text-gray-600 leading-relaxed space-y-6">
                    <?php echo $legalDocs['privacy'] ?? 'Contenu en cours de rédaction...'; ?>
                </div>
            </div>
        </section>

        <!-- ZoeTIC Chatbot UI -->
        <div id="chatbot-container" class="fixed bottom-28 right-8 z-[60] flex flex-col items-end pointer-events-none">
            <!-- Chat Window -->
            <div id="chatbot-window" class="hidden w-80 md:w-96 bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 flex flex-col overflow-hidden mb-4 animate-in pointer-events-auto origin-bottom-right">
                <!-- Header -->
                <div class="bg-primary p-6 text-white flex items-center justify-between shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center backdrop-blur-md">
                            <i class="fas fa-robot text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm">Assistant ZoeTIC</h4>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                <p class="text-[10px] opacity-80 uppercase tracking-widest font-black">En ligne</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button id="chatbot-mute-btn" onclick="toggleChatMute()" class="hover:bg-white/10 p-2 rounded-xl transition-colors" title="Désactiver le son">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <button onclick="toggleChatbot()" class="hover:bg-white/10 p-2 rounded-xl transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- Messages Area -->
                <div id="chatbot-messages" class="h-80 overflow-y-auto p-6 space-y-4 bg-slate-50/50 no-scrollbar">
                    <div class="flex justify-start">
                        <div class="bg-white p-4 rounded-3xl rounded-tl-none shadow-sm border border-slate-100 max-w-[85%]">
                            <p class="text-sm text-slate-700 leading-relaxed font-medium">Bonjour ! 👋 Je suis votre conseiller d'orientation **ZoeTIC**. Prêt à propulser votre carrière ? <br><br>Dites-moi ce que vous recherchez :</p>
                        </div>
                    </div>
                    <div class="chat-suggestions flex flex-wrap gap-2 animate-in mb-4" style="animation-delay: 400ms">
                        <button onclick="handleChatSubmit(null, 'Quelles sont vos formations ?')" class="text-[9px] bg-white border border-slate-100 px-3 py-2 rounded-xl font-black text-slate-600 hover:border-primary hover:text-primary transition-all shadow-sm uppercase tracking-widest">🚀 Nos Parcours</button>
                        <button onclick="handleChatSubmit(null, 'Où se situe votre centre ?')" class="text-[9px] bg-white border border-slate-100 px-3 py-2 rounded-xl font-black text-slate-600 hover:border-primary hover:text-primary transition-all shadow-sm uppercase tracking-widest">📍 Localisation</button>
                        <button onclick="handleChatSubmit(null, 'Quels sont les tarifs ?')" class="text-[9px] bg-white border border-slate-100 px-3 py-2 rounded-xl font-black text-slate-600 hover:border-primary hover:text-primary transition-all shadow-sm uppercase tracking-widest">💰 Tarifs</button>
                        <button onclick="handleChatSubmit(null, 'Comment s\'inscrire ?')" class="text-[9px] bg-white border border-slate-100 px-3 py-2 rounded-xl font-black text-slate-600 hover:border-primary hover:text-primary transition-all shadow-sm uppercase tracking-widest">📝 Inscription</button>
                    </div>
                </div>
                <!-- Input Field -->
                <div id="chatbot-rating-widget" style="display: none; padding: 0 16px 12px 16px;"></div>
                <form id="chatbot-form" onsubmit="handleChatSubmit(event)" class="p-4 bg-white border-t border-slate-100 flex gap-2">
                    <input type="text" id="chatbot-input" placeholder="Posez votre question..." autocomplete="off" class="flex-grow px-5 py-3 bg-slate-50 border-none rounded-full text-sm font-bold focus:ring-4 focus:ring-primary/5 outline-none transition-all">
                    <button type="submit" class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center hover:bg-green-700 transition-all shadow-lg active:scale-90">
                        <i class="fas fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>
            <!-- Floating Launcher Button -->
            <button onclick="toggleChatbot()" class="pointer-events-auto w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center text-2xl shadow-2xl hover:scale-110 active:scale-95 transition-all cursor-pointer border-4 border-white">
                <i id="chatbot-toggle-icon" class="fas fa-comments"></i>
            </button>
        </div>

        <!-- PAGE: MENTIONS LÉGALES -->
        <section id="page-legal" class="page-content hidden py-24 bg-white">
            <div class="container mx-auto px-6 max-w-4xl">
                <h2 class="font-title text-4xl font-bold text-dark mb-8 text-center">Mentions Légales</h2>
                <div class="prose prose-slate max-w-none text-gray-600 leading-relaxed space-y-6">
                    <?php echo $legalDocs['legal'] ?? 'Contenu en cours de rédaction...'; ?>
                </div>
            </div>
        </section>
    </main>

<script>
    /**
     * Système de navigation fluide entre les sections
     */
    window.showPage = function(pageId) {
        const pages = document.querySelectorAll('.page-content');
        pages.forEach(p => p.classList.add('hidden'));
        
        const target = document.getElementById('page-' + pageId);
        if (target) {
            target.classList.remove('hidden');
            target.classList.add('animate-in');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (history.pushState) history.pushState(null, null, '#' + pageId);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Validation du téléphone en temps réel (Format Côte d'Ivoire)
        const phoneInput = document.getElementById('register-phone');
        const phoneFeedback = document.getElementById('phone-feedback');
        
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                const val = this.value.trim();
                // Regex universelle autorisant tous les indicatifs de pays
                const phoneRegex = /^\+?[0-9\s().-]{6,25}$/;
                
                if (val === '') {
                    this.className = "w-full border-b-2 border-gray-100 focus:border-primary outline-none py-2 transition-all";
                    phoneFeedback.classList.add('hidden');
                } else if (phoneRegex.test(val)) {
                    // État valide : vert
                    this.className = "w-full border-b-2 border-primary focus:border-primary outline-none py-2 transition-all text-primary";
                    phoneFeedback.innerText = (window.t ? window.t('phone_valid') : 'Format valide ✓');
                    phoneFeedback.className = "text-[10px] font-bold text-primary mt-1 block";
                } else {
                    // État invalide : rouge
                    this.className = "w-full border-b-2 border-red-500 focus:border-red-500 outline-none py-2 transition-all text-red-500";
                    phoneFeedback.innerText = (window.t ? window.t('phone_format') : 'Format attendu: +225 XX XX XX XX XX (10 chiffres)');
                    phoneFeedback.className = "text-[10px] font-bold text-red-500 mt-1 block";
                }
            });
        }

        // Gérer les paramètres d'URL (ex: ?formation=Nom) pour pré-remplir l'inscription
        const urlParams = new URLSearchParams(window.location.search);
        const formationParam = urlParams.get('formation');
        if (formationParam) {
            setTimeout(() => window.openRegisterForm(decodeURIComponent(formationParam)), 500);
        } else if (window.location.hash) {
            const page = window.location.hash.substring(1).replace('page-', '');
            if (['home', 'formations', 'blog', 'register', 'contact'].includes(page)) {
                showPage(page);
            }
        }
    });

    /**
     * Gestion de l'inscription via AJAX pour une expérience moderne
     */
    window.handleRegistration = async function(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const btnText = btn.querySelector('span');
        const btnIcon = btn.querySelector('i');
        const successDiv = document.getElementById('successMessage');
        const phoneFeedback = document.getElementById('phone-feedback');

        // Vérification de sécurité locale pour le téléphone
        if (phoneFeedback && phoneFeedback.classList.contains('text-red-500')) {
            document.getElementById('register-phone').focus();
            return;
        }

        btn.disabled = true;
        const originalText = btnText.innerText;
        btnText.innerText = "Validation du dossier...";
        btnIcon.className = 'fas fa-circle-notch fa-spin';

        try {
            const formData = new FormData(form);
            const response = await fetch('save_enrollment.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                form.classList.add('hidden');
                successDiv.classList.remove('hidden');
                successDiv.classList.add('animate-in');
                form.reset();
                successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                alert(data.message || 'Une erreur est survenue.');
                btn.disabled = false;
                btnText.innerText = originalText;
                btnIcon.className = 'fas fa-paper-plane';
            }
        } catch (err) {
            alert('Erreur de connexion. Veuillez vérifier votre accès internet.');
            btn.disabled = false;
            btnText.innerText = originalText;
            btnIcon.className = 'fas fa-paper-plane';
        }
    };

    /**
     * Ouvre le formulaire d'inscription et pré-sélectionne la formation choisie
     */
    window.openRegisterForm = function(title) {
        showPage('register');
        const select = document.getElementById('formationSelect');
        if (select) {
            const options = Array.from(select.options);
            const index = options.findIndex(opt => opt.text.trim() === title.trim());
            if (index !== -1) {
                select.selectedIndex = index;
                const titleInput = document.getElementById('formationTitleInput');
                if (titleInput) titleInput.value = title;
            }
        }
    };

    /**
     * Gestion du formulaire de contact via AJAX
     */
    async function handleContact(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const btnText = btn.querySelector('.btn-text');
        const btnIcon = btn.querySelector('i');
        const successDiv = document.getElementById('contactSuccess');
        
        // État de chargement
        btn.disabled = true;
        const originalText = btnText.innerText;
        btnText.innerText = (window.t ? window.t('sending') : 'Envoi en cours...');
        btnIcon.className = 'fas fa-circle-notch fa-spin text-[10px]';

        try {
            const formData = new FormData(form);
            const response = await fetch('save_contact.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                form.classList.add('hidden');
                successDiv.classList.remove('hidden');
                successDiv.classList.add('animate-in');
                form.reset();
            } else {
                alert(data.message || (window.t ? window.t('error_occurred') : 'Une erreur est survenue.'));
                btn.disabled = false;
                btnText.innerText = originalText;
                btnIcon.className = 'fas fa-paper-plane text-[10px]';
            }
        } catch (err) {
            console.error(err);
            alert(window.t ? window.t('server_connection_error') : 'Erreur de connexion au serveur.');
            btn.disabled = false;
            btnText.innerText = originalText;
            btnIcon.className = 'fas fa-paper-plane text-[10px]';
        }
    }

    /**
     * Fonction de copie rapide
     */
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const originalText = btn.innerText;
            btn.innerText = (window.t ? window.t('copied') : 'Copié !');
            btn.classList.add('text-primary');
            setTimeout(() => { btn.innerText = originalText; btn.classList.remove('text-primary'); }, 2000);
        });
    }

    /**
     * Gestion de l'accordéon FAQ
     */
    function toggleFaq(index) {
        const ans = document.getElementById(`faq-ans-${index}`);
        const icon = document.getElementById(`faq-icon-${index}`);
        const isOpen = !ans.classList.contains('hidden');
        
        // Fermer tous les autres
        document.querySelectorAll('[id^="faq-ans-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="faq-icon-"]').forEach(el => el.className = 'fas fa-plus text-xs text-slate-300');

        if (!isOpen) {
            ans.classList.remove('hidden');
            icon.className = 'fas fa-minus text-xs text-primary';
        }
    }

    // Initialisation du son de notification (un "pop" discret)
    const chatbotReplySound = new Audio('https://assets.mixkit.co/active_storage/sfx/2357/2357-preview.mp3');

    // Gestion du mode muet
    let isChatbotMuted = localStorage.getItem('chatbotMuted') === 'true';
    
    const updateMuteIcon = () => {
        const btn = document.getElementById('chatbot-mute-btn');
        if (btn) {
            btn.innerHTML = isChatbotMuted ? '<i class="fas fa-volume-mute"></i>' : '<i class="fas fa-volume-up"></i>';
            btn.title = isChatbotMuted ? 'Activer le son' : 'Désactiver le son';
        }
    };
    updateMuteIcon();

    window.toggleChatMute = function() {
        isChatbotMuted = !isChatbotMuted;
        localStorage.setItem('chatbotMuted', isChatbotMuted);
        updateMuteIcon();
    };

/**
 * Logique de filtrage et de navigation pour le catalogue de formations
 */
let currentCategory = 'All';

/**
 * Filtre les formations par catégorie
 * @param {string} category - Le nom de la catégorie ou 'All'/'Free'
 */
function filterFormationCategory(category) {
    currentCategory = category;
    
    // Mise à jour visuelle des boutons de filtre (état actif)
    document.querySelectorAll('.category-filter-btn').forEach(btn => {
        const btnCat = btn.getAttribute('data-category');
        if (btnCat === category) {
            btn.classList.remove('bg-white', 'text-slate-600', 'border-gray-100');
            btn.classList.add('bg-primary', 'text-white', 'shadow-lg', 'shadow-green-100');
        } else {
            btn.classList.add('bg-white', 'text-slate-600', 'border-gray-100');
            btn.classList.remove('bg-primary', 'text-white', 'shadow-lg', 'shadow-green-100');
        }
    });
    applyFilters();
}

document.addEventListener('DOMContentLoaded', () => {
    // Typewriter for hero title
    const hero = document.getElementById('hero-title');
    if (hero) {
        const text = hero.dataset.text || '';
        let i = 0;
        hero.textContent = '';
        const tick = () => {
            hero.textContent = text.slice(0, i);
            i++;
            if (i <= text.length) setTimeout(tick, 30);
        };
        setTimeout(tick, 300);
    }

    // Simple carousel for featured formations
    const carousel = document.getElementById('featured-carousel');
    if (carousel) {
        const slides = Array.from(carousel.querySelectorAll('.slide'));
        let idx = 0;
        const show = (n) => {
            slides.forEach((s, i) => s.classList.toggle('active', i === n));
        };
        const next = () => { idx = (idx + 1) % slides.length; show(idx); };
        const prev = () => { idx = (idx - 1 + slides.length) % slides.length; show(idx); };
        const nextBtn = document.getElementById('carousel-next');
        const prevBtn = document.getElementById('carousel-prev');
        if (nextBtn) nextBtn.addEventListener('click', () => { next(); resetTimer(); });
        if (prevBtn) prevBtn.addEventListener('click', () => { prev(); resetTimer(); });

        let timer = setInterval(next, 4000);
        const resetTimer = () => { clearInterval(timer); timer = setInterval(next, 4000); };
        carousel.addEventListener('mouseenter', () => clearInterval(timer));
        carousel.addEventListener('mouseleave', () => { timer = setInterval(next, 4000); });
    }

    // Counters animation logic
    const counters = document.querySelectorAll('.stat-counter');
    if (counters.length) {
        const animateSingleCounter = (counter) => {
            const target = parseInt(counter.getAttribute('data-target') || '0', 10);
            const currentVal = parseInt(counter.textContent.replace(/[^0-9]/g, '') || '0', 10);
            if (target === currentVal) return;
            const start = currentVal;
            const duration = 1400;
            const startTime = performance.now();
            const step = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const value = Math.floor(progress * (target - start) + start);
                counter.textContent = value.toLocaleString();
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };

        const startInitialAnimation = () => {
            counters.forEach(counter => animateSingleCounter(counter));
        };

        let statsInterval = null;
        
        // Fonction pour synchroniser avec la DB
        const updateStatsRealTime = async () => {
            try {
                const response = await fetch('api_stats.php');
                const result = await response.json();
                if (result.status === 'success') {
                    const mapping = {
                        'stat-students': result.data.students,
                        'stat-formations': result.data.formations,
                        'stat-blog': result.data.blog_published,
                        'stat-enrollments': result.data.enrollments
                    };
                    Object.entries(mapping).forEach(([id, val]) => {
                        const el = document.getElementById(id);
                        if (el && parseInt(el.getAttribute('data-target')) !== val) {
                            el.setAttribute('data-target', val);
                            animateSingleCounter(el);
                        }
                    });
                }
            } catch (err) { /* Erreur silencieuse */ }
        };

        const obs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateStatsRealTime();
                    startInitialAnimation(); // Lance l'animation immédiate (PHP)
                    updateStatsRealTime();    // Puis vérifie les mises à jour (AJAX)
                    if (!statsInterval) statsInterval = setInterval(updateStatsRealTime, 30000);
                } else {
                    if (statsInterval) {
                        clearInterval(statsInterval);
                        statsInterval = null;
                    }
                }
            });
        }, { threshold: 0.1 });

        const targetSection = document.getElementById('stats-display-section');
        if (targetSection) obs.observe(targetSection);
    }
    
    applyFilters();
});

/**
 * Applique les filtres combinés (catégorie + recherche par mot-clé)
 */
function applyFilters() {
    const keywordInput = document.getElementById('formationKeywordSearch');
    // Normalisation pour ignorer les accents et la casse
    const normalize = (str) => str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
    const keyword = keywordInput ? normalize(keywordInput.value) : '';
    const cards = document.querySelectorAll('.formation-card-item');
    const noResults = document.getElementById('formationNoResults');
    let hasVisible = false;

    cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        const title = normalize(card.querySelector('h3').innerText);
        const price = parseFloat(card.getAttribute('data-price') || 0);
        
        // Logique de filtrage combinée
        const matchCategory = (currentCategory === 'All') || 
                            (currentCategory === 'Free' && price <= 0) || 
                            (cat === currentCategory);
        
        const matchKeyword = title.includes(keyword);

        if (matchCategory && matchKeyword) {
            card.style.setProperty('display', 'flex', 'important');
            card.classList.add('animate-in');
            hasVisible = true;
        } else {
            card.style.setProperty('display', 'none', 'important');
            card.classList.remove('animate-in');
        }
    });

    // Affichage du message "Aucun résultat" si nécessaire
    if (noResults) noResults.classList.toggle('hidden', hasVisible);

    // Gérer la visibilité du bouton pour vider le champ
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) clearBtn.classList.toggle('hidden', keywordInput.value.length === 0);
}

/**
 * Debounce pour la recherche textuelle pour éviter trop de calculs
 */
let filterTimeout;
function debouncedApplyFilters() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFilters, 300);
}

/**
 * Tri des formations par prix (Croissant / Décroissant)
 */
function sortFormations(order) {
    const container = document.getElementById('formationsListContainer');
    if (!container) return;
    
    const cards = Array.from(container.querySelectorAll('.formation-card-item'));
    cards.sort((a, b) => {
        const priceA = parseFloat(a.getAttribute('data-price') || 0);
        const priceB = parseFloat(b.getAttribute('data-price') || 0);
        return order === 'asc' ? priceA - priceB : priceB - priceA;
    });
    
    cards.forEach(card => container.appendChild(card));
}

/**
 * Vide le champ de recherche et réinitialise les filtres
 */
function clearFormationSearch() {
    const input = document.getElementById('formationKeywordSearch');
    if (input) {
        input.value = '';
        applyFilters();
        input.focus();
    }
}

/**
 * Filtre et scroll vers la section formations (utilisé depuis l'accueil)
 */
function filterAndScroll(category) {
    if (typeof showPage === 'function') showPage('formations');
    filterFormationCategory(category);
    
    const target = document.getElementById('page-formations');
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

    // Chatbot Logic
    window.toggleChatbot = function() {
        const windowEl = document.getElementById('chatbot-window');
        const iconEl = document.getElementById('chatbot-toggle-icon');
        const isHidden = windowEl.classList.contains('hidden');
        
        if (isHidden) {
            windowEl.classList.remove('hidden');
            windowEl.classList.add('flex');
            iconEl.className = 'fas fa-chevron-down';
        } else {
            windowEl.classList.add('hidden');
            windowEl.classList.remove('flex');
            iconEl.className = 'fas fa-comments';
        }
    };

    window.handleChatSubmit = async function(e, presetText = null) {
        if (e) e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const messages = document.getElementById('chatbot-messages');
        const text = presetText || input.value.trim();
        
        // Masquer les suggestions après la première interaction
        const suggestions = messages.querySelector('.chat-suggestions');
        if (suggestions) suggestions.style.display = 'none';
        
        if (!text) return;
        
        // Message Utilisateur
        const userMsg = document.createElement('div');
        userMsg.className = 'flex justify-end animate-in';
        userMsg.innerHTML = `
            <div class="bg-primary text-white p-4 rounded-3xl rounded-tr-none shadow-md max-w-[85%] relative mb-6">
                <p class="text-sm font-bold">${text}</p>
                <span class="text-[8px] opacity-50 absolute -bottom-5 right-0 text-slate-400 font-black uppercase tracking-tighter">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
            </div>
        `;
        messages.appendChild(userMsg);
        if (!presetText) input.value = '';
        messages.scrollTop = messages.scrollHeight;
        
        // Indicateur de saisie
        const typingId = 'typing-' + Date.now();
        const typingMsg = document.createElement('div');
        typingMsg.className = 'flex justify-start animate-in';
        typingMsg.id = typingId;
        typingMsg.innerHTML = `
            <div class="bg-white p-4 rounded-3xl rounded-tl-none shadow-sm border border-slate-100 flex items-center gap-3">
                <div class="w-2 h-2 bg-primary rounded-full animate-bounce"></div>
                <div class="flex gap-1 opacity-40">
                    <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span>
                    <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span>
                    <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span>
                </div>
            </div>
        `;
        messages.appendChild(typingMsg);
        messages.scrollTop = messages.scrollHeight;
        
        try {
            const response = await fetch('chatbot_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'message=' + encodeURIComponent(text)
            });
            const data = await response.json();
            
            typingMsg.remove();
            
            const botMsg = document.createElement('div');
            botMsg.className = 'flex justify-start animate-in';
            botMsg.innerHTML = `
                <div class="bg-white p-4 rounded-3xl rounded-tl-none shadow-sm border border-slate-100 max-w-[85%] relative mb-6">
                    <p class="text-sm text-slate-700 leading-relaxed font-medium">${data.reply}</p>
                    <span class="text-[8px] opacity-50 absolute -bottom-5 left-0 text-slate-400 font-black uppercase tracking-tighter">${data.timestamp || new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
            `;
            messages.appendChild(botMsg);

            if (data.message_id) {
                window.chatbotRatingWidget?.setMessageId(data.message_id);
            }

            if (data.needs_support) {
                const supportHint = document.createElement('div');
                supportHint.className = 'flex justify-start animate-in';
                supportHint.innerHTML = `
                    <div class="bg-slate-100 text-slate-700 p-4 rounded-3xl rounded-tl-none shadow-sm border border-slate-200 max-w-[85%] relative mb-6">
                        <p class="text-sm leading-relaxed font-medium"><strong>Besoin d'aide ?</strong> Je peux vous mettre en relation avec un conseiller ou vous partager les meilleurs points de contact.</p>
                    </div>
                `;
                messages.appendChild(supportHint);
            }

            // Jouer le son de notification
            if (!isChatbotMuted) {
                chatbotReplySound.play().catch(err => {
                    // Les navigateurs bloquent souvent l'audio sans interaction préalable, 
                    // mais ici l'utilisateur a déjà interagi (clic ou envoi).
                    console.warn("Lecture audio bloquée :", err);
                });
            }

            messages.scrollTop = messages.scrollHeight;
        } catch (error) {
            typingMsg.remove();
            console.error('Chatbot error:', error);
        }
    };
</script>
<script src="src/js/chatbot-rating-widget.js"></script>

<script>
    // ==========================================
    // LOGIQUE DE L'ESPACE RÉALISATIONS
    // ==========================================
    let activeRealisationCategory = 'Tous';

    function filterRealisations(category, btn) {
        activeRealisationCategory = category;

        // Mise à jour de l'apparence des boutons de filtres
        document.querySelectorAll('.realisation-filter-btn').forEach(b => {
            b.className = 'realisation-filter-btn px-6 py-2.5 rounded-full text-xs font-bold transition-all bg-white text-slate-600 border border-slate-200 hover:border-[#3F8F29] hover:text-[#3F8F29]';
            const badge = b.querySelector('span');
            if (badge) badge.className = 'ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-500';
        });

        if (btn) {
            btn.className = 'realisation-filter-btn px-6 py-2.5 rounded-full text-xs font-bold transition-all shadow-sm bg-[#3F8F29] text-white';
            const badge = btn.querySelector('span');
            if (badge) badge.className = 'ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white';
        }

        applyRealisationFiltering();
    }

    function applyRealisationFiltering() {
        const searchInput = document.getElementById('realisationLiveSearch');
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const cards = document.querySelectorAll('.realisation-card');

        cards.forEach(card => {
            const cat = card.getAttribute('data-category');
            const searchData = card.getAttribute('data-search') || '';

            const matchesCategory = (activeRealisationCategory === 'Tous') || (cat === activeRealisationCategory);
            const matchesSearch = !query || searchData.includes(query);

            if (matchesCategory && matchesSearch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    const liveSearchInput = document.getElementById('realisationLiveSearch');
    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', applyRealisationFiltering);
    }

    // GESTION DU MODAL DÉTAILS RÉALISATION
    function getRealisationsData() {
        try {
            const el = document.getElementById('realisations-json-data');
            return el ? JSON.parse(el.textContent) : [];
        } catch (e) {
            console.error("Erreur de parsing des réalisations :", e);
            return [];
        }
    }

    function openRealisationModal(id) {
        const list = getRealisationsData();
        const item = list.find(r => parseInt(r.id) === parseInt(id));
        if (!item) return;

        const modal = document.getElementById('realisationModal');
        const container = document.getElementById('realisationModalContent');
        if (!modal || !container) return;

        // Préparation des médias
        const photos = (item.media || []).filter(m => m.media_type === 'photo');
        const videos = (item.media || []).filter(m => m.media_type === 'video');

        let workMediaHtml = '';
        if (videos.length > 0 || photos.length > 0) {
            workMediaHtml += `
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-sm font-black uppercase tracking-wider text-slate-800">
                        <i class="fas fa-tools text-[#3F8F29]"></i>
                        <span>Notre Équipe en Plein Travail (Chantier)</span>
                    </div>
            `;

            // Vidéos d'intervention de l'équipe
            if (videos.length > 0) {
                workMediaHtml += `<div class="space-y-4 mb-4">`;
                videos.forEach(v => {
                    if (v.file_path.startsWith('http://') || v.file_path.startsWith('https://')) {
                        workMediaHtml += `
                            <div class="rounded-2xl overflow-hidden shadow-lg border border-slate-200 aspect-video">
                                <iframe class="w-full h-full" src="${v.file_path}" frameborder="0" allowfullscreen></iframe>
                            </div>
                        `;
                    } else {
                        workMediaHtml += `
                            <div class="rounded-2xl overflow-hidden shadow-lg bg-black border border-slate-200">
                                <video controls playsinline class="w-full max-h-[420px] object-contain">
                                    <source src="${v.file_path}" type="video/mp4">
                                    Votre navigateur ne supporte pas la vidéo.
                                </video>
                            </div>
                        `;
                    }
                });
                workMediaHtml += `</div>`;
            }

            // Galerie photos
            if (photos.length > 0) {
                workMediaHtml += `
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        ${photos.map(p => `
                            <a href="${p.file_path}" target="_blank" class="group relative rounded-2xl overflow-hidden h-28 bg-slate-100 border border-slate-200 block shadow-sm hover:shadow-md transition">
                                <img src="${p.file_path}" alt="Photo de chantier" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </a>
                        `).join('')}
                    </div>
                `;
            }

            workMediaHtml += `</div>`;
        }

        // Zone Confession & Témoignage Client
        let testimonialHtml = '';
        if (item.client_quote || item.testimonial_media_url || item.testimonial_author) {
            testimonialHtml = `
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/80 rounded-3xl p-6 md:p-8 relative overflow-hidden shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-800">Retour d'expérience</span>
                            <h4 class="font-title text-lg font-bold text-slate-900">Confession & Avis Client</h4>
                        </div>
                    </div>

                    ${item.client_quote ? `
                        <div class="relative mb-6">
                            <i class="fas fa-quote-left text-amber-300 text-2xl absolute -top-3 -left-1 opacity-50"></i>
                            <p class="text-slate-800 text-base md:text-lg italic font-medium leading-relaxed pl-6">
                                « ${item.client_quote} »
                            </p>
                            ${item.testimonial_author ? `
                                <div class="mt-3 pl-6 text-xs font-bold text-amber-900 uppercase tracking-wider">
                                    — ${item.testimonial_author}
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}

                    <!-- Média du témoignage (Vidéo Smartphone ou Note Vocale Audio) -->
                    ${item.testimonial_type === 'video' && item.testimonial_media_url ? `
                        <div class="mt-4 pt-4 border-t border-amber-200/60">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-100/80 px-3 py-1 rounded-full mb-3">
                                <i class="fas fa-video"></i> Vidéo du client enregistrée sur place
                            </span>
                            <div class="rounded-2xl overflow-hidden bg-black max-w-md shadow-lg border border-amber-200">
                                <video controls playsinline class="w-full max-h-[350px] object-contain">
                                    <source src="${item.testimonial_media_url}">
                                    Votre navigateur ne supporte pas la lecture vidéo.
                                </video>
                            </div>
                        </div>
                    ` : ''}

                    ${item.testimonial_type === 'audio' && item.testimonial_media_url ? `
                        <div class="mt-4 pt-4 border-t border-amber-200/60">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-900 bg-amber-100 px-3 py-1 rounded-full mb-3">
                                <i class="fas fa-microphone-alt"></i> Confession Orale (Note Vocale)
                            </span>
                            <div class="bg-white rounded-2xl p-4 shadow-sm border border-amber-200/80 flex flex-col sm:flex-row items-center gap-4">
                                <audio controls class="w-full focus:outline-none">
                                    <source src="${item.testimonial_media_url}">
                                    Votre navigateur ne supporte pas la balise audio.
                                </audio>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        // Assemblage final du contenu
        container.innerHTML = `
            <div>
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <span class="px-4 py-1.5 rounded-full bg-green-50 text-[#3F8F29] border border-green-200 text-xs font-black uppercase tracking-wider">
                        ${item.category}
                    </span>
                    <span class="text-xs text-slate-400 font-semibold flex items-center gap-1">
                        <i class="fas fa-map-marker-alt text-primary"></i> ${item.location || 'Abidjan'}
                    </span>
                    ${item.client_name ? `
                        <span class="text-xs text-slate-400 font-semibold">
                            • Client : <strong class="text-slate-700">${item.client_name}</strong>
                        </span>
                    ` : ''}
                    ${item.completion_date ? `
                        <span class="text-xs text-slate-400 font-semibold">
                            • Réalisé en ${new Date(item.completion_date).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}
                        </span>
                    ` : ''}
                </div>

                <h2 class="font-title text-2xl md:text-4xl font-extrabold text-slate-900 leading-tight">
                    ${item.title}
                </h2>
            </div>

            <!-- Description du projet -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 text-slate-700 text-sm md:text-base leading-relaxed space-y-3">
                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider text-primary">Détails de l'intervention technique :</h4>
                <div class="whitespace-pre-line text-slate-600">${item.description}</div>
            </div>

            <!-- Zone Médias au travail -->
            ${workMediaHtml}

            <!-- Zone Témoignage Client -->
            ${testimonialHtml}

            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-400 font-medium">Vous avez un projet similaire à réaliser chez vous ou dans vos locaux ?</p>
                <button onclick="closeRealisationModal(); showPage('contact');" class="inline-flex items-center gap-2 rounded-full bg-primary text-white px-6 py-3 text-xs font-bold shadow-lg shadow-green-900/20 hover:bg-green-700 transition">
                    <i class="fas fa-paper-plane"></i> Demander un devis gratuit
                </button>
            </div>
        `;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeRealisationModal() {
        const modal = document.getElementById('realisationModal');
        if (!modal) return;

        // Arrêter toute lecture de vidéo ou audio en cours
        const videos = modal.querySelectorAll('video');
        videos.forEach(v => v.pause());
        const audios = modal.querySelectorAll('audio');
        audios.forEach(a => a.pause());

        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>
