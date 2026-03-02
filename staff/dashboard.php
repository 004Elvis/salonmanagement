<?php
// staff/dashboard.php

require '../config/db.php';
require '../includes/auth_check.php';

// 1. Security: Ensure only Staff can access this page
checkRole(['Staff']); 

// Get the current logged-in staff's ID
$staff_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- HANDLE PROFILE PICTURE UPLOAD ---
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['profile_picture']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        $new_filename = 'staff_' . $staff_id . '_' . time() . '.' . $ext;
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename);
        
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$new_filename, $staff_id]);
        
        header("Location: dashboard.php?success=Profile picture updated");
        exit();
    }
}

// Fetch staff details
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$staff_id]);
$staff_user = $stmt->fetch();
$profile_pic_url = !empty($staff_user['profile_picture']) ? '../uploads/' . htmlspecialchars($staff_user['profile_picture']) : '';

// --- HANDLE STATUS UPDATES ---
if (isset($_POST['status']) && isset($_POST['appointment_id'])) {
    $appt_id = $_POST['appointment_id'];
    $new_status = $_POST['status']; // 'Confirmed' or 'Cancelled'
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND staff_id = ?");
    $stmt->execute([$new_status, $appt_id, $staff_id]);
    
    header("Location: dashboard.php?success=Appointment " . $new_status);
    exit();
}

// --- FETCH DATA FOR DASHBOARD ---

// A. Pending Requests
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS client_name, s.service_name 
    FROM appointments a
    JOIN users u ON a.customer_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.staff_id = ? AND a.status = 'Pending'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute([$staff_id]);
$pending_requests = $stmt->fetchAll();

// B. Upcoming Schedule (All Confirmed appointments)
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS client_name, u.phone, s.service_name, s.duration_minutes, s.price_kes
    FROM appointments a
    JOIN users u ON a.customer_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.staff_id = ? AND a.status = 'Confirmed'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute([$staff_id]);
$scheduled_appointments = $stmt->fetchAll();

// C. Performance Tracker (Weekly Data)
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

