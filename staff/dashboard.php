<?php
// staff/dashboard.php
require '../config/db.php';
require '../includes/auth_check.php';

// Include PHPMailer classes
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

checkRole(['Staff']); 

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
$stmt = $pdo->prepare("SELECT profile_picture, full_name FROM users WHERE user_id = ?");
$stmt->execute([$staff_id]);
$staff_user = $stmt->fetch();
$staff_display_name = $staff_user['full_name'];
$profile_pic_url = !empty($staff_user['profile_picture']) ? '../uploads/' . htmlspecialchars($staff_user['profile_picture']) : '';

// --- HANDLE STATUS UPDATES ---
if (isset($_POST['status']) && isset($_POST['appointment_id'])) {
    $appt_id = $_POST['appointment_id'];
    $new_status = $_POST['status']; 
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND staff_id = ?");
    $stmt->execute([$new_status, $appt_id, $staff_id]);

    if ($new_status === 'Confirmed' || $new_status === 'Completed' || $new_status === 'Cancelled') {
        try {
            $info_stmt = $pdo->prepare("
                SELECT u.email, u.full_name, s.service_name, a.appointment_date, a.appointment_time
                FROM appointments a
                JOIN users u ON a.customer_id = u.user_id
                JOIN services s ON a.service_id = s.service_id
                WHERE a.appointment_id = ?
            ");
            $info_stmt->execute([$appt_id]);
            $details = $info_stmt->fetch();

            if ($details) {
                sendStatusEmail($details['email'], $details['full_name'], $details['service_name'], $details['appointment_date'], $details['appointment_time'], $staff_display_name, $new_status);
            }
        } catch (Exception $e) { error_log("Email Trigger Error: " . $e->getMessage()); }
    }
    header("Location: dashboard.php?success=Appointment " . $new_status);
    exit();
}

function sendStatusEmail($recipientEmail, $recipientName, $service, $date, $time, $staffName, $status) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com'; 
        $mail->Password   = 'ohncquyyclverdfg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Midega Salon');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = "Appointment Status Update - Elvis Salon";
        $mail->Body = "Hello $recipientName, your appointment for $service is $status.";
        $mail->send();
    } catch (Exception $e) { error_log("Mailer Error: {$mail->ErrorInfo}"); }
}

// --- FETCH CORE DASHBOARD DATA ---
$stmt = $pdo->prepare("SELECT a.*, u.full_name AS client_name, s.service_name FROM appointments a JOIN users u ON a.customer_id = u.user_id JOIN services s ON a.service_id = s.service_id WHERE a.staff_id = ? AND a.status = 'Pending' ORDER BY a.appointment_date ASC");
$stmt->execute([$staff_id]);
$pending_requests = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT a.*, u.full_name AS client_name, s.service_name FROM appointments a JOIN users u ON a.customer_id = u.user_id JOIN services s ON a.service_id = s.service_id WHERE a.staff_id = ? AND (a.status = 'Confirmed' OR a.status = 'Completed') ORDER BY a.appointment_date DESC");
$stmt->execute([$staff_id]);
$scheduled_appointments = $stmt->fetchAll();

// --- WEEKLY DATA ---
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));
$stmt = $pdo->prepare("SELECT appointment_date, COUNT(*) as count FROM appointments WHERE staff_id = ? AND (status = 'Confirmed' OR status = 'Completed') AND appointment_date BETWEEN ? AND ? GROUP BY appointment_date");
$stmt->execute([$staff_id, $startOfWeek, $endOfWeek]);
$weekly_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
$week_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$chart_labels = []; $chart_values = [];
foreach($week_days as $i => $dayName) {
    $date = date('Y-m-d', strtotime($startOfWeek . " +$i days"));
    $chart_labels[] = $dayName;
    $chart_values[] = $weekly_counts[$date] ?? 0;
}

// --- MONTHLY PERFORMANCE & COMPARISON DATA ---
$currentMonth = date('m'); $currentYear = date('Y');
$lastMonth = date('m', strtotime("-1 month")); $lastYear = date('Y', strtotime("-1 month"));

// Current Month Stats
$stmt = $pdo->prepare("SELECT DAY(appointment_date) as day, COUNT(*) as count, SUM(s.price_kes) as revenue FROM appointments a JOIN services s ON a.service_id = s.service_id WHERE a.staff_id = ? AND (a.status = 'Confirmed' OR a.status = 'Completed') AND MONTH(appointment_date) = ? AND YEAR(appointment_date) = ? GROUP BY DAY(appointment_date)");
$stmt->execute([$staff_id, $currentMonth, $currentYear]);
$monthly_res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$monthly_raw = []; $total_earnings_current = 0;
foreach($monthly_res as $row) { $monthly_raw[$row['day']] = $row['count']; $total_earnings_current += $row['revenue']; }

