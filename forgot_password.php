<?php
// forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Elvis Salon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">
            <i class="fas fa-spa"></i> Elvis Midega Salon
        </div>
        <button id="theme-toggle" class="btn" style="width: auto; padding: 5px 15px; border: 1px solid #ddd;">🌙 Dark Mode</button>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0 login-card">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-key-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h2 class="fw-bold">Forgot Password?</h2>
                        <p class="text-muted">Enter the email associated with your account and we'll send you a link to reset your password.</p>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>

                        <form action="actions/forgot_password_action.php" method="POST" class="mt-4 text-start">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="example@mail.com" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>

                        <div class="mt-4">
                            <a href="index.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Login</a>
                        </div>
                    </div>
                </div>
                
                <footer class="mt-5 text-center text-muted">
                    <small>Elvis Midega Beauty Salon &copy; 2026</small>
                </footer>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>