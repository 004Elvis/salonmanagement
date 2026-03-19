<?php
session_start();
require '../config/db.php';

// RBAC Check
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
        $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename);
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$new_filename, $admin_id]);
        header("Location: admin_dashboard.php?success=Profile updated");
        exit();
    }
}

// Fetch admin details
$stmt = $pdo->prepare("SELECT full_name, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$admin_image = !empty($admin['profile_picture']) ? '../uploads/' . htmlspecialchars($admin['profile_picture']) : '../assets/images/default_profile.png';

// --- DATA FETCHING FOR CHARTS ---

// 1. Revenue Trend (Last 7 Days)
$stmt_rev = $pdo->query("SELECT DATE_FORMAT(appointment_date, '%b %d') as label, SUM(s.price_kes) as total FROM appointments a JOIN services s ON a.service_id = s.service_id WHERE a.status = 'Completed' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY a.appointment_date ORDER BY a.appointment_date ASC");
$revenue_trend = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);

// 2. Service Split
$stmt_svc = $pdo->query("SELECT s.service_name as label, COUNT(*) as value FROM appointments a JOIN services s ON a.service_id = s.service_id WHERE a.status = 'Completed' GROUP BY s.service_name");
$service_split = $stmt_svc->fetchAll(PDO::FETCH_ASSOC);

// 3. Staff Performance
$stmt_staff = $pdo->query("SELECT u.full_name as label, COUNT(*) as value FROM appointments a JOIN users u ON a.staff_id = u.user_id WHERE a.status = 'Completed' GROUP BY u.user_id");
$staff_perf = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

