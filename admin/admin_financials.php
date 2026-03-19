<?php
// elvis_salon/admin/admin_financials.php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

$admin_image = "../assets/images/default_profile.png"; 

// --- DATE FILTER LOGIC ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- FETCH FINANCIAL DATA ---

// 1. Total Revenue
$stmt_total = $pdo->prepare("
    SELECT SUM(sv.price_kes) as total_revenue, COUNT(a.appointment_id) as total_appointments
    FROM appointments a
    JOIN services sv ON a.service_id = sv.service_id
    WHERE a.status = 'Completed' AND DATE(a.appointment_date) BETWEEN ? AND ?
");
$stmt_total->execute([$start_date, $end_date]);
$summary = $stmt_total->fetch(PDO::FETCH_ASSOC);
$total_revenue = $summary['total_revenue'] ?: 0;
$total_completed = $summary['total_appointments'] ?: 0;

// 2. Revenue Trend
$stmt_trend = $pdo->prepare("
    SELECT DATE(a.appointment_date) as income_date, SUM(sv.price_kes) as daily_total
    FROM appointments a
    JOIN services sv ON a.service_id = sv.service_id
    WHERE a.status = 'Completed' AND DATE(a.appointment_date) BETWEEN ? AND ?
    GROUP BY DATE(a.appointment_date)
    ORDER BY DATE(a.appointment_date) ASC
");
$stmt_trend->execute([$start_date, $end_date]);
$trend_data = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);
$trend_labels = array_column($trend_data, 'income_date');
$trend_values = array_column($trend_data, 'daily_total');

// 3. Revenue by Service
$stmt_services = $pdo->prepare("
    SELECT sv.service_name, SUM(sv.price_kes) as service_revenue
    FROM appointments a
    JOIN services sv ON a.service_id = sv.service_id
    WHERE a.status = 'Completed' AND DATE(a.appointment_date) BETWEEN ? AND ?
    GROUP BY sv.service_id
    ORDER BY service_revenue DESC
");
$stmt_services->execute([$start_date, $end_date]);
$service_data = $stmt_services->fetchAll(PDO::FETCH_ASSOC);
$service_labels = array_column($service_data, 'service_name');
$service_values = array_column($service_data, 'service_revenue');

// 4. Detailed Transaction Log
$stmt_transactions = $pdo->prepare("
    SELECT a.appointment_date, a.appointment_time, u.full_name as client_name, s.full_name as staff_name, sv.service_name, sv.price_kes
    FROM appointments a
    JOIN users u ON a.customer_id = u.user_id
    JOIN users s ON a.staff_id = s.user_id
    JOIN services sv ON a.service_id = sv.service_id
    WHERE a.status = 'Completed' AND DATE(a.appointment_date) BETWEEN ? AND ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt_transactions->execute([$start_date, $end_date]);
$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financials - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f4f6f9;
            --sidebar-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --accent: #4caf50;
            --primary: #0d6efd;
        }

        body.dark-mode {
            --bg-color: #121416;
            --sidebar-bg: #1a1d20;
            --card-bg: #212529;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }
        body { display: flex; height: 100vh; background-color: var(--bg-color); color: var(--text-main); overflow: hidden; }

        .sidebar { width: 250px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px; border-bottom: 1px solid var(--border-color); }
        .nav-links { padding: 15px; }
        .menu-item { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: var(--text-main); border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background: rgba(76, 175, 80, 0.1); color: var(--accent); font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-muted); }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        
        .dashboard-padding { padding: 25px; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Summary Cards */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .summary-card { background: var(--card-bg); border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; text-align: center; }
        .summary-card h3 { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px; }
        .summary-card .val { font-size: 1.8rem; font-weight: bold; }

        /* Filter Form */
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; padding: 15px; margin-bottom: 25px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.8rem; font-weight: 600; margin-bottom: 5px; color: var(--text-muted); }
        .form-group input { padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-color); color: var(--text-main); }
        .btn-filter { padding: 8px 20px; background: var(--accent); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }

        /* Charts */
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 25px; }
        .chart-container { position: relative; height: 280px; width: 100%; }

        /* Table */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        th { color: var(--text-muted); font-weight: 600; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .brand, .menu-item span { display: none; }
            .menu-item i { margin-right: 0; font-size: 1.2rem; }
            .header { padding: 10px 15px; }
            .header h2 { font-size: 1rem; }
            .dashboard-padding { padding: 15px; }
            .filter-form { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">✨ Admin</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_financials.php" class="menu-item active"><i class="fas fa-chart-line"></i> <span>Financials</span></a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> <span>Clients</span></a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Financial Reports</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="../logout.php" style="text-decoration:none; color:var(--text-main); font-size:14px; border:1px solid var(--border-color); padding:5px 10px; border-radius:4px;">Logout</a>
                <img src="<?= $admin_image ?>" style="width:35px; height:35px; border-radius:50%; object-fit:cover; border:2px solid var(--accent);">
            </div>
        </div>

        <div class="dashboard-padding">
            
            <form method="GET" action="admin_financials.php" class="filter-form">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <button type="submit" class="btn-filter">Generate Report</button>
            </form>

            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Total Revenue</h3>
                    <div class="val" style="color: var(--accent);">KES <?= number_format($total_revenue, 2) ?></div>
                </div>
                <div class="summary-card">
                    <h3>Services Completed</h3>
                    <div class="val"><?= $total_completed ?></div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="card">
                    <strong>Income Trend</strong>
                    <div class="chart-container"><canvas id="trendChart"></canvas></div>
                </div>
                <div class="card">
                    <strong>Revenue by Service</strong>
                    <div class="chart-container"><canvas id="serviceChart"></canvas></div>
                </div>
            </div>

            <div class="card">
                <strong>Detailed Transaction Log</strong>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Client</th>
                                <th>Staff</th>
                                <th>Service</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($txn['appointment_date'])) ?> <small><?= date('h:i A', strtotime($txn['appointment_time'])) ?></small></td>
                                    <td><?= htmlspecialchars($txn['client_name']) ?></td>
                                    <td><?= htmlspecialchars($txn['staff_name']) ?></td>
                                    <td><?= htmlspecialchars($txn['service_name']) ?></td>
                                    <td style="font-weight: bold; color: var(--accent);">KES <?= number_format($txn['price_kes'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No transactions found for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Apply Global Theme
        const savedTheme = localStorage.getItem('admin-theme');
        const isDark = savedTheme === 'dark';
        if (isDark) document.body.classList.add('dark-mode');

        const labelColor = isDark ? '#adb5bd' : '#6c757d';

        // 1. Trend Line Chart
        const trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode(empty($trend_labels) ? ['No Data'] : $trend_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(empty($trend_values) ? [0] : $trend_values) ?>,
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: labelColor } },
                    x: { ticks: { color: labelColor }, grid: { display: false } }
                }
            }
        });

        // 2. Service Doughnut Chart
        new Chart(document.getElementById('serviceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(empty($service_labels) ? ['No Data'] : $service_labels) ?>,
                datasets: [{
                    data: <?= json_encode(empty($service_values) ? [1] : $service_values) ?>,
                    backgroundColor: ['#4caf50', '#0d6efd', '#ffc107', '#dc3545', '#0dcaf0', '#6f42c1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: labelColor } } }
            }
        });
    </script>
</body>
</html>