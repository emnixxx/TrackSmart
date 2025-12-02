<?php
//session_start();
require 'db_connect.php';
//$note = "";
$email = $_GET["email"] ?? '';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPass = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password=?, reset_otp=NULL, otp_expire=NULL WHERE email=?");
    $update->bind_param("ss", $newPass, $email);
    $update->execute();

    $message = "<p class='msg success'>Password updated! <a href='login.php'>Login Now</a></p>";
}
// // Check token validity
// $stmt = $conn->prepare("SELECT id, token_expire FROM users WHERE reset_token=?");
// $stmt->bind_param("s", $token);
// $stmt->execute();
// $stmt->bind_result($uid, $expires);
// $stmt->fetch();
// $stmt->close();

// if (!$uid) {
//     die("<body class='auth-page'><form class='auth-card'><p class='msg error'>Invalid reset link!</p></form></body>");
// }

// if (time() > $expires) {
//     die("<body class='auth-page'><form class='auth-card'><p class='msg error'>Link expired!</p></form></body>");
// }

// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     $newPass = password_hash($_POST["password"], PASSWORD_DEFAULT);
//     $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, token_expire=NULL WHERE user_id=?");
//     $update->bind_param("si", $newPass, $uid);
//     $update->execute();

//     $message = "<p class='msg success'>Password updated! <a href='login.php'>Login Now</a></p>";
// }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reset_Pass • TrackSmart</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css?v=14">

  <!-- <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById("myPasswordInput");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }
    </script> -->
  <script>
function togglePasswordVisibility() {
    let p1 = document.getElementById("newPass");
    let p2 = document.getElementById("confirmPass");

    if (p1.type === "password") {
        p1.type = "text";
        p2.type = "text";
    } else {
        p1.type = "password";
        p2.type = "password";
    }
}
</script>
</head>

<body class="auth-page">
  <form class="auth-card" method="post" autocomplete="off">
    <h2>Reset Password</h2>
    <?= $message ?>

    <label>New Password</label>
    <input type="password" name="password" id="newPass" required placeholder="Enter new password">

    <label>Re-type Password</label>
    <input type="password" name="confirm_password" id="confirmPass" required placeholder="Re-type password">

    <div class="show-password">
    <input type="checkbox" id="show-password" onclick="togglePasswordVisibility()">
    <label for="show-password">Show Password</label>
    </div>

    <button type="submit">Change Password</button>
  </form>
</body>
?>