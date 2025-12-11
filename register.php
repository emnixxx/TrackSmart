<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db_connect.php';

$note = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // 1) Kunin inputs
  $username = trim($_POST['username'] ?? "");
  $email    = trim($_POST['email'] ?? "");
  $password = $_POST['password'] ?? "";
  $confirm  = $_POST['confirm'] ?? "";

  // 2) Basic validations
  if ($username === "" || $email === "" || $password === "" || $confirm === "") {
    $note = '<div class="msg error">Please fill in all fields.</div>';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $note = '<div class="msg error">Invalid email format.</div>';
  } elseif ($password !== $confirm) {
    $note = '<div class="msg error">Passwords do not match.</div>';
  } else {
    // 3) Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 4) INSERT gamit prepared statements (mas safe)
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hash);

    try {
      $stmt->execute();
      $note = '<div class="msg success">Registration successful! <a href="login.php">Login here</a>.</div>';
    } catch (mysqli_sql_exception $e) {
      // posibleng duplicate email, etc.
      $note = '<div class="msg error">Could not register (email may already be used).</div>';
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register • FirstProject</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css?v=14">
</head>
<body class="auth-page">
  <div class="auth-left fade-in-left">
  <form class="auth-card" method="post" autocomplete="off">

    <h2>Create Account</h2>
    <?= $note ?>

    <label>Username</label>
    <input type="text" name="username" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm" required>

    <button type="submit">Register</button>

    <div class="small">Already have an account? <a href="login.php">Login</a></div>
  </form>
  </div>

  <!-- RIGHT SIDE -->
    <div class="auth-right fade-in-right">
        <img src="assets/images/logo.png">

        <h1>Manage Your Finances with Ease</h1>
        <p>
            Track expenses, set budgets, and achieve your financial goals with TrackSmart.
        </p>

        <div class="feature-tag">📊 Real-time Analytics</div>
        <div class="feature-tag">💰 Budget Tracking</div>
        <div class="feature-tag">📑 Financial Reports</div>
        <div class="feature-tag">🎯 Savings Goals</div>
    </div>
</body>
</html>
