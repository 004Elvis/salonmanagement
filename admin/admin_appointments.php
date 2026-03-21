<?php
// elvis_salon/admin/admin_appointments.php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

// --- STREAMLINED UPDATE LOGIC ---
// Handling completion and cancellation via POST to avoid "Endpoint Errors"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appt_id = $_POST['appointment_id'];
    $action = $_POST['action'];
    $new_status = ($action === 'complete') ? 'Completed' : 'Cancelled';

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    if ($stmt->execute([$new_status, $appt_id])) {
        header("Location: admin_appointments.php?success=1");
        exit();
    }
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
        :root {
            --sidebar-bg: #f8f9fa;
            --main-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --danger-color: #dc3545;
            --danger-bg: #f8d7da;
            --success-color: #198754;
            --success-bg: #d1e7dd;
            --accent: #4caf50;
        }

        body.dark-mode {
            --sidebar-bg: #1a1d20;
            --main-bg: #121416;
            --card-bg: #212529;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
            --danger-bg: rgba(220, 53, 69, 0.2);
            --success-bg: rgba(25, 135, 84, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; transition: background 0.3s, color 0.3s; }
        body { display: flex; height: 100vh; background-color: var(--main-bg); color: var(--text-main); }

        .sidebar { width: 250px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 15px; }
        .nav-links { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: var(--text-main); border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background-color: rgba(76, 175, 80, 0.1); color: var(--accent); font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-muted); }
        
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .header h2 { font-size: 1.4rem; }
        
        .dashboard-padding { padding: 20px; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Search Bar Styles */
        .search-container { margin-bottom: 20px; position: relative; max-width: 400px; }
        .search-container input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--main-bg); color: var(--text-main); outline: none; }
        .search-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }

        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        th { color: var(--text-muted); font-weight: 600; background: var(--card-bg); }
        
        .btn-action { padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-weight: 600; border: 1px solid transparent; background: none; }
        .btn-cancel { background-color: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color); }
        .btn-complete { background-color: var(--success-bg); color: var(--success-color); border-color: var(--success-color); }
        .btn-receipt { background-color: var(--border-color); color: var(--text-main); border-color: var(--text-muted); }

        .status { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; }
        .status.booked, .status.confirmed, .status.pending { background: #e0f2fe; color: #0284c7; }
        .status.completed { background: var(--success-bg); color: var(--success-color); }
        .status.cancelled { background: var(--danger-bg); color: var(--danger-color); }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .brand, .menu-item span { display: none; }
            .menu-item i { margin-right: 0; font-size: 1.2rem; }
            .header { padding: 10px 15px; }
            .header h2 { font-size: 1.1rem; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">✨ Admin</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="admin_appointments.php" class="menu-item active"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Financials</span></a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> <span>Clients</span></a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>All Appointments</h2>
            <div class="header-actions" style="display:flex; gap:15px; align-items:center;">
                <a href="../logout.php" class="btn-action" style="border:1px solid var(--border-color); color:var(--text-main);">Logout</a>
                <div class="profile-img-container" style="width:35px; height:35px; border-radius:50%; overflow:hidden; border:1px solid var(--accent);">
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Admin" style="width:100%; height:100%; object-fit:cover;">
                </div>
            </div>
        </div>

        <div class="dashboard-padding">
            <div class="card">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="appointmentSearch" placeholder="Search staff, service, or client..." onkeyup="filterAppointments()">
                </div>

                <div class="table-responsive">
                    <table id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Staff</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($all_appointments) > 0): ?>
                                <?php foreach ($all_appointments as $row): ?>
                                <?php $status_low = strtolower($row['status']); ?>
                                <tr class="appointment-row">
                                    <td><strong><?php echo date('M d', strtotime($row['appointment_date'])); ?></strong><br><small><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></small></td>
                                    <td class="client-name"><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td class="service-name"><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td class="staff-name"><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                    <td>
                                        <span class="status <?php echo $status_low; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <?php if ($status_low === 'confirmed' || $status_low === 'pending'): ?>
                                                <form method="POST" onsubmit="return confirm('Mark as Completed?')">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="complete">
                                                    <button type="submit" class="btn-action btn-complete">
                                                        <i class="fas fa-check"></i> Complete
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" onsubmit="return confirm('Cancel this appointment?')">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="btn-action btn-cancel">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php elseif ($status_low === 'completed'): ?>
                                                <a href="view_receipt.php?id=<?php echo $row['appointment_id']; ?>" class="btn-action btn-receipt">
                                                    <i class="fas fa-file-invoice"></i> Receipt
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.8rem;">No Actions</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6">No appointments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const savedTheme = localStorage.getItem('admin-theme');
        if (savedTheme === 'dark') { document.body.classList.add('dark-mode'); }

        // Live Search Filter Function
        function filterAppointments() {
            const input = document.getElementById('appointmentSearch');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.appointment-row');

            rows.forEach(row => {
                const client = row.querySelector('.client-name').textContent.toLowerCase();
                const service = row.querySelector('.service-name').textContent.toLowerCase();
                const staff = row.querySelector('.staff-name').textContent.toLowerCase();

                if (client.includes(filter) || service.includes(filter) || staff.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>