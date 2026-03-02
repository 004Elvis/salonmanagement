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
        /* Shared Dashboard Styles */
        :root { --sidebar-bg: #f8f9fa; --content-bg: #ffffff; --text-dark: #333; --text-light: #6c757d; --border-color: #dee2e6; --primary-color: #0d6efd; --success-color: #198754; --danger-color: #dc3545;}
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f4f6f9; }
        
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
        .logout-btn { padding: 8px 16px; border: 1px solid var(--border-color); background: #fff; border-radius: 4px; text-decoration: none; color: var(--text-dark); }
        .profile-img-container { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #ccc; border: 1px solid #aaa; }
        .profile-img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Settings Page Specific Styles */
        .dashboard-padding { padding: 30px; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        
        /* Tabs Styling */
        .tabs-header { display: flex; border-bottom: 2px solid var(--border-color); margin-bottom: 25px; }
        .tab-btn { padding: 12px 25px; cursor: pointer; background: none; border: none; font-size: 1.05rem; font-weight: 600; color: var(--text-light); position: relative; }
        .tab-btn.active { color: var(--primary-color); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background-color: var(--primary-color); }
        .tab-btn:hover { color: var(--primary-color); }
        
        .tab-content { display: none; background: #fff; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); }
        .tab-content.active { display: block; }

        /* Forms & Tables inside Tabs */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--border-color); }
        .input-editable { padding: 6px; border: 1px solid #ccc; border-radius: 4px; width: 100%; }
        .btn-update { padding: 6px 12px; background: var(--success-color); color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-delete { padding: 6px 12px; background: var(--danger-color); color: white; border: none; border-radius: 4px; cursor: pointer; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; max-width: 400px; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; }
        .btn-submit { padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Admin Dashboard</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> All Appointments</a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> Financials</a>
            <a href="admin_staff.php" class="menu-item"><i class="fas fa-user-tie"></i> Staff</a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> Clients</a>
            <a href="admin_settings.php" class="menu-item active"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>System Settings</h2>
            <div class="header-actions" style="display:flex; gap:20px; align-items:center;">
                <a href="../logout.php" class="logout-btn">Logout</a>
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Admin">
                </div>
            </div>
        </div>

        <div class="dashboard-padding">
            <?php echo $message; ?>

            <div class="tabs-header">
                <button class="tab-btn active" onclick="openTab(event, 'Services')">Services Menu</button>
                <button class="tab-btn" onclick="openTab(event, 'Security')">User Security</button>
                <button class="tab-btn" onclick="openTab(event, 'Hours')">Salon Hours</button>
                <button class="tab-btn" onclick="openTab(event, 'Schedules')">Staff Schedules</button>
            </div>

            <div id="Services" class="tab-content active">
                <h3>Manage Salon Services</h3>
                <p style="color: var(--text-light); margin-bottom: 15px;">Update existing services or add new ones to the catalog.</p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Duration (Mins)</th>
                            <th>Price (KES)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($services as $svc): ?>
                        <tr>
                            <td><input type="text" id="name_<?php echo $svc['service_id']; ?>" class="input-editable" value="<?php echo htmlspecialchars($svc['service_name']); ?>"></td>
                            <td><input type="number" id="dur_<?php echo $svc['service_id']; ?>" class="input-editable" value="<?php echo htmlspecialchars($svc['duration_minutes']); ?>"></td>
                            <td><input type="number" id="price_<?php echo $svc['service_id']; ?>" class="input-editable" value="<?php echo htmlspecialchars($svc['price_kes']); ?>"></td>
                            <td style="display: flex; gap: 5px;">
                                <button class="btn-update" onclick="updateService(<?php echo $svc['service_id']; ?>)">Save</button>
                                <button class="btn-delete" onclick="deleteService(<?php echo $svc['service_id']; ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--border-color);">

                <h4>Add New Service</h4>
                <form method="POST" action="admin_settings.php" style="margin-top: 15px; display: flex; gap: 15px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Service Name</label>
                        <input type="text" name="service_name" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; width: 120px;">
                        <label>Mins</label>
                        <input type="number" name="duration" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; width: 150px;">
                        <label>Price (KES)</label>
                        <input type="number" name="price_kes" required>
                    </div>
                    <button type="submit" name="add_service" class="btn-submit">Add Service</button>
                </form>
            </div>

            <div id="Security" class="tab-content">
                <h3>Force Password Change</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">Change the password for any Admin, Staff, or Client account.</p>
                
                <form method="POST" action="admin_settings.php">
                    <div class="form-group">
                        <label>Select User</label>
                        <select name="target_user_id" required>
                            <option value="">-- Choose a User --</option>
                            <?php foreach($users as $u): ?>
                                <?php 
                                    $role = ($u['role_id'] == 1) ? 'Admin' : (($u['role_id'] == 2) ? 'Staff' : 'Client');
                                ?>
                                <option value="<?php echo $u['user_id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $role; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn-submit">Update Password</button>
                </form>
            </div>

            <div id="Hours" class="tab-content">
                <h3>Operating Hours</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">Set the opening and closing times for the salon.</p>
                
                <form id="hoursForm">
                    <table>
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Open Time</th>
                                <th>Close Time</th>
                                <th>Closed All Day?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($salon_hours as $hour): ?>
                            <tr class="hour-row" data-day="<?php echo $hour['day_of_week']; ?>">
                                <td><?php echo $hour['day_of_week']; ?></td>
                                <td><input type="time" class="input-editable open-time" style="max-width: 150px;" value="<?php echo $hour['open_time']; ?>" <?php echo $hour['is_closed'] ? 'disabled' : ''; ?>></td>
                                <td><input type="time" class="input-editable close-time" style="max-width: 150px;" value="<?php echo $hour['close_time']; ?>" <?php echo $hour['is_closed'] ? 'disabled' : ''; ?>></td>
                                <td><input type="checkbox" class="is-closed" <?php echo $hour['is_closed'] ? 'checked' : ''; ?> onchange="toggleTimeInputs(this)"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn-submit" onclick="saveSalonHours()">Save All Hours</button>
                </form>
            </div>

            <div id="Schedules" class="tab-content">
                <h3>Staff Working Days</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">Assign which days of the week each staff member is available.</p>
                
                <div class="form-group">
                    <label>Select Staff Member</label>
                    <select id="staffScheduleSelect" onchange="loadStaffSchedule()">
                        <option value="">-- Select Staff --</option>
                        <?php foreach($staff_members as $sm): ?>
                            <option value="<?php echo $sm['user_id']; ?>"><?php echo htmlspecialchars($sm['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="scheduleCheckboxes" style="display: none; margin-top: 20px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                        <label><input type="checkbox" class="schedule-day" value="Monday"> Monday</label>
                        <label><input type="checkbox" class="schedule-day" value="Tuesday"> Tuesday</label>
                        <label><input type="checkbox" class="schedule-day" value="Wednesday"> Wednesday</label>
                        <label><input type="checkbox" class="schedule-day" value="Thursday"> Thursday</label>
                        <label><input type="checkbox" class="schedule-day" value="Friday"> Friday</label>
                        <label><input type="checkbox" class="schedule-day" value="Saturday"> Saturday</label>
                        <label><input type="checkbox" class="schedule-day" value="Sunday"> Sunday</label>
                    </div>
                    <button type="button" class="btn-submit" onclick="saveStaffSchedule()">Save Schedule</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Tab Switching Logic
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // --- SERVICES API LOGIC ---
        function updateService(id) {
            const name = document.getElementById('name_' + id).value;
            const dur = document.getElementById('dur_' + id).value;
            const price = document.getElementById('price_' + id).value;
            
            const formData = new URLSearchParams();
            formData.append('service_id', id);
            formData.append('service_name', name);
            formData.append('duration', dur);
            formData.append('price_kes', price);

            fetch('../api/update_service.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Service updated successfully!");
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteService(id) {
            if(confirm("Are you sure you want to completely delete this service?")) {
                fetch('../api/delete_service.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'service_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert("Service deleted successfully.");
                        location.reload(); 
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // --- SALON HOURS API LOGIC ---
        function toggleTimeInputs(checkbox) {
            let row = checkbox.closest('tr');
            row.querySelector('.open-time').disabled = checkbox.checked;
            row.querySelector('.close-time').disabled = checkbox.checked;
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

            fetch('../api/update_salon_hours.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(hoursData)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('Salon hours saved successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error('Error:', err));
        }

        // --- STAFF SCHEDULE API LOGIC ---
        function loadStaffSchedule() {
            var select = document.getElementById("staffScheduleSelect");
            var checkboxesContainer = document.getElementById("scheduleCheckboxes");
            var checkboxes = document.querySelectorAll('.schedule-day');
            
            if (select.value !== "") {
                // Uncheck all boxes first
                checkboxes.forEach(cb => cb.checked = false);
                
                // Fetch this staff member's saved days
                fetch('../api/get_staff_schedule.php?staff_id=' + select.value)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.working_days) {
                        data.working_days.forEach(day => {
                            let cb = document.querySelector(`.schedule-day[value="${day}"]`);
                            if(cb) cb.checked = true;
                        });
                    }
                    checkboxesContainer.style.display = "block";
                })
                .catch(err => console.error('Error:', err));
            } else {
                checkboxesContainer.style.display = "none";
            }
        }

        function saveStaffSchedule() {
            var staffId = document.getElementById("staffScheduleSelect").value;
            var workingDays = [];
            
            document.querySelectorAll('.schedule-day:checked').forEach(cb => {
                workingDays.push(cb.value);
            });

            fetch('../api/update_staff_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ staff_id: staffId, days: workingDays })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Staff schedule saved successfully!");
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => console.error('Error:', err));
        }
    </script>
</body>
</html>