<?php
function render_admin_sidebar(string $active = 'dashboard'): void {
    $links = [
        'dashboard' => ['label' => 'Tableau de bord', 'icon' => 'pie-chart', 'href' => 'dashboard.php'],
        'formations' => ['label' => 'Catalogue Cours', 'icon' => 'graduation-cap', 'href' => 'formations.php'],
        'realisations' => ['label' => 'Nos Réalisations', 'icon' => 'briefcase', 'href' => 'realisations.php'],
        'inscriptions' => ['label' => 'Suivi Inscriptions', 'icon' => 'user-plus', 'href' => 'inscriptions.php'],
        'blog' => ['label' => 'Gestion Blog', 'icon' => 'pen-tool', 'href' => 'blog.php'],
        'comments' => ['label' => 'Modération Comm.', 'icon' => 'message-square', 'href' => 'comments.php'],
        'messages' => ['label' => 'Boîte de réception', 'icon' => 'inbox', 'href' => 'messages.php'],
        'testimonials' => ['label' => 'Avis Étudiants', 'icon' => 'heart', 'href' => 'testimonials.php'],
        'legal' => ['label' => 'Pages Légales', 'icon' => 'shield', 'href' => 'legal_docs.php'],
        'chatbot' => ['label' => 'Logs Chatbot', 'icon' => 'bot', 'href' => 'chatbot_logs.php'],
        'settings' => ['label' => 'Configuration', 'icon' => 'settings', 'href' => 'settings.php'],
    ];

    if (($_SESSION['admin_role'] ?? '') === 'super_admin') {
        $links['admins'] = ['label' => 'Équipe Admin', 'icon' => 'shield-check', 'href' => 'admins.php'];
        $links['logs'] = ['label' => 'Logs d\'activité', 'icon' => 'history', 'href' => 'logs.php'];
    }

    echo '<aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-[#0f172a] text-slate-300 sidebar-transition z-40 border-r border-slate-800/50 shadow-xl">';
    echo '<div class="p-6 flex items-center gap-3 border-b border-slate-800/50 mb-4">';
    
    global $siteSettings, $pdo;
    if (!isset($siteSettings) && isset($pdo)) {
        $siteSettings = get_site_settings($pdo);
    }
    $logoPath = $siteSettings['logo_image'] ?? null;
    $logoHeight = $siteSettings['logo_height'] ?? '32';
    $logoShape = $siteSettings['logo_shape'] ?? 'original';
    $showName = ($siteSettings['show_site_name'] ?? '0') === '1';

    $logoStyle = "height: " . (int)$logoHeight . "px;";
    if ($logoShape === 'rounded') $logoStyle .= " border-radius: 0.5rem;";
    elseif ($logoShape === 'circle') $logoStyle .= " border-radius: 9999px; aspect-ratio: 1/1; object-fit: cover;";

    if ($logoPath && file_exists(__DIR__ . '/../' . $logoPath)) {
        echo '<img src="../' . htmlspecialchars($logoPath) . '?v=' . time() . '" alt="Logo" class="w-auto object-contain sidebar-text shrink-0 transition-transform hover:scale-105" style="' . $logoStyle . '">';
    } else {
        echo '<div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#3F8F29] to-[#2d6a1d] flex items-center justify-center font-bold text-lg text-white shadow-lg shadow-green-900/40 shrink-0 ring-1 ring-white/10">Z</div>';
    }

    if ($showName || (!$logoPath || !file_exists(__DIR__ . '/../' . $logoPath))) {
        echo '<span class="font-title font-bold text-lg tracking-tight sidebar-text text-white">ZoeTIC <span class="text-[#F4A321]">Admin</span></span>';
    }
    
    echo '</div>';
    echo '<nav class="px-4 py-2 space-y-1 overflow-y-auto max-h-[calc(100vh-160px)]">';

    foreach ($links as $key => $link) {
        $isActive = $active === $key;
        $activeClass = $isActive 
            ? 'bg-[#3F8F29] text-white shadow-lg shadow-green-900/40 ring-1 ring-white/10' 
            : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100 transition-all duration-200';
            
        echo '<a href="' . $link['href'] . '" class="flex items-center gap-3 w-full px-4 py-3 rounded-xl group ' . $activeClass . '">';
        echo '<i data-lucide="' . $link['icon'] . '" class="w-4.5 h-4.5 transition-transform group-hover:scale-110 ' . ($isActive ? 'stroke-[2.5px]' : 'stroke-[2px]') . '"></i>';
        echo '<span class="sidebar-text font-semibold text-[11px] uppercase tracking-[0.1em]">' . $link['label'] . '</span>';
        echo '</a>';
    }

    echo '</nav>';
    echo '<div class="absolute bottom-0 w-full p-4 bg-[#0f172a]/50">';
    echo '<a href="logout.php" class="flex items-center gap-3 w-full p-3 rounded-xl hover:bg-red-500/10 text-red-400 transition-colors">';
    echo '<i data-lucide="log-out" class="w-5 h-5"></i>';
    echo '<span class="sidebar-text font-medium text-sm">Déconnexion</span>';
    echo '</a>';
    echo '</div>';
    echo '</aside>';
}

