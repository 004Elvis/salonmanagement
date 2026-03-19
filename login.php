<?php require 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Elvis Midega Salon - Welcome</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Responsive adjustments for the inline elements */
        @media (max-width: 480px) {
            .navbar div { font-size: 1.1rem !important; }
            .navbar button { padding: 4px 10px !important; font-size: 0.8rem !important; }
            .login-card { padding: 25px !important; }
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <div style="font-weight: bold; color: var(--primary); display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-spa"></i> 
            <span class="logo-text">Elvis Midega Salon</span>
        </div>
        <button id="theme-toggle" class="btn btn-outline" style="width: auto; padding: 6px 15px; margin: 0; font-size: 0.9rem;">
            🌙 Dark Mode
        </button>
    </nav>

    <div class="login-container">
        <div class="login-card">
            
            <h2 style="margin-bottom: 5px;">Welcome Back</h2>
            <p style="margin-bottom: 25px;">Please enter your details to sign in</p>
            
            <?php if(isset($_GET['error'])): ?>
                <p style="color:#721c24; background:#f8d7da; padding:10px; border-radius:6px; font-size:0.85rem; border:1px solid #f5c6cb; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                </p>
            <?php endif; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <p style="color:#155724; background:#d4edda; padding:10px; border-radius:6px; font-size:0.85rem; border:1px solid #c3e6cb; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                </p>
            <?php endif; ?>

            <form action="actions/login_action.php" method="POST">
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="password-wrapper">
                        <input type="email" name="email" required placeholder="Enter your email">
                        <i class="fas fa-envelope toggle-password" style="cursor: default; opacity: 0.6;"></i> 
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" required placeholder="Enter your password">
                        <i class="fas fa-eye toggle-password" title="Show Password"></i>
                    </div>
                    <div style="text-align: right; margin-top: 8px;">
                        <a href="forgot_password.php" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600;">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Login</button>
            </form>

            <div style="margin: 35px 0 25px 0; border-top: 1px solid var(--border-color); position: relative;">
                <span style="background: var(--card-bg); padding: 0 15px; position: absolute; top: -11px; left: 50%; transform: translateX(-50%); color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">
                    New to our salon?
                </span>
            </div>

            <a href="register.php" class="btn btn-outline">
                Create New Account
            </a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>