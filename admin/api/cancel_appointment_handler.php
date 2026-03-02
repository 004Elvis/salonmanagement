<?php
// api/cancel_appointment_handler.php

// Turn on error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// MAKE SURE THIS PATH IS CORRECT FOR YOUR PROJECT!
require '../../config/db.php';

header('Content-Type: application/json');

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

// 2. Check if user is an Admin
if ($_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Role is ' . $_SESSION['role'] . ', not Admin.']);
    exit();
}

// 3. Check if we received the ID
if (!isset($_POST['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'No appointment ID received.']);
    exit();
}

$appt_id = $_POST['appointment_id'];

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
    $stmt->execute([$appt_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update ran, but 0 rows changed. ID might be wrong.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>