<?php
// admin/view_logs.php
session_start();
require '../config/db.php';

// 1. Security Check: Only Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

$admin_image = "../assets/images/default_profile.png"; 

// 2. Fetch the latest 50 logs
try {
    $stmt = $pdo->query("SELECT * FROM api_logs ORDER BY called_at DESC LIMIT 50");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs - Elvis Salon</title>
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

        /* Sidebar */
        .sidebar { width: 250px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px; border-bottom: 1px solid var(--border-color); }
        .nav-links { padding: 15px; }
        .menu-item { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: var(--text-main); border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background: rgba(76, 175, 80, 0.1); color: var(--accent); font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-muted); }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        
        .dashboard-padding { padding: 25px; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Table & Badges */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); font-weight: 600; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-endpoint { background: #e0f2fe; color: #0284c7; }
        .role-admin { background: #fee2e2; color: #dc2626; }
        .role-staff { background: #fef3c7; color: #d97706; }
        .role-client { background: #dcfce7; color: #16a34a; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .brand, .menu-item span { display: none; }
            .menu-item i { margin-right: 0; font-size: 1.2rem; }
            .header { padding: 10px 15px; }
            .header h2 { font-size: 1rem; }
            .dashboard-padding { padding: 15px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">✨ Admin</div>
        <div class="nav-links">
            <a href="view_logs.php" class="menu-item active"><i class="fas fa-shield-alt"></i> <span>Audit Logs</span></a>
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Financials</span></a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> <span>Clients</span></a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>System Security Logs</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="../logout.php" style="text-decoration:none; color:var(--text-main); font-size:14px; border:1px solid var(--border-color); padding:5px 10px; border-radius:4px;">Logout</a>
            </div>
        </div>

        <div class="dashboard-padding">
            <div class="card">
                <p style="color: var(--text-muted); margin-bottom: 20px;">Tracking real-time activity across all API endpoints.</p>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Action / Endpoint</th>
                                <th>User ID</th>
                                <th>Role</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 20px;">No activity recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                        $role = $log['user_role'];
                                        $roleClass = 'role-' . strtolower($role);
                                    ?>
                                    <tr>
                                        <td>#<?= $log['id'] ?></td>
                                        <td><span class="badge badge-endpoint"><?= htmlspecialchars($log['endpoint_name']) ?></span></td>
                                        <td><?= $log['user_id'] ?: '<span style="color:var(--text-muted)">N/A</span>' ?></td>
                                        <td><span class="badge <?= $roleClass ?>"><?= $role ?></span></td>
                                        <td><?= date('M d, Y - h:i A', strtotime($log['called_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Apply Global Theme
        if (localStorage.getItem('admin-theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>