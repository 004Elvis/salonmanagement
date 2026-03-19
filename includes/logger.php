<?php
// includes/logger.php

function logApiRequest($pdo, $endpoint) {
    // If $pdo is null or not an object, stop immediately to avoid a fatal error
    if (!$pdo) {
        error_log("Logging Error: PDO connection variable is missing.");
        return;
    }

    try {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

        $stmt = $pdo->prepare("INSERT INTO api_logs (endpoint_name, user_id, user_role) VALUES (?, ?, ?)");
        $stmt->execute([$endpoint, $user_id, $user_role]);
    } catch (Exception $e) {
        // This will print the error to your XAMPP/Apache error log
        error_log("API Log Database Failure: " . $e->getMessage());
    }
}