<?php
// elvis_salon/api/update_service.php
session_start();
require '../config/db.php';

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