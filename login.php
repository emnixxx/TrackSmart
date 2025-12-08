<?php
session_start();
require 'db_connect.php';

$note = "";

// Kung logged in na, dumiretso na sa dashboard
if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email    = trim($_POST['email'] ?? "");
  $password = $_POST['password'] ?? "";

  if ($email === "" || $password === "") {
    $note = '<div class="msg error">Please enter email and password.</div>';
  } else {
    // Hanapin user by email
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
      // Success: set session, go to dashboard
      $_SESSION['user_id']  = $user['id'];
      $_SESSION['username'] = $user['username'];
      header("Location: index.php");
      exit;
    } else {
      $note = '<div class="msg error">Invalid email or password.</div>';
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login • FirstProject</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css?v=14">
  
  <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById("myPasswordInput");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }
    </script>
</head>

<body class="auth-page">
   <!-- LEFT SIDE -->
    <div class="auth-left fade-in-left">
        <form class="auth-card" method="post" autocomplete="on">

            <h2>Login</h2>
            <?= $note ?>

            <label>Email Address</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" id="myPasswordInput" required>

            <div class="show-password">
                <input type="checkbox" onclick="togglePasswordVisibility()">
                <label>Show Password</label>
            </div>

            <button type="submit">Login</button>

            <!-- Google Login Button -->
            <button type="button" class="google-login">
                <img src="assets/img/google-icon.png" width="18">
                Continue with Google
            </button>

            <div class="small">No account? <a href="register.php">Register</a></div>
            <div class="small">Forgot Password? <a href="forgot_password.php">Click Here</a></div>

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
