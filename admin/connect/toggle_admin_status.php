<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Sécurité: Empêcher de désactiver le dernier super_admin
    $stmt = $pdo->prepare("SELECT fullname, role, is_active FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();

    if ($admin['role'] === 'super_admin' && $admin['is_active'] == 1) {
        $count = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1")->fetchColumn();
        if ($count <= 1) {
            header("Location: ../admins.php?status=error&message=Impossible de désactiver le dernier Super Admin.");
            exit;
        }
    }

    $pdo->prepare("UPDATE admins SET is_active = NOT is_active WHERE id = ?")->execute([$id]);

    // Journalisation du changement de statut
    $newStatus = $admin['is_active'] ? 'Désactivé' : 'Activé';
    $logDesc = "Statut de " . $admin['fullname'] . " changé en : " . $newStatus;
    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'STATUS_CHANGE', ?, ?)");
    $logStmt->execute([$_SESSION['admin_id'], $_SESSION['admin_name'], $logDesc, $_SERVER['REMOTE_ADDR']]);
}
header("Location: ../admins.php?status=success&message=Statut mis à jour.");
exit;