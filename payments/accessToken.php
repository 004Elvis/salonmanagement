<?php
// Use Sandbox credentials from Safaricom Daraja Portal
$consumerKey = env("CONSUMERKEY") 
$consumerSecret = env("CONSUMERSECRET")

$headers = ['Content-Type:application/json; charset=utf8'];
$url = env("URL")

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);

$result = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$result = json_decode($result);

// Error Handling
if ($status != 200) {
    die("Failed to get Access Token. Check your Credentials. HTTP Status: " . $status);
}

$access_token = $result->access_token;
// echo $access_token; // Uncomment this to test if it prints a long string
?>