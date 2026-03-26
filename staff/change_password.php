<?php
// staff/change_password.php

require '../config/db.php';
require '../includes/auth_check.php';

// Security check: Ensure only Staff can access this page
checkRole(['Staff', 'staff']); 

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Shared Dashboard Theme Variables */
        :root {
            --bg-color: #f4f6f9;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --accent: #4caf50;
            --danger: #dc3545;
        }

        body.dark-mode {
            --bg-color: #121416;
            --card-bg: #212529;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
        }

        * { transition: background 0.3s, color 0.3s; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px;
        }

        .password-container { 
            background: var(--card-bg); 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
        }

        h2 { text-align: center; margin-bottom: 25px; font-size: 1.4rem; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }

        input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            font-size: 15px; 
            outline: none;
        }

        input[type="password"]:focus {
            border-color: var(--accent);
        }

        .btn-submit { 
            width: 100%; 
            padding: 12px; 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 10px; 
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.2);
        }

        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

        .btn-back { display: block; text-align: center; margin-top: 20px; color: var(--text-muted); text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: var(--accent); }

        /* Alerts */
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: 500; }
        .alert.success { background-color: rgba(76, 175, 80, 0.15); color: var(--accent); border: 1px solid var(--accent); }
        .alert.error { background-color: rgba(220, 53, 69, 0.15); color: var(--danger); border: 1px solid var(--danger); }

        /* Mobile Optimization */
        @media (max-width: 480px) {
            .password-container { padding: 20px; }
            h2 { font-size: 1.2rem; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

    <div class="password-container">
        <h2><i class="fas fa-key"></i> Change Password</h2>

        <?php if ($message != ''): ?>
            <div class="alert <?= $message_type; ?>">
                <i class="fas <?= $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
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
                <input type="password" name="new_password" required minlength="6" placeholder="Min. 6 characters">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6" placeholder="Retype new password">
            </div>

            <button type="submit" class="btn-submit">Update Password</button>
        </form>

        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <script>
        // Global Theme Management
        const savedTheme = localStorage.getItem('admin-theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>