<?php
require_once __DIR__ . '/_auth.php';
require_admin_login();
require_once __DIR__ . '/../src/db/init.php';
require_once __DIR__ . '/_layout.php';

ensure_system_tables($pdo);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$initials = strtoupper(substr($adminName, 0, 1));

// Initialisation des variables pour éviter les erreurs "Undefined variable" en cas d'échec de la base de données
$totalMessages = 0;
$totalSessions = 0;
$todayCount = 0;
$avgRating = 0;
$helpfulCount = 0;
$escalatedCount = 0;
$topIntents = [];
$topCategories = [];
$sessions = [];
$hourlyChartData = array_fill(0, 24, 0);

try {
    // Statistiques globales
    $totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM chatbot_logs")->fetchColumn();
    $totalSessions = (int)$pdo->query("SELECT COUNT(DISTINCT session_id) FROM chatbot_logs")->fetchColumn();
    $todayCount = (int)$pdo->query("SELECT COUNT(*) FROM chatbot_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // Statistiques de satisfaction
    $avgRating = $pdo->query("SELECT AVG(rating) FROM chatbot_logs WHERE rating IS NOT NULL")->fetchColumn();
    $avgRating = $avgRating ? round($avgRating, 1) : 0;
    
    $helpfulCount = (int)$pdo->query("SELECT COUNT(*) FROM chatbot_logs WHERE helpful_feedback = 1")->fetchColumn();
    
    $escalatedCount = (int)$pdo->query("SELECT COUNT(*) FROM chatbot_logs WHERE is_escalated = 1")->fetchColumn();
    
    // Top intents
    $topIntents = $pdo->query("
        SELECT intent, COUNT(*) as count 
        FROM chatbot_logs 
        WHERE intent IS NOT NULL 
        GROUP BY intent 
        ORDER BY count DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top catégories
    $topCategories = $pdo->query("
        SELECT conversation_category, COUNT(*) as count 
        FROM chatbot_logs 
        WHERE conversation_category IS NOT NULL 
        GROUP BY conversation_category 
        ORDER BY count DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des messages par heure pour le graphique
    $hourlyData = $pdo->query("
        SELECT 
            HOUR(created_at) as hour, 
            COUNT(*) as count 
        FROM chatbot_logs 
        WHERE DATE(created_at) = CURDATE() 
        GROUP BY HOUR(created_at) 
        ORDER BY hour ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $hourlyChartData = array_fill(0, 24, 0); // Initialise un tableau de 24h à 0
    foreach ($hourlyData as $row) {
        $hourlyChartData[(int)$row['hour']] = (int)$row['count'];
    }

    // Récupération des sessions (groupées par session_id)
    $sessions = $pdo->query("
        SELECT 
            session_id, 
            MAX(created_at) as last_activity, 
            COUNT(*) as message_count,
            ip_address,
            (SELECT user_message FROM chatbot_logs cl2 WHERE cl2.session_id = chatbot_logs.session_id ORDER BY cl2.created_at DESC LIMIT 1) as last_message,
            (SELECT AVG(rating) FROM chatbot_logs cl3 WHERE cl3.session_id = chatbot_logs.session_id AND cl3.rating IS NOT NULL) as avg_rating,
            (SELECT COUNT(*) FROM chatbot_logs cl4 WHERE cl4.session_id = chatbot_logs.session_id AND cl4.is_escalated = 1) as escalated_count,
            (SELECT GROUP_CONCAT(DISTINCT intent SEPARATOR ', ') FROM chatbot_logs cl5 WHERE cl5.session_id = chatbot_logs.session_id AND intent IS NOT NULL LIMIT 3) as intents
        FROM chatbot_logs 
        GROUP BY session_id 
        ORDER BY last_activity DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Chatbot Logs Error: ' . $e->getMessage());
    $sessions = [];
}

include('../src/include/head.php');
?>

<?php render_admin_sidebar('chatbot'); ?>
<div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
    <?php render_admin_header($adminName, $adminRole, $initials, 'Logs Chatbot', 'Analysez les interactions des visiteurs avec l\'assistant.'); ?>

    <main class="p-4 md:p-8 max-w-[1600px] mx-auto w-full">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Analyse IA</p>
                <h2 class="text-4xl font-title font-black text-slate-900">Conversations Chatbot</h2>
                <p class="text-slate-400 mt-2">Suivez ce que vos prospects demandent en temps réel.</p>
            </div>
        </div>

        <!-- Statistiques avancées -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center gap-5">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <i data-lucide="message-square" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Sessions</p>
                    <p class="text-2xl font-black text-slate-900"><?php echo $totalSessions; ?></p>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center gap-5">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Aujourd'hui</p>
                    <p class="text-2xl font-black text-slate-900"><?php echo $todayCount; ?></p>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center gap-5">
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center">
                    <i data-lucide="thumbs-up" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Satisfaction</p>
                    <p class="text-2xl font-black text-slate-900"><?php echo $avgRating; ?>/5</p>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center gap-5">
                <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Escalades</p>
                    <p class="text-2xl font-black text-slate-900"><?php echo $escalatedCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Graphiques d'analyse -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Graphique des intentions -->
            <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                        <i data-lucide="target" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Intentions Principales</h3>
                </div>
                <div class="h-72">
                    <canvas id="intentsChart"></canvas>
                </div>
            </div>

            <!-- Graphique des catégories -->
            <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="tag" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Catégories de Conversations</h3>
                </div>
                <div class="h-72">
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Graphique d'utilisation par heure -->
        <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/40 mb-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                </div>
                <h3 class="text-xl font-black text-slate-900">Activité du Chatbot (Aujourd'hui)</h3>
            </div>
            <div class="h-80">
                <canvas id="chatbotHourlyChart"></canvas>
            </div>
        </div>

        <div class="mb-8">
            <p class="text-[10px] uppercase tracking-[0.4em] text-primary font-black mb-2">Historique</p>
            <h2 class="text-4xl font-title font-black text-slate-900">Sessions de Conversation</h2>
            <p class="text-slate-400 mt-2">Explorez les discussions passées pour comprendre les besoins des utilisateurs.</p>
        </div>

        <div class="rounded-[2rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/50 text-[11px] uppercase text-slate-400 tracking-[0.25em] font-black border-b border-slate-50">
                            <th class="px-8 py-5">Dernière Activité</th>
                            <th class="px-5 py-5">Dernier Message</th>
                            <th class="px-5 py-5 text-center">Échanges</th>
                            <th class="px-5 py-5">Intentions</th>
                            <th class="px-5 py-5 text-center">Satisfaction</th>
                            <th class="px-8 py-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($sessions as $session): ?>
                            <tr class="hover:bg-slate-50/30 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="text-sm font-black text-slate-900"><?php echo date('d/m/Y', strtotime($session['last_activity'])); ?></div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase"><?php echo date('H:i', strtotime($session['last_activity'])); ?></div>
                                </td>
                                <td class="px-5 py-5">
                                    <p class="text-sm text-slate-600 font-medium line-clamp-1 italic">"<?php echo htmlspecialchars(mb_substr($session['last_message'], 0, 50)); ?>"</p>
                                </td>
                                <td class="px-5 py-5 text-center">
                                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black">
                                        <?php echo $session['message_count']; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-5">
                                    <div class="flex flex-wrap gap-2">
                                        <?php 
                                        if ($session['intents']) {
                                            $intents = explode(', ', $session['intents']);
                                            $intentLabels = [
                                                'formation_inquiry' => ['Formation', 'bg-blue-100', 'text-blue-700'],
                                                'pricing_inquiry' => ['Tarifs', 'bg-orange-100', 'text-orange-700'],
                                                'enrollment' => ['Inscription', 'bg-green-100', 'text-green-700'],
                                                'contact_request' => ['Contact', 'bg-purple-100', 'text-purple-700'],
                                                'location_inquiry' => ['Localisation', 'bg-pink-100', 'text-pink-700'],
                                                'technical_issue' => ['Support', 'bg-red-100', 'text-red-700'],
                                                'career_inquiry' => ['Carrière', 'bg-indigo-100', 'text-indigo-700'],
                                                'greeting' => ['Salutation', 'bg-teal-100', 'text-teal-700'],
                                            ];
                                            foreach (array_slice($intents, 0, 2) as $intent) {
                                                $label = $intentLabels[trim($intent)] ?? [trim($intent), 'bg-gray-100', 'text-gray-700'];
                                                echo "<span class=\"text-[10px] font-black px-2 py-1 rounded-full {$label[1]} {$label[2]}\">{$label[0]}</span>";
                                            }
                                        } else {
                                            echo "<span class=\"text-[10px] text-slate-400\">-</span>";
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-5 py-5 text-center">
                                    <?php 
                                    if ($session['avg_rating']) {
                                        $rating = round($session['avg_rating'], 1);
                                        $color = $rating >= 4 ? 'text-green-600' : ($rating >= 3 ? 'text-yellow-600' : 'text-red-600');
                                        echo "<span class=\"{$color} font-black text-sm\">★ {$rating}/5</span>";
                                    } else {
                                        echo "<span class=\"text-slate-300 text-[10px]\">Non évaluée</span>";
                                    }
                                    ?>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <button onclick="viewTranscript('<?php echo $session['session_id']; ?>')" class="p-3 rounded-xl bg-primary/5 text-primary hover:bg-primary hover:text-white transition-all">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Transcript Modal -->
<div id="transcriptModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col h-[80vh] animate-in">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary text-white flex items-center justify-center">
                    <i data-lucide="bot"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900">Fil de la discussion</h3>
            </div>
            <button onclick="closeModal()" class="p-2 hover:bg-slate-200 rounded-full text-slate-400 transition-colors"><i data-lucide="x"></i></button>
        </div>
        <div id="transcriptContent" class="flex-grow overflow-y-auto p-8 space-y-6 bg-white no-scrollbar">
            <!-- Les messages seront chargés ici -->
        </div>
    </div>
</div>

<script>
async function viewTranscript(sessionId) {
    const modal = document.getElementById('transcriptModal');
    const content = document.getElementById('transcriptContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    content.innerHTML = '<div class="flex justify-center py-10"><i data-lucide="loader-2" class="w-8 h-8 animate-spin text-primary"></i></div>';
    if(window.lucide) window.lucide.createIcons();

    try {
        const response = await fetch(`connect/get_chatbot_transcript.php?session_id=${sessionId}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            content.innerHTML = data.transcript.map(msg => `
                <div class="space-y-4">
                    <div class="flex justify-end">
                        <div class="bg-slate-100 p-4 rounded-2xl rounded-tr-none max-w-[80%]">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Visiteur</p>
                            <p class="text-sm text-slate-700 font-bold">${msg.user_message}</p>
                        </div>
                    </div>
                    <div class="flex justify-start">
                        <div class="bg-primary/5 border border-primary/10 p-4 rounded-2xl rounded-tl-none max-w-[80%]">
                            <p class="text-xs font-black text-primary uppercase tracking-widest mb-1">Assistant ZoeTIC</p>
                            <p class="text-sm text-slate-600 font-medium">${msg.bot_reply}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        content.innerHTML = '<p class="text-center text-red-500">Erreur lors du chargement.</p>';
    }
}

function closeModal() {
    document.getElementById('transcriptModal').classList.add('hidden');
    document.getElementById('transcriptModal').classList.remove('flex');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Graphique des intentions
    const intentsCtx = document.getElementById('intentsChart');
    if (intentsCtx) {
        const intentsData = <?php echo json_encode($topIntents); ?>;
        const intentLabels = intentsData.map(item => {
            const labels = {
                'formation_inquiry': 'Formations',
                'pricing_inquiry': 'Tarifs',
                'enrollment': 'Inscription',
                'contact_request': 'Contact',
                'location_inquiry': 'Localisation',
                'greeting': 'Salutations',
                'acknowledgment': 'Remerciements',
                'technical_issue': 'Problème technique',
                'career_inquiry': 'Carrière',
                'unclear': 'Autre'
            };
            return labels[item.intent] || item.intent;
        });
        const intentCounts = intentsData.map(item => item.count);
        
        new Chart(intentsCtx, {
            type: 'doughnut',
            data: {
                labels: intentLabels,
                datasets: [{
                    data: intentCounts,
                    backgroundColor: [
                        'rgba(63, 143, 41, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(20, 184, 166, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderColor: 'rgba(255, 255, 255, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });
    }

    // Graphique des catégories
    const categoriesCtx = document.getElementById('categoriesChart');
    if (categoriesCtx) {
        const categoriesData = <?php echo json_encode($topCategories); ?>;
        const categoryLabels = categoriesData.map(item => {
            const labels = {
                'product_info': 'Info produits',
                'pricing': 'Tarification',
                'enrollment': 'Inscriptions',
                'contact': 'Contact',
                'location': 'Localisation',
                'welcome': 'Bienvenue',
                'follow_up': 'Suivi',
                'career': 'Carrière',
                'support': 'Support',
                'other': 'Autre'
            };
            return labels[item.conversation_category] || item.conversation_category;
        });
        const categoryCounts = categoriesData.map(item => item.count);
        
        new Chart(categoriesCtx, {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Nombre de conversations',
                    data: categoryCounts,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }

    // Graphique d'activité horaire
    const ctx = document.getElementById('chatbotHourlyChart');
    if (ctx) {
        const hourlyData = <?php echo json_encode($hourlyChartData); ?>;
        const labels = Array.from({length: 24}, (_, i) => `${i}h`);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Messages par heure',
                    data: hourlyData,
                    backgroundColor: 'rgba(63, 143, 41, 0.7)', // Couleur primaire
                    borderColor: 'rgba(63, 143, 41, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Nombre de messages' } },
                    x: { title: { display: true, text: 'Heure de la journée' } }
                }
            }
        });
    }
});
</script>

<?php render_admin_footer_scripts(); ?>
</body>
</html>