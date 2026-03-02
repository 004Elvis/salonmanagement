<?php
// elvis_salon/admin/admin_clients.php
session_start();
require '../config/db.php';

// RBAC Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); 
    exit;
}

$admin_image = "../assets/images/default_profile.png"; 
$message = '';

// --- HANDLE ADD NEW CLIENT FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role_id = 3; // 3 represents Client in your database

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $role_id]);
        $message = "<div style='color: green; padding: 10px; margin-bottom: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>Client added successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div style='color: red; padding: 10px; margin-bottom: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>Error adding client: " . $e->getMessage() . "</div>";
    }
}

// --- FETCH ALL CLIENTS ---
$stmt_clients = $pdo->query("SELECT user_id, full_name, email, phone FROM users WHERE role_id = 3 ORDER BY full_name ASC");
$clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Shared Styles */
        :root { --sidebar-bg: #f8f9fa; --content-bg: #ffffff; --text-dark: #333; --text-light: #6c757d; --border-color: #dee2e6; --danger-color: #dc3545; --danger-bg: #f8d7da; --primary-color: #0d6efd; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f4f6f9; }
        
        /* Sidebar & Header */
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
        .logout-btn { padding: 8px 16px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; border-radius: 4px; font-weight: 500; text-decoration: none; color: var(--text-dark); }
        .profile-img-container { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #ccc; border: 1px solid #aaa; }
        .profile-img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Page Specific Styles */
        .dashboard-padding { padding: 30px; }
        .card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 25px; }
        
        /* Actions Bar */
        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-add { padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 0.95rem; }
        .btn-add:hover { background-color: #0b5ed7; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        th { color: var(--text-light); font-weight: 600; border-bottom: 2px solid var(--border-color); }
        .btn-delete { padding: 6px 12px; background-color: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
        .btn-delete:hover { background-color: var(--danger-color); color: #fff; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 8px; width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; color: var(--text-dark); }
        .close-btn { font-size: 1.5rem; cursor: pointer; color: var(--text-light); border: none; background: none; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-dark); font-weight: 500; font-size: 0.9rem;}
        .form-group input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.95rem; }
        .submit-btn { width: 100%; padding: 10px; background-color: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; margin-top: 10px; }
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
            <a href="admin_clients.php" class="menu-item active"><i class="fas fa-users"></i> Clients</a>
            <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Client Management</h2>
            <div class="header-actions" style="display:flex; gap:20px; align-items:center;">
                <a href="../logout.php" class="logout-btn">Logout</a>
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Admin">
                </div>
            </div>
        </div>

        <div class="dashboard-padding">
            <?php echo $message; ?>

            <div class="card">
                <div class="actions-bar">
                    <h3>Registered Clients</h3>
                    <button class="btn-add" onclick="openModal()">+ Add New Client</button>
                </div>

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
                        <?php if (count($clients) > 0): ?>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                <td>
                                    <button class="btn-delete" onclick="deleteClient(<?php echo $client['user_id']; ?>, '<?php echo addslashes($client['full_name']); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No clients found in the system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Client</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="admin_clients.php">
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
                <button type="submit" name="add_client" class="submit-btn">Save Client</button>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById('addClientModal');
        function openModal() { modal.style.display = 'flex'; }
        function closeModal() { modal.style.display = 'none'; }
        
        // Close modal if user clicks outside of the box
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Delete Logic - Reuses the exact same API endpoint we created for staff
        function deleteClient(userId, clientName) {
            if (confirm("Are you sure you want to completely remove client " + clientName + " from the system?")) {
                fetch('../api/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'user_id=' + userId
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert("Client removed successfully.");
                        location.reload(); 
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>