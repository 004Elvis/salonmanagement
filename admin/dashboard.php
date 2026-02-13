<?php
require '../config/db.php';
// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); exit;
}

// Fetch Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$todaysAppointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard (Owner: Elvis Midega)</h1>
        <div style="display: flex; gap: 20px;">
            <div class="card" style="flex: 1;"><h3>Total Users</h3><p><?php echo $totalUsers; ?></p></div>
            <div class="card" style="flex: 1;"><h3>Today's Appts</h3><p><?php echo $todaysAppointments; ?></p></div>
            <div class="card" style="flex: 1;"><h3>Actions</h3>
                <a href="../logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>

        <div class="card">
            <h2>Upcoming Appointments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Staff</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT a.*, u.full_name as client, s.full_name as staff, sv.service_name 
                            FROM appointments a 
                            JOIN users u ON a.customer_id = u.user_id 
                            JOIN users s ON a.staff_id = s.user_id
                            JOIN services sv ON a.service_id = sv.service_id
                            ORDER BY a.appointment_date DESC LIMIT 10";
                    $stmt = $pdo->query($sql);
                    while($row = $stmt->fetch()): ?>
                    <tr>
                        <td><?php echo $row['appointment_date'] . ' ' . $row['appointment_time']; ?></td>
                        <td><?php echo $row['client']; ?></td>
                        <td><?php echo $row['staff']; ?></td>
                        <td><?php echo $row['service_name']; ?></td>
                        <td><?php echo $row['status']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>