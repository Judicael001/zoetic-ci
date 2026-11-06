<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'admin';
$password = $_POST['password'] ?? '';
$notification_sound = isset($_POST['notification_sound']) ? 1 : 0;

if (!$fullname || !$email) {
    header("Location: ../admins.php?status=error&message=Nom et Email requis.");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../admins.php?status=error&message=" . urlencode("Format d'email invalide."));
    exit;
}

try {
    if ($id > 0) {
        // Récupérer les données actuelles pour comparer le rôle
        $oldStmt = $pdo->prepare("SELECT fullname, role FROM admins WHERE id = ?");
        $oldStmt->execute([$id]);
        $oldAdmin = $oldStmt->fetch();

        // Update
        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE admins SET fullname = ?, email = ?, role = ?, password = ?, notification_sound = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $role, $pass_hash, $notification_sound, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET fullname = ?, email = ?, role = ?, notification_sound = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $role, $notification_sound, $id]);
        }

        // Journalisation si le rôle a été modifié
        if ($oldAdmin && $oldAdmin['role'] !== $role) {
            $logDesc = "Changement de rôle pour " . $oldAdmin['fullname'] . " : de [" . $oldAdmin['role'] . "] à [" . $role . "]";
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'ROLE_CHANGE', ?, ?)");
            $logStmt->execute([$_SESSION['admin_id'], $_SESSION['admin_name'], $logDesc, $_SERVER['REMOTE_ADDR']]);
        }

        // Mettre à jour la session si c'est l'admin actuel qui se modifie
        if ($id === (int)$_SESSION['admin_id']) {
            $_SESSION['admin_notification_sound'] = $notification_sound;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = $fullname;
        }

        $msg = "Compte mis à jour.";
    } else {
        // Create
        $pass_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admins (fullname, email, role, password, is_active, notification_sound) VALUES (?, ?, ?, ?, 1, ?)");
        $stmt->execute([$fullname, $email, $role, $pass_hash, $notification_sound]);

        // Journalisation de la création
        $logDesc = "Nouvel administrateur créé : " . $fullname . " (Rôle initial: " . $role . ")";
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'CREATE_ADMIN', ?, ?)");
        $logStmt->execute([$_SESSION['admin_id'], $_SESSION['admin_name'], $logDesc, $_SERVER['REMOTE_ADDR']]);

        $msg = "Nouvel admin créé.";
    }
    header("Location: ../admins.php?status=success&message=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../admins.php?status=error&message=Erreur: Email peut-être déjà utilisé.");
}
exit;