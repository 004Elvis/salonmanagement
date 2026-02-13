<?php
// 1. Enable Error Reporting (For debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Start Session (To get the logged-in user's ID)
session_start();

// 3. Include Database & PHPMailer
require '../config/db.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// --- MAIN LOGIC STARTS HERE ---

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // A. Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Error: You must be logged in to book an appointment.");
    }

    // B. Get Form Data
    $user_id = $_SESSION['user_id'];
    // We assume your form sends 'service_id', 'staff_id', 'date', 'time'
    $service_id = $_POST['service_id']; 
    $staff_id   = $_POST['staff_id'];
    $date       = $_POST['date']; 
    $time       = $_POST['time'];
    
    // Combine Date and Time for DB (if your DB uses DATETIME format)
    // If your DB has separate columns, change this part.
    // $appointment_datetime = "$date $time"; 

    try {
        // C. Insert into Database
        // Note: Update 'appointments' and column names to match your actual database table!
      // Corrected SQL Query (No dots!)
$sql = "INSERT INTO appointments (customer_id, service_id, staff_id, appointment_date, appointment_time, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')";

// Prepare and Execute
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $service_id, $staff_id, $date, $time]);

        // D. Send Email Reminder (Call the function below)
        // Get user email from session or fetch it if needed. Assuming it's in session:
        $user_email = $_SESSION['email'] ?? 'ochiengengineer17@gmail.com'; // Fallback for testing
        
        sendReminder($user_email, $date, $time);

        // E. Redirect to Success Page
        header("Location: ../dashboard.php?success=Booking Confirmed!");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    } catch (Exception $e) {
        die("Email Error: " . $e->getMessage());
    }
} else {
    // If accessed directly without submitting form
    header("Location: ../index.php");
    exit();
}

// --- EMAIL FUNCTION ---

function sendReminder($userEmail, $date, $time) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ochiengengineer17@gmail.com'; 
        
        // IMPORTANT: REPLACE THIS WITH YOUR REAL 16-CHAR APP PASSWORD
        $mail->Password   = 'ohncquyyclverdfg'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Changed to constant for safety
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('ochiengengineer17@gmail.com', 'Elvis Salon System');
        $mail->addAddress($userEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Appointment Confirmation - Elvis Salon';
        $mail->Body    = "
            <h2>Booking Confirmed!</h2>
            <p>Hello,</p>
            <p>Your appointment has been successfully booked.</p>
            <p><b>Date:</b> $date</p>
            <p><b>Time:</b> $time</p>
            <p>Thank you for choosing Elvis Midega Salon!</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        // Just log the error, don't stop the script
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>