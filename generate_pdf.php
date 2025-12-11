<?php
session_start();
require('fpdf/fpdf.php');
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    exit('User not logged in.');
}

$user_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-t');

$safe_start_date = $conn->real_escape_string($start_date);
$safe_end_date   = $conn->real_escape_string($end_date);

$safe_start_date = filter_var($start_date, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$safe_end_date   = filter_var($end_date, FILTER_SANITIZE_FULL_SPECIAL_CHARS);


/* ===========================
   2. FETCH DATA using Prepared Statement
   =========================== */

$query = $conn->prepare("
    SELECT 
        t.date,
        t.description,
        c.category_name AS category, 
        t.type, 
        t.amount
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? 
      AND t.date BETWEEN ? AND ?
    ORDER BY t.date DESC
");

// Bind parameters and execute
$query->bind_param("iss", $user_id, $safe_start_date, $safe_end_date);
$query->execute();
$result = $query->get_result();

$data = [];
$total_income = 0;
$total_expense = 0;

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    if ($row['type'] == 'income') {
        $total_income += $row['amount'];
    } else {
        $total_expense += $row['amount'];
    }
}
$balance = $total_income - $total_expense;


/* ===========================
   3. PDF INITIALIZATION & TITLE
   =========================== */

$pdf = new FPDF();
$pdf->AddPage();

// Report Title (TrackSmart)
$pdf->SetFont('Times','B',16);
$pdf->Cell(0, 10, 'TrackSmart', 0, 1, 'C');

// Subtitle and Date Period
$pdf->SetFont('Times','',12);
$pdf->Cell(0, 5, 'Financial Report', 0, 1, 'C');
$pdf->Cell(0, 5, 'In the Period of ' . $safe_start_date . ' to ' . $safe_end_date, 0, 1, 'C');

// Spacer
$pdf->Ln(5); 


/* ===========================
   4. TABLE HEADERS
   =========================== */

$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(200, 220, 255); 
$pdf->SetTextColor(0);

// Header widths (Date, Type, Category, Description, Amount)
$w = array(25, 20, 35, 80, 30);
$header = array('Date', 'Type', 'Category', 'Description', 'Amount (PHP)');

// Print Header Row
for($i=0; $i<count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true); 
}
$pdf->Ln(); 


/* ===========================
   5. TABLE BODY
   =========================== */

$pdf->SetFont('Arial','',10);

foreach($data as $row) {
    // Determine row color based on type
    $type_color = ($row['type'] == 'income') ? array(220, 255, 220) : array(255, 220, 220); 
    $pdf->SetFillColor($type_color[0], $type_color[1], $type_color[2]);
    
    // Format data
    $amount_formatted = 'PHP' . number_format($row['amount'], 2);
    $description_truncated = substr($row['description'], 0, 45) . (strlen($row['description']) > 45 ? '...' : '');

    // Data Cells
    $pdf->Cell($w[0], 6, $row['date'], 'LR', 0, 'L', true);
    $pdf->Cell($w[1], 6, ucfirst($row['type']), 'LR', 0, 'C', true);
    $pdf->Cell($w[2], 6, $row['category'], 'LR', 0, 'L', true);
    $pdf->Cell($w[3], 6, $description_truncated, 'LR', 0, 'L', true);
    $pdf->Cell($w[4], 6, $amount_formatted, 'LR', 0, 'R', true);
    $pdf->Ln();
}

// Closing line for the table
$pdf->Cell(array_sum($w), 0, '', 'T', 1);


/* ===========================
   6. SUMMARY TOTALS
   =========================== */

$pdf->Ln(5); 

// Align the summary to the right of the page
$pdf->SetFont('Arial', 'B', 12);

// Left side (label)
$pdf->Cell(95, 7, 'TOTAL INCOME:', 0, 0, 'L');

// Right side (amount)
$pdf->SetTextColor(34, 197, 94); 
$pdf->Cell(95, 7, 'PHP ' . number_format($total_income, 2), 0, 1, 'R');
$pdf->SetTextColor(0);


$pdf->Cell(95, 7, 'TOTAL EXPENSE:', 0, 0, 'L');

$pdf->SetTextColor(230, 50, 50); // Red text
$pdf->Cell(95, 7, 'PHP' . number_format($total_expense, 2), 0, 1, 'R');
$pdf->SetTextColor(0);

$pdf->Ln(2); 

$pdf->Cell(95, 7, 'NET BALANCE:', 0, 0, 'L');

$balance_color = ($balance >= 0) ? array(34, 197, 94) : array(230, 50, 50);
$pdf->SetTextColor($balance_color[0], $balance_color[1], $balance_color[2]);
$pdf->Cell(95, 7, 'PHP' . number_format($balance, 2), 0, 1, 'R');
$pdf->SetTextColor(0); 


/* ===========================
   7. OUTPUT PDF
   =========================== */

// Clear output buffer and send the PDF headers
ob_end_clean(); 
$pdf->Output('D', 'TrackSmart_Report_' . date('Ymd') . '.pdf');

$conn->close();

?>