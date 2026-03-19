<?php
// elvis_salon/admin/api/update_staff_schedule.php
session_start();

// FIX: Go up two levels to reach the root config folder
require '../../config/db.php'; 
require '../../includes/logger.php';
logApiRequest($pdo, 'update_staff_schedule.php');

// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'update_staff_schedule.php';
    $log_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $log_user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // Prevent logging errors from interrupting the schedule update transaction
}
// --- END OF API TRACKING UPDATE ---

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); 
    exit;
}

// Get the JSON data sent from the JavaScript fetch
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['staff_id']) && isset($data['days'])) {
    $staff_id = $data['staff_id'];
    $days = $data['days']; // Array of checked days like ["Monday", "Tuesday"]

    try {
        $pdo->beginTransaction(); // Best practice: use transactions for multiple queries

        // 1. Delete all existing records for this staff member
        $stmt = $pdo->prepare("DELETE FROM staff_schedules WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        
        // 2. Insert the new selected days
        if (!empty($days)) {
            $stmt = $pdo->prepare("INSERT INTO staff_schedules (staff_id, day_of_week, is_working) VALUES (?, ?, 1)");
            foreach ($days as $day) {
                $stmt->execute([$staff_id, $day]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
}
?>