<?php
// budget.php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

/* ADD OR UPDATE BUDGET */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category = trim($_POST['category']);
    $limit = floatval($_POST['limit_amount']);
    $budget_id = $_POST['budget_id'] ?? null;

    if ($budget_id) {
        $stmt = $conn->prepare(
            "UPDATE budgets SET category=?, limit_amount=? WHERE id=? AND user_id=?"
        );
        $stmt->bind_param("sdii", $category, $limit, $budget_id, $user_id);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO budgets (user_id, category, limit_amount) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("isd", $user_id, $category, $limit);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: budget.php?saved=1");
    exit;
}

/* DELETE BUDGET */
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM budgets WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: budget.php?deleted=1");
    exit;
}

/* FETCH BUDGETS */
$budgets = [];
$stmt = $conn->prepare(
    "SELECT id, category, limit_amount FROM budgets WHERE user_id=? ORDER BY id ASC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $budgets[] = $row;
}
$stmt->close();

/* FETCH SPENT TOTAL PER CATEGORY FROM TRANSACTIONS */
$spent_map = [];
$stmt = $conn->prepare("
    SELECT c.category_name, SUM(t.amount) AS spent
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id=? AND t.type='expense'
    GROUP BY c.category_name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $spent_map[$r['category_name']] = floatval($r['spent']);
}
$stmt->close();

function money($n) {
    return "₱" . number_format($n, 2);
}

/* CALCULATE TOTALS */
$total_budget = 0;
$total_spent = 0;
foreach ($budgets as $b) {
    $total_budget += $b['limit_amount'];
    $spent = $spent_map[$b['category']] ?? 0;
    $total_spent += $spent;
}
$total_remaining = $total_budget - $total_spent;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Budgets • TrackSmart</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/budget.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="header">
        <div>
            <h1>Budget Overview</h1>
            <div class="subtext">Manage your spending limits</div>
        </div>
        <button class="add-btn" onclick="openModal()">+ Add Budget</button>
        <a href="transactions.php" class="add-btn" style="margin-left:10px;">View Transactions</a>
    </div>

    <div class="grid">
        <?php foreach ($budgets as $b):
            $cat = $b['category'];
            $limit = $b['limit_amount'];
            $spent = $spent_map[$cat] ?? 0;
            $remaining = $limit - $spent;
            $percent = $limit > 0 ? min(100, ($spent / $limit) * 100) : 0;
        ?>
        <div class="card">
            <div class="card-top">
                <div class="category-title"><?= htmlspecialchars($cat) ?></div>
                <div class="percent-pill"><?= round($percent) ?>%</div>
            </div>

            <button class="menu-btn" onclick="toggleMenu(<?= $b['id'] ?>)">⋮</button>
            <div class="menu-options" id="menu-<?= $b['id'] ?>">
                <button onclick="editBudget('<?= htmlspecialchars($cat) ?>', <?= $limit ?>, <?= $b['id'] ?>)">Edit</button>
                <button onclick="deleteBudget(<?= $b['id'] ?>)">Delete</button>
            </div>

            <div class="row">
                <div class="label">Spent</div>
                <div class="amount"><?= money($spent) ?></div>
            </div>

            <div class="row">
                <div class="label">Limit</div>
                <div class="amount"><?= money($limit) ?></div>
            </div>

            <div class="line"></div>

            <div class="progress-wrap">
                <div class="progress-fill" style="width: <?= $percent ?>%; <?= $percent >= 100 ? 'background:red;' : '' ?>"></div>
            </div>

            <div class="remaining"><?= money($remaining) ?> Remaining</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="budget-summary-card">
        <h3>Budget Summary</h3>
        <div class="summary-row">
            <div class="summary-col">
                <div class="summary-label">Total Budget</div>
                <div class="summary-amount"><?= money($total_budget) ?></div>
            </div>
            <div class="summary-col center">
                <div class="summary-label">Total Spent</div>
                <div class="summary-amount"><?= money($total_spent) ?></div>
            </div>
            <div class="summary-col right">
                <div class="summary-label">Total Remaining</div>
                <div class="summary-amount remaining"><?= money($total_remaining) ?></div>
            </div>
        </div>
    </div>

</div>

<!-- BUDGET MODAL & JS SAME AS YOUR ORIGINAL CODE -->

</body>
</html>
