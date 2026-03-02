<?php
require '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] !== 'Customer') { header("Location: ../index.php"); exit; }

// Fetch Dropdown Data
$staff = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 2")->fetchAll();
$services = $pdo->query("SELECT * FROM services")->fetchAll();

// --- NEW: Check if we are in "Reschedule Mode" ---
$is_rescheduling = false;
$appt_to_edit = null;

if (isset($_GET['reschedule_id'])) {
    $is_rescheduling = true;
    $reschedule_id = $_GET['reschedule_id'];
    
    // Fetch the existing appointment details to pre-fill the form (optional but helpful)
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND customer_id = ?");
    $stmt->execute([$reschedule_id, $_SESSION['user_id']]);
    $appt_to_edit = $stmt->fetch();
    
    // If they tried to edit an appointment that doesn't exist or isn't theirs, kick them back
    if (!$appt_to_edit) {
        header("Location: dashboard.php?error=Invalid_Appointment");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title><?= $is_rescheduling ? 'Reschedule Appointment' : 'Book Appointment' ?></title>
    <style>
        .action-buttons { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .btn-danger { background: #dc3545; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; text-align: center; }
        .btn-danger:hover { background: #c82333; }
        .btn-primary { flex: 1; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        
        <div class="login-card">
            <h2 style="margin-bottom: 20px;">
                <?= $is_rescheduling ? '🔄 Reschedule Appointment' : '✨ Book an Appointment' ?>
            </h2>
            
            <form action="<?= $is_rescheduling ? '../actions/reschedule_action.php' : '../actions/book_action.php' ?>" method="POST">
                
                <?php if ($is_rescheduling): ?>
                    <input type="hidden" name="appointment_id" value="<?= $appt_to_edit['appointment_id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Select Service</label>
                    <select name="service_id" required>
                        <option value="" disabled <?= !$is_rescheduling ? 'selected' : '' ?>>Choose a service...</option>
                        <?php foreach($services as $s): ?>
                            <option value="<?= $s['service_id'] ?>" 
                                <?= ($is_rescheduling && $appt_to_edit['service_id'] == $s['service_id']) ? 'selected' : '' ?>>
                                <?= $s['service_name'] ?> - KES <?= $s['price_kes'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Choose Beautician</label>
                    <select name="staff_id" id="staff_id" required>
                        <option value="" disabled <?= !$is_rescheduling ? 'selected' : '' ?>>Choose a staff member...</option>
                        <?php foreach($staff as $st): ?>
                            <option value="<?= $st['user_id'] ?>"
                                <?= ($is_rescheduling && $appt_to_edit['staff_id'] == $st['user_id']) ? 'selected' : '' ?>>
                                <?= $st['full_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" id="date" min="<?= date('Y-m-d') ?>" 
                           value="<?= $is_rescheduling ? $appt_to_edit['appointment_date'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Available Times</label>
                    <select name="time" id="time" required>
                        <option value="">
                            <?= $is_rescheduling ? "Current time: " . date('h:i A', strtotime($appt_to_edit['appointment_time'])) . " (Select a date to change)" : "Select a beautician and date first..." ?>
                        </option>
                    </select>
                </div>

                <div class="action-buttons">
                    <?php if ($is_rescheduling): ?>
                        <button type="submit" class="btn btn-primary">Update Appointment</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary">Confirm Booking</button>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 15px; text-align: center;">
                    <a href="dashboard.php" style="color: #666; text-decoration: none;">← Back to Dashboard</a>
                </div>
                
            </form>

            <?php if ($is_rescheduling): ?>
            <form action="../actions/cancel_action.php" method="POST" style="margin-top: 15px;" onsubmit="return confirm('Are you sure you want to completely cancel this appointment? This cannot be undone.');">
                <input type="hidden" name="appointment_id" value="<?= $appt_to_edit['appointment_id'] ?>">
                <button type="submit" class="btn btn-danger" style="width: 100%;">✕ Cancel Entire Appointment</button>
            </form>
            <?php endif; ?>

        </div>
    </div>

    <script>
        const staffSelect = document.getElementById('staff_id');
        const dateInput = document.getElementById('date');
        const timeSelect = document.getElementById('time');
        
        function fetchAvailableTimes() {
            const staff_id = staffSelect.value;
            const date_val = dateInput.value;
            
            if (staff_id && date_val) {
                timeSelect.innerHTML = '<option value="">Searching schedule...</option>';
                
                fetch(`get_times.php?staff_id=${staff_id}&date=${date_val}`)
                    .then(response => response.text())
                    .then(htmlData => {
                        timeSelect.innerHTML = htmlData;
                    })
                    .catch(error => {
                        timeSelect.innerHTML = '<option value="">Error loading times</option>';
                    });
            } else {
                timeSelect.innerHTML = '<option value="">Select a beautician and date first...</option>';
            }
        }
        
        staffSelect.addEventListener('change', fetchAvailableTimes);
        dateInput.addEventListener('change', fetchAvailableTimes);

        // If the form loads with data pre-filled (reschedule mode), fetch times immediately!
        if (staffSelect.value && dateInput.value) {
            fetchAvailableTimes();
        }
    </script>
</body>
</html>