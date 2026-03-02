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
        // Create a unique filename
        $new_filename = 'customer_' . $user_id . '_' . time() . '.' . $ext;
        $upload_dir = '../uploads/';
        
        // Ensure upload directory exists
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename);
        
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$new_filename, $user_id]);
        
        // Refresh page to show the new image
        header("Location: dashboard.php?success=Profile_Updated");
        exit();
    }
}

// Fetch user details to display the profile picture
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$profile_pic_url = !empty($current_user['profile_picture']) ? '../uploads/' . htmlspecialchars($current_user['profile_picture']) : '';

// --- FETCH DATA FOR DASHBOARD ---
// Fetch all appointments for this specific customer
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
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9fb; color: #333; margin: 0; padding: 0; }
        .navbar { background: #fff; padding: 15px 30px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; text-decoration: none; }
        
        /* Navbar Actions & Avatar Styling */
        .nav-actions { display: flex; align-items: center; gap: 15px; }
        .avatar-label { cursor: pointer; display: inline-block; margin: 0; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #333; background-color: #ccc; background-size: cover; background-position: center; transition: opacity 0.3s; }
        .avatar:hover { opacity: 0.7; }

        .btn-logout { padding: 8px 15px; border: 1px solid #dc3545; color: #dc3545; text-decoration: none; border-radius: 5px; font-weight: 500; }
        .btn-logout:hover { background: #dc3545; color: #fff; }
        .btn-book { padding: 8px 15px; background: #333; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 500; }
        .btn-book:hover { background: #555; }
        
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb; margin-bottom: 20px; text-align: center; }
        
        .appointment-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .appt-details h3 { margin: 0 0 5px 0; font-size: 18px; }
        .appt-details p { margin: 0; color: #666; font-size: 14px; line-height: 1.5; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; display: inline-block; margin-bottom: 15px; }
        .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-confirmed { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .status-completed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelled { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .appt-actions { display: flex; gap: 10px; flex-direction: column; align-items: flex-end; }
        .btn-sm { padding: 6px 12px; font-size: 13px; border-radius: 4px; cursor: pointer; text-decoration: none; text-align: center; border: none; font-weight: 500; display: inline-block; }
        .btn-edit { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .btn-edit:hover { background-color: #d6d8db; }
        .btn-cancel { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-cancel:hover { background-color: #f5c6cb; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="#" class="navbar-brand">✨ Elvis Salon</a>
        <div class="nav-actions">
            
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="margin: 0;">
                <label for="profileUpload" class="avatar-label" title="Click to upload profile picture">
                    <div class="avatar" style="<?php echo $profile_pic_url ? "background-image: url('$profile_pic_url');" : ""; ?>"></div>
                </label>
                <input type="file" id="profileUpload" name="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit();">
            </form>

            <a href="book.php" class="btn-book">Book New Service</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <?php 
                    if ($_GET['success'] == 'Booking_Confirmed') echo "🎉 <strong>Success!</strong> Your appointment has been booked.";
                    if ($_GET['success'] == 'Appointment_Rescheduled') echo "🔄 <strong>Updated!</strong> Your appointment has been successfully rescheduled.";
                    if ($_GET['success'] == 'Appointment_Cancelled') echo "✕ <strong>Cancelled.</strong> Your appointment has been cancelled successfully.";
                    if ($_GET['success'] == 'Profile_Updated') echo "📸 <strong>Looking good!</strong> Your profile picture has been updated.";
                ?>
            </div>
        <?php endif; ?>

        <div class="header-actions">
            <h2>👋 Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
        </div>

        <h3>My Appointments History</h3>

        <?php if (count($appointments) > 0): ?>
            <?php foreach ($appointments as $appt): ?>
                <div class="appointment-card">
                    <div class="appt-details">
                        <h3><?php echo htmlspecialchars($appt['service_name']); ?></h3>
                        <p><strong>Beautician:</strong> <?php echo htmlspecialchars($appt['beautician_name']); ?></p>
                        <p><strong>Date & Time:</strong> <?php echo date('l, M d, Y', strtotime($appt['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></p>
                        <p><strong>Price:</strong> KES <?php echo number_format($appt['price_kes'], 2); ?></p>
                    </div>
                    
                    <div class="appt-actions">
                        <?php 
                            // Force case-insensitivity
                            $safe_status = strtolower($appt['status']); 
                            
                            $statusClass = 'status-pending'; // Default
                            if ($safe_status == 'confirmed') $statusClass = 'status-confirmed';
                            if ($safe_status == 'completed') $statusClass = 'status-completed';
                            if ($safe_status == 'cancelled') $statusClass = 'status-cancelled';
                            
                            $display_status = ucfirst($safe_status);
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($display_status); ?>
                        </span>

                        <?php if ($safe_status == 'pending' || $safe_status == 'confirmed'): ?>
                            <div style="display: flex; gap: 8px;">
                                <a href="book.php?reschedule_id=<?php echo $appt['appointment_id']; ?>" class="btn-sm btn-edit">🔄 Reschedule</a>
                                
                                <form action="../actions/cancel_action.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <button type="submit" class="btn-sm btn-cancel">✕ Cancel</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background: #fff; padding: 40px; text-align: center; border-radius: 8px; border: 1px solid #ddd;">
                <p style="color: #666; margin-bottom: 20px;">You don't have any appointments yet.</p>
                <a href="book.php" class="btn-book">Book Your First Appointment</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>