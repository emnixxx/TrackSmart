<?php  
// transactions.php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ========================================================
   DELETE TRANSACTION
======================================================== */
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM transactions WHERE id = $delete_id AND user_id = $user_id");
    header("Location: transactions.php?deleted=1");
    exit;
}

/* ========================================================
   SAVE NEW TRANSACTION
======================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_transaction'])) {

    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $income_category_id = $_POST['income_category_id'] ?? null;
    $expense_category_id = $_POST['expense_category_id'] ?? null;
    $date = $_POST['date'];
    $notes = $_POST['notes'] ?? "";

    $category_id = $type === "expense" ? $expense_category_id : $income_category_id;

    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, type, description, amount, category_id, date, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdiss",
        $user_id, $type, $description, $amount, $category_id, $date, $notes
    );

    $stmt->execute();
    $stmt->close();

    header("Location: transactions.php?added=1");
    exit;
}

/* ========================================================
   UPDATE TRANSACTION
======================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_transaction'])) {

    $id = $_POST['id'];
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $income_category_id = $_POST['income_category_id'] ?? null;
    $expense_category_id = $_POST['expense_category_id'] ?? null;
    $notes = $_POST['notes'];

    $category_id = $type === "expense" ? $expense_category_id : $income_category_id;

    $stmt = $conn->prepare("
        UPDATE transactions 
        SET date=?, type=?, description=?, amount=?, category_id=?, notes=? 
        WHERE id=? AND user_id=?
    ");

    $stmt->bind_param("sssdisii",
        $date, $type, $description, $amount, $category_id, $notes, $id, $user_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: transactions.php?updated=1");
    exit;
}

/* ========================================================
   FETCH TRANSACTIONS
======================================================== */
$transactions = $conn->query("
    SELECT t.*, c.category_name, c.hex_color 
    FROM transactions t 
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
    ORDER BY date DESC
");

/* ==========================================
   FETCH CATEGORIES
========================================== */
$incomeCategoryQuery = $conn->query("
    SELECT * FROM categories WHERE type='income'
");
$incomeCategories = [];
while ($row = $incomeCategoryQuery->fetch_assoc()) {
    $incomeCategories[] = [$row['id'], $row['category_name']];
}

$expenseCategoryQuery = $conn->query("
    SELECT * FROM categories WHERE type='expense'
");
$expenseCategories = [];
while ($row = $expenseCategoryQuery->fetch_assoc()) {
    $expenseCategories[] = [$row['id'], $row['category_name']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transactions • TrackSmart</title>
    <link rel="stylesheet" href="assets/css/style.css?v=21" />
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
<div class="transactions-wrapper">

    <div class="transactions-header">
        <div class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
        <h1>Transactions</h1>
        <p class="subtext">Welcome back!</p>
    </div>

    <div class="transaction-topbar">
        <button class="add-btn" onclick="openModal()">+ Add Transaction</button>
        <a href="budget.php" class="add-btn" style="margin-left:10px;">View Budget</a>
    </div>

    <div class="transaction-card">
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="amount-col">Amount</th>
                    <th class="actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($transactions->num_rows == 0): ?>
                <tr>
                    <td colspan="5" class="empty">No transactions yet.</td>
                </tr>
            <?php else: ?>
                <?php while ($t = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><?= $t['date'] ?></td>
                    <td><?= $t['description'] ?></td>
                    <td><span style="background: <?= $t['hex_color'] ?>;" class="tag"><?= $t['category_name'] ?></span></td>
                    <td class="<?= $t['type'] === 'income' ? 'amount income' : 'amount expense' ?>">
                        ₱<?= number_format($t['amount'], 2) ?>
                    </td>
                    <td class="actions">
                        <button class="edit-btn" 
                            onclick="openEditModal(
                                '<?= $t['id'] ?>',
                                '<?= $t['date'] ?>',
                                '<?= $t['type'] ?>',
                                '<?= htmlspecialchars($t['description']) ?>',
                                '<?= htmlspecialchars($t['category_id']) ?>',
                                '<?= $t['amount'] ?>',
                                `<?= htmlspecialchars($t['notes']) ?>`
                            )">✏️</button>
                        <button class="delete-btn" onclick="deleteTransaction(<?= $t['id'] ?>)">🗑</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="page-info">Showing <?= $transactions->num_rows ?> transactions</p>
    </div>

</div>
</div>

<!-- MODALS & JS SAME AS YOUR ORIGINAL TRANSACTIONS CODE -->

</body>
</html>
