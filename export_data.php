<?php
session_start();
require 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    exit('User not logged in.');
}

$user_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-t');

$safe_start_date = $conn->real_escape_string($start_date);
$safe_end_date   = $conn->real_escape_string($end_date);

$file_name = "financial_report_" . $start_date . "_to_" . $end_date . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $file_name . '"');

$output = fopen('php://output', 'w'); // Open output stream

fputcsv($output, ['Date', 'Description', 'Category', 'Type', 'Amount'], ','); 

$result = $conn->query("
    SELECT 
        t.date,
        t.description,
        c.category_name AS category, 
        t.type, 
        t.amount
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id 
      AND t.date BETWEEN '$safe_start_date' AND '$safe_end_date'
    ORDER BY t.date DESC
");

// --- Write Data Rows ---
if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row, ',');
    }
}

fclose($output);
exit;
?>