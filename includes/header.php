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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    

    <style>
        /* Responsive Navigation Styling */
        .navbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .brand-logo {
            font-size: 1.4rem;
            font-weight: bold;
            text-decoration: none;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
            list-style: none;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: var(--primary);
        }

        /* Mobile Menu Toggle (Hamburger) */
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--text-main);
        }

        #nav-check {
            display: none;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .nav-menu {
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                background: var(--card-bg);
                flex-direction: column;
                gap: 0;
                height: 0;
                overflow: hidden;
                transition: all 0.3s ease-in;
                border-bottom: 0 solid var(--border-color);
            }

            .nav-menu a, .nav-menu span {
                padding: 15px 20px;
                width: 100%;
                border-bottom: 1px solid var(--border-color);
            }

            #nav-check:checked ~ .nav-menu {
                height: auto;
                border-bottom: 1px solid var(--border-color);
            }
            
            .nav-container {
                height: 50px;
            }
        }
        
        #theme-toggle {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: var(--main-bg);
            color: var(--text-main);
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand-logo">
                <i class="fas fa-spa"></i> <span>Elvis Midega Salon</span>
            </a>

            <input type="checkbox" id="nav-check">
            <label for="nav-check" class="menu-toggle">
                <i class="fas fa-bars"></i>
            </label>

            <div class="nav-menu">
                <button id="theme-toggle" type="button">🌙 Mode</button>

                <?php if (isset($_SESSION['role'])): ?>
                    
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a href="../admin/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dash</a>
                        <a href="../admin/admin_staff.php">Staff</a>
                        <a href="../admin/admin_settings.php">Services</a>
                    
                    <?php elseif ($_SESSION['role'] === 'Staff'): ?>
                        <a href="../staff/dashboard.php"><i class="fas fa-calendar-day"></i> My Dash</a>
                        <a href="../staff/availability.php">Availability</a>

                    <?php elseif ($_SESSION['role'] === 'Customer'): ?>
                        <a href="../customer/book.php"><i class="fas fa-plus"></i> Book Now</a>
                        <a href="../customer/dashboard.php">My Appointments</a>
                    <?php endif; ?>

                    <a href="../logout.php" style="color: #dc3545; font-weight: bold;">
                        <i class="fas fa-sign-out-alt"></i> Logout (<?= htmlspecialchars($_SESSION['name'] ?? 'User'); ?>)
                    </a>

                <?php else: ?>
                    <a href="../index.php">Login</a>
                    <a href="../register.php" style="color: var(--primary);">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        const themeBtn = document.getElementById('theme-toggle');
        const body = document.body;

        function applyHeaderTheme(theme) {
            if (theme === 'dark') {
                body.classList.add('dark-mode');
                if(themeBtn) themeBtn.innerHTML = '☀️ Light';
            } else {
                body.classList.remove('dark-mode');
                if(themeBtn) themeBtn.innerHTML = '🌙 Dark';
            }
        }

        if(themeBtn) {
            themeBtn.addEventListener('click', () => {
                const isDark = body.classList.toggle('dark-mode');
                const newTheme = isDark ? 'dark' : 'light';
                localStorage.setItem('admin-theme', newTheme);
                localStorage.setItem('customer-theme', newTheme);
                applyHeaderTheme(newTheme);
            });
        }

        // Initialize theme based on preference
        const savedHeaderTheme = localStorage.getItem('admin-theme') || localStorage.getItem('customer-theme') || 'light';
        applyHeaderTheme(savedHeaderTheme);
    </script>

    <div class="container" style="padding: 20px; max-width: 1200px; margin: 0 auto;">