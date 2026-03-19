<?php
// actions/reschedule_action.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config/db.php';
require '../includes/logger.php';
logApiRequest($pdo, 'reschedule_action.php');

// --- API TRACKING ---
try {
    $endpoint_name = 'reschedule_action.php';
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

    $appointment_id = $_POST['appointment_id'];
    $service_id     = $_POST['service_id']; 
    $staff_id       = $_POST['staff_id'];
    $date           = $_POST['date']; 
    $time           = $_POST['time'];
    $user_id        = $_SESSION['user_id'];

    try {
        // 1. Update the appointment in the database
        // We set status back to 'Pending' because a reschedule usually needs new approval
        $sql = "UPDATE appointments 
                SET service_id = ?, staff_id = ?, appointment_date = ?, appointment_time = ?, status = 'Pending' 
                WHERE appointment_id = ? AND customer_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$service_id, $staff_id, $date, $time, $appointment_id, $user_id]);

        // 2. Fetch User & Service details for the email (Direct DB lookup)
        $info_stmt = $pdo->prepare("
            SELECT u.email, u.full_name, s.service_name 
            FROM users u
            JOIN appointments a ON u.user_id = a.customer_id
            JOIN services s ON s.service_id = ?
            WHERE a.appointment_id = ?
        ");
        $info_stmt->execute([$service_id, $appointment_id]);
        $details = $info_stmt->fetch();

        if ($details) {
            $customer_email = $details['email'];
            $customer_name  = $details['full_name'];
            $service_name   = $details['service_name'];
            
            $formatted_date = date('l, F j, Y', strtotime($date));
            $formatted_time = date('h:i A', strtotime($time));

            // Send notification to CUSTOMER
            sendRescheduleEmail($customer_email, $customer_name, $service_name, $formatted_date, $formatted_time, 'customer');
            
            // Send alert to ADMIN
            sendRescheduleEmail('ochiengengineer17@gmail.com', 'Admin', $service_name, $formatted_date, $formatted_time, 'admin', $customer_name);
        }

        header("Location: ../customer/dashboard.php?success=Reschedule_Submitted");
        exit();

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        header("Location: ../customer/book.php?reschedule_id=$appointment_id&error=Update_Failed");
        exit();
    }
}

function sendRescheduleEmail($recipientEmail, $recipientName, $service, $date, $time, $type, $clientName = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com'; 
        $mail->Password   = 'ohncquyyclverdfg'; // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Midega Salon');
        $mail->addAddress($recipientEmail, $recipientName);

        $mail->isHTML(true);
        
        if ($type === 'customer') {
            $mail->Subject = 'Appointment Rescheduled - Elvis Salon';
            $mail->Body    = "
                <div style='font-family: sans-serif; color: #333; max-width: 600px; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #d4af37;'>Hello $recipientName!</h2>
                    <p>Your request to reschedule your <strong>$service</strong> appointment has been received.</p>
                    <p>Your new requested slot is:</p>
                    <div style='background: #fdfaf0; border-left: 4px solid #d4af37; padding: 15px; border-radius: 5px;'>
                        <p><strong>New Date:</strong> $date</p>
                        <p><strong>New Time:</strong> $time</p>
                    </div>
                    <p>This is currently <strong>Pending Approval</strong>. We will notify you once confirmed.</p>
                </div>";
        } else {
            $mail->Subject = 'Reschedule Request Alert - Elvis Salon';
            $mail->Body    = "<h2>Customer Reschedule Request</h2>
                              <p>Customer <strong>$clientName</strong> has changed their <strong>$service</strong> booking.</p>
                              <p>New Schedule: $date at $time. Please review in the admin panel.</p>";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>