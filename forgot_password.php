<?php
session_start();
require 'db_connect.php';
$note = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $otp = rand(100000, 999999); // 6-digit OTP
        $expires = time() + 300; // 5 minutes

        $stmt = $conn->prepare("UPDATE users SET reset_otp=?, otp_expire=? WHERE email=?");
        $stmt->bind_param("sis", $otp, $expires, $email);
        $stmt->execute();

        // Send OTP to email
        mail($email, "Your OTP Code", "Your OTP to reset password is: $otp");

        // Redirect to OTP verify page
        header("Location: verify_otp.php?email=$email");
        exit();
    } else {
        $note = "<p class='msg error'>Email not found!</p>";
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
  <form class="auth-card" method="post" autocomplete="on">
    <h2>Forgot Password</h2>
    <?= $note ?>

     <label>Email</label>
    <input type="email" name="email" required placeholder="Enter your email">

    <button type="submit">Send OTP</button>

    <div class="small"><a href="login.php">Back to Login</a></div>
  </div>
  </form>

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