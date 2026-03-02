<?php
// elvis_salon/api/update_salon_hours.php
session_start();
require '../config/db.php';
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