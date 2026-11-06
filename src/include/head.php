<?php
require_once __DIR__ . '/i18n.php';
// Détecter si la page actuelle est dans le dossier admin pour ajuster les chemins relatifs
$assetPrefix = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';

// Chargement global du thème si non défini par la page parente
if (!isset($siteSettings) && isset($pdo)) {
    $siteSettings = get_site_settings($pdo);
}
$pColor = $siteSettings['theme_primary'] ?? $settings['theme_primary'] ?? '#3F8F29';
$sColor = $siteSettings['theme_secondary'] ?? $settings['theme_secondary'] ?? '#F4A321';
$nmPColor = $siteSettings['night_mode_primary_color'] ?? '#86efac'; // Default light green for night mode
$nmSColor = $siteSettings['night_mode_secondary_color'] ?? '#fcd34d'; // Default light orange for night mode

// Logique du mode nuit automatique (Actif entre 18h et 6h)
$autoNightMode = ($siteSettings['auto_night_mode'] ?? '0') === '1';
$startHour = (int)($siteSettings['auto_night_start'] ?? 18);
$endHour = (int)($siteSettings['auto_night_end'] ?? 6);
$isNightTime = false;
if ($autoNightMode) {
    $hour = (int)date('G');
    if ($startHour > $endHour) {
        $isNightTime = ($hour >= $startHour || $hour < $endHour);
    } else {
        $isNightTime = ($hour >= $startHour && $hour < $endHour);
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'fr', ENT_QUOTES, 'UTF-8'); ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? t('site_title') ?? 'ZoeTIC | Excellence en Formation Numérique'; ?></title>
    <meta name="description" content="<?php echo $pageDescription ?? (t('site_description') ?? 'ZoeTIC forme les leaders technologiques de demain.'); ?>">
    
    <!-- Favicon & App Icons (Pastille circulaire ZoeTIC haute visibilité) -->
    <?php
    $baseIconDir = __DIR__ . '/../../';
    $favIcoPath = $assetPrefix . 'favicon.ico';
    $favPng32 = $assetPrefix . 'favicon.png';
    $favSvg = $assetPrefix . 'favicon.svg';
    $appleTouch = $assetPrefix . 'apple-touch-icon.png';
    $favVer = file_exists($baseIconDir . 'favicon.ico') ? '?v=' . filemtime($baseIconDir . 'favicon.ico') : '';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo htmlspecialchars($favSvg . $favVer); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($favPng32 . $favVer); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favIcoPath . $favVer); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($appleTouch . $favVer); ?>">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $pageTitle ?? (t('site_title') ?? 'ZoeTIC | Formation Numérique'); ?>">
    <meta property="og:description" content="<?php echo $pageDescription ?? (t('site_description') ?? 'Formations certifiantes en Côte d\'Ivoire.'); ?>">
    <!--<meta property="og:image" content="<?php echo $pageImage ?? 'https://zoetic.ci/logo.png'; ?>">-->

    <!-- Google Fonts -->
    <link rel="stylesheet" href="./<?php echo $assetPrefix; ?>src/css/googleapis.css">
     
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!--<script src="./<?php echo $assetPrefix; ?>src/js/tailwindcss.js"></script>-->
    <!-- Font Awesome for Icons -->
    <!--<link rel="stylesheet" href="./<?php echo $assetPrefix; ?>src/css/all.min.css">-->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--color-primary)',
                        secondary: 'var(--color-secondary)',
                        dark: '#1A202C',
                        light: '#F7FAFC',
                    },
                    fontFamily: {
                        sans: ['Open Sans', 'sans-serif'],
                        title: ['Montserrat', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        // Expose server translations to client-side JS
        window.I18N = <?php echo json_encode($translations ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        window.I18N_LANG = '<?php echo htmlspecialchars($_SESSION['lang'] ?? 'fr', ENT_QUOTES, 'UTF-8'); ?>';
        // Simple helper: t('key', {name: 'John'}) supports {placeholders}
        window.t = function(key, replacements) {
            var val = window.I18N && window.I18N[key] ? window.I18N[key] : key;
            if (!replacements) return val;
            for (var k in replacements) {
                if (!Object.prototype.hasOwnProperty.call(replacements, k)) continue;
                var re = new RegExp('\\{' + k + '\\}', 'g');
                val = val.replace(re, replacements[k]);
            }
            return val;
        };
    </script>
    <style>
        :root {
            --color-primary: <?php echo $pColor; ?>;
            --color-secondary: <?php echo $sColor; ?>;
        }
        .hero-gradient {
            background: linear-gradient(135deg, var(--color-primary) 0%, rgba(26, 32, 44, 0.95) 100%);
        }
        .nav-active {
            color: var(--color-secondary) !important;
            border-bottom: 2px solid var(--color-secondary);
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .whatsapp-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
        }
        .page-transition {
            transition: all 0.4s ease-in-out;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Overrides pour le mode nuit (utilisé par l'automatisme ou la prévisualisation) */
        body.night-mode {
            --color-primary: <?php echo $nmPColor; ?>;
            --color-secondary: <?php echo $nmSColor; ?>;
        }

        /* 1. Global Page Backgrounds & Cards */
        body.night-mode.bg-light,
        body.night-mode .bg-light,
        body.night-mode .bg-slate-50,
        body.night-mode .bg-slate-50\/50,
        body.night-mode .bg-gray-50,
        body.night-mode .bg-gray-100,
        body.night-mode .bg-slate-100,
        body.night-mode .bg-\[\#FAFBFF\],
        body.night-mode .bg-\[\#FAFBFF\]\/50,
        body.night-mode .bg-\[\#F8FAFC\],
        body.night-mode .bg-\[\#F7FAFC\],
        body.night-mode .bg-slate-50\/50,
        body.night-mode .bg-slate-50\/30 { 
            background-color: #0f172a !important; 
            color: #f1f5f9 !important; 
        }

        /* Elements that are white in light mode become dark grey cards in dark mode */
        body.night-mode .bg-white,
        body.night-mode .bg-white\/90,
        body.night-mode .bg-white\/95,
        body.night-mode .bg-\[\#F8FAFC\] { 
            background-color: #1e293b !important; 
            border-color: #334155 !important; 
        }

        /* 2. Specific Accent Backgrounds (like colored features, badges) */
        /* Light green background: becomes dark green, text becomes light green */
        body.night-mode .bg-\[\#F0FDF4\],
        body.night-mode .bg-\[\#E6F7E6\] { 
            background-color: #022c22 !important; 
            color: #86efac !important;
            border-color: #065f46 !important;
        }
        body.night-mode .text-\[\#2B6A2B\] {
            color: #86efac !important;
        }

        /* Light orange background: becomes dark amber, text becomes light amber */
        body.night-mode .bg-\[\#FFF8ED\] { 
            background-color: #2d1e10 !important; 
            color: #fcd34d !important;
            border-color: #9a3412 !important;
        }

        /* Blue info boxes (admin dashboard converter support box etc) */
        body.night-mode .bg-blue-50\/30,
        body.night-mode .bg-blue-50 {
            background-color: #0d1e3d !important;
            border-color: #1e3a8a !important;
        }

        /* 3. Text Contrast Overrides */
        body.night-mode .text-gray-900,
        body.night-mode .text-gray-800,
        body.night-mode .text-gray-700,
        body.night-mode .text-slate-900,
        body.night-mode .text-slate-800,
        body.night-mode .text-slate-700,
        body.night-mode .text-dark { 
            color: #f8fafc !important; 
        }

        body.night-mode .text-gray-600,
        body.night-mode .text-gray-500,
        body.night-mode .text-gray-400,
        body.night-mode .text-slate-600,
        body.night-mode .text-slate-500,
        body.night-mode .text-slate-400 { 
            color: #94a3b8 !important; 
        }

        /* Announcement bar & secondary color backgrounds (yellow/orange in dark mode)
           Make sure text inside is dark slate (#0f172a) for readability! */
        body.night-mode .bg-secondary,
        body.night-mode .bg-secondary * {
            color: #0f172a !important;
        }
        /* Except when nested icons need custom styles */
        body.night-mode .bg-secondary i {
            color: #0f172a !important;
        }

        body.night-mode .text-\[\#0F766E\] { 
            color: var(--color-secondary) !important; 
        }

        /* 4. Borders & Dividers */
        body.night-mode .border-gray-100,
        body.night-mode .border-gray-200,
        body.night-mode .border-gray-300,
        body.night-mode .border-\[\#E2E8F0\],
        body.night-mode .border-\[\#E6F7E6\],
        body.night-mode .border-slate-100,
        body.night-mode .border-slate-200,
        body.night-mode .divide-gray-100 > * + *,
        body.night-mode .divide-gray-200 > * + * { 
            border-color: #334155 !important; 
        }

        /* 5. Headers & Footers */
        body.night-mode header.bg-white,
        body.night-mode header.fixed { 
            background-color: #0f172a !important; 
            border-color: #1e293b !important; 
        }
        body.night-mode header.bg-white span,
        body.night-mode header.fixed span {
            color: #f8fafc !important;
        }
        /* Mobile menu */
        body.night-mode #mobile-menu {
            background-color: #0f172a !important;
            border-color: #1e293b !important;
        }
        body.night-mode #mobile-menu button {
            color: #f8fafc !important;
        }

        /* 6. Hover States */
        /* Overrides hover classes that were turning background light grey */
        body.night-mode .hover\:bg-gray-50:hover,
        body.night-mode .hover\:bg-gray-100:hover,
        body.night-mode .hover\:bg-slate-50:hover,
        body.night-mode .hover\:bg-slate-100:hover,
        body.night-mode .group:hover .group-hover\:bg-gray-50,
        body.night-mode .group:hover .group-hover\:bg-slate-50 {
            background-color: #334155 !important;
            color: #f8fafc !important;
        }
        body.night-mode .hover\:text-slate-900:hover,
        body.night-mode .hover\:text-gray-900:hover {
            color: #ffffff !important;
        }

        /* 7. Buttons */
        body.night-mode .bg-white.text-dark {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }
        body.night-mode .bg-white.text-dark:hover {
            background-color: #334155 !important;
        }
        body.night-mode .bg-white.text-slate-900,
        body.night-mode .bg-white.text-slate-700 {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }
        body.night-mode .bg-white.text-slate-900:hover,
        body.night-mode .bg-white.text-slate-700:hover {
            background-color: #334155 !important;
        }

        /* Tone down shadow colors in night mode */
        body.night-mode .shadow-sm,
        body.night-mode .shadow-md,
        body.night-mode .shadow-lg,
        body.night-mode .shadow-xl,
        body.night-mode .shadow-2xl,
        body.night-mode .shadow-green-100,
        body.night-mode .shadow-green-200,
        body.night-mode .shadow-green-900\/20,
        body.night-mode .shadow-green-900\/40 { 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5) !important; 
        }

        /* 8. Forms & Inputs */
        body.night-mode input,
        body.night-mode select,
        body.night-mode textarea { 
            background-color: #1e293b !important; 
            color: #f1f5f9 !important; 
            border-color: #334155 !important; 
        }
        body.night-mode input::placeholder,
        body.night-mode textarea::placeholder {
            color: #64748b !important;
        }
        body.night-mode input:focus,
        body.night-mode select:focus,
        body.night-mode textarea:focus {
            border-color: var(--color-primary) !important;
            box-shadow: 0 0 0 3px rgba(134, 239, 172, 0.2) !important;
        }

        /* Checkbox overrides */
        body.night-mode input[type="checkbox"],
        body.night-mode input[type="radio"] {
            accent-color: var(--color-primary) !important;
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        /* File Upload area in settings */
        body.night-mode #logo-upload-area {
            border-color: #334155 !important;
            background-color: #0f172a !important;
        }
        body.night-mode #logo-upload-area:hover {
            border-color: var(--color-primary) !important;
            background-color: #1e293b !important;
        }

        /* 9. Scrollbar styling */
        body.night-mode::-webkit-scrollbar {
            width: 10px;
        }
        body.night-mode::-webkit-scrollbar-track {
            background: #0f172a;
        }
        body.night-mode::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 5px;
        }
        body.night-mode::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* 10. Specific Exception (Keep White Hero Background in Dashboard Preview Card) */
        body.night-mode .relative.h-\[600px\] .bg-white { 
            background-color: #ffffff !important; 
            color: #1A202C !important; 
        }
    </style>
</head>

    <!-- Lucide Icons initialization & Theme Logic -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Fonction pour appliquer le thème et mettre à jour les icônes
        function applyTheme(theme) {
            const icons = document.querySelectorAll('#theme-toggle-icon, .theme-toggle-icon-mobile');
            if (theme === 'dark') {
                document.body.classList.add('night-mode');
                icons.forEach(icon => icon.className = 'fas fa-sun');
            } else {
                document.body.classList.remove('night-mode');
                icons.forEach(icon => icon.className = 'fas fa-moon');
            }
        }

        // Bascule manuelle déclenchée par le bouton
        function toggleTheme() {
            const isCurrentlyDark = document.body.classList.contains('night-mode');
            const newTheme = isCurrentlyDark ? 'light' : 'dark';
            localStorage.setItem('user-theme', newTheme);
            applyTheme(newTheme);
        }

        document.addEventListener('DOMContentLoaded', () => {
            if(window.lucide) window.lucide.createIcons();
            
            // 1. Vérifier si l'utilisateur a déjà une préférence stockée
            const savedTheme = localStorage.getItem('user-theme');
            
            if (savedTheme) {
                applyTheme(savedTheme);
            } else {
                // 2. Sinon, synchroniser l'icône avec l'état auto calculé par PHP
                const isNightModeActive = document.body.classList.contains('night-mode');
                const icons = document.querySelectorAll('#theme-toggle-icon, .theme-toggle-icon-mobile');
                icons.forEach(icon => icon.className = isNightModeActive ? 'fas fa-sun' : 'fas fa-moon');
            }
        });
    </script>

<body class="bg-light font-sans text-gray-800 <?php echo $isNightTime ? 'night-mode' : ''; ?>">