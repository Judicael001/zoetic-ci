<?php
session_start();

// Connexion à la base de données
require_once '../../src/db/init.php';

// Récupérer les données du formulaire
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$role = $_POST['role'] ?? 'admin';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validations
$errors = [];

if (empty($name)) {
    $errors[] = 'Le nom est requis.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide.';
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = 'Le mot de passe doit contenir au minimum 6 caractères.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Les mots de passe ne correspondent pas.';
}

if (!in_array($role, ['admin', 'editor', 'super_admin'])) {
    $errors[] = 'Rôle invalide.';
}

// S'il y a des erreurs, rediriger avec message
if (!empty($errors)) {
    $message = implode(' ', $errors);
    header("Location: ../inscription.php?message=" . urlencode($message) . "&status=error");
    exit;
}

try {
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: ../inscription.php?message=" . urlencode("Cet email est déjà utilisé.") . "&status=error");
        exit;
    }

    // Créer un hash du mot de passe
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insérer l'administrateur dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO admins (fullname, email, phone, role, password, is_active)
        VALUES (?, ?, ?, ?, ?, TRUE)
    ");

    $stmt->execute([$name, $email, $phone, $role, $password_hash]);

    // Rediriger avec message de succès
    header("Location: ../inscription.php?message=" . urlencode("Admin créé avec succès ! Vous pouvez maintenant vous connecter.") . "&status=success");
    exit;

} catch (PDOException $e) {
    header("Location: ../inscription.php?message=" . urlencode("Erreur lors de la création du compte.") . "&status=error");
    exit;
}
?>
