# Conference Room Reservation System (Single Room Edition)

A clean, streamlined web application built in **PHP 8.3+** and **MySQL/MariaDB** with **Bootstrap 5**, **FullCalendar 6**, **PDO**, and **PHPMailer**.

Designed specifically for single-facility office environments (such as the **DIWA Center Conference Room**), eliminating unnecessary room selection dropdowns while providing automatic schedule conflict validation, live real-time availability badges, automated email notifications, and an administrator dashboard.

---

## Key Features

### Simplified Public Reservation Workflow
- **7-Field Streamlined Form**:
  - Name of Requesting Personnel *
  - Email Address *
  - Project / Team / Office *
  - Purpose of Meeting / Activity *
  - Date *
  - Start Time *
  - End Time *
- **Live Real-time Availability Indicator**: Instantly evaluates date and time inputs via AJAX:
  - 🟢 `Available — 9:00 AM to 11:00 AM`
  - 🔴 `Unavailable — This schedule overlaps with an existing reservation.`
- **Atomic Double-Booking Prevention**: Strict transactional locking (`start_time < requested.end_time AND end_time > requested.start_time`) backed by MySQL `FOR UPDATE`.
- **Public Schedule Matrix**: View open/occupied hourly slots for any date without exposing confidential requester names or meeting topics.

### Email Notification Engine
- **PHPMailer SMTP Integration**: Configured server-side.
- **Canva PNG Inline CID Signature**: Embedded via Content-ID (`cid:email_signature`) so signatures render properly inside all email clients without relying on external web hosting.
- **Audit Logs & Manual Resend**: All delivery attempts are logged in `email_logs`. Email failures never delete reservations, enabling admins to manually re-trigger notifications.

### Admin Dashboard & Management
- **Secure Authentication**: Admin sessions using `password_hash()` and `password_verify()` with session regeneration.
- **Dashboard Overview**: Metrics for Today's, Upcoming, Total Confirmed, and Total Cancelled reservations.
- **Conference Room Calendar**: FullCalendar 6 interactive view with Month, Week, and Day views and detailed event modals.
- **Reservations Management**: Searchable and filterable table with full details, cancellation, rejection (with custom reason), and email resend controls.
- **System Settings & Signature**: Configurable organization metadata and signatures.
- **Admin User Accounts**: Create and manage administrator accounts.

---

## Technology Stack

- **Backend**: PHP 8.3+ (Native Sessions, PDO)
- **Database**: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.2+
- **Mail Engine**: PHPMailer (v6.x) via SMTP
- **Frontend UI**: Bootstrap 5, Bootstrap Icons, Vanilla JavaScript
- **Calendar**: FullCalendar 6 (CDN)

---

## Simplified Database Schema

- **`admins`**: Administrator authentication credentials.
- **`reservations`**: Stores requester info, date, time slots, and status (`CONFIRMED`, `REJECTED`, `CANCELLED`).
- **`email_logs`**: Audit trail for all email send attempts (`SENT` / `FAILED`).
- **`system_settings`**: Organization details and email signatures.

---

## Local Development Setup

### 1. Import Database Schema
Create the MySQL database and schema using `database/schema.sql`:

```bash
mysql -u root -p < database/schema.sql
```

### 2. Configuration
Copy `config/config.example.php` to `config/config.php` and set your local database credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'conference_reservation');
define('DB_USER', 'root');
define('DB_PASS', '');

define('CONFERENCE_ROOM_NAME', 'DIWA Center Conference Room');
```

### 3. Default Admin Credentials
- **Email**: `admin@example.com`
- **Password**: `AdminPassword123!`

### 4. Run the Automated Test Suite
```bash
php test_suite.php
```
*Executes all 20 automated verification tests (validation, double-booking prevention, atomic locking, CSRF, SQLi, XSS).*
