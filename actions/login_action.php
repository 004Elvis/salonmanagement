<?php
// 1. TURN ON SESSIONS FIRST!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/db.php';
require '../../includes/logger.php';
logApiRequest($pdo, 'login_action.php');

// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'login_action.php';
    // At this point, we don't know the user yet, so we log as Guest
    $log_user_id = null;
    $log_user_role = 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // Logging failure should not prevent the login process
}
// --- END OF API TRACKING UPDATE ---

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
    
    // --- SECONDARY UPDATE: Update the log with the now-known User ID ---
    try {
        $update_log = $pdo->prepare("UPDATE api_logs SET user_id = ?, user_role = ? WHERE endpoint_name = 'login_action.php' AND user_id IS NULL ORDER BY id DESC LIMIT 1");
        $update_log->execute([$user['user_id'], $user['role_name']]);
    } catch (Exception $e) { }
    // --- END OF SECONDARY UPDATE ---

    // Insert Log (Existing logic)
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