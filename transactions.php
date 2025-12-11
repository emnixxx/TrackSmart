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
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: transactions.php?deleted=1");
    exit;
}

/* ========================================================
   SAVE NEW TRANSACTION — FIXED
======================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_transaction'])) {

    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $income_category_id = $_POST['income_category_id'] ?? null;
    $expense_category_id = $_POST['expense_category_id'] ?? null;
    $date = $_POST['date'];
    $notes = $_POST['notes'] ?? "";

    // Correct category_id
    $category_id = ($type === "expense") ? $expense_category_id : $income_category_id;

    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, type, description, amount, category_id, date, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("issdiss",
        $user_id,
        $type,
        $description,
        $amount,
        $category_id,
        $date,
        $notes
    );

    if (!$stmt->execute()) {
        die("<p style='color:red;'>Insert Error: " . $stmt->error . "</p>");
    }

    $stmt->close();
    header("Location: transactions.php?added=1");
    exit;
}

/* ========================================================
   UPDATE TRANSACTION — FIXED
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

    // Fix category_id
    $category_id = ($type === "expense") ? $expense_category_id : $income_category_id;

    $stmt = $conn->prepare("
        UPDATE transactions
        SET date=?, type=?, description=?, amount=?, category_id=?, notes=?
        WHERE id=? AND user_id=?
    ");

    $stmt->bind_param("sssdisii",
        $date,
        $type,
        $description,
        $amount,
        $category_id,
        $notes,
        $id,
        $user_id
    );

    if (!$stmt->execute()) {
        die("<p style='color:red;'>Update Error: " . $stmt->error . "</p>");
    }

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
    ORDER BY t.date DESC
");

/* ========================================================
   FETCH CATEGORIES
======================================================== */
$incomeCategories = [];
$res = $conn->query("SELECT * FROM categories WHERE type='income'");
while ($r = $res->fetch_assoc()) $incomeCategories[] = $r;

$expenseCategories = [];
$res = $conn->query("SELECT * FROM categories WHERE type='expense'");
while ($r = $res->fetch_assoc()) $expenseCategories[] = $r;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions • TrackSmart</title>
    <link rel="stylesheet" href="assets/css/style.css?v=25">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
