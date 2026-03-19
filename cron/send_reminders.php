<?php
// cron/send_reminders.php
date_default_timezone_set('Africa/Nairobi');
require '../config/db.php';

// Include PHPMailer
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Fetch appointments happening exactly tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$stmt = $pdo->prepare("
    SELECT a.appointment_id, u.email, u.full_name, s.service_name, a.appointment_time, st.full_name as staff_name
    FROM appointments a
    JOIN users u ON a.customer_id = u.user_id
    JOIN users st ON a.staff_id = st.user_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.appointment_date = ? AND a.status = 'Confirmed'
");
$stmt->execute([$tomorrow]);
$reminders = $stmt->fetchAll();

if (count($reminders) > 0) {
    $mail = new PHPMailer(true);
    
    // SMTP Settings (Shared for the whole loop)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ochiengengineer17@gmail.com';
    $mail->Password   = 'ohncquyyclverdfg'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Midega Salon');
    $mail->isHTML(true);

    foreach ($reminders as $row) {
        try {
            $mail->clearAddresses(); // CRITICAL: Clear previous recipient
            $mail->addAddress($row['email'], $row['full_name']);
            
            $mail->Subject = 'Reminder: Your Appointment Tomorrow - Elvis Salon';
            
            $time = date('h:i A', strtotime($row['appointment_time']));
            
            $mail->Body = "
                <div style='font-family: sans-serif; color: #333; max-width: 600px; border: 2px solid #d4af37; padding: 25px; border-radius: 12px;'>
                    <h2 style='color: #d4af37;'>See You Tomorrow! 👋</h2>
                    <p>Hello <strong>{$row['full_name']}</strong>,</p>
                    <p>This is a friendly reminder of your scheduled beauty session tomorrow at <strong>Elvis Midega Salon</strong>.</p>
                    
                    <div style='background: #fffcf5; padding: 20px; border-radius: 8px; border: 1px solid #f9f1dc; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Service:</strong> {$row['service_name']}</p>
                        <p style='margin: 5px 0;'><strong>Time:</strong> $time</p>
                        <p style='margin: 5px 0;'><strong>Staff:</strong> {$row['staff_name']}</p>
                    </div>

                    <p>Please remember to arrive 10 minutes early. We look forward to serving you!</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <small>Nairobi, Kenya | +254 700 000 000</small>
                </div>";

            $mail->send();
            echo "Reminder sent to: " . $row['email'] . "<br>";
        } catch (Exception $e) {
            echo "Failed for " . $row['email'] . ": " . $mail->ErrorInfo . "<br>";
        }
    }
} else {
    echo "No appointments scheduled for tomorrow ($tomorrow).";
}
?>