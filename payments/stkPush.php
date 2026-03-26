<?php
include 'accessToken.php';
include '../config/db.php';

// 1. Read JSON input sent from the frontend (fetch with JSON.stringify)
$input = json_decode(file_get_contents('php://input'), true);

$phone        = $input['phone']          ?? '';
$amount       = $input['amount']         ?? '1';
$appointment_id = $input['appointment_id'] ?? '';

// Normalise phone: strip +/spaces, convert 07... or 7... to 2547...
$phone = str_replace(['+', ' '], '', $phone);
if (substr($phone, 0, 1) === '0') {
    $phone = '254' . substr($phone, 1);
} elseif (substr($phone, 0, 1) === '7') {
    $phone = '254' . $phone;
}

// 2. Prepare M-Pesa STK Push request (Sandbox credentials)
$processurl      = env("PROCESSURL")
$callbackurl     = env("CALLBACKURL");
$passkey         = env("PASSKEY");
$BusinessShortCode = env("BUSINESSSHORTCODE");
$Timestamp       = date('YmdHis');
$Password        = base64_encode($BusinessShortCode . $passkey . $Timestamp);

$post_data = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password'          => $Password,
    'Timestamp'         => $Timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => $BusinessShortCode,
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $callbackurl,
    'AccountReference'  => 'ElvisSalon',
    'TransactionDesc'   => 'Service Payment ID: ' . $appointment_id,
];

// 3. Send CURL request to Safaricom
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $processurl);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token,
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response);

// 4. Save CheckoutRequestID to the database so callback.php can match it later
if (isset($result->CheckoutRequestID) && !empty($appointment_id)) {
    $checkoutID = $result->CheckoutRequestID;

    $updateSql = "UPDATE appointments SET checkout_id = ? WHERE appointment_id = ?";
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "si", $checkoutID, $appointment_id);
    mysqli_stmt_execute($stmt);
}

// 5. Return Safaricom's response to the frontend
header('Content-Type: application/json');
echo $response;
?>