$stmt = $pdo->prepare("
    SELECT appointment_date, COUNT(*) as count 
    FROM appointments 
    WHERE staff_id = ? AND status = 'Confirmed' 
    AND appointment_date BETWEEN ? AND ? 
    GROUP BY appointment_date
");
$stmt->execute([$staff_id, $startOfWeek, $endOfWeek]);
$weekly_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 

$week_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$chart_data = [];
$max_appts = 0;

foreach($week_days as $i => $dayName) {
    $date = date('Y-m-d', strtotime($startOfWeek . " +$i days"));
    $count = $weekly_counts[$date] ?? 0;
    $chart_data[$dayName] = $count;
    if($count > $max_appts) $max_appts = $count;
}

// D. Clients List
$stmt = $pdo->prepare("
    SELECT DISTINCT u.user_id, u.full_name, u.phone 
    FROM appointments a
    JOIN users u ON a.customer_id = u.user_id
    WHERE a.staff_id = ? AND a.status = 'Confirmed'
");
$stmt->execute([$staff_id]);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Elvis Salon</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; height: 100vh; background-color: #f9f9fb; color: #333; }
        
        .sidebar { width: 250px; background-color: #fff; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; font-size: 24px; font-weight: bold; border-bottom: 1px solid #ddd; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-item { padding: 15px 25px; cursor: pointer; color: #555; display: flex; align-items: center; gap: 15px; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background-color: #f0f0f0; color: #000; font-weight: 600; }
        
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { height: 70px; border-bottom: 1px solid #ddd; display: flex; justify-content: flex-end; align-items: center; padding: 0 30px; gap: 15px; }
        .btn-logout { padding: 8px 15px; border: 1px solid #333; background: #fff; border-radius: 5px; text-decoration: none; color: #333; font-size: 14px; }
        
        .avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #333; background-size: cover; background-position: center; background-color: #eee; cursor: pointer; }
        
        .content { flex: 1; padding: 30px; overflow-y: auto; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .requests-container { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .request-card { background: #fff; border: 1px solid #333; border-radius: 8px; padding: 15px; width: 300px; display: flex; justify-content: space-between; }
        .btn-action { padding: 6px 12px; border: 1px solid #333; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; margin-bottom: 5px; width: 100%; }
        .btn-accept { background: #85d285; }
        .btn-decline { background: #f28b82; }

        .schedule-box { background: #fff; border: 1px solid #333; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        .schedule-table { width: 100%; border-collapse: collapse; }
        .schedule-table th, .schedule-table td { border: 1px solid #eee; padding: 12px; text-align: left; }
        .appt-block { background: #e8f5e9; padding: 8px; border-left: 4px solid #4caf50; border-radius: 4px; font-size: 13px; }

        .perf-box { background: #fff; border: 1px solid #333; border-radius: 8px; padding: 20px; max-width: 500px; height: 220px; position: relative; }
        .graph-container { display:flex; align-items:flex-end; justify-content: space-around; height:120px; margin-top: 40px; border-bottom:1px solid #333; }
        .bar-wrapper { display:flex; flex-direction:column; align-items:center; width: 12%; }
        .graph-bar { width: 100%; background: #4caf50; border: 1px solid #333; border-radius: 3px 3px 0 0; transition: height 0.5s; }
        
        .success-msg { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">👤 Elvis Salon</div>
        <ul class="nav-menu">
            <li class="nav-item active" onclick="switchTab('home')">🏠 Home</li>
            <li class="nav-item" onclick="switchTab('schedule')">📅 Schedule</li>
            <li class="nav-item" onclick="switchTab('requests')">📄 Requests</li>
            <li class="nav-item" onclick="switchTab('clients')">👥 Clients</li>
        </ul>
    </div>

    <div class="main-wrapper">
        <div class="topbar">
            <a href="availability.php" class="btn-logout">📅 Manage Availability</a>
            <a href="../logout.php" class="btn-logout" style="background: #333; color: #fff;">Logout</a>
            
            <form id="avatarForm" method="POST" enctype="multipart/form-data">
                <label for="profileUpload">
                    <div class="avatar" style="<?= $profile_pic_url ? "background-image: url('$profile_pic_url');" : "" ?>"></div>
                </label>
                <input type="file" id="profileUpload" name="profile_picture" style="display: none;" onchange="this.form.submit();">
            </form>
        </div>

        <div class="content">
            <?php if(isset($_GET['success'])): ?>
                <div class="success-msg"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div id="home" class="tab-content active">
                <h2>Pending Requests Summary</h2>
                <div class="requests-container">
                    <?php if(count($pending_requests) > 0): ?>
                        <?php foreach($pending_requests as $req): ?>
                            <div class="request-card">
                                <div class="request-info">
                                    <strong><?= htmlspecialchars($req['client_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($req['service_name']) ?></small><br>
                                    <small><?= date('M d, h:i A', strtotime($req['appointment_date'] . ' ' . $req['appointment_time'])) ?></small>
                                </div>
                                <div class="request-actions">
                                    <form method="POST">
                                        <input type="hidden" name="appointment_id" value="<?= $req['appointment_id'] ?>">
                                        <button type="submit" name="status" value="Confirmed" class="btn-action btn-accept">Accept</button>
                                        <button type="submit" name="status" value="Cancelled" class="btn-action btn-decline">Decline</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No new requests.</p>
                    <?php endif; ?>
                </div>

                <div class="schedule-box">
                    <h2>My Schedule</h2>
                    <table class="schedule-table">
                        <thead>
                            <tr><th>Time</th><th>Appointment</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $home_schedule = array_slice($scheduled_appointments, 0, 5);
                            if(count($home_schedule) > 0): 
                                foreach($home_schedule as $appt): ?>
                                    <tr>
                                        <td><?= date('D, M d', strtotime($appt['appointment_date'])) ?><br><small><?= date('h:i A', strtotime($appt['appointment_time'])) ?></small></td>
                                        <td><div class="appt-block"><?= htmlspecialchars($appt['client_name']) ?> - <?= htmlspecialchars($appt['service_name']) ?></div></td>
                                    </tr>
                                <?php endforeach; 
                            else: ?>
                                <tr><td colspan="2">No confirmed appointments yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="perf-box">
                    <strong>Weekly Performance (Approved)</strong>
                    <div class="graph-container">
                        <?php foreach($chart_data as $day => $count): 
                            $height = ($max_appts > 0) ? ($count / $max_appts) * 100 : 0;
                        ?>
                            <div class="bar-wrapper" title="<?= $count ?> Appointments">
                                <div class="graph-bar" style="height: <?= max($height, 5) ?>%; background: <?= $count > 0 ? '#4caf50' : '#ddd' ?>;"></div>
                                <span style="font-size:10px;"><?= $day ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="schedule" class="tab-content">
                <h2>All Scheduled Appointments</h2>
                <div class="schedule-box">
                    <table class="schedule-table">
                        <thead>
                            <tr><th>Date/Time</th><th>Client & Service</th><th>Contact</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($scheduled_appointments as $appt): ?>
                                <tr>
                                    <td><strong><?= date('M d', strtotime($appt['appointment_date'])) ?></strong><br><?= date('h:i A', strtotime($appt['appointment_time'])) ?></td>
                                    <td><?= htmlspecialchars($appt['client_name']) ?> (<?= htmlspecialchars($appt['service_name']) ?>)</td>
                                    <td>📞 <?= htmlspecialchars($appt['phone']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="requests" class="tab-content">
                <h2>All Pending Requests Log</h2>
                <div class="schedule-box">
                    <?php if(count($pending_requests) > 0): ?>
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Client & Service</th>
                                    <th>Requested For</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pending_requests as $req): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($req['client_name']) ?></strong><br>
                                            <small><?= htmlspecialchars($req['service_name']) ?></small>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($req['appointment_date'])) ?><br>
                                            <small><?= date('h:i A', strtotime($req['appointment_time'])) ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:flex; gap:5px;">
                                                <input type="hidden" name="appointment_id" value="<?= $req['appointment_id'] ?>">
                                                <button type="submit" name="status" value="Confirmed" class="btn-action btn-accept" style="width:auto; padding: 5px 10px;">Accept</button>
                                                <button type="submit" name="status" value="Cancelled" class="btn-action btn-decline" style="width:auto; padding: 5px 10px;">Decline</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="padding: 20px;">No pending requests found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="clients" class="tab-content">
                <h2>My Clients</h2>
                <div class="schedule-box">
                    <table class="schedule-table">
                        <thead>
                            <tr><th>Name</th><th>Phone Number</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($clients as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['phone']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            // Fixed the active class trigger for the sidebar
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>