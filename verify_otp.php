<?php
session_start();
require 'db_connect.php';

$email = $_GET["email"] ?? "";
$note = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_otp = $_POST["otp"];

    $stmt = $conn->prepare("SELECT reset_otp, otp_expire FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($otp, $expire);
    $stmt->fetch();

    if ($otp == $input_otp && time() < $expire) {
        // OTP verified → go to reset password page
        header("Location: reset_password.php?email=$email");
        exit();
    } else {
        $note = "<p class='msg error'>Invalid or Expired OTP!</p>";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Forgot_Pass • TrackSmart</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css?v=14">
</head>

<body class="auth-page">
  <div class="auth-left fade-in-left">
  <form class="auth-card" method="post" autocomplete="off">
    <h2>Verify OTP</h2>
    <?= $note ?>

    <label>Enter OTP</label>
    <input type="text" name="otp" maxlength="6" required placeholder="6-digit code">

    <button type="submit">Verify</button>

    <div class="small"><a href="forgot_password.php">Resend OTP</a></div>
    <div class="small"><a href="login.php">Back to Login</a></div>
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