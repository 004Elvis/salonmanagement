<?php
// elvis_salon/admin/api/get_staff_schedule.php
session_start();

/** * FIX 1: Path change
 * Since this file is in admin/api/, we need to go UP two levels 
 * to find the project root config folder.
 */
require '../../config/db.php'; 
require '../../includes/logger.php';
logApiRequest($pdo, 'get_staff_schedule.php');


// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'get_staff_schedule.php';
    $log_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $log_user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // Prevent logging errors from stopping the schedule retrieval
}
// --- END OF API TRACKING UPDATE ---

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); 
    exit;
}

// Get the staff ID from the URL
$staff_id = $_GET['staff_id'] ?? 0;

try {
    /**
     * FIX 2: Added 'is_working' logic
     * Ensure your 'staff_schedules' table actually has an 'is_working' column.
     * If it doesn't, remove 'AND is_working = 1' from the query below.
     */
    $stmt = $pdo->prepare("SELECT day_of_week FROM staff_schedules WHERE staff_id = ? AND is_working = 1");
    $stmt->execute([$staff_id]);
    $days = $stmt->fetchAll(PDO::FETCH_COLUMN); 
    
    echo json_encode(['success' => true, 'working_days' => $days]);
} catch (PDOException $e) {
    // This will return the exact SQL error to the console if it fails
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>