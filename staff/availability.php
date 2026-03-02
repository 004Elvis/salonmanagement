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
        $pdo->beginTransaction(); // Use a transaction for better reliability
        
        foreach ($days_of_week as $day) {
            $is_working = isset($_POST['working'][$day]) ? 1 : 0;
            
            // Default times if not provided or day is unchecked
            $start_time = !empty($_POST['start_time'][$day]) ? $_POST['start_time'][$day] : '09:00:00';
            $end_time = !empty($_POST['end_time'][$day]) ? $_POST['end_time'][$day] : '17:00:00';

            // Check if a record already exists
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

// Fetch existing availability to pre-fill the form
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
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9fb; color: #333; margin: 0; padding: 40px; }
        .container { max-width: 700px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 0; }
        .alert { padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        
        .day-row { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #eee; transition: all 0.3s; }
        .day-row:last-child { border-bottom: none; }
        .day-label { width: 150px; font-weight: bold; display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .time-inputs { display: flex; align-items: center; gap: 15px; }
        
        input[type="time"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
        input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        
        .btn-submit { display: block; width: 100%; padding: 12px; background: #333; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 25px; }
        .btn-submit:hover { background: #555; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: #000; text-decoration: underline; }

        /* Style for non-working days */
        .not-working { background-color: #fafafa; }
        .not-working .time-inputs { opacity: 0.3; pointer-events: none; }
    </style>
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        <h2>📅 Manage Weekly Availability</h2>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
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
                        <span>to</span>
                        <input type="time" name="end_time[<?= $day ?>]" value="<?= $end ?>">
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-submit">Save My Schedule</button>
        </form>
    </div>

    <script>
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