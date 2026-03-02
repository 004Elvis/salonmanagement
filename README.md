Elvis Salon Management System Documentation
1. Executive Summary
The Elvis Salon Management System is a comprehensive web-based platform designed to automate salon operations. It bridges the gap between clients seeking beauty services and salon staff managing their schedules. The system provides three distinct interfaces: an Admin Panel for business oversight, a Staff Dashboard for appointment management, and a Customer Portal for easy booking.

2. System Architecture
The project is built using the LAMP stack pattern, focusing on security, simplicity, and speed.

Frontend: HTML5, CSS3, and JavaScript (Vanilla).

Backend: PHP 8.x (Procedural and Object-Oriented patterns).

Database: MySQL (Relational).

Database Connectivity: PDO (PHP Data Objects) to prevent SQL Injection.

Authentication: Session-based login with Role-Based Access Control (RBAC).

3. Database Design
The system uses a relational database structure to ensure data integrity.

Core Tables:
users: Stores credentials and profiles. Roles include Admin, Staff, and Customer.

services: Contains list of services (e.g., Haircut, Manicure), prices, and duration.

appointments: The central table linking customers, staff, and services.

Columns: appointment_id, customer_id, staff_id, service_id, date, time, status.

categories: Groups services for better navigation.

4. System Modules
A. Admin Module
The Admin acts as the system controller with the following capabilities:

User Management: Create, update, or deactivate Staff and Customer accounts.

Service Management: Add new salon services, set prices, and assign categories.

Global Overview: View all appointments across the entire salon to ensure no double-bookings.

Reporting: Access total revenue data and salon performance metrics.

B. Staff Module
Designed for the service providers (Barbers, Stylists, etc.):

Appointment Handling: Real-time notification of "Pending" requests. Staff can Accept (Confirm) or Decline (Cancel).

Personal Schedule: A dedicated view of only the appointments assigned to them.

Performance Tracker: A visual bar graph showing weekly appointment counts.

Availability Management: Tools to set working hours or days off.

C. Customer Module
The client-facing interface focused on user experience:

Booking Engine: Select a service, choose a preferred staff member, and pick an available time slot.

Appointment History: Track the status of past and upcoming bookings.

Profile Management: Update contact information and profile pictures.

5. Security Protocols
To ensure the safety of user data, the following measures are implemented:

Password Hashing: Uses password_hash() and password_verify() for secure credential storage.

Prepared Statements: All SQL queries use PDO prepared statements to block SQL Injection attacks.

Session Validation: Every page includes an auth_check.php file to ensure users cannot bypass the login screen via direct URL access.

Role Validation: A custom checkRole() function prevents a Customer from accessing Admin or Staff pages.

6. Technical Setup & Installation
Prerequisites:
Local Server (XAMPP / WAMP / Laragon).

PHP 8.0 or higher.

MySQL Database.

Installation Steps:
Database Import: Import the salon_db.sql file into your MySQL server.

Configuration: Update config/db.php with your database credentials:

PHP
$host = 'localhost';
$db   = 'salon_db';
$user = 'root';
$pass = '';
Folder Permissions: Ensure the uploads/ directory has write permissions for profile picture storage.

7. Future Enhancements
M-Pesa Integration: Automating payments for confirmed appointments.

Automated Reminders: SMS notifications sent to customers 1 hour before their appointment.

Rating System: Allowing customers to review specific staff members after a service is completed.