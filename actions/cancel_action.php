<?php
// actions/cancel_action.php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $appt_id = $_POST['appointment_id'];
    $user_id = $_SESSION['user_id'];

    // Update status to Cancelled. (We don't DELETE it so you keep the history!)
    $sql = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND customer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$appt_id, $user_id]);

    header("Location: ../customer/dashboard.php?success=Appointment_Cancelled");
    exit();
}
header("Location: ../index.php");
?>