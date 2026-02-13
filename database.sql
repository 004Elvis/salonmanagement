-- Create Database
CREATE DATABASE IF NOT EXISTS elvis_salon_db;
USE elvis_salon_db;

-- 1. Roles Table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL -- 'Admin', 'Staff', 'Customer'
);

-- 2. Security Questions (Zero Trust)
CREATE TABLE security_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    question_text VARCHAR(255) NOT NULL
);

-- 3. Users Table (Centralized User Management)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT,
    security_question_id INT,
    security_answer_hash VARCHAR(255), -- Hashed for security
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (security_question_id) REFERENCES security_questions(question_id)
);

-- 4. Services Table
CREATE TABLE services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    duration_minutes INT NOT NULL,
    price_kes DECIMAL(10, 2) NOT NULL,
    description TEXT
);

-- 5. Appointments Table
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    staff_id INT,
    service_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'No-show') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id),
    FOREIGN KEY (staff_id) REFERENCES users(user_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);

-- 6. Login Logs (Security Auditing)
CREATE TABLE login_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    status ENUM('Success', 'Failed'),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- SEED DATA ---------------------------------------------------

-- Roles
INSERT INTO roles (role_name) VALUES ('Admin'), ('Staff'), ('Customer');

-- Security Questions
INSERT INTO security_questions (question_text) VALUES 
('What is your mother''s maiden name?'),
('What was the name of your first pet?'),
('What city were you born in?');

-- Admin User (Password: 'admin123' - Change immediately)
INSERT INTO users (full_name, email, password_hash, role_id, phone) 
VALUES ('Elvis Midega', 'elvismidega@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '0700000000'); 

-- Kenyan Staff
INSERT INTO users (full_name, email, password_hash, role_id, phone) VALUES 
('Achieng Odhiambo', 'achiengstaff@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0711111111'),
('Wanjiku Kamau', 'wanjikustaff@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0722222222'),
('Kevin Otieno', 'kevinstaff@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0733333333'),
('Zainab Ali', 'zainabstaff@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0744444444'),
('Brian Kiprop', 'brianstaff@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0755555555'),
('Mercy Chebet', 'mercystaff@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0766666666');

-- Services (KES)
INSERT INTO services (service_name, duration_minutes, price_kes) VALUES 
('Full Manicure', 45, 1500.00),
('Gel Pedicure', 60, 2000.00),
('Swedish Massage', 60, 3500.00),
('Braiding (Knotless)', 180, 4500.00),
('Standard Haircut', 30, 500.00),
('Facial Treatment', 45, 2500.00);