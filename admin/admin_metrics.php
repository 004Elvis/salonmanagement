<?php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

// 1. Service Profitability (Total KES per Service)
$stmt_profit = $pdo->query("
    SELECT s.service_name, SUM(s.price_kes) as total_revenue, COUNT(a.appointment_id) as total_bookings 
    FROM appointments a 
    JOIN services s ON a.service_id = s.service_id 
    WHERE a.status = 'Completed' 
    GROUP BY s.service_id 
    ORDER BY total_revenue DESC
");
$service_metrics = $stmt_profit->fetchAll(PDO::FETCH_ASSOC);

// 2. Monthly Registration Growth (Last 6 Months)
$stmt_growth = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count 
    FROM users 
    WHERE role_id = 3 
    GROUP BY YEAR(created_at), MONTH(created_at) 
    ORDER BY created_at ASC 
    LIMIT 6
");
$growth_data = $stmt_growth->fetchAll(PDO::FETCH_ASSOC);

// 3. Busy Hours (When do most appointments happen?)
$stmt_hours = $pdo->query("
    SELECT HOUR(appointment_time) as hr, COUNT(*) as count 
    FROM appointments 
    WHERE status != 'Cancelled' 
    GROUP BY hr 
    ORDER BY hr ASC
");
$hour_metrics = $stmt_hours->fetchAll(PDO::FETCH_ASSOC);

// 4. Staff Performance Metrics 
$stmt_staff = $pdo->query("
    SELECT u.full_name as staff_name, COUNT(a.appointment_id) as total_jobs, SUM(s.price_kes) as revenue_generated
    FROM appointments a
    JOIN users u ON a.staff_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.status = 'Completed'
    GROUP BY u.user_id
    ORDER BY revenue_generated DESC
");
$staff_metrics = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

// 5. Monthly Comparison Logic (NEW FEATURE)
// Current Month
$curr_month_stmt = $pdo->query("SELECT COUNT(*) as total_appts, IFNULL(SUM(s.price_kes), 0) as total_rev FROM appointments a JOIN services s ON a.service_id = s.service_id WHERE MONTH(a.appointment_date) = MONTH(CURRENT_DATE()) AND YEAR(a.appointment_date) = YEAR(CURRENT_DATE()) AND a.status = 'Completed'");
$curr_month = $curr_month_stmt->fetch();

// Previous Month
$prev_month_stmt = $pdo->query("SELECT COUNT(*) as total_appts, IFNULL(SUM(s.price_kes), 0) as total_rev FROM appointments a JOIN services s ON a.service_id = s.service_id WHERE a.appointment_date >= DATE_SUB(DATE_FORMAT(NOW() ,'%Y-%m-01'), INTERVAL 1 MONTH) AND a.appointment_date < DATE_FORMAT(NOW() ,'%Y-%m-01') AND a.status = 'Completed'");
$prev_month = $prev_month_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon Metrics - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #f4f6f9; --card: #fff; --text: #333; --accent: #4caf50; --border: #dee2e6; --sidebar-w: 250px; }
        body.dark-mode { --bg: #121416; --card: #212529; --text: #f8f9fa; --border: #373b3e; }
        
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; flex-direction: row; min-height: 100vh; }
        
        .sidebar { width: var(--sidebar-w); background: var(--card); border-right: 1px solid var(--border); position: sticky; top: 0; height: 100vh; flex-shrink: 0; transition: all 0.3s; }
        .main-content { flex: 1; padding: 30px; overflow-x: hidden; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 25px; margin-top: 20px; }
        .card { background: var(--card); padding: 20px; border-radius: 10px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        
        .menu-item { display: flex; align-items: center; gap: 10px; padding: 15px 25px; color: var(--text); text-decoration: none; border-bottom: 1px solid var(--border); }
        .menu-item:hover { background: rgba(76, 175, 80, 0.1); color: var(--accent); }
        
        .table-wrapper { width: 100%; overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; min-width: 400px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
        th { color: #888; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
        
        .chart-h { height: 300px; width: 100%; }

        /* PDF Button Style */
        .btn-pdf { background: #e74c3c; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; border: none; cursor: pointer; font-weight: bold; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; }
        .btn-pdf:hover { background: #c0392b; }

        @media (max-width: 992px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid var(--border); }
            .sidebar-header { display: none; }
            .nav-links { display: flex; overflow-x: auto; white-space: nowrap; }
            .menu-item { border-bottom: none; border-right: 1px solid var(--border); padding: 12px 20px; }
            .grid { grid-template-columns: 1fr; }
        }

        /* PRINT STYLES FOR PDF */
        @media print {
            .sidebar, .btn-pdf, .nav-links { display: none !important; }
            .main-content { padding: 0; width: 100%; }
            .grid { display: block; }
            .card { break-inside: avoid; border: 1px solid #ccc; margin-bottom: 20px; box-shadow: none; }
            body { background: white; color: black; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header" style="padding: 25px; font-weight: bold; font-size: 1.2rem;">✨ Admin Metrics</div>
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-arrow-left"></i> <span>Dashboard</span></a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1>Salon Analytics & Metrics</h1>
                <p>Detailed performance comparison and insights.</p>
            </div>
            <button onclick="window.print()" class="btn-pdf">
                <i class="fas fa-file-pdf"></i> Download PDF Report
            </button>
        </div>

        <div class="grid">
            <div class="card">
                <h3>This Month vs Last Month (Appointments)</h3>
                <div class="chart-h"><canvas id="monthCompChart"></canvas></div>
            </div>
            <div class="card">
                <h3>Revenue Comparison (KES)</h3>
                <div class="chart-h"><canvas id="revCompChart"></canvas></div>
            </div>
        </div>

        <div class="grid" style="margin-top: 25px;">
            <div class="card">
                <h3>Customer Registration Growth</h3>
                <div class="chart-h"><canvas id="growthChart"></canvas></div>
            </div>
            <div class="card">
                <h3>Peak Booking Hours</h3>
                <div class="chart-h"><canvas id="hourChart"></canvas></div>
            </div>
        </div>

        <div class="grid" style="margin-top: 25px;">
            <div class="card">
                <h3>Service Profitability</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Service</th><th>Bookings</th><th>Revenue</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($service_metrics as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['service_name']) ?></td>
                                <td><?= $m['total_bookings'] ?></td>
                                <td><strong>KES <?= number_format($m['total_revenue'], 0) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Staff Performance Breakdown</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Staff Member</th><th>Jobs Done</th><th>Revenue Generated</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($staff_metrics as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['staff_name']) ?></td>
                                <td><?= $s['total_jobs'] ?></td>
                                <td><strong>KES <?= number_format($s['revenue_generated'], 0) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const isDark = localStorage.getItem('admin-theme') === 'dark';
        if (isDark) {
            Chart.defaults.color = '#adb5bd';
            Chart.defaults.borderColor = '#373b3e';
            document.body.classList.add('dark-mode');
        }

        new Chart(document.getElementById('monthCompChart'), {
            type: 'doughnut',
            data: {
                labels: ['Last Month', 'This Month'],
                datasets: [{
                    data: [<?= (int)$prev_month['total_appts'] ?>, <?= (int)$curr_month['total_appts'] ?>],
                    backgroundColor: ['#adb5bd', '#4caf50'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('revCompChart'), {
            type: 'bar',
            data: {
                labels: ['Last Month', 'This Month'],
                datasets: [{
                    label: 'Revenue (KES)',
                    data: [<?= (float)$prev_month['total_rev'] ?>, <?= (float)$curr_month['total_rev'] ?>],
                    backgroundColor: ['#6c757d', '#2196f3'],
                    borderRadius: 8
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('growthChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($growth_data, 'month')) ?>,
                datasets: [{ label: 'New Customers', data: <?= json_encode(array_column($growth_data, 'count')) ?>, backgroundColor: '#4caf50', borderRadius: 5 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('hourChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($h){ return $h['hr'].":00"; }, $hour_metrics)) ?>,
                datasets: [{ label: 'Appointments', data: <?= json_encode(array_column($hour_metrics, 'count')) ?>, borderColor: '#2196f3', borderWidth: 3, tension: 0.4, fill: true, backgroundColor: 'rgba(33, 150, 243, 0.1)', pointRadius: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>