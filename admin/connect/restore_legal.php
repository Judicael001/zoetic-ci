<?php
require_once '../_auth.php';
require_admin_login();
require_once '../../src/db/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $pdo->beginTransaction();

        // 1. Récupérer le type et la langue de la version cible
        $stmt = $pdo->prepare("SELECT doc_type, lang FROM legal_documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if ($doc) {
            // 2. Désactiver l'ancienne version active
            $pdo->prepare("UPDATE legal_documents SET is_active = 0 WHERE doc_type = ? AND lang = ?")->execute([$doc['doc_type'], $doc['lang']]);
            
            // 3. Activer la version choisie
            $pdo->prepare("UPDATE legal_documents SET is_active = 1 WHERE id = ?")->execute([$id]);
            
            $pdo->commit();
            header("Location: ../legal_docs.php?type={$doc['doc_type']}&lang={$doc['lang']}&message=Ancienne version restaurée avec succès.");
            exit;
        }
    } catch (Exception $e) { $pdo->rollBack(); }
}
header("Location: ../legal_docs.php?status=error&message=Erreur lors de la restauration.");
exit;