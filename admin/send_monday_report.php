<?php
/**
 * Script d'automatisation du rapport hebdomadaire ZoeTIC
 * À exécuter via Cron ou Task Scheduler
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/../src/support/phpmailer_fallback.php';
    error_log('PHPMailer non disponible: fallback local chargé.');
}

require_once __DIR__ . '/../../src/db/init.php';

// 1. SÉCURITÉ : Vérifier le jeton si appelé via URL (ex: ?token=VOTRE_CLE_SECRETE)
// En CLI (ligne de commande), on ignore cette vérification.
$secret_token = "ZoeTIC_Weekly_Report_2024"; 
if (php_sapi_name() !== 'cli') {
    if (($_GET['token'] ?? '') !== $secret_token) {
        http_response_code(403);
        die("Accès non autorisé.");
    }
}

// 2. RÉCUPÉRATION DES DONNÉES
try {
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users) as users,
            (SELECT COUNT(*) FROM formations) as formations,
            (SELECT COUNT(*) FROM enrollments) as enrollments,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'approved') as approved,
            (SELECT COUNT(*) FROM blog_posts) as blog,
            (SELECT COUNT(*) FROM newsletter_subscribers) as newsletter
    ")->fetch();

    $userCount = (int) $stats['users'];
    $enrollmentCount = (int) $stats['enrollments'];
    $newsletterCount = (int) $stats['newsletter'];
    $convRate = $userCount > 0 ? round(($enrollmentCount / $userCount) * 100, 1) : 0;
    $newsRate = $userCount > 0 ? round(($newsletterCount / $userCount) * 100, 1) : 0;

} catch (PDOException $e) {
    error_log("Cron Error: " . $e->getMessage());
    exit;
}

// 3. CONSTRUCTION DE L'EMAIL (Style inspiré de report_performance.php)
$to = "direction@zoetic.ci"; // Changez par votre email
$subject = "📊 ZoeTIC : Rapport Hebdomadaire de Performance - " . date('d/m/Y');

$message = "
<html>
<body style='font-family: sans-serif; color: #334155; padding: 20px; line-height: 1.6;'>
    <div style='max-width: 600px; margin: auto; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden;'>
        <div style='background-color: #3F8F29; padding: 30px; text-align: center; color: white;'>
            <h1 style='margin: 0; font-size: 24px;'>Rapport de Performance Hebdomadaire</h1>
            <p style='margin-top: 10px; opacity: 0.9;'>Généré automatiquement le " . date('d/m/Y à H:i') . "</p>
        </div>
        
        <div style='padding: 30px;'>
            <div style='display: flex; justify-content: space-between; margin-bottom: 30px; text-align: center;'>
                <div style='flex: 1; padding: 10px; border-right: 1px solid #f1f5f9;'>
                    <span style='font-size: 10px; font-weight: bold; text-transform: uppercase; color: #94a3b8;'>Étudiants</span>
                    <div style='font-size: 20px; font-weight: bold; color: #0f172a;'>$userCount</div>
                </div>
                <div style='flex: 1; padding: 10px; border-right: 1px solid #f1f5f9;'>
                    <span style='font-size: 10px; font-weight: bold; text-transform: uppercase; color: #94a3b8;'>Inscriptions</span>
                    <div style='font-size: 20px; font-weight: bold; color: #0f172a;'>$enrollmentCount</div>
                </div>
                <div style='flex: 1; padding: 10px;'>
                    <span style='font-size: 10px; font-weight: bold; text-transform: uppercase; color: #94a3b8;'>Validées</span>
                    <div style='font-size: 20px; font-weight: bold; color: #3F8F29;'>".$stats['approved']."</div>
                </div>
            </div>

            <h2 style='font-size: 14px; text-transform: uppercase; border-bottom: 2px solid #3F8F29; padding-bottom: 5px;'>Indicateurs Clés (KPI)</h2>
            
            <p><strong>Taux de Conversion : $convRate%</strong></p>
            <div style='background: #f1f5f9; border-radius: 10px; height: 8px; margin-bottom: 20px;'>
                <div style='background: #3F8F29; width: $convRate%; height: 8px; border-radius: 10px;'></div>
            </div>

            <p><strong>Engagement Newsletter : $newsRate%</strong></p>
            <div style='background: #f1f5f9; border-radius: 10px; height: 8px; margin-bottom: 20px;'>
                <div style='background: #3b82f6; width: $newsRate%; height: 8px; border-radius: 10px;'></div>
            </div>

            <div style='margin-top: 40px; padding: 20px; background: #f8fafc; border-radius: 15px; font-size: 12px; color: #64748b;'>
                Ce rapport est généré par le système ZoeTIC. Pour plus de détails, connectez-vous au <a href='https://zoetic.ci/admin/dashboard.php' style='color: #3F8F29;'>Tableau de bord</a>.
            </div>
        </div>
    </div>
</body>
</html>
";

// 4. ENVOI VIA PHPMAILER
$mail = new PHPMailer(true);

try {
    // Configuration du serveur SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.votre-serveur.com'; // Ex: smtp.gmail.com ou mail.zoetic.ci
    $mail->SMTPAuth   = true;
    $mail->Username   = 'votre-email@zoetic.ci'; // Votre identifiant SMTP
    $mail->Password   = 'votre-mot-de-passe';    // Votre mot de passe SMTP
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Ou ENCRYPTION_SMTPS pour le port 465
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Destinataires
    $mail->setFrom('noreply@zoetic.ci', 'System ZoeTIC');
    $mail->addAddress($to);

    // Contenu de l'email
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;

    $mail->send();
    echo "Rapport envoyé avec succès à $to";

    // Optionnel : Logger l'envoi dans admin_logs
    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description) VALUES (0, 'SYSTEM', 'REPORT_SENT', 'Envoi automatique du rapport hebdo via PHPMailer')");
    $logStmt->execute();
} catch (Exception $e) {
    echo "Erreur lors de l'envoi du mail : {$mail->ErrorInfo}";
    error_log("PHPMailer Error: " . $mail->ErrorInfo);
}