// 4. Full Recent Appointments (Back to original logic)
$sql_appointments = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, u.full_name as client_name, s.full_name as staff_name, sv.service_name FROM appointments a JOIN users u ON a.customer_id = u.user_id JOIN users s ON a.staff_id = s.user_id JOIN services sv ON a.service_id = sv.service_id WHERE a.status != 'Cancelled' ORDER BY a.appointment_date DESC LIMIT 5";
$recent_appointments = $pdo->query($sql_appointments)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Elvis Salon</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        if (localStorage.getItem('admin-theme') === 'dark') {
            document.documentElement.classList.add('dark-mode-init');
        }
    </script>

    <style>
        :root { 
            --sidebar-bg: #f8f9fa; 
            --main-bg: #f4f6f9; 
            --card-bg: #ffffff; 
            --text-main: #333; 
            --border-color: #dee2e6; 
            --accent: #4caf50; 
            --sidebar-width: 250px;
        }
        
        body.dark-mode { 
            --sidebar-bg: #1a1d20; 
            --main-bg: #121416; 
            --card-bg: #212529; 
            --text-main: #f8f9fa; 
            --border-color: #373b3e; 
        }
        .dark-mode-init { background-color: #121416 !important; }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: background 0.2s, color 0.2s; }
        body { display: flex; min-height: 100vh; background-color: var(--main-bg); color: var(--text-main); }

        /* Sidebar - Hidden on mobile, shown on desktop */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--sidebar-bg); 
            border-right: 1px solid var(--border-color); 
            display: flex; 
            flex-direction: column; 
            height: 100vh;
            position: sticky;
            top: 0;
            flex-shrink: 0;
        }

        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px; border-bottom: 1px solid var(--border-color); }
        .nav-links { padding: 10px; flex: 1; }
        .menu-item { display: flex; align-items: center; padding: 12px 20px; text-decoration: none; color: var(--text-main); border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background: rgba(76, 175, 80, 0.1); color: var(--accent); font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; }

        .main-content { flex: 1; display: flex; flex-direction: column; width: 100%; min-width: 0; }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 20px; 
            background: var(--card-bg); 
            border-bottom: 1px solid var(--border-color); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        .header h2 { font-size: 1.2rem; }
        .header-actions { display: flex; gap: 10px; align-items: center; }

        .dashboard-padding { padding: 20px; width: 100%; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        /* Grid Layouts - Responsive */
        .charts-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        
        /* Specific layout for bottom section */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .chart-container { height: 250px; position: relative; width: 100%; }
        
        /* Table Responsiveness */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .status-confirmed { background: #e3f2fd; color: #1976d2; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }

        .btn-action { padding: 6px 12px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-main); border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; }
        .btn-cancel { color: #dc3545; border-color: #dc3545; }

        /* Media Queries */
        @media (max-width: 992px) {
            .bottom-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .brand, .menu-item span { display: none; }
            .menu-item i { margin-right: 0; font-size: 1.2rem; }
            .header { padding: 10px 15px; }
            .header h2 { font-size: 1rem; }
            .dashboard-padding { padding: 15px; }
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">✨ Admin</div>
        <div class="nav-links">
            <a href="view_logs.php" class="menu-item"><i class="fas fa-shield-alt"></i> <span>Audit Logs</span></a>
            <a href="admin_dashboard.php" class="menu-item active"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Financials</span></a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> <span>Clients</span></a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Dashboard</h2>
            <div class="header-actions">
                <button id="theme-toggle" class="btn-action">🌙 Mode</button>
                <a href="../logout.php" class="btn-action" style="background: var(--accent); color: white; border: none;">Logout</a>
                <form method="POST" enctype="multipart/form-data" style="margin:0;">
                    <label for="profileUpload"><img src="<?= $admin_image ?>" style="width:35px; height:35px; border-radius:50%; object-fit:cover; cursor:pointer; border: 2px solid var(--accent);"></label>
                    <input type="file" id="profileUpload" name="profile_picture" style="display:none;" onchange="this.form.submit()">
                </form>
            </div>
        </div>

        <div class="dashboard-padding">
            <div class="charts-grid">
                <div class="card">
                    <strong>7-Day Revenue Trend (KES)</strong>
                    <div class="chart-container"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="card">
                    <strong>Service Popularity</strong>
                    <div class="chart-container"><canvas id="serviceChart"></canvas></div>
                </div>
            </div>

            <div class="bottom-grid">
                <div class="card">
                    <strong>Staff Workload (Completed)</strong>
                    <div class="chart-container"><canvas id="staffChart"></canvas></div>
                </div>
                
                <div class="card">
                    <strong>Recent Appointments</strong>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Client</th><th>Service</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($recent_appointments as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['client_name']) ?></td>
                                        <td><?= htmlspecialchars($a['service_name']) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                                        <td><button class="btn-action btn-cancel" onclick="cancelAppointment(<?= $a['appointment_id'] ?>)">Cancel</button></td>
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
        // Data and Chart Logic (Same as before, Chart.js handles its own resizing)
        const revLabels = <?= json_encode(array_column($revenue_trend, 'label')) ?>;
        const revData = <?= json_encode(array_column($revenue_trend, 'total')) ?>;
        const svcLabels = <?= json_encode(array_column($service_split, 'label')) ?>;
        const svcData = <?= json_encode(array_column($service_split, 'value')) ?>;
        const staffLabels = <?= json_encode(array_column($staff_perf, 'label')) ?>;
        const staffData = <?= json_encode(array_column($staff_perf, 'value')) ?>;

        const revenueChart = new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: { labels: revLabels, datasets: [{ label: 'Income', data: revData, borderColor: '#4caf50', tension: 0.4, fill: true, backgroundColor: 'rgba(76, 175, 80, 0.1)' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        const serviceChart = new Chart(document.getElementById('serviceChart'), {
            type: 'doughnut',
            data: { labels: svcLabels, datasets: [{ data: svcData, backgroundColor: ['#4caf50', '#2196f3', '#ff9800', '#f44336', '#9c27b0'] }] },
            options: { responsive: true, maintainAspectRatio: false }
        });

        const staffChart = new Chart(document.getElementById('staffChart'), {
            type: 'bar',
            data: { labels: staffLabels, datasets: [{ label: 'Workload', data: staffData, backgroundColor: '#2196f3', borderRadius: 5 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        const themeBtn = document.getElementById('theme-toggle');
        function applyTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                themeBtn.innerHTML = '☀️ Mode';
            } else {
                document.body.classList.remove('dark-mode');
                themeBtn.innerHTML = '🌙 Mode';
            }
            updateChartColors(theme === 'dark');
        }

        themeBtn.addEventListener('click', () => {
            const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            localStorage.setItem('admin-theme', newTheme);
            applyTheme(newTheme);
        });

        function updateChartColors(isDark) {
            const color = isDark ? '#f8f9fa' : '#333';
            [revenueChart, staffChart].forEach(c => {
                c.options.scales.x.ticks.color = color;
                c.options.scales.y.ticks.color = color;
                c.update();
            });
        }

        applyTheme(localStorage.getItem('admin-theme') || 'light');

        function cancelAppointment(id) {
            if (confirm("Cancel this appointment?")) {
                fetch('api/cancel_appointment_handler.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'appointment_id=' + id })
                .then(res => res.json()).then(data => { if(data.success) location.reload(); else alert(data.message); });
            }
        }
    </script>
</body>
</html>