<?php
// elvis_salon/api/delete_user.php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

// Security check: Only Admin can delete users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id_to_delete = $_POST['user_id'];

    // Prevent the admin from accidentally deleting themselves
    if ($user_id_to_delete == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own admin account.']);
        exit;
    }

    try {
        // Delete the user from the database
        // Note: If they have existing appointments, you might need to handle foreign key constraints 
        // (e.g., by changing their appointments to a different staff member or setting staff_id to NULL first).
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id_to_delete]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error. Make sure this user has no tied appointments before deleting.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>