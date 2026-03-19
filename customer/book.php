<?php
require '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] !== 'Customer') { header("Location: ../index.php"); exit; }

// Fetch Dropdown Data
$staff = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 2")->fetchAll();
$services = $pdo->query("SELECT * FROM services")->fetchAll();

// --- Reschedule Mode logic ---
$is_rescheduling = false;
$appt_to_edit = null;

if (isset($_GET['reschedule_id'])) {
    $is_rescheduling = true;
    $reschedule_id = $_GET['reschedule_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND customer_id = ?");
    $stmt->execute([$reschedule_id, $_SESSION['user_id']]);
    $appt_to_edit = $stmt->fetch();
    
    if (!$appt_to_edit) {
        header("Location: dashboard.php?error=Invalid_Appointment");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <title><?= $is_rescheduling ? 'Reschedule Appointment' : 'Book Appointment' ?></title>
    <style>
        :root {
            --body-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --accent: #4caf50;
            --danger: #dc3545;
        }

        body.dark-mode {
            --body-bg: #121416;
            --card-bg: #212529;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
        }

        * { transition: background 0.3s, color 0.3s; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-color: var(--body-bg); 
            color: var(--text-main); 
            margin: 0; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container { width: 100%; max-width: 500px; padding: 20px; }
        .login-card { 
            background: var(--card-bg); 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            padding: 30px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }

        h2 { font-size: 1.5rem; color: var(--text-main); text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: var(--text-main); }
        
        .form-group select, .form-group input { 
            width: 100%; 
            padding: 12px; 
            border-radius: 6px; 
            border: 1px solid var(--border-color); 
            background: var(--card-bg); 
            color: var(--text-main);
            font-size: 1rem;
        }

        /* --- CALENDAR VISIBILITY FIXES (FORCED BLACK TEXT) --- */
        .flatpickr-calendar {
            z-index: 9999 !important;
            background: #ffffff !important; /* Force white background */
            border: 1px solid #ccc !important;
        }

        /* Force ALL text to be black regardless of mode */
        .flatpickr-day, 
        .flatpickr-weekday, 
        .cur-month, 
        .cur-year, 
        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            color: #000000 !important;
            fill: #000000 !important;
            font-weight: 600 !important;
        }

        /* Active/Selected day remains white text for contrast on the green circle */
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            color: #ffffff !important;
        }

        /* Disabled days stay light grey but readable */
        .flatpickr-day.flatpickr-disabled {
            color: #bbbbbb !important;
        }

        #date {
            cursor: pointer;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%234caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 18px;
        }

        .action-buttons { display: flex; gap: 10px; margin-top: 25px; }
        .btn { padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; text-align: center; font-size: 1rem; }
        .btn-primary { background: var(--accent); color: white; flex: 1; }
        .btn-danger { background: var(--danger); color: white; width: 100%; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2><?= $is_rescheduling ? '🔄 Reschedule Appointment' : '✨ Book a Service' ?></h2>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px;">Elvis Salon Nairobi</p>
            
            <form action="<?= $is_rescheduling ? '../actions/reschedule_action.php' : '../actions/book_action.php' ?>" method="POST">
                <?php if ($is_rescheduling): ?>
                    <input type="hidden" name="appointment_id" value="<?= $appt_to_edit['appointment_id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label><i class="fas fa-cut"></i> Select Service</label>
                    <select name="service_id" required>
                        <option value="" disabled <?= !$is_rescheduling ? 'selected' : '' ?>>Choose a service...</option>
                        <?php foreach($services as $s): ?>
                            <option value="<?= $s['service_id'] ?>" <?= ($is_rescheduling && $appt_to_edit['service_id'] == $s['service_id']) ? 'selected' : '' ?>>
                                <?= $s['service_name'] ?> - KES <?= number_format($s['price_kes']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user-tie"></i> Choose Beautician</label>
                    <select name="staff_id" id="staff_id" required>
                        <option value="" disabled <?= !$is_rescheduling ? 'selected' : '' ?>>Select a specialist...</option>
                        <?php foreach($staff as $st): ?>
                            <option value="<?= $st['user_id'] ?>" <?= ($is_rescheduling && $appt_to_edit['staff_id'] == $st['user_id']) ? 'selected' : '' ?>>
                                <?= $st['full_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Select Date (Calendar or Manual)</label>
                    <input type="text" name="date" id="date" placeholder="YYYY-MM-DD" value="<?= $is_rescheduling ? $appt_to_edit['appointment_date'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Available Times</label>
                    <select name="time" id="time" required>
                        <option value=""><?= $is_rescheduling ? "Current: " . date('h:i A', strtotime($appt_to_edit['appointment_time'])) : "Choose specialist and date..." ?></option>
                    </select>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary"><?= $is_rescheduling ? 'Update Appointment' : 'Confirm Booking' ?></button>
                </div>
                <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Dashboard</a>
            </form>

            <?php if ($is_rescheduling): ?>
                <form action="../actions/cancel_action.php" method="POST" style="margin-top: 15px;" onsubmit="return confirm('Completely cancel this appointment?');">
                    <input type="hidden" name="appointment_id" value="<?= $appt_to_edit['appointment_id'] ?>">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Cancel Appointment</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        if (localStorage.getItem('customer-theme') === 'dark' || localStorage.getItem('admin-theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        flatpickr("#date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            allowInput: true,
            disableMobile: "true",
            onChange: function(selectedDates, dateStr) {
                fetchAvailableTimes();
            }
        });

        const staffSelect = document.getElementById('staff_id');
        const dateInput = document.getElementById('date');
        const timeSelect = document.getElementById('time');
        
        function fetchAvailableTimes() {
            const staff_id = staffSelect.value;
            const date_val = dateInput.value;
            if (staff_id && date_val) {
                timeSelect.innerHTML = '<option value="">Searching...</option>';
                fetch(`get_times.php?staff_id=${staff_id}&date=${date_val}`)
                    .then(r => r.text())
                    .then(data => { timeSelect.innerHTML = data; })
                    .catch(() => { timeSelect.innerHTML = '<option>Error</option>'; });
            }
        }
        
        staffSelect.addEventListener('change', fetchAvailableTimes);
        dateInput.addEventListener('blur', fetchAvailableTimes);

        if (staffSelect.value && dateInput.value) { fetchAvailableTimes(); }
    </script>
</body>
</html>