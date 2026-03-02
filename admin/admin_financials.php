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
// Default to the current month if no dates are selected
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- FETCH FINANCIAL DATA ---

// 1. Total Revenue in the selected period
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

// 2. Revenue Trend (Daily income over the selected period)
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

// 3. Revenue by Service (Which services make the most money)
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
        :root { --sidebar-bg: #f8f9fa; --content-bg: #ffffff; --text-dark: #333; --text-light: #6c757d; --border-color: #dee2e6; --primary-color: #0d6efd;}
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f4f6f9; }
        
        .sidebar { width: 250px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; }
        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-color); margin-bottom: 15px; }
        .nav-links { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: var(--text-dark); border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem; }
        .menu-item:hover { background-color: #e9ecef; }
        .menu-item.active { background-color: #e2e3e5; font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-light); }
        .menu-item.active i { color: var(--text-dark); }
        
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: #fff; border-bottom: 1px solid var(--border-color); }
        .header h2 { font-size: 1.4rem; color: var(--text-dark); }
        .logout-btn { padding: 8px 16px; border: 1px solid var(--border-color); background: #fff; border-radius: 4px; text-decoration: none; color: var(--text-dark); }
        .profile-img-container { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #ccc; border: 1px solid #aaa; }
        .profile-img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        .dashboard-padding { padding: 30px; }
        .card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 25px; margin-bottom: 30px; }
        .card-header { font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: var(--text-dark); }
        
        /* Summary Blocks */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; text-align: center; }
        .summary-card h3 { color: var(--text-light); font-size: 1rem; margin-bottom: 10px; }
        .summary-card .val { font-size: 2rem; font-weight: bold; color: var(--text-dark); }
        .val.kes { color: #198754; }
        
        /* Filter Form */
        .filter-form { display: flex; gap: 15px; align-items: flex-end; margin-bottom: 25px; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.9rem; font-weight: 600; margin-bottom: 5px; }
        .form-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-filter { padding: 8px 20px; background: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        
        /* Charts Grid */
        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px; }
        .chart-container { position: relative; height: 300px; width: 100%; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        th { color: var(--text-light); font-weight: 600; border-bottom: 2px solid var(--border-color); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Admin Dashboard</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> All Appointments</a>
            <a href="admin_financials.php" class="menu-item active"><i class="fas fa-chart-line"></i> Financials</a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> Staff</a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> Clients</a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Financial Reports</h2>
            <div class="header-actions" style="display:flex; gap:20px; align-items:center;">
                <a href="../logout.php" class="logout-btn">Logout</a>
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Admin">
                </div>
            </div>
        </div>

        <div class="dashboard-padding">
            
            <form method="GET" action="admin_financials.php" class="filter-form">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <button type="submit" class="btn-filter">Generate Report</button>
            </form>

            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Total Revenue Generated</h3>
                    <div class="val kes">KES <?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Completed Appointments</h3>
                    <div class="val"><?php echo $total_completed; ?></div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">Income Trend (Selected Period)</div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">Revenue by Service</div>
                    <div class="chart-container">
                        <canvas id="serviceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Detailed Transactions (Completed Only)</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Client</th>
                                <th>Staff Member</th>
                                <th>Service Provided</th>
                                <th>Amount Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($txn['appointment_date'])) . ' at ' . date('h:i A', strtotime($txn['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['staff_name']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['service_name']); ?></td>
                                    <td style="font-weight: bold;">KES <?php echo number_format($txn['price_kes'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No completed transactions found for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        // --- Chart Configs ---
        
        // 1. Trend Line Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(empty($trend_labels) ? ['No Data'] : $trend_labels); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode(empty($trend_values) ? [0] : $trend_values); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: value => 'KES ' + value } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Service Breakdown Doughnut Chart
        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(empty($service_labels) ? ['No Data'] : $service_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode(empty($service_values) ? [1] : $service_values); ?>,
                    backgroundColor: [
                        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6f42c1', '#fd7e14'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>