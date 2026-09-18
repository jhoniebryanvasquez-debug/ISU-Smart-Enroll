# ISU SmartEnroll

ISU SmartEnroll is a PHP and MySQL enrollment guide and decision-support system for
Isabela State University - Cauayan Campus. It provides students with enrollment
guidance and gives authorized staff a small administration portal for maintaining
student records, programs, requirements, and administrator accounts.

> **Project status:** This is a local development project. The enrollment guide is
> informational and does not replace the official enrollment process or university
> approval.

## Features

### Public enrollment guide

- Responsive landing page at `index.php`
- Enrollment questionnaire at `enrollment.php`
- Guidance result page at `result.php`
- Student categories for first-year, regular/continuing, irregular, transferee,
  and returning students
- Undergraduate and graduate program selection
- Requirements reference page

### Administration portal

- Session-protected administrator login
- Dashboard at `admin_dashboard.php`
- Administrator and staff account management
- Student record management, including add, edit, list, and delete operations
- Academic program management, including add, edit, list, and delete operations
- Enrollment requirement management, including add, edit, list, and delete operations

## Technology stack

- PHP 7.4 or later
- MySQL 5.7+ or MariaDB 10.4+
- Apache (the project is intended for XAMPP)
- HTML5 and CSS3
- MySQLi prepared statements for most database writes and lookups
- PHP sessions for administrator authentication

No Composer, Node.js, or frontend build step is required.

## Project structure

| File or directory | Purpose |
| --- | --- |
| `index.php` | Public home page |
| `enrollment.php` | Public enrollment questionnaire |
| `result.php` | Questionnaire guidance and recommendations |
| `requirements.php` | Admin requirement management page |
| `admin_login.php` | Administrator login form |
| `admin_dashboard.php` | Authenticated admin dashboard |
| `student.php` | Student record management |
| `edit_student.php` | Student record edit form |
| `programs.php` | Academic program management |
| `users.php` | Administrator and staff account management |
| `edit_user.php` | Administrator or staff account edit form |
| `db.php` | MySQL connection configuration |
| `assets/` | Logos and background images |
| `php-test.php` | PHP installation diagnostic; do not expose in production |
| `test.html` | Basic static HTML diagnostic page |

The file `students(1).php` is an older duplicate of the student management page
and is not linked by the current dashboard.

## Requirements

Install the following before running the project:

