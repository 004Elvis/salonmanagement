<?php
header("Content-Type: application/json");
include '../config/db.php'; // Ensure this points to your database connection file

// 1. Receive the raw data from Safaricom
$stk_response = file_get_contents('php://input');

// 2. Log it to a file for debugging (Check MpesaResponse.json if things don't work)
$logFile = __DIR__ . "/MpesaResponse.json";
$log = fopen($logFile, "a");
fwrite($log, $stk_response . PHP_EOL);
fclose($log);

$data = json_decode($stk_response);

// 3. Extract the essential IDs
$resultCode = $data->Body->stkCallback->ResultCode;
$checkoutRequestID = $data->Body->stkCallback->CheckoutRequestID;

// 4. Check if the transaction was successful (ResultCode 0)
if ($resultCode == 0) {
    // Extract metadata details
    // Note: The index [0, 1, 2, 3] can sometimes vary in Sandbox, 
    // but typically: 0=Amount, 1=Receipt, 3=Date
    $amount = $data->Body->stkCallback->CallbackMetadata->Item[0]->Value;
    $mpesaReceiptNumber = $data->Body->stkCallback->CallbackMetadata->Item[1]->Value;

    // 5. Update the Database
    $status = 'Paid';
    $updateSql = "UPDATE appointments SET payment_status = ?, mpesa_receipt = ? WHERE checkout_id = ?";
    
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "sss", $status, $mpesaReceiptNumber, $checkoutRequestID);
    
    if (mysqli_stmt_execute($stmt)) {
        // Success: Database updated
    } else {
        // Log database error to your file
        error_log("DB Update Failed for CheckoutID: " . $checkoutRequestID);
    }
} else {
    // Transaction failed or was cancelled by the user
    $status = 'Failed';
    $updateSql = "UPDATE appointments SET payment_status = ? WHERE checkout_id = ?";
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "ss", $status, $checkoutRequestID);
    mysqli_stmt_execute($stmt);
}

// 6. Respond to Safaricom (They expect a 200 OK)
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
?>