<?php
// staff/change_password.php

require '../config/db.php';
require '../includes/auth_check.php';

// Security check: Ensure only Staff can access this page
checkRole(['Staff', 'staff']); // Checking both cases just to be safe

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Fetch the user's current hashed password from the database
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 2. Verify the old password is correct
    if ($user && password_verify($current_password, $user['password_hash'])) {
        
        // 3. Check if new passwords match
        if ($new_password === $confirm_password) {
            
            // 4. Hash the new password securely
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // 5. Update the database
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            if ($updateStmt->execute([$new_hashed_password, $user_id])) {
                $message = "Success! Your password has been updated.";
                $message_type = "success";
            } else {
                $message = "Database error. Could not update password.";
                $message_type = "error";
            }
        } else {
            $message = "Error: The new passwords do not match.";
            $message_type = "error";
        }
    } else {
        $message = "Error: The current password you entered is incorrect.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Elvis Salon</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9fb; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #333; }
        .password-container { background: #fff; padding: 30px 40px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 25px; font-size: 22px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { width: 100%; padding: 10px; background: #333; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #555; }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: #000; text-decoration: underline; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="password-container">
        <h2>🔒 Change Password</h2>

        <?php if ($message != ''): ?>
            <div class="alert <?= $message_type; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required placeholder="Enter current password">
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6" placeholder="At least 6 characters">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6" placeholder="Retype new password">
            </div>

            <button type="submit" class="btn-submit">Update Password</button>
        </form>

        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

</body>
</html>