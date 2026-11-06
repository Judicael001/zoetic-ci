<?php
require_once '_auth.php';
require_admin_login();
require_once '../src/db/init.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inscriptions_zoetic_'.date('Y-m-d').'.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Nom', 'Email', 'Telephone', 'Formation', 'Statut', 'Date']);

$query = $pdo->query("SELECT id, student_name, student_email, student_phone, formation_title, status, enrollment_date FROM enrollments ORDER BY enrollment_date DESC");

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;