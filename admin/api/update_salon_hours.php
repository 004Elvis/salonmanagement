<?php
// elvis_salon/api/update_salon_hours.php
session_start();
require '../config/db.php';
require '../../includes/logger.php';
logApiRequest($pdo, 'update_salon_hours.php');

// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'update_salon_hours.php';
    $log_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $log_user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // Logging failure should not prevent the settings update from running
}
// --- END OF API TRACKING UPDATE ---

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (is_array($data)) {
    try {
        $stmt = $pdo->prepare("UPDATE salon_settings SET open_time = ?, close_time = ?, is_closed = ? WHERE day_of_week = ?");
        foreach ($data as $dayData) {
            $stmt->execute([$dayData['open'], $dayData['close'], $dayData['closed'], $dayData['day']]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data format.']);
}
?>