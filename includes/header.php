<?php
// Ensure session is started to read user roles
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elvis Midega Salon</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <nav class="navbar" style="background: var(--card-bg); padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            
            <a href="#" style="font-size: 1.5rem; font-weight: bold; text-decoration: none; color: var(--primary);">
                Elvis Midega Salon
            </a>

            <div class="nav-links">
                <button id="theme-toggle" class="btn" style="margin-right: 15px;">🌙 Dark Mode</button>

                <?php if (isset($_SESSION['role'])): ?>
                    
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a href="../admin/dashboard.php">Dashboard</a> | 
                        <a href="../admin/manage_staff.php">Staff</a> | 
                        <a href="../admin/manage_services.php">Services</a> | 
                    
                    <?php elseif ($_SESSION['role'] === 'Staff'): ?>
                        <a href="../staff/dashboard.php">Dashboard</a> | 
                        <a href="../staff/view_schedule.php">My Schedule</a> | 

                    <?php elseif ($_SESSION['role'] === 'Customer'): ?>
                        <a href="../customer/book.php">Book Now</a> | 
                        <a href="../customer/history.php">My History</a> | 
                    <?php endif; ?>

                    <a href="../logout.php" style="color: red; margin-left: 10px;">Logout (<?= htmlspecialchars($_SESSION['name']); ?>)</a>

                <?php else: ?>
                    <a href="../index.php">Login</a> | 
                    <a href="../register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">