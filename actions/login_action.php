<?php
require '../config/db.php';

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$ip = $_SERVER['REMOTE_ADDR'];

// 1. Fetch User
$stmt = $pdo->prepare("SELECT * FROM users JOIN roles ON users.role_id = roles.role_id WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    // 2. Success: Log and Set Session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['name'] = $user['full_name'];
    
    // Insert Log
    $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status) VALUES (?, ?, 'Success')");
    $logStmt->execute([$user['user_id'], $ip]);

    // Role Based Redirect
    if ($user['role_name'] === 'Admin') header("Location: ../admin/dashboard.php");
    elseif ($user['role_name'] === 'Staff') header("Location: ../staff/dashboard.php");
    else header("Location: ../customer/book.php");
} else {
    // 3. Failure: Log and Redirect
    // Note: We don't have user_id if user not found, handling generically for security
    header("Location: ../index.php?error=Invalid Credentials");
}
?>