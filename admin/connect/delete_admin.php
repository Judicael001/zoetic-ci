<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_admin_id = $_SESSION['admin_id'];

if ($id <= 0) {
    header("Location: ../admins.php?message=" . urlencode("Identifiant invalide.") . "&status=error");
    exit;
}

if ($id === (int)$current_admin_id) {
    header("Location: ../admins.php?message=" . urlencode("Vous ne pouvez pas supprimer votre propre compte.") . "&status=error");
    exit;
}

try {
    // 1. Récupérer le rôle de l'admin à supprimer
    $stmt = $pdo->prepare("SELECT fullname, role FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $adminToDelete = $stmt->fetch();

    if (!$adminToDelete) {
        throw new Exception("Administrateur introuvable.");
    }

    // 2. Si c'est un super_admin, vérifier qu'il en reste d'autres actifs
    if ($adminToDelete['role'] === 'super_admin') {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1");
        $superAdminCount = (int)$countStmt->fetchColumn();

        if ($superAdminCount <= 1) {
            throw new Exception("Action impossible : il doit rester au moins un Super Administrateur sur la plateforme.");
        }
    }

    $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);

    // Journalisation de la suppression
    $logDesc = "Administrateur supprimé : " . $adminToDelete['fullname'] . " (Rôle: " . $adminToDelete['role'] . ")";
    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'DELETE_ADMIN', ?, ?)");
    $logStmt->execute([$_SESSION['admin_id'], $_SESSION['admin_name'], $logDesc, $_SERVER['REMOTE_ADDR']]);

    header("Location: ../admins.php?message=" . urlencode("Administrateur supprimé avec succès.") . "&status=success");

} catch (Exception $e) {
    header("Location: ../admins.php?message=" . urlencode($e->getMessage()) . "&status=error");
}
exit;