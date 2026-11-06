<?php
session_start();
use App\Core\APIHelper;

require_once __DIR__ . '/src/db/init.php';
require_once __DIR__ . '/src/core/APIHelper.php';

header('Content-Type: application/json; charset=utf-8');

$message = strtolower(trim(filter_input(INPUT_POST, 'message', FILTER_DEFAULT) ?? ''));
$response = [
    'reply' => "Désolé, je n'ai pas bien compris votre demande. Pouvez-vous reformuler ou m'indiquer si vous cherchez une formation spécifique, nos tarifs, nos coordonnées ou les modalités d'inscription ?",
    'timestamp' => date('H:i'),
    'intent' => 'unclear',
    'needs_support' => false,
    'message_id' => 0
];

if (empty($message)) {
    echo json_encode($response);
    exit;
}

// ===== INITIALISATION =====
$settings = get_site_settings($pdo);
$siteName = $settings['site_name'] ?? 'ZoeTIC';
$contactPhone = $settings['contact_phone'] ?? '+225 05 74 67 72 46';
$contactEmail = $settings['contact_email'] ?? 'contact@zoetic.ci';
$contactAddress = $settings['contact_address'] ?? 'Abidjan, Côte d\'Ivoire';

// Récupération des formations/catégories
$stmt = $pdo->query("SELECT name FROM categories LIMIT 15");
$cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
$catList = !empty($cats) ? implode(', ', $cats) : 'numérique, cybersécurité, développement web, design graphique';

