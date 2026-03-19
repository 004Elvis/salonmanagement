<?php
// elvis_salon/admin/admin_settings.php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

$admin_image = "../assets/images/default_profile.png"; 
$message = '';

// --- HANDLE ADD NEW SERVICE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $service_name = trim($_POST['service_name']);
    $price_kes = trim($_POST['price_kes']);
    $duration = trim($_POST['duration']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO services (service_name, price_kes, duration_minutes) VALUES (?, ?, ?)");
        $stmt->execute([$service_name, $price_kes, $duration]);
        $message = "<div class='alert alert-success'>Service added successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error adding service: " . $e->getMessage() . "</div>";
    }
}

// --- HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $target_user_id = $_POST['target_user_id'];
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $target_user_id]);
        $message = "<div class='alert alert-success'>Password updated successfully for selected user!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error updating password.</div>";
    }
}

// --- FETCH DATA FOR TABS ---
$services = $pdo->query("SELECT * FROM services ORDER BY service_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT user_id, full_name, role_id FROM users ORDER BY role_id ASC, full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$staff_members = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 2 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$salon_hours = $pdo->query("SELECT * FROM salon_settings ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
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
            --primary: #0d6efd;
            --danger: #dc3545;
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

        .sidebar { width: 250px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px; border-bottom: 1px solid var(--border-color); }
        .menu-item { display: flex; align-items: center; padding: 12px 20px; text-decoration: none; color: var(--text-main); border-radius: 6px; margin: 5px 15px; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background: rgba(76, 175, 80, 0.1); color: var(--accent); font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-muted); }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        
        .dashboard-padding { padding: 25px; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* Responsive Tabs */
        .tabs-header { display: flex; flex-wrap: wrap; border-bottom: 2px solid var(--border-color); margin-bottom: 25px; gap: 5px; }
        .tab-btn { padding: 12px 20px; cursor: pointer; background: none; border: none; font-size: 1rem; font-weight: 600; color: var(--text-muted); position: relative; }
        .tab-btn.active { color: var(--accent); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: var(--accent); }
        
        .tab-content { display: none; background: var(--card-bg); padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .tab-content.active { display: block; }

        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        th { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; }

        .input-editable { padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-color); color: var(--text-main); width: 100%; }
        .btn-submit { padding: 10px 20px; background: var(--accent); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-update { background: var(--accent); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-delete { background: var(--danger); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; max-width: 400px; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-color); color: var(--text-main); }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .brand, .menu-item span { display: none; }
            .menu-item i { margin-right: 0; font-size: 1.2rem; }
            .header { padding: 10px 15px; }
            .dashboard-padding { padding: 15px; }
            .tab-btn { flex: 1 1 auto; font-size: 0.9rem; padding: 10px; }
            .service-add-form { flex-direction: column; align-items: stretch !important; gap: 10px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">✨ Admin</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Financials</span></a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> <span>Clients</span></a>
            <a href="admin_settings.php" class="menu-item active"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>System Settings</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="../logout.php" style="text-decoration:none; color:var(--text-main); font-size:14px; border:1px solid var(--border-color); padding:5px 10px; border-radius:4px;">Logout</a>
                <img src="<?php echo htmlspecialchars($admin_image); ?>" style="width:35px; height:35px; border-radius:50%; object-fit:cover; border:2px solid var(--accent);">
            </div>
        </div>

        <div class="dashboard-padding">
            <?php echo $message; ?>

            <div class="tabs-header">
                <button class="tab-btn active" onclick="openTab(event, 'Services')">Services</button>
                <button class="tab-btn" onclick="openTab(event, 'Security')">Security</button>
                <button class="tab-btn" onclick="openTab(event, 'Hours')">Business Hours</button>
                <button class="tab-btn" onclick="openTab(event, 'Schedules')">Staffing</button>
            </div>

            <div id="Services" class="tab-content active">
                <h3>Catalog Management</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mins</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($services as $svc): ?>
                            <tr>
                                <td><input type="text" id="name_<?= $svc['service_id'] ?>" class="input-editable" value="<?= htmlspecialchars($svc['service_name']) ?>"></td>
                                <td><input type="number" id="dur_<?= $svc['service_id'] ?>" class="input-editable" value="<?= $svc['duration_minutes'] ?>"></td>
                                <td><input type="number" id="price_<?= $svc['service_id'] ?>" class="input-editable" value="<?= $svc['price_kes'] ?>"></td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn-update" onclick="updateService(<?= $svc['service_id'] ?>)"><i class="fas fa-save"></i></button>
                                        <button class="btn-delete" onclick="deleteService(<?= $svc['service_id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <hr style="margin: 25px 0; border: 0; border-top: 1px solid var(--border-color);">
                <h4>Add New Service</h4>
                <form method="POST" action="admin_settings.php" class="service-add-form" style="margin-top: 15px; display: flex; gap: 15px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Service Name</label>
                        <input type="text" name="service_name" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0; width: 100px;">
                        <label>Mins</label>
                        <input type="number" name="duration" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0; width: 150px;">
                        <label>KES</label>
                        <input type="number" name="price_kes" required>
                    </div>
                    <button type="submit" name="add_service" class="btn-submit">Add</button>
                </form>
            </div>

            <div id="Security" class="tab-content">
                <h3>Credential Management</h3>
                <form method="POST" action="admin_settings.php">
                    <div class="form-group">
                        <label>Target User</label>
                        <select name="target_user_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach($users as $u): 
                                $r = ($u['role_id']==1)?'Admin':(($u['role_id']==2)?'Staff':'Client'); ?>
                                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= $r ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Set New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn-submit">Force Update</button>
                </form>
            </div>

            <div id="Hours" class="tab-content">
                <h3>Salon Operating Hours</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Day</th><th>Open</th><th>Close</th><th>Closed All Day?</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($salon_hours as $hour): ?>
                            <tr class="hour-row" data-day="<?= $hour['day_of_week'] ?>">
                                <td><?= $hour['day_of_week'] ?></td>
                                <td><input type="time" class="input-editable open-time" value="<?= $hour['open_time'] ?>" <?= $hour['is_closed'] ? 'disabled' : '' ?>></td>
                                <td><input type="time" class="input-editable close-time" value="<?= $hour['close_time'] ?>" <?= $hour['is_closed'] ? 'disabled' : '' ?>></td>
                                <td><input type="checkbox" class="is-closed" <?= $hour['is_closed'] ? 'checked' : '' ?> onchange="toggleTimeInputs(this)"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn-submit" onclick="saveSalonHours()">Update Hours</button>
            </div>

            <div id="Schedules" class="tab-content">
                <h3>Staff Availability Shifts</h3>
                <div class="form-group">
                    <label>Staff Member</label>
                    <select id="staffScheduleSelect" onchange="loadStaffSchedule()">
                        <option value="">-- Choose --</option>
                        <?php foreach($staff_members as $sm): ?>
                            <option value="<?= $sm['user_id'] ?>"><?= htmlspecialchars($sm['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="scheduleCheckboxes" style="display: none; margin-top: 20px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                        <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach($days as $d): ?>
                            <label style="cursor:pointer;"><input type="checkbox" class="schedule-day" value="<?= $d ?>"> <?= $d ?></label>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn-submit" onclick="saveStaffSchedule()">Update Shift Pattern</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Apply Global Theme
        if (localStorage.getItem('admin-theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        // Tab Switcher
        function openTab(evt, tabName) {
            document.querySelectorAll(".tab-content").forEach(t => t.style.display = "none");
            document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.classList.add("active");
        }

        // --- API FUNCTIONS ---
        function updateService(id) {
            const formData = new URLSearchParams();
            formData.append('service_id', id);
            formData.append('service_name', document.getElementById('name_' + id).value);
            formData.append('duration', document.getElementById('dur_' + id).value);
            formData.append('price_kes', document.getElementById('price_' + id).value);

            fetch('api/update_service.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            }).then(res => res.json()).then(data => {
                alert(data.success ? "Success!" : "Error: " + data.message);
            });
        }

        function deleteService(id) {
            if(confirm("Delete this service?")) {
                fetch('api/delete_service.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'service_id=' + id
                }).then(res => res.json()).then(data => {
                    if(data.success) location.reload(); else alert(data.message);
                });
            }
        }

        function toggleTimeInputs(cb) {
            let row = cb.closest('tr');
            row.querySelector('.open-time').disabled = cb.checked;
            row.querySelector('.close-time').disabled = cb.checked;
        }

        function saveSalonHours() {
            let hoursData = [];
            document.querySelectorAll('.hour-row').forEach(row => {
                hoursData.push({
                    day: row.getAttribute('data-day'),
                    open: row.querySelector('.open-time').value,
                    close: row.querySelector('.close-time').value,
                    closed: row.querySelector('.is-closed').checked ? 1 : 0
                });
            });
            fetch('api/update_salon_hours.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(hoursData)
            }).then(res => res.json()).then(data => {
                alert(data.success ? "Hours saved!" : data.message);
            });
        }

        function loadStaffSchedule() {
            const id = document.getElementById("staffScheduleSelect").value;
            const container = document.getElementById("scheduleCheckboxes");
            if (!id) { container.style.display = "none"; return; }
            
            document.querySelectorAll('.schedule-day').forEach(cb => cb.checked = false);
            fetch('api/get_staff_schedule.php?staff_id=' + id)
            .then(res => res.json()).then(data => {
                if (data.success && data.working_days) {
                    data.working_days.forEach(day => {
                        const cb = document.querySelector(`.schedule-day[value="${day}"]`);
                        if(cb) cb.checked = true;
                    });
                    container.style.display = "block";
                }
            });
        }

        function saveStaffSchedule() {
            const id = document.getElementById("staffScheduleSelect").value;
            let days = [];
            document.querySelectorAll('.schedule-day:checked').forEach(cb => days.push(cb.value));
            fetch('api/update_staff_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ staff_id: id, days: days })
            }).then(res => res.json()).then(data => {
                alert(data.success ? "Schedule saved!" : data.message);
            });
        }
    </script>
</body>
</html>