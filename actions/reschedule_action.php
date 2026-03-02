<?php
// actions/reschedule_action.php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $appt_id    = $_POST['appointment_id'];
    $service_id = $_POST['service_id']; 
    $staff_id   = $_POST['staff_id'];
    $date       = $_POST['date']; 
    $time       = $_POST['time'];
    $user_id    = $_SESSION['user_id'];

    // Redirect gracefully instead of dying
    if (empty($time)) {
        header("Location: ../customer/book.php?reschedule_id=$appt_id&error=Missing_Time");
        exit();
    }

    try {
        // Update the existing record and set it back to Pending so staff has to re-approve
        $sql = "UPDATE appointments SET service_id = ?, staff_id = ?, appointment_date = ?, appointment_time = ?, status = 'Pending' 
                WHERE appointment_id = ? AND customer_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$service_id, $staff_id, $date, $time, $appt_id, $user_id]);

        header("Location: ../customer/dashboard.php?success=Appointment_Rescheduled");
        exit();
    } catch (PDOException $e) {
        // Log the error silently and redirect
        error_log("Reschedule Error: " . $e->getMessage());
        header("Location: ../customer/dashboard.php?error=Database_Error");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}