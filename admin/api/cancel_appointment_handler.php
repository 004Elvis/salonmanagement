<?php
// api/cancel_appointment_handler.php

// Turn on error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require '../../config/db.php';
require '../../includes/logger.php';
logApiRequest($pdo, 'cancel_appointment_handler.php');

// Include PHPMailer classes
require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'cancel_appointment_handler.php';
    $log_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $log_user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // We catch this so a logging error doesn't stop the actual appointment cancellation
}
// --- END OF API TRACKING UPDATE ---

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
    // --- FETCH CLIENT DETAILS FOR EMAIL BEFORE UPDATING ---
    $info_stmt = $pdo->prepare("
        SELECT u.email, u.full_name, s.service_name, a.appointment_date, a.appointment_time
        FROM appointments a
        JOIN users u ON a.customer_id = u.user_id
        JOIN services s ON a.service_id = s.service_id
        WHERE a.appointment_id = ?
    ");
    $info_stmt->execute([$appt_id]);
    $details = $info_stmt->fetch();

    // Perform the update
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
    $stmt->execute([$appt_id]);
    
    if ($stmt->rowCount() > 0) {
        
        // --- SEND APOLOGY EMAIL ---
        if ($details) {
            sendAdminCancellationEmail(
                $details['email'], 
                $details['full_name'], 
                $details['service_name'], 
                $details['appointment_date'], 
                $details['appointment_time']
            );
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update ran, but 0 rows changed. ID might be wrong.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}

// --- EMAIL HELPER FUNCTION ---
function sendAdminCancellationEmail($recipientEmail, $recipientName, $service, $date, $time) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com';
        $mail->Password   = 'ohncquyyclverdfg'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Midega Salon');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);

        $mail->Subject = 'Sincere Apology: Appointment Cancellation - Elvis Salon';
        
        $f_date = date('l, M j, Y', strtotime($date));
        $f_time = date('h:i A', strtotime($time));

        $mail->Body = "
            <div style='font-family: sans-serif; color: #333; max-width: 600px; border: 1px solid #dc3545; padding: 25px; border-radius: 12px; margin: 0 auto;'>
                <h2 style='color: #dc3545;'>Sincere Apologies ✨</h2>
                <p>Hello <strong>$recipientName</strong>,</p>
                <p>We are writing to deeply apologize. Unfortunately, due to an unavoidable administrative conflict, we have had to cancel your appointment for <strong>$service</strong> on $f_date at $f_time.</p>
                
                <div style='background: #fff5f5; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                    <p style='margin: 0; color: #721c24;'>We understand your time is valuable and sincerely regret any inconvenience this causes to your schedule.</p>
                </div>

                <p>We would love to make this right. Please log in to your dashboard to choose another time, or reply to this email so we can assist you personally.</p>
                
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #888; text-align: center;'>Elvis Midega Beauty Salon | Excellence in Service</p>
            </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>