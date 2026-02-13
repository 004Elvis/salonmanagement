<?php
// Start the session to access user data (like user_id and role)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is NOT logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect them to the login page immediately
    header("Location: ../index.php?error=Access Denied. Please Login.");
    exit(); // Stop loading the rest of the page
}

// Optional: Function to restrict access by role
// Usage: checkRole(['Admin']); or checkRole(['Admin', 'Staff']);
function checkRole($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // If user's role is not in the allowed list, send them back
        header("Location: ../index.php?error=Unauthorized Access");
        exit();
    }
}
?>