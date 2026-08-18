# DIWA Center - Conference Room Reservation System

A modern, responsive, single-facility web application built with **PHP 8.3+**, **MySQL/MariaDB**, **Bootstrap 5**, **FullCalendar 6**, and **PHPMailer**. 

Designed specifically for the **DIWA Center Conference Room**, eliminating unnecessary multi-room dropdowns while providing atomic schedule conflict prevention, real-time availability checks, email notifications, responsive mobile views, and a comprehensive admin management portal.

---

## 🌟 Key Features

### 📅 Public Reservation Portal
- **Streamlined Booking Form**:
  - Requester Name
  - Email Address
  - Project / Team / Office
  - Purpose of Meeting / Activity
  - Date, Start Time, and End Time
- **Real-Time Availability Indicator**: Live AJAX validation instantly checks date and time inputs against existing bookings.
- **Interactive Time Slot Grid**: View occupied vs. available hourly slots for any date. Clicking an occupied slot opens a detail modal showing meeting details.
- **Atomic Double-Booking Prevention**: Strict database transaction locking (`start_time < requested.end_time AND end_time > requested.start_time`) backed by `SELECT ... FOR UPDATE` to guarantee zero overlapping reservations.
- **Fully Responsive Footer & UI**: Responsive design across mobile, tablet, and desktop viewports.

### ✉️ Automated Email Notifications
- **SMTP Email Engine**: Powered by PHPMailer for reliable delivery.
- **Responsive Email Templates**: Clean HTML notifications for booking confirmations and cancellations.
- **Audit Logging**: All email attempts are tracked in `email_logs` table. Email failures do not block reservations, allowing administrators to manually re-send notifications from the dashboard.

### 🛡️ Admin Portal (`/admin`)
- **Dashboard Overview**: Metrics for Today's Bookings, Upcoming Bookings, Total Confirmed, and Total Cancelled.
- **Interactive Calendar**: FullCalendar 6 integration with Month, Week, and Day views.
- **Reservation Management**: Filter, search, inspect detailed meeting info, cancel reservations, or re-trigger email notifications.
- **Email Delivery Logs**: Monitor email status (`SENT` / `FAILED`) with 1-click manual resend.
- **Security Hardening**: Session regeneration, password hashing (`password_hash()`), CSRF token validation on form submissions, and SQL injection prevention via PDO prepared statements.

---

## 📂 Project Structure

```
conference_reservation/
├── admin/                     # Admin Portal
│   ├── index.php              # Dashboard summary & metrics
│   ├── calendar.php           # FullCalendar interactive view
│   ├── reservations.php       # Reservation listing & management
│   ├── reservation-view.php   # Detailed reservation view
│   ├── email-logs.php         # Email audit log & resend
│   ├── login.php              # Admin authentication
│   └── logout.php             # Admin logout handler
├── api/                       # AJAX endpoints
│   ├── check_availability.php # Real-time availability check
│   ├── cancel_reservation.php# Reservation cancellation API
│   └── submit_reservation.php# Reservation submission API
├── assets/                    # Static Assets
│   ├── css/style.css          # Custom responsive CSS & brand theme
│   ├── js/app.js              # Client-side validation & AJAX logic
│   └── images/                # Logos & assets
├── config/                    # Application Configuration
│   ├── database.php           # Database connection (PDO)
│   ├── config.php             # General settings & constants
│   └── mail.php               # PHPMailer SMTP credentials
├── database/                  # SQL Database Files
│   └── schema.sql             # Table structures & initial seed data
├── includes/                  # Reusable PHP Components
│   ├── header.php             # Public header template
│   ├── footer.php             # Responsive public footer template
│   ├── admin_header.php       # Admin navigation header
│   ├── admin_footer.php       # Admin script footer
│   ├── availability.php       # Slot checking functions
│   ├── email.php              # PHPMailer integration
│   ├── reservation.php        # Database reservation queries
│   └── functions.php          # Helper & sanitization functions
├── templates/                 # Email Templates
│   └── emails/                # Confirmation & cancellation HTML emails
├── .github/workflows/         # CI/CD & Deployment
│   └── deploy.yml             # Automated FTP deployment to InfinityFree
├── index.php                  # Public Homepage & Schedule Grid
├── reserve.php                # Booking Request Form
├── success.php                # Booking Confirmation Screen
└── conflict.php               # Schedule Overlap Warning Screen
```

---

## 🚀 How to Run Locally

### Requirements
- **PHP**: 8.1 or higher
- **Web Server**: Apache / Nginx (Laragon, XAMPP, or WAMP recommended)
- **Database**: MySQL 5.7+ / MySQL 8.0+ or MariaDB 10.2+

### Step-by-Step Setup

1. **Clone or Download the Project**:
   ```bash
   git clone https://github.com/tomi-up/diwa_conference_reservation.git
   cd diwa_conference_reservation
   ```

2. **Import Database Schema**:
   Import `database/schema.sql` into MySQL using phpMyAdmin or the command line:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Configure Database & Mail Settings**:
   Edit `config/database.php` and set your local MySQL credentials:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'conference_reservation');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

   *(Optional)* Configure SMTP settings in `config/mail.php` for email delivery.

4. **Run the Application**:
   - Open your browser and navigate to `http://localhost/conference_reservation` (or your local vhost URL).

5. **Default Admin Login Credentials**:
   - **URL**: `http://localhost/conference_reservation/admin/login.php`
   - **Email**: `admin@example.com`
   - **Password**: `AdminPassword123!`

---

## 📖 How to Use

### For General Users (Public)
1. **Check Room Availability**:
   - Visit the homepage to view the interactive schedule matrix for any selected date.
2. **Submit a Booking Request**:
   - Click **Book a Reservation**.
   - Fill in requester details, meeting date, start time, and end time.
   - The form live-evaluates time slot availability.
   - Click **Submit Reservation**. Upon success, a confirmation message and notification email will be dispatched.

### For Administrators
1. **Login**: Access `/admin/login.php` using admin credentials.
2. **Dashboard**: View today's schedule, upcoming reservations, and total reservation statistics.
3. **Calendar View**: Navigate to **Calendar** for visual monthly/weekly/daily schedule management.
4. **Manage Reservations**:
   - Navigate to **Reservations** to filter by status (`CONFIRMED`, `CANCELLED`).
   - Click on any booking to view full details or cancel the reservation.
5. **Monitor Emails**: Navigate to **Email Logs** to verify sent notifications or trigger manual resends.

---

## ⚡ Automated Deployment to InfinityFree

This project includes a **GitHub Actions FTP workflow** (`.github/workflows/deploy.yml`) that automatically deploys your latest code to **InfinityFree** every time you push to the `main` branch.

### Setup Instructions

1. Obtain your FTP credentials from your **InfinityFree Client Area** (**Account Details** $\rightarrow$ **FTP Details**):
   - **FTP Server**: `ftpupload.net` (or provided host)
   - **FTP Username**: `if0_XXXXXXXX`
   - **FTP Password**: *(Account Password)*

2. Add credentials to GitHub Secrets:
   - Go to your GitHub repo: **Settings** $\rightarrow$ **Secrets and variables** $\rightarrow$ **Actions**
   - Add three repository secrets:
     - `FTP_SERVER`
     - `FTP_USERNAME`
     - `FTP_PASSWORD`

3. Push changes to GitHub:
   ```bash
   git add .
   git commit -m "Update README and project files"
   git push origin main
   ```
   GitHub Actions will automatically sync modified files to InfinityFree's `htdocs/` folder!

---

## 📄 License
Developed for DIWA Center Conference Services. All rights reserved.
