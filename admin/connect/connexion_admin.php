<?php
require_once '../_auth.php';

// Connexion à la base de données
require_once '../../src/db/init.php';

// Récupérer les données du formulaire
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validations
if (empty($email) || empty($password)) {
    header("Location: ../connexion.php?message=" . urlencode("Email et mot de passe requis.") . "&status=error");
    exit;
}

try {
    // Récupérer l'administrateur
    $stmt = $pdo->prepare("SELECT id, fullname, email, password, role, is_active, notification_sound FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || (int)$admin['is_active'] !== 1 || !password_verify($password, $admin['password'])) {
        header("Location: ../connexion.php?message=" . urlencode("Email ou mot de passe incorrect ou compte inactif.") . "&status=error");
        exit;
    }

    // Créer la session
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['fullname'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_notification_sound'] = (int)$admin['notification_sound'];

    // Rediriger vers le tableau de bord
    header("Location: ../dashboard.php");
    exit;

} catch (PDOException $e) {
    header("Location: ../connexion.php?message=" . urlencode("Erreur lors de la connexion.") . "&status=error");
    exit;
}
?>