$sessionId = session_id() ?: 'no-session';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// ===== RATE LIMITING =====
$limitReached = false;
try {
    $checkLimit = $pdo->prepare("SELECT COUNT(*) FROM chatbot_logs WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $checkLimit->execute([$ipAddress]);
    if ((int)$checkLimit->fetchColumn() >= 80) {
        $limitReached = true;
        $response['reply'] = "Vous avez atteint la limite de messages. Veuillez réessayer plus tard ou nous contacter directement.";
        echo json_encode($response);
        save_chat_log($pdo, $message, $response['reply'], 'rate_limit', 0, 1);
        exit;
    }
} catch (Exception $e) { }

// ===== DÉTECTION D'INTENT (Classification) =====
$intent = detect_intent($message);
$response['intent'] = $intent;

// ===== RÉCUPÉRATION DE L'HISTORIQUE (10 derniers messages pour meilleur contexte) =====
$historyStmt = $pdo->prepare("SELECT user_message, bot_reply, intent FROM chatbot_logs WHERE session_id = ? ORDER BY created_at DESC LIMIT 10");
$historyStmt->execute([$sessionId]);
$history = array_reverse($historyStmt->fetchAll());

// Récupérer les préférences utilisateur de la session
$userPrefs = get_user_preferences($pdo, $sessionId);

// ===== TENTATIVE AVEC GEMINI API =====
$geminiApiKey = getenv('GOOGLE_GEMINI_API_KEY') ?: ($settings['google_gemini_api_key'] ?? null);
if ($geminiApiKey && strpos($geminiApiKey, 'AIza') === false) {
    $geminiApiKey = decrypt_data($geminiApiKey);
}

$aiReplyGenerated = false;
if ($geminiApiKey && !$limitReached) {
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $geminiApiKey;

    $contents = [];
    
    // Prompt système AMÉLIORÉ avec instructions détaillées
    $systemPrompt = <<<PROMPT
RÔLE: Tu es l'assistant virtuel de $siteName, un centre de formation numérique en Côte d'Ivoire.

CONTEXTE:
- Centre : $siteName
- Téléphone/WhatsApp : $contactPhone
- Email : $contactEmail
- Adresse : $contactAddress
- Domaines de formation : $catList
PROMPT;

    if (!empty($userPrefs)) {
        $systemPrompt .= "\n- Préférences utilisateur : " . json_encode($userPrefs);
    }

    $systemPrompt .= <<<PROMPT

DIRECTIVES IMPORTANTES:
1. Réponds TOUJOURS en français, de manière concise, polie et professionnelle
2. Sois amical et encourageant sans être excessif
3. Si le sujet sort de ta compétence (questions techniques complexes, problèmes légaux), propose une escalade
4. Ne promets jamais des garanties que tu ne peux pas tenir
5. Si l'utilisateur demande les PRIX : explique que des facilités de paiement existent, propose de le mettre en contact avec un conseiller
6. Si l'utilisateur demande à s'INSCRIRE : dirige-le vers la section dédiée ou propose que quelqu'un l'appelle
7. N'utilise pas de formatage complexe (markdown minimal : gras/italique seulement si nécessaire)
8. Reste dans le contexte du centre de formation
9. Propose des actions concrètes ou des informations précises
10. Si après 2 tentatives le problème persiste, marque comme nécessitant une escalade

Type de demande détecté : $intent
PROMPT;

    // Ajouter l'historique (max 10 messages = 5 échanges)
    foreach ($history as $index => $chat) {
        $userText = ($index === 0) ? $systemPrompt . "\n\nMessage utilisateur: " . $chat['user_message'] : $chat['user_message'];
        $contents[] = ["role" => "user", "parts" => [["text" => $userText]]];
        $contents[] = ["role" => "model", "parts" => [["text" => $chat['bot_reply']]]];
    }

    // Message actuel
    $currentInput = (empty($contents)) ? $systemPrompt . "\n\nMessage utilisateur: " . $message : $message;
    $contents[] = ["role" => "user", "parts" => [["text" => $currentInput]]];

    $data = [
        "contents" => $contents,
        "generationConfig" => [
            "temperature" => 0.6,
            "maxOutputTokens" => 250,
            "topP" => 0.9,
            "topK" => 40,
        ]
    ];

    $apiResult = APIHelper::post($apiUrl, $data);

    if ($apiResult['success'] && isset($apiResult['data']['candidates'][0]['content']['parts'][0]['text'])) {
        $aiReply = $apiResult['data']['candidates'][0]['content']['parts'][0]['text'];
        $response['reply'] = trim($aiReply);
        $aiReplyGenerated = true;
        
        // Détecter si escalade nécessaire
        $escalationKeywords = ['escalade', 'technicien', 'spécialiste', 'vous appeler', 'quelqu\'un va vous'];
        if (preg_match('/' . implode('|', $escalationKeywords) . '/i', $response['reply'])) {
            $response['needs_support'] = true;
        }
    }
}

// ===== FALLBACK PAR MOTS-CLÉS si Gemini n'a pas généré de réponse =====
if (!$aiReplyGenerated) {
    $response['reply'] = generate_fallback_reply($message, $intent, $siteName, $contactPhone, $contactEmail, $contactAddress, $catList);
    
    // Détecter si escalade nécessaire
    if (in_array($intent, ['technical_issue', 'support_request'], true)) {
        $response['needs_support'] = true;
    }
}

// Mémoriser les préférences si détectées
if ($intent === 'formation_inquiry' && preg_match('/\b(formation|domaine|secteur|parcours|certification)\b/', $message)) {
    update_user_preferences($pdo, $sessionId, ['interested_in_formation' => true, 'last_intent' => $intent]);
}

$response['timestamp'] = date('H:i');
$logId = save_chat_log($pdo, $message, $response['reply'], $intent, $response['needs_support'] ? 1 : 0);
$response['message_id'] = $logId;
echo json_encode($response);
exit;

// ===== FONCTIONS UTILITAIRES =====

/**
 * Détecte l'intention de l'utilisateur
 */
function detect_intent(string $message): string {
    $message = strtolower($message);
    
    if (preg_match('/\b(formation|apprendre|cours|parcours|domaine|secteur|programme|certification|diplôme)\b/', $message)) {
        return 'formation_inquiry';
    } elseif (preg_match('/\b(prix|tarif|coût|argent|payer|paye|gratuit|offert|financement|facilité|devis)\b/', $message)) {
        return 'pricing_inquiry';
    } elseif (preg_match('/\b(inscription|inscrire|postuler|rejoindre|enrôlement|candidature|s\'inscrire|s inscrire)\b/', $message)) {
        return 'enrollment';
    } elseif (preg_match('/\b(contact|whatsapp|téléphone|appel|écrire|mail|email|courriel)\b/', $message)) {
        return 'contact_request';
    } elseif (preg_match('/\b(où|ou|siège|adresse|lieu|situé|localisation|map|gps|zone)\b/', $message)) {
        return 'location_inquiry';
    } elseif (preg_match('/\b(bonjour|salut|hello|hi|hey|ça va|bonsoir|coucou)\b/', $message)) {
        return 'greeting';
    } elseif (preg_match('/\b(merci|ok|d\'accord|super|top|génial|parfait|bien reçu)\b/', $message)) {
        return 'acknowledgment';
    } elseif (preg_match('/\b(problème|erreur|bug|ne fonctionne|crash|ne marche|souci|issue|incident|panne)\b/', $message)) {
        return 'technical_issue';
    } elseif (preg_match('/\b(débouchés|métier|emploi|carrière|après|suite|opportunité|reconversion)\b/', $message)) {
        return 'career_inquiry';
    } elseif (preg_match('/\b(caméra|caméras|surveillance|vidéosurveillance|installation|câblage|réseau|fibre|chantier|prestation|réalisation|travaux|intervention)\b/u', $message)) {
        return 'service_realisation_inquiry';
    } elseif (preg_match('/\b(urgent|immédiat|besoin d aide|support|conseiller|spécialiste|devis personnalisé)\b/', $message)) {
        return 'support_request';
    } else {
        return 'unclear';
    }
}

/**
 * Génère une réponse de fallback basée sur l'intent
 */
function generate_fallback_reply(string $message, string $intent, string $siteName, string $contactPhone, string $contactEmail, string $contactAddress, string $catList): string {
    $message = strtolower($message);
    
    switch ($intent) {
        case 'formation_inquiry':
            return "Chez $siteName, nous proposons des formations d'excellence en : $catList. Avez-vous un domaine particulier qui vous intéresse ? Je peux vous en dire plus sur un parcours spécifique.";
        
        case 'service_realisation_inquiry':
            return "Absolument ! Chez $siteName, notre équipe technique intervient directement chez nos clients pour l'installation de caméras de surveillance, le câblage réseau et la sécurité électronique. Vous pouvez voir nos interventions en vidéo et les témoignages de nos clients satisfaits dans la section 'Réalisations' de notre site, ou nous joindre au $contactPhone pour un devis gratuit.";
        
        case 'pricing_inquiry':
            return "Nos tarifs sont adaptés à chaque parcours de formation. Certaines sessions d'initiation sont offertes, tandis que nos parcours certifiants peuvent être financés en plusieurs fois. Pour un devis personnalisé, contactez un conseiller au $contactPhone.";
        
        case 'enrollment':
            return "C'est simple ! Vous pouvez remplir le formulaire d'inscription dans la section 'S'inscrire'. Un de nos conseillers vous rappellera pour valider votre dossier. Vous pouvez aussi nous contacter au $contactPhone pour une aide.";
        
        case 'contact_request':
            return "Vous pouvez nous joindre 📞 par WhatsApp/téléphone : $contactPhone ou par 📧 email : $contactEmail. Nous répondons généralement en moins de 2 heures ! Vous êtes également le bienvenue à visiter notre centre.";
        
        case 'location_inquiry':
            return "Notre centre de formation se situe à : $contactAddress. Vous êtes les bienvenus pour une visite de nos locaux et une discussion avec notre équipe. Vous pouvez nous appeler avant pour vous assurer que quelqu'un sera disponible.";
        
        case 'greeting':
            return "Bonjour 👋 ! Je suis l'assistant virtuel de $siteName. Comment puis-je vous aider ? Vous cherchez une formation, nos tarifs, nos coordonnées ou des infos sur l'inscription ?";
        
        case 'acknowledgment':
            return "Avec plaisir ! Avez-vous d'autres questions sur nos formations ou votre projet professionnel ?";
        
        case 'career_inquiry':
            return "Excellente question ! Nos formations sont conçues pour vous préparer à des métiers en forte demande. Les débouchés dépendent du parcours choisi. Je vous recommande de contacter un conseiller au $contactPhone pour discuter des opportunités d'emploi après votre formation.";
        
        case 'technical_issue':
            return "Je comprends que vous rencontrez un problème technique. Un de nos spécialistes va vous aider. Veuillez nous contacter au $contactPhone ou par email à $contactEmail avec une description du souci, et nous vous répondrons au plus vite.";

        case 'support_request':
            return "Vous souhaitez un support rapide ou un devis personnalisé. Je vous recommande de contacter directement un conseiller au $contactPhone ou par email à $contactEmail. Nous vous accompagnons dès maintenant.";
        
        default:
            return "Je n'ai pas bien compris votre demande. Pouvez-vous préciser si vous cherchez une formation, plus d'infos sur nos tarifs, comment vous inscrire, ou vous avez besoin de nous contacter autrement ?";
    }
}

/**
 * Récupère les préférences utilisateur stockées
 */
function get_user_preferences(\PDO $pdo, string $sessionId): ?array {
    try {
        $stmt = $pdo->prepare("SELECT user_preferences FROM chatbot_logs WHERE session_id = ? AND user_preferences IS NOT NULL ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ? json_decode($row['user_preferences'], true) : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Met à jour les préférences utilisateur
 */
function update_user_preferences(\PDO $pdo, string $sessionId, array $prefs): void {
    // On stocke temporairement en session pour l'inclure dans le prochain log de message
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $current = $_SESSION['chatbot_prefs'] ?? [];
    $_SESSION['chatbot_prefs'] = array_merge($current, $prefs);
}

/**
 * Journalise les conversations avec les nouveaux champs
 */
function save_chat_log(\PDO $pdo, string $msg, string $reply, string $intent = 'unclear', ?int $isEscalated = 0, ?int $rateLimit = 0): int {
    try {
        $prefs = isset($_SESSION['chatbot_prefs']) ? json_encode($_SESSION['chatbot_prefs']) : null;
        
        $logStmt = $pdo->prepare(
            "INSERT INTO chatbot_logs (session_id, user_message, bot_reply, ip_address, intent, conversation_category, is_escalated, user_preferences) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $category = categorize_conversation($intent);
        
        $logStmt->execute([
            session_id() ?: 'no-session',
            mb_substr($msg, 0, 500),
            mb_substr($reply, 0, 500),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $intent,
            $category,
            (int)($isEscalated ?? 0),
            $prefs
        ]);

        return (int)$pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Chatbot Log Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Catégorise la conversation pour analyse
 */
function categorize_conversation(string $intent): string {
    $categories = [
        'formation_inquiry' => 'product_info',
        'pricing_inquiry' => 'pricing',
        'enrollment' => 'enrollment',
        'contact_request' => 'contact',
        'location_inquiry' => 'location',
        'greeting' => 'welcome',
        'acknowledgment' => 'follow_up',
        'career_inquiry' => 'career',
        'technical_issue' => 'support',
        'support_request' => 'support',
        'unclear' => 'other'
    ];
    
    return $categories[$intent] ?? 'other';
}