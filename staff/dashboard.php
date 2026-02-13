<?php
// staff/dashboard.php

require '../config/db.php';
require '../includes/auth_check.php';

// 1. Security: Ensure only Staff can access this page
checkRole(['Staff']); 

// Get the current logged-in staff's ID
$staff_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- HANDLE STATUS UPDATES (Quick Actions) ---
if (isset($_POST['update_status'])) {
    $appt_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    
    // Security: Ensure this appointment actually belongs to this staff member
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND staff_id = ?");
    $stmt->execute([$new_status, $appt_id, $staff_id]);
    
    // Refresh to show changes
    header("Location: dashboard.php?success=Status updated to $new_status");
    exit();
}

// --- FETCH DATA FOR DASHBOARD ---

// A. Get Today's Appointments Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE staff_id = ? AND appointment_date = ?");
$stmt->execute([$staff_id, $today]);
$today_count = $stmt->fetchColumn();

// B. Calculate Total Revenue (Completed Appointments Only)
// Joins with 'services' table to sum the price
$stmt = $pdo->prepare("
    SELECT SUM(s.price_kes) 
    FROM appointments a 
    JOIN services s ON a.service_id = s.service_id 
    WHERE a.staff_id = ? AND a.status = 'Completed'
");
$stmt->execute([$staff_id]);
$total_revenue = $stmt->fetchColumn() ?: 0.00; // Default to 0 if null

// C. Fetch Upcoming Schedule (Date >= Today)
$sql = "
    SELECT a.*, u.full_name AS client_name, u.phone, s.service_name, s.duration_minutes, s.price_kes
    FROM appointments a
    JOIN users u ON a.customer_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.staff_id = ? AND a.appointment_date >= ?
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$staff_id, $today]);
$appointments = $stmt->fetchAll();

// --- START VIEW ---
require '../includes/header.php'; 
?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>👋 Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
    <div>
        <a href="availability.php" class="btn" style="background: #555; color: white;">Manage My Availability</a>
    </div>
</div>

<div class="stats-grid" style="display: flex; gap: 20px; margin-bottom: 30px;">
    <div class="card" style="flex: 1; text-align: center; border-left: 5px solid var(--primary);">
        <h3>Appointments Today</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 10px 0;"><?php echo $today_count; ?></p>
        <small><?php echo date('l, d M Y'); ?></small>
    </div>

    <div class="card" style="flex: 1; text-align: center; border-left: 5px solid #27ae60;">
        <h3>My Total Earnings</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 10px 0; color: #27ae60;">
            KES <?php echo number_format($total_revenue, 2); ?>
        </p>
        <small>From completed services</small>
    </div>
</div>

<div class="card">
    <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">My Schedule</h2>

    <?php if (count($appointments) > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th>Date & Time</th>
                        <th>Client Details</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('M d', strtotime($appt['appointment_date'])); ?></strong><br>
                                <span style="color: #666;"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($appt['client_name']); ?><br>
                                <small>📞 <?php echo htmlspecialchars($appt['phone']); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($appt['service_name']); ?><br>
                                <small>KES <?php echo number_format($appt['price_kes']); ?> • <?php echo $appt['duration_minutes']; ?> mins</small>
                            </td>

                            <td>
                                <?php 
                                    $statusColor = 'gray';
                                    if($appt['status'] == 'Confirmed') $statusColor = 'blue';
                                    if($appt['status'] == 'Completed') $statusColor = 'green';
                                    if($appt['status'] == 'Cancelled') $statusColor = 'red';
                                ?>
                                <span style="padding: 5px 10px; background: <?php echo $statusColor; ?>; color: white; border-radius: 15px; font-size: 0.8rem;">
                                    <?php echo $appt['status']; ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($appt['status'] != 'Completed' && $appt['status'] != 'Cancelled'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        
                                        <button type="submit" name="update_status" value="Completed" class="btn" style="background: #27ae60; color: white; padding: 5px 10px; font-size: 0.8rem;" title="Mark as Done">
                                            ✔ Done
                                        </button>
                                        
                                        <button type="submit" name="update_status" value="Cancelled" class="btn" style="background: #c0392b; color: white; padding: 5px 10px; font-size: 0.8rem;" title="Cancel Appointment" onclick="return confirm('Are you sure you want to cancel this?');">
                                            ✖ Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #aaa;">Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; padding: 20px; color: #666;">You have no upcoming appointments.</p>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>