<?php 
require 'config/db.php'; 

// Fetch Security Questions
$questions = [];
try {
    // We check if the table exists and fetch data
    // detailed error handling removed for production feel, but try-catch remains
    $stmt = $pdo->query("SELECT * FROM security_questions");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If table doesn't exist or connection fails, questions remains empty
    // You might want to log this error: error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Elvis Midega Salon</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav style="width: 100%; padding: 20px; display: flex; justify-content: flex-end;">
        <button id="theme-toggle" class="theme-switch btn btn-primary" style="width: auto;">🌙 Dark Mode</button>
    </nav>

    <div class="register-container">
        
        <div class="register-card">
            <h2 style="text-align: center; color: var(--primary);">Create Account</h2>
            
            <?php if(isset($_GET['error'])): ?>
                <p style="color: red; text-align: center; background: #ffe6e6; padding: 10px; border-radius: 4px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </p>
            <?php endif; ?>

            <form action="actions/register_action.php" method="POST">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="e.g. Jane Doe">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="e.g. jane@example.com">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="0712345678">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                        <i class="fa-solid fa-eye toggle-password"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                        <i class="fa-solid fa-eye toggle-password"></i>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                <div class="form-group">
    <label>Security Question (For Account Recovery)</label>
    <select name="security_question_id" class="form-control" required>
        <option value="" disabled selected>-- Select a Question --</option>
        <?php if (!empty($questions)): ?>
            <?php foreach($questions as $q): ?>
                <option value="<?php echo isset($q['question_id']) ? $q['question_id'] : $q['id']; ?>">
                    <?php echo htmlspecialchars($q['question_text']); ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="" disabled>No questions found in database</option>
        <?php endif; ?>
    </select>
</div>

                <div class="form-group">
                    <label>Security Answer</label>
                    <input type="text" name="security_answer" class="form-control" required placeholder="Your answer...">
                    <small style="color: var(--text-color); opacity: 0.7; font-size: 0.8em; display: block; text-align: left; margin-top: 5px;">
                        (This will be encrypted for your privacy)
                    </small>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Register</button>
            </form>

            <p style="text-align: center; margin-top: 20px;">
                Already have an account? <a href="index.php" style="color: var(--primary); font-weight: bold;">Login here</a>
            </p>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>