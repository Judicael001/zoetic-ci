<?php
require_once '../_auth.php';
require_super_admin();
require_once '../../src/db/init.php';

try {
    // 1. Compter les entrées à supprimer pour le rapport
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
    $checkStmt->execute();
    $count = (int)$checkStmt->fetchColumn();

    if ($count > 0) {
        // 2. Journaliser l'action avant la purge
        $logDesc = "Purge de la base de données : $count logs de plus de 6 mois supprimés.";
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action_type, description, ip_address) VALUES (?, ?, 'CLEANUP', ?, ?)");
        $logStmt->execute([$_SESSION['admin_id'], $_SESSION['admin_name'], $logDesc, $_SERVER['REMOTE_ADDR']]);

        // 3. Supprimer les anciens logs
        $stmt = $pdo->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
        $stmt->execute();

        $message = "Nettoyage réussi. $count entrées ont été supprimées.";
        $status = "success";
    } else {
        $message = "Aucun log ancien à supprimer.";
        $status = "info";
    }
} catch (PDOException $e) {
    $message = "Erreur lors du nettoyage : " . $e->getMessage();
    $status = "error";
}

header("Location: ../logs.php?message=" . urlencode($message) . "&status=" . $status);
exit;