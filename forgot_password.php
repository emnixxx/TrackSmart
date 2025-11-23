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
// sends reset link
// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     $email = $_POST["email"];

//     // Check if email exists
//     $check = $conn->prepare("SELECT id FROM users WHERE email=?");
//     $check->bind_param("s", $email);
//     $check->execute();
//     $check->store_result();

//     if ($check->num_rows > 0) {
//         // Generate token
//         $token = bin2hex(random_bytes(40));
//         $expires = time() + 1800; // 30 minutes valid

//         // Save token to DB
//         $stmt = $conn->prepare("UPDATE users SET reset_token=?, token_expire=? WHERE email=?");
//         $stmt->bind_param("sis", $token, $expires, $email);
//         $stmt->execute();

//         // Send email (use your domain)
//         $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
//         mail($email, "Password Reset", "Reset your password here: $resetLink");

//         $note = "<p class='msg success'>Reset link has been sent to your email!</p>";
//     } else {
//         $note = "<p class='msg error'>Email not found!</p>";
//     }
// }
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
  <form class="auth-card" method="post" autocomplete="off">
    <h2>Forgot Password</h2>
    <?= $note ?>

     <label>Email</label>
    <input type="email" name="email" required placeholder="Enter your email">

    <button type="submit">Send OTP</button>

    <!-- <label>Email</label>
    <input type="email" name="email" required placeholder="Enter your email">

    <button type="submit">Send Reset Link</button> -->

    <div class="small"><a href="login.php">Back to Login</a></div>
  </form>
</body>