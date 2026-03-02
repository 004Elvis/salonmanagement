Purpose: Overview for anyone looking at the project for the first time.

# Elvis Salon Management System

A comprehensive web-based salon booking and management platform built for Zetech University.

## 🚀 Features
- **Admin Panel:** Oversight of services, staff, and system-wide appointments.
- **Staff Dashboard:** Real-time request approval, personal schedule, and performance tracking.
- **Customer Portal:** Easy booking engine with staff and service selection.
- **Security:** Role-based access, password hashing, and PDO-based SQL protection.

## 🛠️ Technology Stack
- **Backend:** PHP 8.x
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** Apache (XAMPP/WAMP)



## 📂 Project Structure
- `/config`: Database connection logic.
- `/includes`: Reusable authentication and role checks.
- `/staff`: Staff-specific dashboard and availability logic.
- `/admin`: Administrative controls.
- `/uploads`: Storage for user profile pictures.

## ⚙️ Setup Instructions
1. Clone the repo to your `htdocs` folder.
2. Import `salon_db.sql` into phpMyAdmin.
3. Configure your credentials in `config/db.php`.
4. Ensure the `/uploads` folder has write permissions.