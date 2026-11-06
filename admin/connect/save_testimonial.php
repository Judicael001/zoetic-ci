<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$name = trim($_POST['author_name'] ?? '');
$role = trim($_POST['author_role'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($name && $content) {
    try {
        $stmt = $pdo->prepare("INSERT INTO testimonials (author_name, author_role, content) VALUES (?, ?, ?)");
        $stmt->execute([$name, $role, $content]);
        $msg = "Témoignage ajouté avec succès.";
        $status = "success";
    } catch (PDOException $e) {
        $msg = "Erreur lors de l'enregistrement.";
        $status = "error";
    }
} else {
    $msg = "Le nom et le contenu sont obligatoires.";
    $status = "error";
}

header("Location: ../testimonials.php?message=" . urlencode($msg) . "&status=" . $status);
exit;