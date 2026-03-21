<?php
// admin/api/complete_appointment.php

// Updated path: go up two levels (../../) to reach the root config folder
require '../../config/db.php'; 
session_start();
require '../../includes/dompdf/autoload.inc.php';
require '../../includes/logger.php';
logApiRequest($pdo, 'complete_appointment.php');

// Include PHPMailer classes
require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Header for JSON response
header('Content-Type: application/json');

// Security: Only Admins can complete appointments
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    try {
        // --- FETCH DETAILS FOR THANK YOU EMAIL & RECEIPT ---
        $info_stmt = $pdo->prepare("
            SELECT u.email, u.full_name, s.service_name, s.price_kes 
            FROM appointments a
            JOIN users u ON a.customer_id = u.user_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.appointment_id = ?
        ");
        $info_stmt->execute([$appointment_id]);
        $details = $info_stmt->fetch();

        // Update status to 'Completed' 
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);

        if ($stmt->rowCount() > 0) {
            
            // --- TRIGGER THANK YOU EMAIL WITH RECEIPT INFO ---
            if ($details) {
                sendThankYouEmail($details['email'], $details['full_name'], $details['service_name'], $details['price_kes'], $appointment_id);
            }

            echo json_encode(['success' => true, 'message' => 'Appointment marked as completed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made. Check if the ID is correct.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing ID.']);
}

// --- THANK YOU EMAIL FUNCTION ---
function sendThankYouEmail($recipientEmail, $recipientName, $service, $price, $app_id) {
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

        $mail->Subject = 'Your Receipt & Thank You - Elvis Midega Salon! ❤️';

        $mail->Body = "
            <div style='font-family: sans-serif; color: #333; max-width: 600px; border: 1px solid #d4af37; padding: 25px; border-radius: 12px; margin: 0 auto;'>
                <h2 style='color: #d4af37; text-align: center;'>You Look Amazing! ✨</h2>
                <p>Hello <strong>$recipientName</strong>,</p>
                <p>Thank you for choosing <strong>Elvis Midega Salon</strong> today. Your appointment is now complete!</p>
                
                <div style='background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px dashed #ccc; margin: 20px 0;'>
                    <h4 style='margin-top: 0;'>Visit Summary:</h4>
                    <p style='margin: 5px 0;'><strong>Service:</strong> $service</p>
                    <p style='margin: 5px 0;'><strong>Amount Paid:</strong> KES " . number_format($price, 2) . "</p>
                    <p style='margin: 5px 0;'><strong>Receipt No:</strong> #" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . "</p>
                </div>

                <div style='text-align: center; margin: 25px 0;'>
                    <a href='http://localhost/elvis_salon/actions/download_receipt.php?id=$app_id' 
                       style='background-color: #d4af37; color: white; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 5px;'>
                       Download PDF Receipt
                    </a>
                </div>

                <p style='font-size: 14px; text-align: center;'>We hope you love your new look! Ready for your next glow-up?</p>
                <p style='text-align: center;'><a href='http://localhost/elvis_salon/customer/book.php' style='color: #d4af37; font-weight: bold;'>Book Your Next Session →</a></p>

                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #888; text-align: center;'>Elvis Midega Beauty Salon | Nairobi, Kenya</p>
            </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}