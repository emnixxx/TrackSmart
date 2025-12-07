<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$default_start_date = date('Y-m-01');
$default_end_date   = date('Y-m-t');

$start_date = $default_start_date;
$end_date   = $default_end_date;
$date_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['start_date']) && isset($_GET['end_date']))) {
    $input_start = $_POST['start_date'] ?? $_GET['start_date'];
    $input_end   = $_POST['end_date']   ?? $_GET['end_date'];

    if (strtotime($input_start) === false || strtotime($input_end) === false) {
        $date_error = "Invalid date format submitted.";
    } elseif (strtotime($input_start) > strtotime($input_end)) {
        $date_error = "Start date cannot be after the end date.";
    } else {
        $start_date = $input_start;
        $end_date   = $input_end;
    }
}

$safe_start_date = $conn->real_escape_string($start_date);
$safe_end_date   = $conn->real_escape_string($end_date);

$date_filter = "t.date BETWEEN '$safe_start_date' AND '$safe_end_date'";

// =========================
// TOTALS (INCOME / EXPENSE)
// =========================
$totalIncome = 0;
$totalExpense = 0;

$totals = $conn->query("
    SELECT 
        SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
    FROM transactions t
    WHERE t.user_id = $user_id
    AND $date_filter
");

if ($row = $totals->fetch_assoc()) {
    $totalIncome  = $row['total_income']  ?? 0;
    $totalExpense = $row['total_expense'] ?? 0;
}

$netSavings = $totalIncome - $totalExpense;

// =========================
// EXPENSES BY CATEGORY
// =========================
$catLabels = [];
$catTotals = [];
$catResult = $conn->query("
    SELECT 
        c.category_name, SUM(t.amount) AS total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id 
      AND t.type = 'expense' 
      AND $date_filter
    GROUP BY c.category_name
    ORDER BY total DESC
");

while ($row = $catResult->fetch_assoc()) {
    $catLabels[] = $row['category_name'];
    $catTotals[] = (float) $row['total'];
}

// =========================
// INCOME vs EXPENSE
// =========================
$incomeSeries = 0;
$expenseSeries = 0;
$barResult = $conn->query("
    SELECT 
        SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) AS income_total,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense_total
    FROM transactions t
    WHERE t.user_id = $user_id 
      AND $date_filter
");

if ($row = $barResult->fetch_assoc()) {
    $incomeSeries  = (float) $row['income_total']  ?? 0;
    $expenseSeries = (float) $row['expense_total'] ?? 0;
}

$label = date('M d', strtotime($start_date)) . ' - ' . date('M d', strtotime($end_date));
$dateLabel = [$label];
$incomeData = [$incomeSeries];
$expenseData = [$expenseSeries];

// =========================
// DATA TABLE
// =========================
$dataResult = $conn->query("
    SELECT 
        t.*, c.category_name, c.hex_color
    FROM transactions t, categories c
    WHERE t.user_id = $user_id 
      AND t.category_id = c.id
      AND $date_filter
    ORDER BY t.date DESC
");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports • TrackSmart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=30">
    <link rel="stylesheet" href="assets/css/reports.css?v=30">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/reports.js"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="reports-wrapper">

        <!-- HEADER -->
        <h1>Reports</h1>
        <?php if ($date_error): ?>
            <p class="date-error">⚠️ <?= htmlspecialchars($date_error) ?></p>
        <?php endif; ?>

        <div  class="report-input-card">
            <form method="POST" id="dateFilterForm" class="report-form">
                <label for="start_date">Start Date:</label>
                <input type="date" class="todo-date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                
                <label for="end_date">End Date:</label>
                <input type="date" class="todo-date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                
                <button class="add-btn" type="submit">Apply Filter</button>
            </form>

            <div class="export-buttons">
                <button onclick="window.location.href='export_data.php?start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>'" class="add-btn">Export as CSV</button>
                <button onclick="exportReportToPDF()" class="add-btn">Export as PDF</button>
            </div>
        </div>

        <div id="reportContent" class="report-section">
            <!-- TOP SUMMARY CARDS -->
            <div class="dashboard-cards">
                <div class="card green">
                    <h3><img src="assets/images/totalincIcon.png" class="dashboard-icon" alt="Image here"></img>&nbsp;Total Income</h3>
                    <p>₱<?= number_format($totalIncome, 2); ?></p>
                </div>

                <div class="card red">
                    <h3><img src="assets/images/totalexpensesIcon.png" class="dashboard-icon" alt="Image here"></img>&nbsp;Total Expenses</h3>
                    <p>₱<?= number_format($totalExpense, 2); ?></p>
                </div>

                <div class="card purple">
                    <h3><img src="assets/images/totalbalanceIcon.png"  class="dashboard-icon" alt="Image here" ></img>&nbsp;Net Savings</h3>
                    <p>₱<?= number_format($netSavings, 2); ?></p>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="report-chart-card">
                <div class="chart-container">
                    <h2>Expense Breakdown (Pie Chart)</h2>
                    <canvas id="expensePie"></canvas>
                </div>

                <div class="chart-container">
                    <h2>Income vs Expense (Bar Graph)</h2>
                    <canvas id="incomeExpenseBar"></canvas>
                </div>
            </div>
            
            <div style="clear: both;"></div>

            <!-- DATA TABLE -->
            <div class="category-table-container">
                <h2>Data Table</h2>

                <table id="dataTable"  class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th class="amount-col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalExpense > 0): ?>
                            <?php while ($row = $dataResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['date'] ?></td>
                                    <td><?= $row['description'] ?></td>
                                    <td><span style="background: <?= $row['hex_color'] ?>;" class="tag"><?= $row['category_name'] ?></span></td>

                                    <td class="<?= $row['type'] === 'income' ? 'amount income' : 'amount expense' ?>">
                                        ₱<?= number_format($row['amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
// -------------------------
// DATA FROM PHP
// -------------------------
const pieLabels   = <?= json_encode($catLabels) ?>;
const pieData     = <?= json_encode($catTotals) ?>;
const dateLabel = <?= json_encode($dateLabel) ?>;
const incomeData  = <?= json_encode($incomeData) ?>;
const expenseData = <?= json_encode($expenseData) ?>;

// -------------------------
// EXPENSE PIE CHART
// -------------------------
const ctxPie = document.getElementById('expensePie').getContext('2d');
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: pieLabels,
        datasets: [{
            data: pieData,
            backgroundColor: [
                '#6d28d9', '#f97316', '#22c55e', '#0ea5e9',
                '#facc15', '#ec4899', '#14b8a6', '#a855f7'
            ]
        }]
    }
});

// -------------------------
// INCOME vs EXPENSE BAR
// -------------------------
const ctxBar = document.getElementById('incomeExpenseBar').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: dateLabel,
        datasets: [
            {
                label: 'Income',
                data: incomeData,
                backgroundColor: '#22c55e'
            },
            {
                label: 'Expense',
                data: expenseData,
                backgroundColor: '#8b5cf6'
            }
        ]
    }
});

// -------------------------
// PDF EXPORT FUNCTION
// -------------------------
function exportReportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4'); // Portrait, mm units, A4 size
    const content = document.getElementById('reportContent');

    html2canvas(content, { 
        scale: 2,
        logging: true,
        useCORS: true
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/jpeg', 1.0);
        
        const imgWidth = 210; // A4 width in mm
        const pageHeight = 295; // A4 height in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        
        let position = 0;

        doc.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            doc.addPage();
            doc.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        doc.save('Financial_Report_<?= date('Y-m') ?>.pdf');
    }).catch(err => {
        console.error("PDF generation failed:", err);
        alert("Failed to generate PDF. Check console for details.");
    });
}
</script>

</body>
</html>
