<?php
// actions/register_action.php

// 1. Include Database Connection
require '../config/db.php';

// 2. Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Sanitize and Collect Inputs
    // We use trim() to remove accidental spaces before/after text
    $full_name = trim($_POST['full_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $question_id = $_POST['security_question_id'];
    $security_answer = strtolower(trim($_POST['security_answer'])); // Normalize answer to lowercase for consistency

    // 4. Basic Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($security_answer)) {
        header("Location: ../register.php?error=All fields are required");
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Passwords do not match");
        exit();
    }

    try {
        // 5. Check if Email Already Exists
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->rowCount() > 0) {
            header("Location: ../register.php?error=Email already registered");
            exit();
        }

        // 6. Security Hashing
        // Hash the password using Bcrypt (Default)
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Hash the security answer so even admins can't see it (Zero Trust)
        $security_answer_hash = password_hash($security_answer, PASSWORD_DEFAULT);

        // 7. Insert New User into Database
        // Role ID 3 = Customer (as defined in your 'roles' table seed data)
        $role_id = 3; 

        $sql = "INSERT INTO users (full_name, email, phone, password_hash, role_id, security_question_id, security_answer_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $full_name, 
            $email, 
            $phone, 
            $password_hash, 
            $role_id, 
            $question_id, 
            $security_answer_hash
        ]);

        // 8. Success Redirect
        header("Location: ../index.php?success=Account created successfully! Please login.");
        exit();

    } catch (PDOException $e) {
        // Handle Database Errors
        header("Location: ../register.php?error=Registration failed: " . $e->getMessage());
        exit();
    }

} else {
    // If someone tries to open this file directly without submitting the form
    header("Location: ../register.php");
    exit();
}
?>