<?php
// actions/book_action.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config/db.php';
require '../includes/logger.php';
logApiRequest($pdo, 'book_action.php');

// --- API TRACKING ---
try {
    $endpoint_name = 'book_action.php';
    $log_user_id = $_SESSION['user_id'] ?? null;
    $log_user_role = $_SESSION['role'] ?? 'Guest';
    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) { }

// Include PHPMailer
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }

    $user_id    = $_SESSION['user_id'];
    $service_id = $_POST['service_id']; 
    $staff_id   = $_POST['staff_id'];
    $date       = $_POST['date']; 
    $time       = $_POST['time'];

    if (empty($time)) {
        header("Location: ../customer/book.php?error=Missing_Time");
        exit();
    }

    try {
        // 1. Insert into Database
        $sql = "INSERT INTO appointments (customer_id, service_id, staff_id, appointment_date, appointment_time, status) 
                VALUES (?, ?, ?, ?, ?, 'Pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $service_id, $staff_id, $date, $time]);

        // 2. Fetch User & Service details for the email (The CRITICAL FIX)
        $info_stmt = $pdo->prepare("
            SELECT u.email, u.full_name, s.service_name 
            FROM users u, services s 
            WHERE u.user_id = ? AND s.service_id = ?
        ");
        $info_stmt->execute([$user_id, $service_id]);
        $details = $info_stmt->fetch();

        if ($details) {
            $customer_email = $details['email'];
            $customer_name  = $details['full_name'];
            $service_name   = $details['service_name'];
            
            $formatted_date = date('l, F j, Y', strtotime($date));
            $formatted_time = date('h:i A', strtotime($time));

            // Send confirmation to CUSTOMER
            sendEmail($customer_email, $customer_name, $service_name, $formatted_date, $formatted_time, 'customer');
            
            // Send notification to ADMIN (Optional - so YOU know someone booked)
            sendEmail('ochiengengineer17@gmail.com', 'Admin', $service_name, $formatted_date, $formatted_time, 'admin', $customer_name);
        }

        header("Location: ../customer/dashboard.php?success=Booking_Confirmed");
        exit();

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        header("Location: ../customer/book.php?error=Database_Error");
        exit();
    }
}

function sendEmail($recipientEmail, $recipientName, $service, $date, $time, $type, $clientName = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com'; // Your Gmail
        $mail->Password   = 'ohncquyyclverdfg';         // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Midega Salon');
        $mail->addAddress($recipientEmail, $recipientName);

        $mail->isHTML(true);
        
        if ($type === 'customer') {
            $mail->Subject = 'Booking Confirmed - Elvis Salon';
            $mail->Body    = "
                <div style='font-family: sans-serif; color: #333; max-width: 600px; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #4caf50;'>Hello $recipientName!</h2>
                    <p>Your booking for <strong>$service</strong> has been received and is <strong>Pending Approval</strong>.</p>
                    <div style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>
                        <p><strong>Date:</strong> $date</p>
                        <p><strong>Time:</strong> $time</p>
                    </div>
                    <p>We will notify you once the staff confirms your slot.</p>
                </div>";
        } else {
            $mail->Subject = 'New Booking Alert - Elvis Salon';
            $mail->Body    = "<h2>New Booking Received</h2>
                              <p>Customer <strong>$clientName</strong> has booked <strong>$service</strong>.</p>
                              <p>Schedule: $date at $time. Please log in to approve.</p>";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>