<?php
// actions/book_action.php

// 1. Enable Error Reporting (For debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Start Session
session_start();

// 3. Include Database & PHPMailer
require '../config/db.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// --- MAIN LOGIC ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // A. Check login
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }

    // B. Get Form Data
    $user_id    = $_SESSION['user_id'];
    $service_id = $_POST['service_id']; 
    $staff_id   = $_POST['staff_id'];
    $date       = $_POST['date']; 
    $time       = $_POST['time'];

    // Prevent submission if the time is empty (Redirect instead of die)
    if (empty($time)) {
        header("Location: ../customer/book.php?error=Missing_Time");
        exit();
    }

    try {
        // C. Insert into Database
        $sql = "INSERT INTO appointments (customer_id, service_id, staff_id, appointment_date, appointment_time, status) 
                VALUES (?, ?, ?, ?, ?, 'Pending')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $service_id, $staff_id, $date, $time]);

        // D. Send Email Reminder
        $user_email = $_SESSION['email'] ?? 'ochiengengineer17@gmail.com'; 
        
        // Format date and time nicely for the email
        $formatted_date = date('l, F j, Y', strtotime($date));
        $formatted_time = date('h:i A', strtotime($time));
        
        sendReminder($user_email, $formatted_date, $formatted_time);

        // E. Redirect
        header("Location: ../customer/dashboard.php?success=Booking_Confirmed");
        exit();

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        header("Location: ../customer/book.php?error=Database_Error");
        exit();
    }
} else {
    // Direct access redirect
    header("Location: ../index.php");
    exit();
}

// --- EMAIL FUNCTION ---
function sendReminder($userEmail, $date, $time) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com'; 
        $mail->Password   = 'ohncquyyclverdfg'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Salon System');
        $mail->addAddress($userEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Appointment Confirmation - Elvis Salon';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                <h2 style='color: #2c3e50;'>Booking Confirmed! ✨</h2>
                <p>Your appointment has been successfully booked and is currently <strong>Pending</strong> approval by our staff.</p>
                <div style='background: #f9f9fb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><b>Date:</b> $date</p>
                    <p style='margin: 5px 0;'><b>Time:</b> $time</p>
                </div>
                <p>Thank you for choosing Elvis Midega Beauty Salon!</p>
            </div>
        ";

        $mail->send();
    } catch (Exception $e) {
        // Silently log the email error so the customer still gets their booking success screen
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>