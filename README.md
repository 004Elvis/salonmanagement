# Elvis Midega Beauty Salon Management System

A comprehensive, professional web application designed to streamline salon operations. This system manages client bookings, staff schedules, and financial analytics with a focus on Role-Based Access Control (RBAC) and a modern, responsive user experience.

## 🚀 Key Features
- **Client Side:** Appointment booking, profile management, and automated PDF receipts.
- **Staff Side:** Performance tracking, shift management, and automated client email notifications.
- **Admin Side:** Real-time business analytics, service profitability tracking, and professional financial reporting (PDF export).
- **System-Wide:** Light/Dark mode support, mobile-responsive UI, and session-secure logout validation.

## 🛠️ Tech Stack
- **Backend:** PHP 8.x
- **Database:** MySQL (MariaDB)
- **Frontend:** HTML5, CSS3 (Modern CSS variables), JavaScript (Vanilla)
- **Charts:** Chart.js (CDN)
- **Email:** PHPMailer
- **Icons:** FontAwesome 6

## 📦 Installation
1. Clone or download this repository into your local server directory (e.g., `htdocs` for XAMPP).
2. Import the provided `.sql` file into your MySQL database via phpMyAdmin.
3. Configure your database connection in `config/db.php`.
4. (Optional) Update PHPMailer credentials in `staff/dashboard.php` and `actions/` to your business email settings.
5. Access the project via `localhost/elvis_salon/index.php`.

---
*Developed by Elvis Midega - 2026*