<div class="transactions-wrapper">

    <div class="transactions-header">
        <h1>Transactions</h1>
        <p class="subtext">Welcome back!</p>
    </div>

    <div class="transaction-topbar">
        <button class="add-btn" onclick="openModal()">+ Add Transaction</button>
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
                <tr><td colspan="5" class="empty">No transactions yet.</td></tr>
            <?php else: ?>
                <?php while ($t = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><?= $t['date'] ?></td>
                    <td><?= $t['description'] ?></td>
                    <td>
                        <span style="background: <?= $t['hex_color'] ?>;" class="tag">
                            <?= $t['category_name'] ?>
                        </span>
                    </td>
                    <td class="<?= $t['type'] == 'income' ? 'amount income' : 'amount expense' ?>">
                        ₱<?= number_format($t['amount'], 2) ?>
                    </td>
                    <td class="actions">
                        <button class="edit-btn"
                            onclick="openEditModal(
                                '<?= $t['id'] ?>',
                                '<?= $t['date'] ?>',
                                '<?= $t['type'] ?>',
                                `<?= htmlspecialchars($t['description']) ?>`,
                                '<?= $t['category_id'] ?>',
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
    </div>

</div>
</div>

<!-- ADD MODAL -->
<div id="transactionModal" class="modal-overlay">
    <div class="modal-box">
        <h2>Add Transaction</h2>
        <span class="close-btn" onclick="closeModal()">✕</span>

        <form method="POST">
            <input type="hidden" name="save_transaction" value="1">

            <label>Date</label>
            <input type="date" name="date" class="input" required>

            <label>Type</label>
            <div class="type-switch">
                <button type="button" class="type-btn active" id="expenseBtn" onclick="setType('expense')">Expense</button>
                <button type="button" class="type-btn" id="incomeBtn" onclick="setType('income')">Income</button>
            </div>
            <input type="hidden" name="type" id="typeField" value="expense">

            <label>Description</label>
            <input type="text" name="description" class="input" required>

            <div id="expenseDropdown">
                <label>Expense Category</label>
                <select name="expense_category_id" class="input">
                    <?php foreach ($expenseCategories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="incomeDropdown" style="display:none;">
                <label>Income Category</label>
                <select name="income_category_id" class="input">
                    <?php foreach ($incomeCategories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label>Amount</label>
            <input type="number" name="amount" step="0.01" class="input" required>

            <label>Notes</label>
            <textarea name="notes" class="input"></textarea>

            <button class="save-btn">Save Transaction</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <h2>Edit Transaction</h2>
        <span class="close-btn" onclick="closeEditModal()">✕</span>

        <form method="POST">
            <input type="hidden" name="edit_transaction" value="1">
            <input type="hidden" id="edit_id" name="id">

            <label>Date</label>
            <input type="date" id="edit_date" name="date" class="input" required>

            <label>Type</label>
            <div class="type-switch">
                <button type="button" class="type-btn" id="edit_expenseBtn" onclick="editSetType('expense')">Expense</button>
                <button type="button" class="type-btn" id="edit_incomeBtn" onclick="editSetType('income')">Income</button>
            </div>
            <input type="hidden" name="type" id="edit_typeField">

            <label>Description</label>
            <input type="text" id="edit_description" name="description" class="input" required>

            <div id="edit_expenseDropdown">
                <label>Expense Category</label>
                <select id="edit_expense_category_id" name="expense_category_id" class="input">
                    <?php foreach ($expenseCategories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="edit_incomeDropdown" style="display:none;">
                <label>Income Category</label>
                <select id="edit_income_category_id" name="income_category_id" class="input">
                    <?php foreach ($incomeCategories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label>Amount</label>
            <input type="number" id="edit_amount" name="amount" step="0.01" class="input" required>

            <label>Notes</label>
            <textarea id="edit_notes" name="notes" class="input"></textarea>

            <button class="save-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openModal() { document.getElementById("transactionModal").style.display = "flex"; }
function closeModal() { document.getElementById("transactionModal").style.display = "none"; }

function openEditModal(id, date, type, desc, category_id, amount, notes) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_date").value = date;
    document.getElementById("edit_description").value = desc;
    document.getElementById("edit_amount").value = amount;
    document.getElementById("edit_notes").value = notes;

    editSetType(type);

    if (type === "expense")
        document.getElementById("edit_expense_category_id").value = category_id;
    else
        document.getElementById("edit_income_category_id").value = category_id;

    document.getElementById("editModal").style.display = "flex";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

function deleteTransaction(id) {
    if (confirm("Delete this transaction?"))
        window.location = "transactions.php?delete=" + id;
}

function setType(type) {
    document.getElementById("typeField").value = type;

    expenseBtn.classList.remove("active");
    incomeBtn.classList.remove("active");

    if (type === "expense") {
        expenseBtn.classList.add("active");
        expenseDropdown.style.display = "";
        incomeDropdown.style.display = "none";
    } else {
        incomeBtn.classList.add("active");
        expenseDropdown.style.display = "none";
        incomeDropdown.style.display = "";
    }
}

document.addEventListener("DOMContentLoaded", () => setType("expense"));

function editSetType(type) {
    document.getElementById("edit_typeField").value = type;

    edit_expenseBtn.classList.remove("active");
    edit_incomeBtn.classList.remove("active");

    if (type === "expense") {
        edit_expenseBtn.classList.add("active");
        edit_expenseDropdown.style.display = "";
        edit_incomeDropdown.style.display = "none";
    } else {
        edit_incomeBtn.classList.add("active");
        edit_expenseDropdown.style.display = "none";
        edit_incomeDropdown.style.display = "";
    }
}
</script>

</body>
</html>
