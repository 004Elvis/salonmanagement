<?php
// elvis_salon/api/update_staff_schedule.php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false]); exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['staff_id']) && isset($data['days'])) {
    $staff_id = $data['staff_id'];
    $days = $data['days']; // Array of checked days

    try {
        // First, delete all existing schedule records for this staff member
        $pdo->prepare("DELETE FROM staff_schedules WHERE staff_id = ?")->execute([$staff_id]);
        
        // Then, insert the new ones
        if (!empty($days)) {
            $stmt = $pdo->prepare("INSERT INTO staff_schedules (staff_id, day_of_week, is_working) VALUES (?, ?, 1)");
            foreach ($days as $day) {
                $stmt->execute([$staff_id, $day]);
            }
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
}
?>