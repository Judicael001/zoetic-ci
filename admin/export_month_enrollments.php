<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';
require_once '../src/db/enrollments.php';

ensure_enrollments_table($pdo);

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$filename = "inscriptions_zoetic_" . $year . "_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Ajout du BOM UTF-8 pour une compatibilité parfaite avec Excel (accents)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes du fichier
fputcsv($output, ['ID', 'Nom Complet', 'Email', 'Téléphone', 'Formation', 'Message', 'Statut', 'Date Inscription'], ';');

$stmt = $pdo->prepare("
    SELECT id, student_name, student_email, student_phone, formation_title, message, status, enrollment_date 
    FROM enrollments 
    WHERE YEAR(enrollment_date) = ? AND MONTH(enrollment_date) = ? 
    ORDER BY enrollment_date DESC
");
$stmt->execute([$year, $month]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;