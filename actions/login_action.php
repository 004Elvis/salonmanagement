<?php
// 1. TURN ON SESSIONS FIRST!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/db.php';

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$ip = $_SERVER['REMOTE_ADDR'];

// 2. Fetch User
$stmt = $pdo->prepare("SELECT * FROM users JOIN roles ON users.role_id = roles.role_id WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    // 3. Success: Log and Set Session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['name'] = $user['full_name'];
    
    // Insert Log
    $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status) VALUES (?, ?, 'Success')");
    $logStmt->execute([$user['user_id'], $ip]);

    // Role Based Redirect
    if ($user['role_name'] === 'Admin') {
        header("Location: ../admin/admin_dashboard.php");
        exit(); // ALWAYS exit after a header redirect
    } elseif ($user['role_name'] === 'Staff') {
        header("Location: ../staff/dashboard.php");
        exit(); 
    } else {
        header("Location: ../customer/book.php");
        exit();
    }
} else {
    // 4. Failure: Log and Redirect
    header("Location: ../index.php?error=Invalid Credentials");
    exit();
}
?>