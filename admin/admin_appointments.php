<?php
// elvis_salon/admin/admin_appointments.php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

$admin_image = "../assets/images/default_profile.png"; 

// Fetch ALL Appointments
$sql_all_appointments = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, 
           u.full_name as client_name, s.full_name as staff_name, sv.service_name 
    FROM appointments a 
    JOIN users u ON a.customer_id = u.user_id 
    JOIN users s ON a.staff_id = s.user_id
    JOIN services sv ON a.service_id = sv.service_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";
$stmt_all = $pdo->query($sql_all_appointments);
$all_appointments = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Exact same styling variables as your dashboard */
        :root {
            --sidebar-bg: #f8f9fa; --content-bg: #ffffff; --text-dark: #333;
            --text-light: #6c757d; --border-color: #dee2e6; --danger-color: #dc3545; --danger-bg: #f8d7da;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f4f6f9; }

        /* Sidebar & Header Styles */
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
        .logout-btn { padding: 8px 16px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; border-radius: 4px; font-weight: 500; text-decoration: none; color: var(--text-dark); }
        .profile-img-container { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #ccc; border: 1px solid #aaa; }
        .profile-img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Table Styles */
        .dashboard-padding { padding: 30px; }
        .card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 25px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        th { color: var(--text-light); font-weight: 600; border-bottom: 2px solid var(--border-color); }
        .btn-cancel { padding: 6px 12px; background-color: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
        .btn-cancel:hover { background-color: var(--danger-color); color: #fff; }
        
        /* Status Badges */
        .status { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status.booked { background: #e0f2fe; color: #0284c7; }
        .status.completed { background: #dcfce7; color: #16a34a; }
        .status.cancelled { background: #fee2e2; color: #ef4444; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Admin Dashboard</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
            <a href="admin_appointments.php" class="menu-item active"><i class="far fa-calendar-alt"></i> All Appointments</a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> Financials</a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> Staff</a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> Clients</a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>All Appointments</h2>
            <div class="header-actions" style="display:flex; gap:20px; align-items:center;">
                <a href="../logout.php" class="logout-btn">Logout</a>
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Admin">
                </div>
            </div>
        </div>

        <div class="dashboard-padding">
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Staff</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_appointments) > 0): ?>
                            <?php foreach ($all_appointments as $row): ?>
                            <tr>
                                <td><?php echo date('M d, Y - h:i A', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] !== 'Cancelled' && $row['status'] !== 'Completed'): ?>
                                        <button class="btn-cancel" onclick="cancelAppointment(<?php echo $row['appointment_id']; ?>)">Cancel X</button>
                                    <?php else: ?>
                                        <span style="color: var(--text-light); font-size: 0.85rem;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No appointments found in the system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function cancelAppointment(id) {
            if (confirm("Are you sure you want to cancel this appointment?")) {
                fetch('../api/cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'appointment_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert("Appointment cancelled successfully.");
                        location.reload(); // Refresh to update the table status
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>