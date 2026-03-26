<?php
// actions/cancel_action.php
session_start();
require '../config/db.php';
require '../includes/logger.php';
logApiRequest($pdo, 'cancel_action.php');

// Include PHPMailer classes
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- START OF API TRACKING UPDATE ---
try {
    $endpoint_name = 'cancel_action.php';
    $log_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $log_user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

    $log_stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
    $log_stmt->execute([$endpoint_name, $log_user_id, $log_user_role]);
} catch (Exception $e) {
    // Prevent logging errors from stopping the cancellation process
}
// --- END OF API TRACKING UPDATE ---

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $appt_id = $_POST['appointment_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 1. Fetch details BEFORE updating for email data
        $info_stmt = $pdo->prepare("
            SELECT u.email, u.full_name, s.service_name, a.appointment_date, a.appointment_time
            FROM users u
            JOIN appointments a ON u.user_id = a.customer_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.appointment_id = ? AND a.customer_id = ?
        ");
        $info_stmt->execute([$appt_id, $user_id]);
        $details = $info_stmt->fetch();

        if ($details) {
            // 2. Update status to Cancelled.
            $sql = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND customer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$appt_id, $user_id]);

            // 3. Send Email Notifications
            $formatted_date = date('M d, Y', strtotime($details['appointment_date']));
            $formatted_time = date('h:i A', strtotime($details['appointment_time']));

            // To Customer (Dynamic Email)
            sendCancelEmail($details['email'], $details['full_name'], $details['service_name'], $formatted_date, $formatted_time, 'customer');
            
            // To Admin (Fixed Email)
            sendCancelEmail('ochiengengineer17@gmail.com', 'Admin', $details['service_name'], $formatted_date, $formatted_time, 'admin', $details['full_name']);
        }

        header("Location: ../customer/dashboard.php?success=Appointment_Cancelled");
        exit();

    } catch (PDOException $e) {
        error_log("Cancellation Error: " . $e->getMessage());
        header("Location: ../customer/dashboard.php?error=Database_Error");
        exit();
    }
}
header("Location: ../index.php");

// --- EMAIL HELPER FUNCTION ---
function sendCancelEmail($recipientEmail, $recipientName, $service, $date, $time, $type, $clientName = '') {
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
        
        if ($type === 'customer') {
            $mail->Subject = 'Appointment Cancelled - Elvis Salon';
            $mail->Body    = "
                <div style='font-family: sans-serif; color: #333; max-width: 600px; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #dc3545;'>Cancellation Confirmed</h2>
                    <p>Hello $recipientName, your appointment for <strong>$service</strong> on $date at $time has been successfully cancelled.</p>
                    <p>We hope to see you again soon. You can book a new session anytime from your dashboard.</p>
                </div>";
        } else {
            $mail->Subject = 'Alert: Appointment Cancelled - Elvis Salon';
            $mail->Body    = "<h2>Customer Cancellation Notification</h2>
                              <p>Customer <strong>$clientName</strong> has cancelled their <strong>$service</strong> booking.</p>
                              <p>Original Schedule: $date at $time.</p>";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>