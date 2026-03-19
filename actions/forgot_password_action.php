<?php
date_default_timezone_set('Africa/Nairobi'); // Sets PHP to Kenya time
// actions/forgot_password_action.php
require '../config/db.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // 1. Check if email exists in users table
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        // 2. Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + (30 * 60));

        // 3. Store token in database
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        // 4. Send the Email
        $resetLink = "http://localhost/elvis_salon/reset_password.php?token=" . $token;
        sendResetEmail($email, $resetLink);
    }

    // Always redirect to a "Check your email" page for security (don't reveal if email exists)
    header("Location: ../index.php?success=If that email exists, a reset link has been sent.");
    exit();
}

function sendResetEmail($userEmail, $link) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com'; 
        $mail->Password   = 'ohncquyyclverdfg'; // Your App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Salon Support');
        $mail->addAddress($userEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "Click the link below to reset your password. It expires in 30 minutes:<br><br>
                          <a href='$link'>$link</a>";
        $mail->send();
    } catch (Exception $e) {
        error_log("Reset Mailer Error: " . $mail->ErrorInfo);
    }
}