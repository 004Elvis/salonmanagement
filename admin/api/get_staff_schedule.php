<?php
// elvis_salon/api/get_staff_schedule.php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false]); exit;
}

$staff_id = $_GET['staff_id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT day_of_week FROM staff_schedules WHERE staff_id = ? AND is_working = 1");
    $stmt->execute([$staff_id]);
    $days = $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns a simple array like ['Monday', 'Tuesday']
    
    echo json_encode(['success' => true, 'working_days' => $days]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>