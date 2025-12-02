<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id']; 

function autoCategory($desc) {
    $desc = strtolower($desc);

    if (str_contains($desc, "food") || str_contains($desc, "eat") || str_contains($desc, "restaurant") || str_contains($desc, "dining"))
        return "Food & Dining";

    if (str_contains($desc, "grocery") || str_contains($desc, "shop") || str_contains($desc, "mall"))
        return "Shopping";

    if (str_contains($desc, "gas") || str_contains($desc, "fuel") || str_contains($desc, "jeep") || str_contains($desc, "tricycle") || str_contains($desc, "grab"))
        return "Transportation";

    if (str_contains($desc, "bill") || str_contains($desc, "meralco") || str_contains($desc, "internet") || str_contains($desc, "utilities"))
        return "Utilities";

    if (str_contains($desc, "netflix") || str_contains($desc, "movie") || str_contains($desc, "cinema") || str_contains($desc, "game"))
        return "Entertainment";

    if (str_contains($desc, "doctor") || str_contains($desc, "medicine") || str_contains($desc, "hospital") || str_contains($desc, "clinic"))
        return "Healthcare";

    if (str_contains($desc, "salary") || str_contains($desc, "payroll") || str_contains($desc, "pay"))
        return "Salary";

    if (str_contains($desc, "business") || str_contains($desc, "office"))
        return "Business";

    return "Other";
}

/* SEARCH */
$search = "";
$where = "user_id = $user_id";

if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where .= " AND (
        description LIKE '%$search%' OR
        category LIKE '%$search%' OR
        amount LIKE '%$search%' OR
        date LIKE '%$search%'
    )";
}

/* DELETE */
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM transactions WHERE id = $delete_id AND user_id = $user_id");
    header("Location: transactions.php?deleted=1");
    exit;
}

/* ========================================================
   SAVE NEW TRANSACTION  (FIXED)
======================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_transaction'])) {

    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'];
    $notes = $_POST['notes'];

    $category = $_POST['category'] !== "" 
        ? $_POST['category'] 
        : autoCategory($description);

    $stmt = $conn->prepare(
        "INSERT INTO transactions (user_id, type, description, amount, category, date, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issdsss", $user_id, $type, $description, $amount, $category, $date, $notes);

    if (!$stmt->execute()) {
        echo "<p style='color:red;'>Insert Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
    header("Location: transactions.php?added=1");
    exit;
}

/* ========================================================
   FETCH ALL TRANSACTIONS (WITH ERROR CHECK)
======================================================== */
$transactions = $conn->query("SELECT * FROM transactions WHERE $where ORDER BY date DESC");

if (!$transactions) {
    echo "<p style='color:red;'>SQL Error: " . $conn->error . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transactions • TrackSmart</title>
<link rel="stylesheet" href="assets/css/style.css">

<style>
.transaction-table th, 
.transaction-table td {
    text-align: center;
    padding: 12px;
    vertical-align: middle;
}
.amount { text-align:center; font-weight:600; }
.amount.income { color: green; }
.amount.expense { color: red; }
.delete-btn { background:#ff4d4d; color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
.delete-btn:hover { background:#cc0000; }
.type-btn.active { background:#4b0082; color:#fff; }
</style>
</head>

<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
  <div class="transactions-wrapper">

    <!-- SEARCH BAR + BUTTONS -->
    <div class="transaction-topbar">
      <form method="GET" style="flex: 1;">
        <input type="text" name="search" 
               placeholder="🔍 Search transactions…" 
               value="<?= htmlspecialchars($search) ?>" 
               class="search-input">
      </form>

      <div class="right-buttons">
        <button class="add-btn" onclick="openModal()">Add Transaction</button>
        <button class="import-btn" onclick="window.location='transaction_pdf.php'">📥</button>
      </div>
    </div>

    <!-- TABLE -->
    <div class="transaction-card">
      <table class="transaction-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Category</th>
            <th>Amount</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
        <?php if ($transactions->num_rows == 0): ?>
          <tr><td colspan="5">No transactions yet.</td></tr>

        <?php else: while ($t = $transactions->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($t['date']) ?></td>
            <td><?= htmlspecialchars($t['description']) ?></td>
            <td><?= htmlspecialchars($t['category']) ?></td>

            <td class="amount <?= $t['type'] === 'income' ? 'income' : 'expense' ?>">
              <?= ($t['type'] === 'income' ? '+' : '-') ?>₱<?= number_format($t['amount'], 2) ?>
            </td>

            <td>
              <button class="delete-btn" onclick="deleteTransaction(<?= $t['id'] ?>)">🗑</button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- ADD TRANSACTION MODAL -->
<div id="transactionModal" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <div class="modal-header">
      <h2>Add Transaction</h2>
      <span class="close-btn" onclick="closeModal()">✕</span>
    </div>

    <form method="POST">

      <label>Date</label>
      <input type="date" name="date" required class="input">

      <label>Type</label>
      <div class="type-switch">
        <button type="button" class="type-btn active" id="expenseBtn" onclick="setType('expense', event)">Expense</button>
        <button type="button" class="type-btn" id="incomeBtn" onclick="setType('income', event)">Income</button>
      </div>
      <input type="hidden" name="type" id="typeField" value="expense">

      <label>Description</label>
      <input type="text" name="description" required class="input">

      <label>Amount</label>
      <input type="number" name="amount" step="0.01" required class="input">

      <label>Category</label>
      <select name="category" class="input">
          <option value="">Auto</option>
          <option>Food & Dining</option>
          <option>Transportation</option>
          <option>Utilities</option>
          <option>Entertainment</option>
          <option>Shopping</option>
          <option>Healthcare</option>
          <option>Business</option>
          <option>Salary</option>
          <option>Other</option>
      </select>

      <label>Notes</label>
      <textarea name="notes" rows="2" class="input"></textarea>

      <!-- FIXED BUTTON -->
      <div class="modal-actions">
        <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
        <button type="submit" name="save_transaction" class="save-btn">Save Transaction</button>
      </div>

    </form>
  </div>
</div>

<script>
function openModal() {
    document.getElementById("transactionModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("transactionModal").style.display = "none";
}

function deleteTransaction(id) {
    if (confirm("Delete this transaction?")) {
        window.location = "transactions.php?delete=" + id;
    }
}

function setType(type) {
    document.getElementById("typeField").value = type;
    document.getElementById("expenseBtn").classList.toggle('active', type === 'expense');
    document.getElementById("incomeBtn").classList.toggle('active', type === 'income');
}
</script>

</body>
</html>
