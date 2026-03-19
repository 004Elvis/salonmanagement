<?php
// elvis_salon/api/update_service.php
session_start();
require '../config/db.php';
require '../../includes/logger.php';
logApiRequest($pdo, 'update_service.php');

// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'update_service.php';
    $log_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $log_user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // Logging failure should not prevent the service update from running
}
// --- END OF API TRACKING UPDATE ---

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'] ?? '';
    $service_name = trim($_POST['service_name'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price_kes = trim($_POST['price_kes'] ?? '');

    if (empty($service_id) || empty($service_name) || empty($duration) || empty($price_kes)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE services SET service_name = ?, duration_minutes = ?, price_kes = ? WHERE service_id = ?");
        $stmt->execute([$service_name, $duration, $price_kes, $service_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>