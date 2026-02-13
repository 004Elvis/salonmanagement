<?php
// Database Credentials
$host = 'localhost';
$dbname = 'elvis_salon_db';
$username = 'root'; // Default XAMPP user
$password = '';     // Default XAMPP password

try {
    // Create PDO instance with error mode set to Exception
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Start Session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>