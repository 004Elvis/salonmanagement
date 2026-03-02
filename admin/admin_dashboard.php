<?php
session_start();
require '../config/db.php';

// RBAC Check - Using your exact logic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

$admin_id = $_SESSION['user_id'];

// --- HANDLE PROFILE PICTURE UPLOAD ---
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['profile_picture']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        // Create a unique filename
        $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
        $upload_dir = '../uploads/';
        
        // Ensure upload directory exists
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename);
        
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$new_filename, $admin_id]);
        
        // Refresh page to show the new image
        header("Location: admin_dashboard.php?success=Profile picture updated");
        exit();
    }
}

// Fetch admin details for the header 
$stmt = $pdo->prepare("SELECT full_name, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$admin_name = $admin['full_name'] ?? 'Admin';

// Set image path dynamically 
$admin_image = !empty($admin['profile_picture']) ? '../uploads/' . htmlspecialchars($admin['profile_picture']) : '../assets/images/default_profile.png';

// --- DATA FETCHING ---

// 1. Fetch Recent Appointments (Updated to fetch 'status' so admin sees staff approvals)
$sql_appointments = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, u.full_name as client_name, s.full_name as staff_name, sv.service_name 
    FROM appointments a 
    JOIN users u ON a.customer_id = u.user_id 
    JOIN users s ON a.staff_id = s.user_id
    JOIN services sv ON a.service_id = sv.service_id
    WHERE a.status != 'Cancelled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC 
    LIMIT 5
";
$stmt_apt = $pdo->query($sql_appointments);
$recent_appointments = $stmt_apt->fetchAll(PDO::FETCH_ASSOC);


// --- FINANCIAL DATA ASSUMPTIONS ---

// 2. Total Revenue (YTD) - Updated to use price_kes
$stmt_ytd = $pdo->query("
    SELECT SUM(sv.price_kes) as total_ytd
    FROM appointments a
    JOIN services sv ON a.service_id = sv.service_id
    WHERE YEAR(a.appointment_date) = YEAR(CURDATE()) AND a.status = 'Completed'
");
$total_ytd = $stmt_ytd->fetchColumn() ?: 0;

// 3. Daily Income (Monday to Sunday of the current week) - Updated to use price_kes
$stmt_daily = $pdo->query("
    SELECT DATE_FORMAT(a.appointment_date, '%W') as day_name, SUM(sv.price_kes) as daily_total
    FROM appointments a
    JOIN services sv ON a.service_id = sv.service_id
    WHERE YEARWEEK(a.appointment_date, 1) = YEARWEEK(CURDATE(), 1) AND a.status = 'Completed'
    GROUP BY DAYOFWEEK(a.appointment_date)
");
$daily_data_raw = $stmt_daily->fetchAll(PDO::FETCH_KEY_PAIR);

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$daily_income_data = [];
foreach ($days_of_week as $day) {
    $daily_income_data[] = $daily_data_raw[$day] ?? 0;
}

// 4. Weekly Revenue (Last 4 Weeks) - Updated to use price_kes
$stmt_weekly = $pdo->query("
    SELECT CONCAT('Week ', WEEK(a.appointment_date, 1)) as week_label, SUM(sv.price_kes) as weekly_total
    FROM appointments a
    JOIN services sv ON a.service_id = sv.service_id
    WHERE a.appointment_date >= DATE_SUB(NOW(), INTERVAL 4 WEEK) AND a.status = 'Completed'
    GROUP BY WEEK(a.appointment_date, 1)
    ORDER BY WEEK(a.appointment_date, 1) ASC
");
$weekly_data_raw = $stmt_weekly->fetchAll(PDO::FETCH_ASSOC);
$weekly_labels = array_column($weekly_data_raw, 'week_label');
$weekly_revenue_data = array_column($weekly_data_raw, 'weekly_total');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Elvis Midega Beauty Salon</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-bg: #f8f9fa;
            --content-bg: #ffffff;
            --text-dark: #333;
            --text-light: #6c757d;
            --border-color: #dee2e6;
            --danger-color: #dc3545;
            --danger-bg: #f8d7da;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f4f6f9; }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }
        .brand { 
            font-size: 1.2rem; font-weight: bold; padding: 25px 20px; 
            color: var(--text-dark); border-bottom: 1px solid var(--border-color);
            margin-bottom: 15px;
        }
        .nav-links { padding: 0 15px; }
        .menu-item {
            display: flex; align-items: center; padding: 12px 15px;
            text-decoration: none; color: var(--text-dark);
            border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem;
        }
        .menu-item:hover { background-color: #e9ecef; }
        .menu-item.active { background-color: #e2e3e5; font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-light); }
        .menu-item.active i { color: var(--text-dark); }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        /* Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 30px; background: #fff; border-bottom: 1px solid var(--border-color);
        }
        .header h2 { font-size: 1.4rem; color: var(--text-dark); }
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .logout-btn { 
            padding: 8px 16px; border: 1px solid var(--border-color); 
            background: #fff; cursor: pointer; border-radius: 4px; font-weight: 500;
            text-decoration: none; color: var(--text-dark);
        }
        .logout-btn:hover { background: #f8f9fa; }
        
        /* Profile Image Upload */
        .profile-wrapper { display: flex; align-items: center; gap: 10px; }
        .profile-img-container { 
            width: 40px; height: 40px; border-radius: 50%; overflow: hidden; 
            background: #ccc; cursor: pointer; border: 1px solid #aaa; transition: opacity 0.3s;
        }
        .profile-img-container:hover { opacity: 0.7; }
        .profile-img-container img { width: 100%; height: 100%; object-fit: cover; }

        /* Content Area */
        .dashboard-padding { padding: 30px; }
        .card {
            background: #fff; border: 1px solid var(--border-color);
            border-radius: 8px; padding: 25px; margin-bottom: 30px;
        }
        .card-header { font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: var(--text-dark); }

        /* Table & Badges */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        th { color: var(--text-light); font-weight: 600; border-bottom: 2px solid var(--border-color); }
        
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-confirmed { background-color: #d4edda; color: #155724; }
        
        .btn-cancel {
            padding: 6px 12px; background-color: var(--danger-bg); color: var(--danger-color);
            border: 1px solid var(--danger-color); border-radius: 4px; cursor: pointer; font-size: 0.85rem;
        }
        .btn-cancel:hover { background-color: var(--danger-color); color: #fff; }

        /* Charts */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .chart-container { position: relative; height: 250px; width: 100%; }
        .section-title { margin-bottom: 20px; font-size: 1.2rem; color: var(--text-dark); font-weight: 600; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Admin Dashboard</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item active"><i class="fas fa-home"></i> Home</a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> All Appointments</a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> Financials</a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> Staff</a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> Clients</a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Admin Dashboard</h2>
            <div class="header-actions">
                <a href="../logout.php" class="logout-btn">Logout</a>
                
                <div class="profile-wrapper">
                    <form id="avatarForm" method="POST" enctype="multipart/form-data" style="margin: 0;">
                        <label for="profileUpload" class="profile-img-container" title="Click to change profile picture" style="display: block;">
                            <img src="<?php echo $admin_image; ?>" alt="Admin">
                        </label>
                        <input type="file" id="profileUpload" name="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit();">
                    </form>
                </div>
                
            </div>
        </div>

        <div class="dashboard-padding">
            
            <div class="card">
                <div class="card-header">Recent Appointments Overview</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Staff</th>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_appointments) > 0): ?>
                                <?php foreach ($recent_appointments as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                    <td><?php echo date('M d, h:i A', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'])); ?></td>
                                    
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <button class="btn-cancel" onclick="cancelAppointment(<?php echo $row['appointment_id']; ?>)">Cancel X</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6">No appointments currently booked.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h3 class="section-title">Financial Tracking</h3>

            <div class="charts-grid">
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">Total Revenue (YTD) <br><span style="font-size: 1.5rem; color: #555;">KES <?php echo number_format($total_ytd, 2); ?></span></div>
                    <div class="chart-container">
                        <canvas id="ytdChart"></canvas>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">Daily Income</div>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Weekly Revenue</div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Chart Configuration
        const chartOptions = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: value => 'KES ' + value } },
                x: { grid: { display: false } }
            }
        };

        // 1. YTD Line Chart (Visual representation)
        new Chart(document.getElementById('ytdChart'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, <?php echo $total_ytd; ?>], // Needs real monthly grouping in PHP
                    borderColor: '#a0a0a0', backgroundColor: 'rgba(160, 160, 160, 0.2)', fill: true, tension: 0.3
                }]
            },
            options: chartOptions
        });

        // 2. Daily Bar Chart (Mon - Sun)
        new Chart(document.getElementById('dailyChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($days_of_week); ?>,
                datasets: [{
                    data: <?php echo json_encode($daily_income_data); ?>,
                    backgroundColor: '#cccccc', barPercentage: 0.6
                }]
            },
            options: chartOptions
        });

        // 3. Weekly Bar Chart
        new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(empty($weekly_labels) ? ['Current Week'] : $weekly_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode(empty($weekly_revenue_data) ? [0] : $weekly_revenue_data); ?>,
                    backgroundColor: '#cccccc', barPercentage: 0.6
                }]
            },
            options: chartOptions
        });

        // Cancel Appointment Logic
        function cancelAppointment(id) {
            if (confirm("Are you sure you want to cancel this appointment?")) {
                fetch('api/cancel_appointment_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'appointment_id=' + id
})
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert("Cancelled successfully.");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>