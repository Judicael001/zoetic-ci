<?php
require_once '../../src/db/init.php';

$enrollmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = $_GET['action'] ?? 'approve';
$status = 'approved';

if ($action === 'cancel') {
    $status = 'cancelled';
} elseif ($action === 'approve') {
    $status = 'approved';
}

if ($enrollmentId <= 0) {
    header('Location: ../inscriptions.php?message=' . urlencode('Identifiant d’inscription invalide.') . '&status=error');
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE enrollments SET status = ? WHERE id = ?');
    $stmt->execute([$status, $enrollmentId]);

    $message = $status === 'cancelled' ? 'Inscription marquée comme annulée.' : 'Inscription approuvée avec succès.';
    header('Location: ../inscriptions.php?message=' . urlencode($message) . '&status=success');
    exit;
} catch (PDOException $e) {
    error_log('update_enrollment.php PDOException: ' . $e->getMessage());
    header('Location: ../inscriptions.php?message=' . urlencode('Impossible de mettre à jour le statut de l’inscription.') . '&status=error');
    exit;
}
