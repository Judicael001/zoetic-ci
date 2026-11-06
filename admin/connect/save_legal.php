<?php
session_start();
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$type = $_POST['doc_type'] ?? '';
$lang = $_POST['lang'] ?? 'fr';
$content = $_POST['content'] ?? '';
$admin_id = $_SESSION['admin_id'];

if ($type && $content) {
    try {
        $pdo->beginTransaction();

        // Désactiver l'ancienne version
        $stmt = $pdo->prepare("UPDATE legal_documents SET is_active = 0 WHERE doc_type = ? AND lang = ?");
        $stmt->execute([$type, $lang]);

        // Récupérer le dernier numéro de version
        $vStmt = $pdo->prepare("SELECT MAX(version) FROM legal_documents WHERE doc_type = ? AND lang = ?");
        $vStmt->execute([$type, $lang]);
        $nextVersion = (int)$vStmt->fetchColumn() + 1;

        // Insérer la nouvelle version
        $ins = $pdo->prepare("INSERT INTO legal_documents (doc_type, lang, content, version, is_active, updated_by) VALUES (?, ?, ?, ?, 1, ?)");
        $ins->execute([$type, $lang, $content, $nextVersion, $admin_id]);

        $pdo->commit();
        header("Location: ../legal_docs.php?type=$type&lang=$lang&message=Nouvelle version publiée avec succès !");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur : " . $e->getMessage());
    }
}