function render_admin_header(string $adminName, string $adminRole, string $initials, string $title = 'Vue d\'ensemble', string $subtitle = 'Un espace sécurisé pour piloter votre plateforme.', int $notificationCount = 0, int $adminId = 0): void {
    echo '<header class="h-20 bg-white/90 backdrop-blur-xl border-b border-slate-200/60 flex items-center justify-between px-8 sticky top-0 z-30">';
    echo '<div class="flex items-center gap-6">';
    echo '<button onclick="toggleSidebar()" class="p-2.5 hover:bg-slate-100 rounded-xl text-slate-600 transition-all active:scale-95 shadow-sm bg-white border border-slate-200">';
    echo '<i data-lucide="menu" class="w-5 h-5"></i>';
    echo '</button>';
    echo '<div><h1 id="page-title" class="font-title font-bold text-xl text-slate-900 tracking-tight">' . $title . '</h1>';
    echo '<p class="text-xs text-slate-500 font-medium mt-0.5">Tableau de bord <span class="mx-1 opacity-30">/</span> ' . htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') . '</p></div>';
    echo '</div>';

    echo '<div class="flex items-center gap-4">';
    echo '<div class="hidden md:flex relative group mr-4">';
    echo '<i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>';
    echo '<input type="text" id="globalSearch" placeholder="Recherche rapide..." class="pl-10 pr-4 py-2.5 bg-slate-100 border border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-[#3F8F29]/20 focus:border-[#3F8F29]/30 outline-none w-72 transition-all">';
    echo '</div>';
    echo '<div class="relative">'; // Conteneur pour la cloche et le dropdown
    echo '<button id="notification-bell-btn" class="relative p-2.5 hover:bg-slate-100 rounded-xl text-slate-500 transition-all active:scale-95 group" onclick="toggleNotificationsDropdown()">';
    echo '<i data-lucide="bell" class="w-5 h-5 group-hover:rotate-12 transition-transform"></i>';
    echo '<span id="notification-badge" class="absolute top-2 right-2.5 bg-red-500 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold ' . ($notificationCount > 0 ? '' : 'hidden') . '">' . $notificationCount . '</span>';
    echo '</button>';
    // Dropdown des notifications
    echo '<div id="notifications-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 hidden z-50 animate-in" style="animation-delay: 100ms;">';
    echo '<div class="p-4 border-b border-gray-100 flex items-center justify-between">';
    echo '<h4 class="font-bold text-slate-900 text-sm">Notifications récentes</h4>';
    echo '<a href="logs.php" class="text-xs text-primary hover:underline">Voir tout</a>';
    echo '</div>';
    echo '<div id="notifications-list" class="divide-y divide-gray-100 max-h-80 overflow-y-auto">';
    echo '<div class="p-4 text-center text-sm text-slate-400">Chargement...</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // Fin du conteneur relatif

    // Bouton de bascule de thème Jour/Nuit
    echo '<button onclick="toggleTheme()" class="p-2.5 hover:bg-slate-100 rounded-xl text-slate-500 hover:text-primary transition-all active:scale-95" title="Bascule de thème">';
    echo '<i id="theme-toggle-icon" class="fas fa-moon"></i>';
    echo '</button>';

    echo '<div class="h-8 w-px bg-slate-200 mx-2"></div>';
    echo '<div class="flex items-center gap-3 pl-2">';
    echo '<div class="text-right hidden sm:block"><p class="text-sm font-bold text-slate-900 leading-none">' . htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="text-[10px] text-[#3F8F29] mt-1.5 font-bold uppercase tracking-widest opacity-80">En ligne</p></div>';
    echo '<div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#3F8F29] to-green-400 shadow-lg shadow-green-200/50 flex items-center justify-center text-white font-bold transform hover:scale-105 transition-all cursor-pointer ring-2 ring-offset-2 ring-transparent hover:ring-[#3F8F29]/20">' . htmlspecialchars($initials ?: 'AZ', ENT_QUOTES, 'UTF-8') . '</div>';
    echo '</div>';
    echo '</div>';
    echo '</header>';
}

