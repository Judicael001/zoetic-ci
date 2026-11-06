<?php
require 'src/db/init.php';
require 'src/db/enrollments.php';

// Ensure the table exists
ensure_enrollments_table($pdo);
echo "✓ Table enrollments vérifiée\n";

// Count current enrollments
$count = $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
echo "✓ Inscriptions actuelles: " . $count . "\n\n";

// List recent enrollments
echo "=== Inscriptions récentes ===\n";
$recent = $pdo->query('SELECT id, student_name, student_email, formation_title, status, enrollment_date FROM enrollments ORDER BY enrollment_date DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

if (empty($recent)) {
    echo "Aucune inscription trouvée.\n";
} else {
    foreach ($recent as $enrollment) {
        echo "ID: " . $enrollment['id'] . "\n";
        echo "  Nom: " . $enrollment['student_name'] . "\n";
        echo "  Email: " . $enrollment['student_email'] . "\n";
        echo "  Formation: " . $enrollment['formation_title'] . "\n";
        echo "  Statut: " . $enrollment['status'] . "\n";
        echo "  Date: " . $enrollment['enrollment_date'] . "\n";
        echo "---\n";
    }
}

// Test: Insert a test enrollment
echo "\n=== Test d'inscription ===\n";
try {
    $testStmt = $pdo->prepare(
        'INSERT INTO enrollments (student_name, student_email, student_phone, formation_id, formation_title, message, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $testStmt->execute([
        'Test Utilisateur',
        'test@example.com',
        '0123456789',
        1,
        'Cybersécurité (Niveau 1)',
        'Ceci est une inscription de test',
        'pending'
    ]);
    echo "✓ Inscription de test ajoutée avec succès\n";
    
    // Check new count
    $newCount = $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
    echo "✓ Nouveau total: " . $newCount . "\n";
} catch (Exception $e) {
    echo "✗ Erreur lors de l'insertion: " . $e->getMessage() . "\n";
}
?>
