<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE contacts SET is_read = 0 WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: ../messages.php?message_status=" . urlencode("Message marqué comme non lu.") . "&status=success");
exit;