1. [XAMPP](https://www.apachefriends.org/) with Apache, PHP, and MySQL enabled.
2. A browser such as Chrome, Edge, or Firefox.
3. Access to phpMyAdmin or the MySQL command line to create the database.

## Local installation

### 1. Copy the project into XAMPP

Place the project directory in the Apache document root:

```text
C:\xampp\htdocs\ISU-Smart-Enroll
```

If the project is already in that location, no copy step is necessary.

### 2. Start Apache and MySQL

Open the XAMPP Control Panel and start:

- **Apache**
- **MySQL**

### 3. Create the database

Create a database named `isu_smartenroll` in phpMyAdmin, then run the following
SQL in that database:

```sql
CREATE DATABASE IF NOT EXISTS isu_smartenroll
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE isu_smartenroll;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff'
);

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    student_type VARCHAR(50) NOT NULL,
    educational_level VARCHAR(100) NOT NULL,
    program VARCHAR(255) NOT NULL,
    academic_term VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(50) NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Active'
);

CREATE TABLE IF NOT EXISTS requirements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requirement_name VARCHAR(255) NOT NULL,
    description TEXT,
    student_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Active'
);
```

The application does not currently include a separate migration or database dump
file, so the schema above is the setup source of truth.

### 3a. Prepare an administrator account

No default administrator account or default password is included. Create an
account only for local development and never publish its credentials.

Before creating the first account, note that the current implementation has an
authentication mismatch: `users.php` and `edit_user.php` store passwords with
`password_hash()`, while `admin_login.php` currently compares the submitted
password directly. As a result, accounts created through the User Management
page cannot authenticate until `admin_login.php` is updated to use
`password_verify()`. This is a known development limitation and should be fixed
before the administration portal is used.

### 4. Check the database connection

The default connection in `db.php` is:

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "isu_smartenroll";
```

This matches a default XAMPP installation. If the local MySQL username, password,
host, or database name differs, update `db.php` before opening the application.
Do not use these development credentials in a production deployment.

### 5. Open the application

Visit:

```text
http://localhost/ISU-Smart-Enroll/index.php
```

The administration login is available at:

```text
http://localhost/ISU-Smart-Enroll/admin_login.php
```

## Using the application

### Student workflow

1. Open the home page.
2. Select **Enrollment Guide**.
3. Choose a student type, educational level, program, and academic term.
4. Submit the questionnaire.
5. Review the guidance on the result page.
6. Confirm all requirements and final instructions with the appropriate university
   office.

The result page uses the selected student type to display general recommended
steps. It does not create a student record or submit an official enrollment.

### Administrator workflow

1. Open `admin_login.php`.
2. Sign in with an account that exists in the `admins` table.
3. Use the dashboard to open User Management, Student Records, Programs, or
   Requirements.
4. Add, edit, or delete records as needed.
5. Return to the dashboard when finished.

All administration pages check for the `admin_logged_in` session flag and redirect
unauthenticated visitors to the login page.

## Page reference

| URL | Access | Description |
| --- | --- | --- |
| `index.php` | Public | Home page and links to the public guide |
| `enrollment.php` | Public | Enrollment questionnaire |
| `result.php` | Public | Guidance generated from questionnaire input |
| `admin_login.php` | Public | Administrator sign-in |
| `admin_dashboard.php` | Admin session | Administration landing page |
| `users.php` | Admin session | Add, list, edit, and delete admin/staff accounts |
| `edit_user.php?id=<id>` | Admin session | Edit one account |
| `student.php` | Admin session | Add, list, and delete student records |
| `edit_student.php?id=<id>` | Admin session | Edit one student record |
| `programs.php` | Admin session | Add, list, edit, and delete programs |
| `requirements.php` | Admin session | Add, list, edit, and delete requirements |

## Data model

The application uses four tables:

- **`admins`** stores administrator and staff usernames, passwords, and roles.
- **`students`** stores the student ID, name, student type, educational level,
  program, and academic term.
- **`programs`** stores the program code, name, department, and status.
- **`requirements`** stores requirement names, descriptions, applicable student
  types, and status.

The public questionnaire currently calculates guidance in `result.php`; it does
not read the `requirements` or `programs` tables.

## Troubleshooting

### Apache or MySQL will not start

- Check whether another service is using ports 80 or 3306.
- Review the Apache and MySQL logs in the XAMPP Control Panel.
- Change the XAMPP service ports only if necessary, then use the matching URL or
  connection settings.

### Database connection failed

- Confirm MySQL is running.
- Confirm the `isu_smartenroll` database exists.
- Check the values in `db.php`.
- Confirm the MySQL account has permission to access the database.

### A page redirects to the login page

The administration pages require an active PHP session. Open
`admin_login.php`, sign in, and ensure cookies are enabled in the browser.

### A logo or background image is missing

Confirm that the requested file exists under `assets/` and that the project is
being served from the correct Apache document-root path.

## Security and deployment notes

This project is configured for local development and needs additional hardening
before production use:

- Set a non-empty database password and move credentials out of source code.
- Use HTTPS and secure, HTTP-only session cookies.
- Add CSRF protection to all state-changing forms.
- Validate and authorize every edit and delete operation server-side.
- Use `password_verify()` consistently when checking password hashes.
- Restrict or remove `php-test.php`, which exposes PHP configuration details.
- Add an explicit logout endpoint that calls `session_destroy()`.
- Replace destructive GET actions with protected POST actions and confirmation.
- Add database indexes and uniqueness constraints appropriate to institutional
  data requirements.
- Back up the database and define a retention policy before storing real student
  information.

## Development notes

There is currently no automated test suite or package manager configuration in
the repository. For manual verification after a change:

1. Open the public home page and questionnaire.
2. Submit each student type and confirm a result is displayed.
3. Sign in as an administrator.
4. Add and edit one record in each administration module.
5. Verify that unauthenticated access redirects to `admin_login.php`.
6. Check the Apache/PHP error log for warnings or fatal errors.

The public home page also contains links for procedures, announcements, and
account pages that are not currently implemented in this repository. Use
`enrollment.php` and `admin_login.php` directly when testing the available
workflows.

## License

No license file is currently included. Add a license before distributing or
reusing the project outside its intended academic or institutional context.
