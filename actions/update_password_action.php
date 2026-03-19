<?php
// actions/update_password_action.php
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // 1. Get the email associated with this token
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $request = $stmt->fetch();

    if ($request) {
        $email = $request['email'];

        // 2. Update the User's password
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$new_pass, $email]);

        // 3. Delete the token so it can't be reused
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        header("Location: ../reset_password.php?updated=true");
    } else {
        header("Location: ../index.php?error=Session expired.");
    }
}