function render_admin_footer_scripts(): void {
?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const main = document.getElementById("main-content");
        if (sidebar && main) {
            sidebar.classList.toggle("-translate-x-full");
            main.classList.toggle("ml-0");
        }
    }
    document.addEventListener("DOMContentLoaded", () => {
        if (window.Lucide) window.Lucide.replace();
        document.querySelectorAll("a.delete-article").forEach(link => {
            link.addEventListener("click", event => {
                if (!confirm("Confirmer la suppression de cet article ?")) {
                    event.preventDefault();
                }
            });
        });
        
        const cleanStr = (str) => {
            if (!str) return "";
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
        };

        const filterData = (query) => {
            const cleanQuery = cleanStr(query);

            // Synchronize search input elements on the same page
            document.querySelectorAll("#globalSearch, #blogSearch, #formationSearch, #enrollmentSearch, #messageSearch, #commentSearch, #adminSearch").forEach(input => {
                if (input && input.value !== query) {
                    input.value = query;
                }
            });

            let items = [];
            let container = null;
            let type = "";

            if (document.querySelectorAll(".comment-row").length > 0) {
                items = document.querySelectorAll(".comment-row");
                container = items[0].closest("tbody");
                type = "table";
            } else if (document.querySelectorAll(".message-row").length > 0) {
                items = document.querySelectorAll(".message-row");
                container = document.getElementById("messagesContainer") || items[0].parentElement;
                type = "messages";
            } else if (document.getElementById("admins-list")) {
                items = document.querySelectorAll("#admins-list > div");
                container = document.getElementById("admins-list");
                type = "grid";
            }

            if (items.length === 0 && document.querySelectorAll("table tbody tr").length > 0) {
                items = Array.from(document.querySelectorAll("table tbody tr")).filter(tr => {
                    return !tr.classList.contains("skeleton-row") &&
                           !tr.closest("#no-results-body") &&
                           tr.id !== "no-results-row" &&
                           !tr.classList.contains("no-results-row");
                });
                if (items.length > 0) {
                    container = items[0].closest("tbody");
                    type = "table";
                }
            }

            let visibleCount = 0;
            items.forEach(item => {
                const text = cleanStr(item.textContent);
                const isMatch = text.includes(cleanQuery);
                if (isMatch) {
                    item.style.display = "";
                    visibleCount++;
                } else {
                    item.style.display = "none";
                }
            });

            // Remove existing dynamic placeholders
            const existingRow = document.getElementById("no-results-row");
            if (existingRow) existingRow.remove();
            if (container) {
                const existingBlock = container.querySelector(".no-results-block");
                if (existingBlock) existingBlock.remove();
            }

            // Check for built-in no-results placeholders (like in formations.php)
            const builtInNoResults = document.getElementById("no-results-body");
            const termDisplay = document.getElementById("search-term-display");
            if (builtInNoResults) {
                if (visibleCount === 0) {
                    builtInNoResults.classList.remove("hidden");
                    builtInNoResults.style.display = "table-row-group";
                    if (termDisplay) termDisplay.textContent = query;
                } else {
                    builtInNoResults.classList.add("hidden");
                    builtInNoResults.style.display = "none";
                }
                return;
            }

            // Fallback dynamic placeholder injection if no results found
            if (visibleCount === 0 && query !== "" && container) {
                if (type === "table") {
                    const colCount = container.closest("table").querySelectorAll("thead th").length || 6;
                    const tr = document.createElement("tr");
                    tr.id = "no-results-row";
                    tr.className = "no-results-row";
                    tr.innerHTML = `
                        <td colspan="${colCount}" class="px-8 py-16 text-center bg-transparent">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-300 dark:bg-slate-800 dark:border-slate-700">
                                    <i class="fas fa-search-minus text-2xl"></i>
                                </div>
                                <h4 class="font-bold text-slate-900 dark:text-slate-100">Aucun résultat</h4>
                                <p class="text-xs text-slate-400 max-w-xs leading-relaxed">Aucun élément ne correspond à votre recherche "${query}".</p>
                            </div>
                        </td>
                    `;
                    container.appendChild(tr);
                } else {
                    const div = document.createElement("div");
                    div.className = "no-results-block py-16 text-center w-full col-span-full";
                    div.innerHTML = `
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-300 dark:bg-slate-800 dark:border-slate-700">
                            <i class="fas fa-search-minus text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-100">Aucun résultat</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">Aucun élément ne correspond à votre recherche "${query}".</p>
                    `;
                    container.appendChild(div);
                }
            }
        };

        // Attach filter listeners
        ["blogSearch", "formationSearch", "enrollmentSearch", "messageSearch", "commentSearch", "adminSearch", "globalSearch"].forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener("input", (e) => filterData(e.target.value));
            }
        });

        // Notifications logic
        let lastTotal = null;
        const adminId = <?php echo (int)($_SESSION['admin_id'] ?? 0); ?>;
        const badge = document.getElementById("notification-badge");
        const sound = new Audio("https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3");
        const isSoundEnabled = <?php echo (int)($_SESSION['admin_notification_sound'] ?? 1); ?>;

        async function pollNotifications() {
            try {
                const res = await fetch("connect/get_notifications_count.php");
                const data = await res.json();
                if (data.status === "success") {
                    const total = data.total;
                    if (lastTotal !== null && total > lastTotal && isSoundEnabled) {
                        sound.play().catch(e => console.warn("Audio bloqué par le navigateur (nécessite un clic utilisateur)"));
                    }
                    lastTotal = total;
                    if (badge) {
                        badge.textContent = total;
                        if (total > 0) badge.classList.remove("hidden");
                        else badge.classList.add("hidden");
                    }
                }
            } catch (err) {
                console.error("Erreur polling notifications:", err);
            }
        }

        if (badge) lastTotal = parseInt(badge.textContent) || 0;
        pollNotifications();
        setInterval(pollNotifications, 30000);

        // Notifications dropdown logic
        const notificationsDropdown = document.getElementById("notifications-dropdown");
        const notificationsList = document.getElementById("notifications-list");
        const notificationBellBtn = document.getElementById("notification-bell-btn");

        window.toggleNotificationsDropdown = async function() {
            if (!notificationsDropdown || !notificationsList) return;
            notificationsDropdown.classList.toggle("hidden");
            if (!notificationsDropdown.classList.contains("hidden")) {
                notificationsList.innerHTML = '<div class="p-4 text-center text-sm text-slate-400"><i class="fas fa-spinner animate-spin inline-block mr-2"></i> Chargement...</div>';
                try {
                    const res = await fetch("connect/get_recent_activities.php");
                    const data = await res.json();
                    if (data.status === "success") {
                        notificationsList.innerHTML = "";
                        if (data.activities.length === 0) {
                            notificationsList.innerHTML = '<div class="p-4 text-center text-sm text-slate-400">Aucune activité récente.</div>';
                        } else {
                            data.activities.forEach(activity => {
                                const item = document.createElement("div");
                                item.className = "flex items-start gap-3 p-4 hover:bg-slate-50 transition-colors";
                                let icon = "bell";
                                let color = "text-primary";
                                switch(activity.action_type) {
                                    case "NEW_COMMENT": icon = "message-square"; color = "text-blue-500"; break;
                                    case "NEW_ENROLLMENT": icon = "user-plus"; color = "text-green-500"; break;
                                    case "NEW_CONTACT_MESSAGE": icon = "mail"; color = "text-orange-500"; break;
                                    case "NEW_NEWSLETTER_SUB": icon = "mail-check"; color = "text-purple-500"; break;
                                    case "CREATE_ADMIN": icon = "user-plus"; color = "text-primary"; break;
                                    case "UPDATE": icon = "edit"; color = "text-blue-500"; break;
                                    case "DELETE_ADMIN": icon = "trash-2"; color = "text-red-500"; break;
                                    case "ROLE_CHANGE": icon = "shield-check"; color = "text-yellow-500"; break;
                                }
                                item.innerHTML = `
                                    <i data-lucide="${icon}" class="w-5 h-5 ${color} shrink-0"></i>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800 line-clamp-2">${activity.description}</p>
                                        <p class="text-xs text-slate-400 mt-1">${activity.time_ago} par ${activity.admin_name}</p>
                                    </div>
                                `;
                                notificationsList.appendChild(item);
                            });
                            if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
                        }
                    }
                } catch (err) {
                    console.error("Erreur chargement notifications:", err);
                    notificationsList.innerHTML = '<div class="p-4 text-center text-sm text-red-500">Erreur de chargement.</div>';
                }
            }
        };

        document.addEventListener("click", (event) => {
            if (notificationBellBtn && !notificationBellBtn.contains(event.target) && notificationsDropdown && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add("hidden");
            }
        });
    });
    </script>
<?php
}

function render_counter_script(): void {
?>
    <script>
    function animateCounters() {
        document.querySelectorAll("[data-target]").forEach(element => {
            const target = Number(element.getAttribute("data-target")) || 0;
            const duration = 1200;
            const stepTime = Math.max(Math.floor(duration / (target || 1)), 20);
            let current = 0;
            const increment = Math.max(1, Math.floor(target / 15));
            const counter = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(counter);
                } else {
                    element.textContent = current;
                }
            }, stepTime);
        });
    }
    document.addEventListener("DOMContentLoaded", () => animateCounters());
    </script>
<?php
}
?>