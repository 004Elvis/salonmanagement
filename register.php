<?php 
require 'config/db.php'; 

// Fetch Security Questions
$questions = [];
try {
    $stmt = $pdo->query("SELECT * FROM security_questions");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Error logged silently for user experience
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Elvis Midega Salon</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional responsive tweaks specific to the registration flow */
        .register-container {
            min-height: calc(100vh - 80px); /* Adjust for nav height */
            padding-top: 20px;
            padding-bottom: 40px;
        }

        .register-card {
            max-width: 450px; /* Slightly wider for longer registration forms */
        }

        @media (max-width: 480px) {
            nav { padding: 10px !important; }
            .register-card { padding: 25px 20px !important; border-radius: 10px; }
            h2 { font-size: 1.5rem; }
            .btn-primary { padding: 12px; }
        }
    </style>
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <nav style="width: 100%; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-weight: bold; color: var(--primary); font-size: 1.2rem;">
            <i class="fas fa-spa"></i> Elvis Salon
        </div>
        <button id="theme-toggle" class="btn btn-outline" style="width: auto; padding: 6px 15px; margin: 0; font-size: 0.85rem;">
            🌙 Dark Mode
        </button>
    </nav>

    <div class="register-container">
        
        <div class="register-card">
            <h2 style="margin-bottom: 10px;">Create Account</h2>
            <p style="margin-bottom: 25px;">Join us for a premium salon experience</p>
            
            <?php if(isset($_GET['error'])): ?>
                <p style="color: #721c24; background: #f8d7da; padding: 12px; border-radius: 6px; border: 1px solid #f5c6cb; font-size: 0.9rem; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </p>
            <?php endif; ?>

            <form action="actions/register_action.php" method="POST">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="e.g. Jane Doe">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="e.g. jane@example.com">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="e.g. 0712 345 678">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="Create a password" required>
                        <i class="fa-solid fa-eye toggle-password" title="Toggle Visibility"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" placeholder="Confirm password" required>
                        <i class="fa-solid fa-eye toggle-password" title="Toggle Visibility"></i>
                    </div>
                </div>

                <div style="margin: 25px 0; border-top: 1px solid var(--border-color);"></div>

                <div class="form-group">
                    <label>Security Question</label>
                    <select name="security_question_id" required>
                        <option value="" disabled selected>-- Select for account recovery --</option>
                        <?php if (!empty($questions)): ?>
                            <?php foreach($questions as $q): ?>
                                <option value="<?php echo isset($q['question_id']) ? $q['question_id'] : $q['id']; ?>">
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Questions currently unavailable</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Security Answer</label>
                    <input type="text" name="security_answer" required placeholder="Your answer...">
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 5px;">
                        <i class="fas fa-lock"></i> Your answer will be encrypted for privacy.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Register</button>
            </form>

            <p style="margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
                Already have an account? <a href="index.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login here</a>
            </p>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>