<?php
require '../config/db.php';
if ($_SESSION['role'] !== 'Customer') { header("Location: ../index.php"); exit; }

// Fetch Dropdown Data
$staff = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 2")->fetchAll();
$services = $pdo->query("SELECT * FROM services")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Book Appointment</title>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Book an Appointment</h2>
            <form action="../actions/book_action.php" method="POST">
                <div class="form-group">
                    <label>Select Service</label>
                    <select name="service_id" required>
                        <?php foreach($services as $s): ?>
                            <option value="<?= $s['service_id'] ?>">
                                <?= $s['service_name'] ?> - KES <?= $s['price_kes'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Choose Beautician</label>
                    <select name="staff_id" required>
                        <?php foreach($staff as $st): ?>
                            <option value="<?= $st['user_id'] ?>"><?= $st['full_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="time" min="08:00" max="18:00" required>
                </div>

                <button type="submit" class="btn btn-primary">Confirm Booking</button>
            </form>
        </div>
    </div>
</body>
</html>