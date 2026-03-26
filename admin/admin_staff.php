<?php
// elvis_salon/admin/admin_staff.php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

$admin_image = "../assets/images/default_profile.png"; 
$message = '';

// --- HANDLE ADD NEW STAFF FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role_id = 2; // Staff

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $role_id]);
        $message = "<div class='alert alert-success'>Staff member added successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error adding staff: " . $e->getMessage() . "</div>";
    }
}

// --- FETCH ALL STAFF MEMBERS ---
$stmt_staff = $pdo->query("SELECT user_id, full_name, email, phone FROM users WHERE role_id = 2 ORDER BY full_name ASC");
$staff_members = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #f8f9fa;
            --main-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --accent: #4caf50;
            --danger: #dc3545;
            --primary: #0d6efd;
        }

        body.dark-mode {
            --sidebar-bg: #1a1d20;
            --main-bg: #121416;
            --card-bg: #212529;
            --text-main: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #373b3e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }
        body { display: flex; height: 100vh; background-color: var(--main-bg); color: var(--text-main); overflow: hidden; }

        /* Sidebar */
        .sidebar { width: 250px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { font-size: 1.2rem; font-weight: bold; padding: 25px; border-bottom: 1px solid var(--border-color); }
        .nav-links { padding: 15px; }
        .menu-item { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: var(--text-main); border-radius: 6px; margin-bottom: 5px; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background: rgba(76, 175, 80, 0.1); color: var(--accent); font-weight: 600; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; color: var(--text-muted); }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        
        .dashboard-padding { padding: 25px; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* UI Elements */
        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 10px; flex-wrap: wrap; }
        .btn-add { padding: 10px 20px; background: var(--accent); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-logout { padding: 8px 16px; border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 4px; text-decoration: none; color: var(--text-main); font-size: 14px; }

        /* Responsive Table */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        th { color: var(--text-muted); font-weight: 600; }
        .btn-delete { color: var(--danger); border: 1px solid var(--danger); background: transparent; padding: 5px 10px; border-radius: 4px; cursor: pointer; }

        /* Alerts */
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 15px; }
        .modal-content { background: var(--card-bg); padding: 25px; border-radius: 8px; width: 100%; max-width: 450px; color: var(--text-main); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-color); color: var(--text-main); }
        .submit-btn { width: 100%; padding: 12px; background: var(--accent); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }

        /* Mobile Breakpoints */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .brand, .menu-item span { display: none; }
            .menu-item i { margin-right: 0; font-size: 1.2rem; }
            .header { padding: 10px 15px; }
            .header h2 { font-size: 1rem; }
            .dashboard-padding { padding: 15px; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

    <div class="sidebar">
        <div class="brand">✨ Admin</div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="admin_appointments.php" class="menu-item"><i class="far fa-calendar-alt"></i> <span>Appointments</span></a>
            <a href="admin_financials.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Financials</span></a>
            <a href="admin_staff.php" class="menu-item active"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
            <a href="admin_clients.php" class="menu-item"><i class="fas fa-users"></i> <span>Clients</span></a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Staff Management</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="../logout.php" class="btn-logout">Logout</a>
                <div style="width: 35px; height: 35px; border-radius: 50%; overflow: hidden; border: 2px solid var(--accent);">
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Admin" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        </div>

        <div class="dashboard-padding">
            <?php echo $message; ?>

            <div class="card">
                <div class="actions-bar">
                    <h3>Registered Beauticians</h3>
                    <button class="btn-add" onclick="openModal()"><i class="fas fa-plus"></i> Add Staff</button>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($staff_members) > 0): ?>
                                <?php foreach ($staff_members as $staff): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($staff['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td>
                                        <button class="btn-delete" onclick="deleteStaff(<?php echo $staff['user_id']; ?>, '<?php echo addslashes($staff['full_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">No staff members found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add New Staff Member</h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
            </div>
            <form method="POST" action="admin_staff.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Temporary Password</label>
                    <input type="password" name="password" value="password" required>
                </div>
                <button type="submit" name="add_staff" class="submit-btn">Save Member</button>
            </form>
        </div>
    </div>

    <script>
        // Global Theme Check
        if (localStorage.getItem('admin-theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        // Modal Logic
        const modal = document.getElementById('addStaffModal');
        function openModal() { modal.style.display = 'flex'; }
        function closeModal() { modal.style.display = 'none'; }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }

        // Delete Logic
        function deleteStaff(userId, staffName) {
            if (confirm("Are you sure you want to remove " + staffName + " from the system?")) {
                fetch('../api/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'user_id=' + userId
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        location.reload(); 
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>