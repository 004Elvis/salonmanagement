<?php
// customer/dashboard.php
require '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure only customers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- HANDLE PROFILE PICTURE UPLOAD ---
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['profile_picture']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        $new_filename = 'customer_' . $user_id . '_' . time() . '.' . $ext;
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename);
        
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$new_filename, $user_id]);
        
        header("Location: dashboard.php?success=Profile_Updated");
        exit();
    }
}

// Fetch user details
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$profile_pic_url = !empty($current_user['profile_picture']) ? '../uploads/' . htmlspecialchars($current_user['profile_picture']) : '';

// --- FETCH DATA FOR DASHBOARD ---
$stmt = $pdo->prepare("
    SELECT a.*, s.service_name, s.price_kes, u.full_name AS beautician_name
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.staff_id = u.user_id
    WHERE a.customer_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Elvis Salon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --body-bg: #f4f6f9;
            --card-bg: #ffffff;
            --nav-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --accent: #4caf50;
        }

        body.dark-mode {
            --body-bg: #121416;
            --card-bg: #212529;
            --nav-bg: #1a1d20;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
        }

        * { transition: background 0.3s, color 0.3s; box-sizing: border-box; }
        
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--body-bg); color: var(--text-main); margin: 0; padding: 0; }
        
        /* Navbar Responsive */
        .navbar { background: var(--nav-bg); padding: 15px 5%; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; }
        .navbar-brand { font-size: 1.2rem; font-weight: bold; color: var(--text-main); text-decoration: none; }
        
        .nav-actions { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; border: 2px solid var(--accent); background-color: #ccc; background-size: cover; background-position: center; cursor: pointer; }

        .btn-logout { padding: 6px 12px; border: 1px solid #dc3545; color: #dc3545; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .btn-logout:hover { background: #dc3545; color: #fff; }
        
        .btn-book { padding: 8px 16px; background: var(--accent); color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 14px; }
        
        #theme-toggle { padding: 6px 12px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-main); cursor: pointer; border-radius: 5px; font-size: 14px; }

        .container { max-width: 900px; margin: 30px auto; padding: 0 15px; }
        
        /* Card & Table Responsiveness */
        .appointment-card { 
            background: var(--card-bg); 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 15px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .appt-details h3 { margin: 0 0 5px 0; font-size: 17px; color: var(--text-main); }
        .appt-details p { margin: 0; color: var(--text-muted); font-size: 14px; line-height: 1.6; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; margin-bottom: 8px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #e3f2fd; color: #1976d2; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .appt-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
        .btn-sm { padding: 6px 12px; font-size: 13px; border-radius: 4px; cursor: pointer; text-decoration: none; border: none; font-weight: 500; display: inline-block; }
        .btn-edit { background-color: var(--border-color); color: var(--text-main); }
        .btn-cancel { background-color: #f8d7da; color: #721c24; }
        .btn-receipt { background-color: var(--accent); color: #fff; text-align: center; }
        
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; border: 1px solid #c3e6cb; margin-bottom: 20px; font-size: 14px; }
        .empty-state { background: var(--card-bg); padding: 40px; text-align: center; border-radius: 8px; border: 1px solid var(--border-color); }

        /* Mobile Adjustments */
        @media (max-width: 600px) {
            .navbar { padding: 10px 15px; }
            .navbar-brand span { display: none; } /* Hide text, keep emoji if needed */
            .btn-book { padding: 6px 10px; font-size: 12px; }
            .appointment-card { flex-direction: column; align-items: flex-start; }
            .appt-actions { width: 100%; align-items: flex-start; margin-top: 15px; border-top: 1px solid var(--border-color); padding-top: 15px; }
            .appt-actions div { width: 100%; display: flex; gap: 5px; }
            .btn-sm { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="#" class="navbar-brand">✨ <span>Elvis Salon</span></a>
        <div class="nav-actions">
            <button id="theme-toggle">🌙 Mode</button>
            
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="margin: 0;">
                <label for="profileUpload" style="display:block;">
                    <div class="avatar" style="<?php echo $profile_pic_url ? "background-image: url('$profile_pic_url');" : ""; ?>"></div>
                </label>
                <input type="file" id="profileUpload" name="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit();">
            </form>

            <a href="book.php" class="btn-book">Book Now</a>
            <a href="../logout.php" class="btn-logout" title="Logout" onclick="return confirm('Are you sure you want to exit?');"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo str_replace('_', ' ', htmlspecialchars($_GET['success'])); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 30px;">
            <h2 style="margin:0;">👋 Hello, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p style="color: var(--text-muted); margin-top:5px;">Manage your beauty appointments and view your receipts.</p>
        </div>

        <h3 style="font-size: 1.1rem; margin-bottom: 20px; border-left: 4px solid var(--accent); padding-left: 10px;">My Bookings</h3>

        <?php if (count($appointments) > 0): ?>
            <?php foreach ($appointments as $appt): ?>
                <?php $status_low = strtolower($appt['status']); ?>
                <div class="appointment-card">
                    <div class="appt-details">
                        <span class="status-badge status-<?php echo $status_low; ?>">
                            <?php echo $appt['status']; ?>
                        </span>
                        <h3><?php echo htmlspecialchars($appt['service_name']); ?></h3>
                        <p><i class="far fa-user"></i> <strong>Staff:</strong> <?php echo htmlspecialchars($appt['beautician_name']); ?></p>
                        <p><i class="far fa-calendar-alt"></i> <strong>When:</strong> <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></p>
                        <p><i class="fas fa-tag"></i> <strong>Price:</strong> KES <?php echo number_format($appt['price_kes'], 2); ?></p>
                    </div>
                    
                    <div class="appt-actions">
                        <?php if ($status_low === 'pending' || $status_low === 'confirmed'): ?>
                            <div style="display: flex; gap: 8px;">
                                <a href="book.php?reschedule_id=<?php echo $appt['appointment_id']; ?>" class="btn-sm btn-edit"><i class="fas fa-sync"></i> Reschedule</a>
                                <form action="../actions/cancel_action.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to cancel?');">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <button type="submit" class="btn-sm btn-cancel">✕ Cancel</button>
                                </form>
                            </div>
                        <?php elseif ($status_low === 'completed'): ?>
                            <a href="view_receipt.php?id=<?php echo $appt['appointment_id']; ?>" class="btn-sm btn-receipt">
                                <i class="fas fa-file-invoice"></i> View Receipt
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-calendar-times" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display:block;"></i>
                <p>You don't have any appointments yet.</p>
                <a href="book.php" class="btn-book">Book Your First Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Global Theme Management
        const btn = document.getElementById('theme-toggle');
        const body = document.body;

        function applyTheme(theme) {
            if (theme === 'dark') {
                body.classList.add('dark-mode');
                btn.innerHTML = '☀️ Light';
            } else {
                body.classList.remove('dark-mode');
                btn.innerHTML = '🌙 Dark';
            }
        }

        btn.addEventListener('click', () => {
            const isDark = body.classList.toggle('dark-mode');
            const theme = isDark ? 'dark' : 'light';
            localStorage.setItem('customer-theme', theme);
            applyTheme(theme);
        });

        // Sync with LocalStorage (Matches Admin/Staff keys)
        const savedTheme = localStorage.getItem('customer-theme') || localStorage.getItem('admin-theme');
        applyTheme(savedTheme);
    </script>
</body>
</html>