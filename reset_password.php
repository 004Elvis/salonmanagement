<?php
date_default_timezone_set('Africa/Nairobi'); 
require 'config/db.php';

$token = $_GET['token'] ?? '';
$isValid = false;
$isSuccess = isset($_GET['updated']) && $_GET['updated'] == 'true';

if ($token && !$isSuccess) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch();
    
    if ($resetRequest) {
        $isValid = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Elvis Salon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css"> 

    <style>
        /* Responsive Overrides to ensure card looks good on mobile */
        .login-card {
            max-width: 450px;
            margin: 0 auto;
        }
        
        @media (max-width: 480px) {
            .navbar div { font-size: 1.1rem !important; }
            .navbar button { padding: 4px 10px !important; font-size: 0.8rem !important; }
            .login-card { padding: 25px 15px !important; }
            h2 { font-size: 1.5rem !important; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="font-weight: bold; color: var(--primary); display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-spa"></i> 
        <span>Elvis Midega Salon</span>
    </div>
    <button id="theme-toggle" class="btn btn-outline" style="width: auto; padding: 6px 15px; margin: 0; font-size: 0.9rem;">
        🌙 Dark Mode
    </button>
</nav>

<div class="login-container">
    <div class="login-card"> 
        <div class="card-body">
                
                <?php if ($isSuccess): ?>
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
                    </div>
                    <h2 class="fw-bold">Password Updated!</h2>
                    <p class="text-muted" style="font-size: 0.9rem;">Your security is our priority. Your password has been successfully changed.</p>
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary">Go to Login</a>
                    </div>

                <?php elseif ($isValid): ?>
                    <div class="mb-3">
                        <i class="bi bi-shield-lock text-primary" style="font-size: 3rem; color: var(--primary) !important;"></i>
                    </div>
                    <h2 class="fw-bold">Reset Password</h2>
                    <p class="text-muted" style="font-size: 0.9rem;">Please enter and confirm your new password below.</p>

                    <form action="actions/update_password_action.php" method="POST" class="text-start mt-4" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" id="new_password" required placeholder="Min. 8 characters">
                                <i class="fas fa-eye toggle-password" onclick="toggleVisibility('new_password', this)"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-type password">
                                <i class="fas fa-eye toggle-password" onclick="toggleVisibility('confirm_password', this)"></i>
                            </div>
                            <div id="password-error" class="text-danger mt-1" style="font-size: 0.8rem; display: none; font-weight: 500;">
                                <i class="fas fa-times-circle"></i> Passwords do not match!
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary mt-2" id="submitBtn">Update Password</button>
                    </form>

                <?php else: ?>
                    <div class="mb-3 text-danger">
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                    </div>
                    <h2 class="fw-bold">Expired Link</h2>
                    <p class="text-muted">This password reset link is invalid or has expired.</p>
                    <a href="forgot_password.php" class="btn btn-outline mt-3" style="text-decoration: none;">Request New Link</a>
                <?php endif; ?>

        </div>
    </div>
</div>

<script>
    // 1. Password Visibility Toggle
    function toggleVisibility(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // 2. Client-side matching check
    const form = document.getElementById('resetForm');
    const pass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    const errorDiv = document.getElementById('password-error');

    if(form) {
        form.addEventListener('submit', function(e) {
            if (pass.value !== confirmPass.value) {
                e.preventDefault();
                errorDiv.style.display = 'block';
                confirmPass.style.borderColor = 'var(--danger)';
            }
        });

        confirmPass.addEventListener('input', () => {
            errorDiv.style.display = 'none';
            confirmPass.style.borderColor = 'var(--border-color)';
        });
    }
</script>

<script src="assets/js/script.js"></script>
</body>
</html>