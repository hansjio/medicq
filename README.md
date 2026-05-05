# MEDICQ - Medical Appointment Management System

A PHP/MySQL-based medical appointment booking system for software engineering projects.

## Features

### Patient Portal
- Dashboard with appointment statistics
- Book appointments (4-step wizard)
- View and manage appointments
- Profile settings

### Doctor Portal
- Dashboard with today's appointments
- Manage assigned appointments
- Set weekly availability schedule
- Block specific time slots
- Profile settings

### Admin Portal
- System overview dashboard
- Manage doctors (add, edit, delete)
- Manage patients
- Manage all appointments
- Configure doctor schedules

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server

## Installation

### 1. Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE medicq;
```

2. Import the schema with sample data:
```bash
mysql -u your_username -p medicq < database/medicq_schema.sql
```

Or use phpMyAdmin to import `database/medicq_schema.sql`

### 2. Configuration

1. Edit `includes/config.php` and update database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'medicq');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Web Server Setup

#### Using XAMPP/WAMP/MAMP:
- Copy project folder to `htdocs` (XAMPP) or `www` (WAMP)
- Access via `http://localhost/medicq`

#### Using PHP Built-in Server:
```bash
cd /path/to/medicq
php -S localhost:8000
```
Access via `http://localhost:8000`

## Demo Accounts

After importing the database, use these accounts:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@medicq.com | admin123 |
| Doctor | dr.sarah@medicq.com | doctor123 |
| Doctor | dr.michael@medicq.com | doctor123 |
| Doctor | dr.emily@medicq.com | doctor123 |
| Doctor | dr.james@medicq.com | doctor123 |
| Patient | john.doe@email.com | patient123 |
| Patient | jane.smith@email.com | patient123 |
| Patient | robert.wilson@email.com | patient123 |

## Project Structure

```
medicq/
├── admin/              # Admin portal pages
│   ├── dashboard.php
│   ├── doctors.php
│   ├── patients.php
│   ├── appointments.php
│   └── schedules.php
├── api/                # API endpoints
│   ├── appointment-action.php
│   ├── get-slots.php
│   └── notifications.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── images/
│   │   ├── logo.svg
│   │   └── login-bg.jpg
│   └── js/
│       └── main.js
├── database/
│   └── medicq_schema.sql
├── doctor/             # Doctor portal pages
│   ├── dashboard.php
│   ├── appointments.php
│   ├── schedule.php
│   └── profile.php
├── includes/           # PHP includes
│   ├── config.php
│   ├── auth.php
│   ├── appointment.php
│   ├── doctor.php
│   ├── header.php
│   └── footer.php
├── patient/            # Patient portal pages
│   ├── dashboard.php
│   ├── appointments.php
│   ├── book-appointment.php
│   ├── appointment-details.php
│   └── profile.php
├── index.php           # Entry point
├── login.php
├── register.php
├── logout.php
└── README.md
```

## Database Schema

### Tables:
- `users` - All user accounts (patients, doctors, admins)
- `doctors` - Doctor-specific information
- `doctor_schedules` - Weekly availability
- `blocked_slots` - Blocked time periods
- `appointments` - All appointments
- `notifications` - System notifications

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Styling**: Custom CSS with CSS Variables

## Notes

This is a mock/demo system for educational purposes. For production use, consider:
- Implementing email/SMS notifications
- Adding payment gateway integration
- Implementing real video call functionality
- Adding more robust security measures
- Setting up proper session management
- Adding CSRF protection
