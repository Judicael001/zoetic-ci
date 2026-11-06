<?php
require_once __DIR__ . '/src/db/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Veuillez remplir tous les champs obligatoires.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Adresse email invalide.']);
    exit;
}

try {
    ensure_system_tables($pdo);
    
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Votre message a été envoyé avec succès.'
    ]);
} catch (PDOException $e) {
    error_log('save_contact.php error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Une erreur est survenue lors de l\'enregistrement de votre message.'
    ]);
}