// Last Month Stats
$stmt_l = $pdo->prepare("SELECT DAY(appointment_date) as day, COUNT(*) as count, SUM(s.price_kes) as revenue FROM appointments a JOIN services s ON a.service_id = s.service_id WHERE a.staff_id = ? AND (a.status = 'Confirmed' OR a.status = 'Completed') AND MONTH(appointment_date) = ? AND YEAR(appointment_date) = ? GROUP BY DAY(appointment_date)");
$stmt_l->execute([$staff_id, $lastMonth, $lastYear]);
$last_monthly_res = $stmt_l->fetchAll(PDO::FETCH_ASSOC);
$last_monthly_raw = []; $total_earnings_last = 0;
foreach($last_monthly_res as $row) { $last_monthly_raw[$row['day']] = $row['count']; $total_earnings_last += $row['revenue']; }

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$monthly_labels = []; $monthly_values = []; $last_month_values = [];
for($d = 1; $d <= $daysInMonth; $d++) {
    $monthly_labels[] = $d;
    $monthly_values[] = $monthly_raw[$d] ?? 0;
    $last_month_values[] = $last_monthly_raw[$d] ?? 0;
}

$stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.full_name, u.phone FROM appointments a JOIN users u ON a.customer_id = u.user_id WHERE a.staff_id = ? AND (a.status = 'Confirmed' OR a.status = 'Completed')");
$stmt->execute([$staff_id]);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Elvis Salon</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f9f9fb; --card-bg: #ffffff; --text-color: #333333; --text-muted: #6c757d; --border-color: #dddddd; --sidebar-bg: #ffffff; --nav-hover: #f0f0f0; --accent: #4caf50; }
        body.dark-mode { --bg-color: #121416; --card-bg: #1e2125; --text-color: #e9ecef; --text-muted: #adb5bd; --border-color: #343a40; --sidebar-bg: #1a1d21; --nav-hover: #2c3034; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }
        body { display: flex; height: 100vh; background-color: var(--bg-color); color: var(--text-color); overflow: hidden; }
        .sidebar { width: 250px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 25px; font-size: 1.1rem; font-weight: bold; border-bottom: 1px solid var(--border-color); }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-item { padding: 15px 25px; cursor: pointer; color: var(--text-color); display: flex; align-items: center; gap: 15px; opacity: 0.8; text-decoration: none; }
        .nav-item:hover, .nav-item.active { background-color: var(--nav-hover); opacity: 1; font-weight: 600; color: var(--accent); }
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { height: 70px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: flex-end; align-items: center; padding: 0 20px; gap: 15px; background: var(--card-bg); position: sticky; top: 0; z-index: 100; }
        .btn-style { padding: 8px 15px; border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 5px; text-decoration: none; color: var(--text-color); font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--accent); background-size: cover; background-position: center; background-color: #eee; cursor: pointer; }
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); color: var(--text-color); font-size: 14px; }
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-confirmed { background: #e3f2fd; color: #1976d2; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .btn-action { padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn-accept, .btn-complete { background: var(--accent); color: white; border: none; }
        .btn-decline { background: #f28b82; color: #721c24; border: none; }
        .chart-container { position: relative; height: 250px; width: 100%; margin-top: 20px; }
        .success-msg { background: rgba(76, 175, 80, 0.1); color: var(--accent); padding: 12px; margin-bottom: 20px; border-radius: 5px; border: 1px solid var(--accent); }
        .metric-card { text-align: center; padding: 15px; border-right: 1px solid var(--border-color); }
        .metric-card:last-child { border-right: none; }
        .metric-value { font-size: 1.2rem; font-weight: bold; color: var(--accent); }
        .metric-label { font-size: 0.8rem; color: var(--text-muted); }
    </style>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : '' ?>">

    <div class="sidebar">
        <div class="sidebar-header">👥 Staff Dashboard</div>
        <ul class="nav-menu">
            <li class="nav-item active" onclick="switchTab('home')"><i class="fas fa-home"></i> <span>Home</span></li>
            <li class="nav-item" onclick="switchTab('schedule')"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></li>
            <li class="nav-item" onclick="switchTab('requests')"><i class="fas fa-inbox"></i> <span>Requests</span></li>
            <li class="nav-item" onclick="switchTab('clients')"><i class="fas fa-users"></i> <span>Clients</span></li>
            <a href="change_password.php" class="nav-item"><i class="fas fa-lock"></i> <span>Security</span></a>
        </ul>
    </div>

    <div class="main-wrapper">
        <div class="topbar">
            <button class="btn-style" id="theme-toggle"><i class="fas fa-moon"></i> <span>Mode</span></button>
            <a href="availability.php" class="btn-style"><i class="fas fa-clock"></i> <span>Shifts</span></a>
            <a href="../logout.php" class="btn-style" style="color: #dc3545; border-color: #dc3545;" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
            
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
                <h2>Welcome Back, <?= explode(' ', $staff_display_name)[0] ?>!</h2>
                
                <div class="card" style="display: flex; justify-content: space-around; padding: 10px; margin-top:20px;">
                    <div class="metric-card">
                        <div class="metric-value">KES <?= number_format($total_earnings_current, 0) ?></div>
                        <div class="metric-label">Earnings (<?= date('F') ?>)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: var(--text-muted);">KES <?= number_format($total_earnings_last, 0) ?></div>
                        <div class="metric-label">Last Month</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value"><?= count($scheduled_appointments) ?></div>
                        <div class="metric-label">Total Jobs</div>
                    </div>
                </div>

                <div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="card">
                        <strong>Weekly Appointments</strong>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <strong>Monthly vs Last Month</strong>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="schedule" class="tab-content">
                <div class="card">
                    <h2>Appointment Schedule</h2>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Time</th><th>Client & Service</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($scheduled_appointments as $appt): ?>
                                    <tr>
                                        <td><?= date('M d, h:i A', strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time'])) ?></td>
                                        <td><strong><?= htmlspecialchars($appt['client_name']) ?></strong><br><small><?= htmlspecialchars($appt['service_name']) ?></small></td>
                                        <td><span class="status-pill status-<?= strtolower($appt['status']) ?>"><?= $appt['status'] ?></span></td>
                                        <td>
                                            <?php if(strtolower($appt['status']) == 'confirmed'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                                    <button type="submit" name="status" value="Completed" class="btn-action btn-complete">Done</button>
                                                </form>
                                            <?php else: ?><i class="fas fa-check-circle" style="color: var(--accent);"></i><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="requests" class="tab-content">
                <div class="card">
                    <h2>New Booking Requests</h2>
                    <div class="table-responsive">
                        <?php if(count($pending_requests) > 0): ?>
                            <table>
                                <thead><tr><th>Details</th><th>Requested Time</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($pending_requests as $req): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($req['client_name']) ?></strong><br><small><?= htmlspecialchars($req['service_name']) ?></small></td>
                                            <td><?= date('M d, h:i A', strtotime($req['appointment_date'] . ' ' . $req['appointment_time'])) ?></td>
                                            <td>
                                                <form method="POST" style="display:flex; gap:5px;">
                                                    <input type="hidden" name="appointment_id" value="<?= $req['appointment_id'] ?>">
                                                    <button type="submit" name="status" value="Confirmed" class="btn-action btn-accept">Accept</button>
                                                    <button type="submit" name="status" value="Cancelled" class="btn-action btn-decline">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="padding:15px; color: var(--text-muted);">No new requests.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="clients" class="tab-content">
                <div class="card">
                    <h2>My Regular Clients</h2>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Name</th><th>Contact info</th></tr></thead>
                            <tbody>
                                <?php foreach($clients as $c): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                                        <td>📞 <?= htmlspecialchars($c['phone']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        const themeBtn = document.getElementById('theme-toggle');
        themeBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('staff-theme', isDark ? 'dark' : 'light');
            themeBtn.innerHTML = isDark ? '<i class="fas fa-sun"></i> Mode' : '<i class="fas fa-moon"></i> Mode';
            updateChartColors(isDark);
        });

        // Weekly Chart
        const ctxWeekly = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctxWeekly, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{ label: 'Appointments', data: <?= json_encode($chart_values) ?>, backgroundColor: '#4caf50', borderRadius: 5 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // Monthly Chart with Comparison
        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctxMonthly, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [
                    { label: 'This Month', data: <?= json_encode($monthly_values) ?>, borderColor: '#4caf50', tension: 0.3, fill: false },
                    { label: 'Last Month', data: <?= json_encode($last_month_values) ?>, borderColor: '#888', borderDash: [5, 5], tension: 0.3, fill: false }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
            }
        });

        function updateChartColors(isDark) {
            const textColor = isDark ? '#e9ecef' : '#333';
            [performanceChart, monthlyChart].forEach(chart => {
                chart.options.scales.y.ticks.color = textColor;
                chart.options.scales.x.ticks.color = textColor;
                if(chart.options.plugins.legend) chart.options.plugins.legend.labels.color = textColor;
                chart.update();
            });
        }

        if (localStorage.getItem('staff-theme') === 'dark') {
            document.body.classList.add('dark-mode');
            themeBtn.innerHTML = '<i class="fas fa-sun"></i> Mode';
            updateChartColors(true);
        }
    </script>
</body>
</html>