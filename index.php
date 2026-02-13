<?php require 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Elvis Midega Salon - Welcome</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <nav class="navbar" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">
            <i class="fas fa-spa"></i> Elvis Midega Salon
        </div>
        <button id="theme-toggle" class="btn" style="width: auto; padding: 5px 15px;">🌙 Dark Mode</button>
    </nav>

    <div class="login-container">
        <div class="login-card">
            
            <h2 style="margin-bottom: 5px;">Welcome Back</h2>
            <p style="margin-bottom: 25px;">Please enter your details to sign in</p>
            
            <?php if(isset($_GET['error'])) echo "<p style='color:red; background:#ffe6e6; padding:8px; border-radius:4px;'>".$_GET['error']."</p>"; ?>
            <?php if(isset($_GET['success'])) echo "<p style='color:green; background:#e6ffe6; padding:8px; border-radius:4px;'>".$_GET['success']."</p>"; ?>

            <form action="actions/login_action.php" method="POST">
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="password-wrapper">
                        <input type="email" name="email" required placeholder="Enter your email">
                        <i class="fas fa-envelope toggle-password" style="cursor: default;"></i> 
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" required placeholder="Enter your password">
                        <i class="fas fa-eye toggle-password" title="Show Password"></i>
                    </div>
                    <div style="text-align: right; margin-top: 5px;">
                        <a href="#" style="font-size: 0.8rem; color: #666; text-decoration: none;">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div style="margin: 25px 0; border-top: 1px solid #ddd; position: relative;">
                <span style="background: var(--card-bg); padding: 0 10px; position: absolute; top: -10px; left: 50%; transform: translateX(-50%); color: #888; font-size: 0.8rem;">
                    New here?
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