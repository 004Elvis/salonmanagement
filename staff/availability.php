<?php
// staff/availability.php
require '../config/db.php';
require '../includes/auth_check.php';

// Security check
checkRole(['Staff', 'staff']);

$staff_id = $_SESSION['user_id'];
$message = '';

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction(); 
        
        foreach ($days_of_week as $day) {
            $is_working = isset($_POST['working'][$day]) ? 1 : 0;
            
            $start_time = !empty($_POST['start_time'][$day]) ? $_POST['start_time'][$day] : '09:00:00';
            $end_time = !empty($_POST['end_time'][$day]) ? $_POST['end_time'][$day] : '17:00:00';

            $stmt = $pdo->prepare("SELECT id FROM staff_availability WHERE staff_id = ? AND day_of_week = ?");
            $stmt->execute([$staff_id, $day]);
            
            if ($stmt->fetch()) {
                $upd = $pdo->prepare("UPDATE staff_availability SET is_working = ?, start_time = ?, end_time = ? WHERE staff_id = ? AND day_of_week = ?");
                $upd->execute([$is_working, $start_time, $end_time, $staff_id, $day]);
            } else {
                $ins = $pdo->prepare("INSERT INTO staff_availability (staff_id, day_of_week, is_working, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$staff_id, $day, $is_working, $start_time, $end_time]);
            }
        }
        
        $pdo->commit();
        $message = "Your weekly availability has been updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error saving schedule: " . $e->getMessage();
    }
}

// Fetch existing availability
$stmt = $pdo->prepare("SELECT * FROM staff_availability WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$existing_schedule = [];
while ($row = $stmt->fetch()) {
    $existing_schedule[$row['day_of_week']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - Elvis Salon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Shared Dashboard Theme Variables */
        :root {
            --bg-color: #f4f6f9;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --accent: #4caf50;
        }

        body.dark-mode {
            --bg-color: #121416;
            --card-bg: #212529;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
        }

        * { transition: background 0.3s, color 0.3s; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            margin: 0; 
            padding: 20px; 
            min-height: 100vh;
        }

        .container { 
            max-width: 700px; 
            margin: 20px auto; 
            background: var(--card-bg); 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }

        h2 { margin-top: 0; font-size: 1.5rem; border-left: 4px solid var(--accent); padding-left: 15px; margin-bottom: 25px; }

        .btn-back { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: var(--text-muted); text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: var(--accent); }

        .alert { padding: 15px; background: rgba(76, 175, 80, 0.1); color: var(--accent); border: 1px solid var(--accent); border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }

        /* Row Styling */
        .day-row { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 15px; 
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .day-label { 
            font-weight: bold; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            cursor: pointer; 
            min-width: 120px;
        }

        .time-inputs { display: flex; align-items: center; gap: 10px; }

        input[type="time"] { 
            padding: 8px; 
            border: 1px solid var(--border-color); 
            border-radius: 4px; 
            background: var(--bg-color); 
            color: var(--text-main); 
            font-family: inherit; 
        }

        input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent); }

        .btn-submit { 
            display: block; 
            width: 100%; 
            padding: 15px; 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 25px; 
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.2);
        }

        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Logic for inactive days */
        .not-working { background-color: rgba(0,0,0,0.02); border-style: dashed; }
        body.dark-mode .not-working { background-color: rgba(255,255,255,0.02); }
        .not-working .time-inputs { opacity: 0.3; pointer-events: none; }

        /* Responsiveness */
        @media (max-width: 600px) {
            .container { padding: 20px; }
            .day-row { flex-direction: column; align-items: flex-start; gap: 15px; }
            .time-inputs { width: 100%; justify-content: space-between; border-top: 1px solid var(--border-color); pt: 10px; padding-top: 10px; }
            input[type="time"] { flex: 1; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <h2><i class="far fa-calendar-check"></i> Manage Weekly Availability</h2>

        <?php if ($message): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php foreach ($days_of_week as $day): 
                $is_working = isset($existing_schedule[$day]) ? $existing_schedule[$day]['is_working'] : 1;
                $start = isset($existing_schedule[$day]) ? $existing_schedule[$day]['start_time'] : '09:00';
                $end = isset($existing_schedule[$day]) ? $existing_schedule[$day]['end_time'] : '17:00';
                
                $start = date('H:i', strtotime($start));
                $end = date('H:i', strtotime($end));
            ?>
                <div class="day-row <?= $is_working ? '' : 'not-working' ?>" id="row_<?= $day ?>">
                    <label class="day-label">
                        <input type="checkbox" name="working[<?= $day ?>]" value="1" <?= $is_working ? 'checked' : '' ?> onchange="toggleDay('<?= $day ?>', this)">
                        <?= $day ?>
                    </label>
                    
                    <div class="time-inputs">
                        <input type="time" name="start_time[<?= $day ?>]" value="<?= $start ?>">
                        <span style="font-size: 12px; color: var(--text-muted)">to</span>
                        <input type="time" name="end_time[<?= $day ?>]" value="<?= $end ?>">
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-submit">Update Shift Patterns</button>
        </form>
    </div>

    <script>
        // Global Theme Management
        const savedTheme = localStorage.getItem('admin-theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        function toggleDay(day, checkbox) {
            const row = document.getElementById('row_' + day);
            if (checkbox.checked) {
                row.classList.remove('not-working');
            } else {
                row.classList.add('not-working');
            }
        }
    </script>

</body>
</html>