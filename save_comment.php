<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/src/db/init.php';
require_once __DIR__ . '/src/db/blog.php';

ensure_blog_table($pdo);
ensure_blog_comments_table($pdo);

$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/src/support/phpmailer_fallback.php';
    error_log('PHPMailer non disponible: fallback local chargé.');
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

$postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$content = trim((string)($_POST['content'] ?? ''));
$notifyReplies = isset($_POST['notify_replies']) ? 1 : 0;

// Vérification Anti-spam HoneyPot
if (!empty($_POST['website_url'])) {
    echo json_encode(['status' => 'error', 'message' => 'Spam détecté.']);
    exit;
}

if ($postId <= 0 || $name === '' || $email === '' || $content === '') {
    echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont obligatoires.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Adresse email invalide.']);
    exit;
}

    $postStmt = $pdo->prepare("SELECT title, slug FROM blog_posts WHERE id = ?");
    $postStmt->execute([$postId]);
    $postData = $postStmt->fetch(PDO::FETCH_ASSOC);
    $postTitle = $postData['title'] ?? 'Article ZoeTIC';

try {
    $stmt = $pdo->prepare("INSERT INTO blog_comments (post_id, parent_id, name, email, content, notify_replies) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$postId, $parentId, $name, $email, $content, $notifyReplies]);

    // Log new comment for admin notification
    try {
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([
            0, // System or visitor action
            'VISITOR',
            'NEW_COMMENT',
            "Nouveau commentaire sur l'article '" . $postTitle . "' par " . $name,
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    } catch (Throwable $logError) {
        error_log('save_comment.php log failed: ' . $logError->getMessage());
    }

    $articleSlug = $postData['slug'] ?? '';
    $articleUrl = $articleSlug !== '' ? "https://zoetic.ci/blog_post.php?slug=" . urlencode($articleSlug) : "https://zoetic.ci/index.php#page-blog";

    if (class_exists(PHPMailer::class)) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.votre-serveur.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'votre-email@zoetic.ci';
            $mail->Password = 'votre-mot-de-passe';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('noreply@zoetic.ci', 'ZoeTIC Blog');
            $mail->addAddress('direction@zoetic.ci');

            $mail->isHTML(true);
            $mail->Subject = "💬 Nouveau commentaire : " . $postTitle;

            $type = ($parentId !== null) ? 'Réponse' : 'Nouveau commentaire';
            $mail->Body = "
                <div style='font-family: sans-serif; color: #334155; line-height: 1.6;'>
                    <h2 style='color: #3F8F29;'>$type sur ZoeTIC</h2>
                    <p><strong>Article :</strong> $postTitle</p>
                    <p><strong>Auteur :</strong> $name ($email)</p>
                    <p><strong>Message :</strong><br>" . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . "</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #64748b;'>Vous pouvez gérer ce commentaire dans votre <a href='https://zoetic.ci/admin/comments.php' style='color: #3F8F29;'>espace de modération</a>.</p>
                </div>";

            $mail->send();

            if ($parentId !== null) {
                $parentStmt = $pdo->prepare("SELECT name, email, notify_replies FROM blog_comments WHERE id = ?");
                $parentStmt->execute([$parentId]);
                $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);

                if ($parent && !empty($parent['email']) && $parent['email'] !== $email && (int)$parent['notify_replies'] === 1) {
                    $mail->clearAddresses();
                    $mail->addAddress($parent['email'], $parent['name']);

                    $mail->Subject = "🔔 Nouvelle réponse à votre commentaire - ZoeTIC";
                    $mail->Body = "
                        <div style='font-family: sans-serif; color: #334155; line-height: 1.6;'>
                            <h2 style='color: #3F8F29;'>Bonjour " . htmlspecialchars($parent['name'], ENT_QUOTES, 'UTF-8') . ",</h2>
                            <p><strong>$name</strong> vient de répondre à votre commentaire sur l'article :<br>
                            <span style='color: #64748b; font-weight: bold;'>$postTitle</span></p>

                            <div style='background: #f8fafc; padding: 20px; border-radius: 1rem; margin: 20px 0; border: 1px solid #e2e8f0;'>
                                <p style='margin: 0; font-size: 14px; color: #94a3b8; text-transform: uppercase; font-weight: bold;'>Sa réponse :</p>
                                <p style='font-style: italic; margin-top: 10px; color: #334155;'>\"" . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . "\"</p>
                            </div>

                            <p>Vous pouvez rejoindre la discussion en cliquant sur le lien ci-dessous :<br>
                            <a href='$articleUrl#comments-section' style='display: inline-block; background-color: #3F8F29; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; margin-top: 10px;'>Voir la discussion</a></p>

                            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                            <p style='font-size: 12px; color: #94a3b8; text-align: center;'>Cet email est généré automatiquement par le blog ZoeTIC.</p>
                        </div>";
                    $mail->send();
                }
            }
        } catch (Exception $e) {
            error_log('Comment Notification Email Error: ' . $mail->ErrorInfo);
        }
    } else {
        error_log('Comment notification email ignoré: PHPMailer non installé.');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Commentaire publié !',
        'date' => "À l'instant",
        'initial' => strtoupper(substr($name, 0, 1)),
        'is_reply' => $parentId !== null
    ]);
} catch (PDOException $e) {
    error_log('save_comment.php PDOException: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la publication.']);
}
