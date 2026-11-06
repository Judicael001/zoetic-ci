<?php
session_start();
require_once __DIR__ . '/src/db/init.php';
require_once __DIR__ . '/src/db/enrollments.php';

header('Content-Type: application/json');

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide. Veuillez réessayer.']);
    exit;
}

ensure_enrollments_table($pdo);

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$formationId = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;
$formationTitle = trim($_POST['formation_title'] ?? '');
$message = trim(strip_tags($_POST['message'] ?? ''));

$studentName = trim($firstName . ' ' . $lastName);

if ($studentName === '' || $email === '' || $phone === '') {
    echo json_encode(['status' => 'error', 'message' => 'Votre nom, email et téléphone sont requis.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Veuillez fournir une adresse email valide.']);
    exit;
}

$phone = preg_replace('/[^0-9+]/', '', $phone);
if ($phone === '') {
    echo json_encode(['status' => 'error', 'message' => 'Veuillez fournir un numéro de téléphone valide.']);
    exit;
}

if ($formationId > 0) {
    $foundTitle = get_formation_title($pdo, $formationId);
    if ($foundTitle !== null) {
        $formationTitle = $foundTitle;
    }
}

if ($formationTitle === '') {
    $formationTitle = 'Session de formation';
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO enrollments (student_name, student_email, student_phone, formation_id, formation_title, message, status) VALUES (?, ?, ?, ?, ?, ?, ?)' 
    );
    $stmt->execute([
        $studentName,
        $email,
        $phone,
        $formationId > 0 ? $formationId : null,
        $formationTitle,
        $message,
        'pending',
    ]);

    // Log new enrollment for admin notification
    try {
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([
            0, // System or visitor action
            'VISITOR',
            'NEW_ENROLLMENT',
            "Nouvelle demande d'inscription pour '" . $formationTitle . "' par " . $studentName,
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    } catch (Throwable $logError) {
        error_log('save_enrollment.php log failed: ' . $logError->getMessage());
    }

    echo json_encode(['status' => 'success', 'message' => 'Votre demande est bien enregistrée. Nous vous contacterons dès que possible.']);
    exit;
} catch (PDOException $e) {
    error_log('save_enrollment.php PDOException: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Impossible d’enregistrer votre inscription pour le moment. Réessayez plus tard.']);
    exit;
}
