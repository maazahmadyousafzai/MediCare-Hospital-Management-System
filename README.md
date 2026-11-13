# MediCare – Hospital Management System

MediCare is a web-based hospital management project developed as a final-year Computer Science project at Government Degree College Hayatabad, affiliated with the University of Peshawar, Pakistan.

The application provides separate workflows for patients, doctors, and administrators, including appointment booking and management, prescription records, doctor feedback, manual payment-reference review, email notifications, and a video-call interface (currently disabled).

## Main Features

- **Patient module:** Registration, login, doctor browsing, appointment booking, appointment history, profile management, manual payment-reference workflow, prescription viewing, and doctor ratings.
- **Doctor module:** Doctor registration and approval workflow, profile and appointment management, and prescription creation. The video-call interface is currently disabled.
- **Admin module:** Dashboard and management workflows for doctors, patients, and appointments.
- **Video consultations:** The interface is present, but live calls and video appointment booking are disabled until secure server-side token generation is implemented and configured.
- **Digital prescriptions:** Doctors can create prescriptions with diagnosis, medicines, instructions, and follow-up dates.
- **Email notifications:** PHPMailer-based SMTP email functionality (requires valid SMTP configuration).
- **Manual payment review:** Patients can submit a JazzCash, Easypaisa, or bank-transfer reference; payments remain pending until an administrator verifies them. This is not a live payment-provider integration.

## Technology Stack

| Area | Technologies |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| UI framework | Bootstrap 5 |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Local development | XAMPP (Apache and MySQL) |
| Video-call interface | Present but disabled pending secure server-side token support |
| Email | PHPMailer |
| Dependency management | Composer |

## Repository Structure

```text
MediCare-Hospital-Management-System/
├── admin/                  # Admin dashboard and management pages
├── db/
│   ├── connection.php      # Database connection settings
│   ├── email.php           # Email helper
│   └── hospital_db.sql     # Empty database schema (no sample patient records)
├── doctor/                 # Doctor dashboard and workflows
├── patient/                # Patient dashboard and workflows
├── vendor/                 # Composer dependencies, including PHPMailer
├── index.php               # Public landing page
├── login.php               # Login page
├── login_process.php       # Login processing
├── register.php            # Registration page
├── logout.php              # Logout
├── composer.json           # PHP dependency definition
└── README.md
```

## Requirements

- XAMPP with Apache, PHP, and MySQL/MariaDB
- A web browser
- Composer (only if you need to install or refresh PHP dependencies)
- Internet access for externally hosted frontend assets and the ZEGOCLOUD JavaScript SDK

## Local Installation

1. Install XAMPP and start **Apache** and **MySQL**.
2. Download or clone this repository into XAMPP's `htdocs` directory.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Create a database named **hospital_db**.
5. Select the database and use **Import** to import `db/hospital_db.sql`. This SQL file creates the tables and relationships only; it contains no sample users or medical records.
6. The connection file uses local XAMPP defaults. For another environment, configure `MEDICARE_DB_HOST`, `MEDICARE_DB_USERNAME`, `MEDICARE_DB_PASSWORD`, and `MEDICARE_DB_NAME` as server environment variables.
7. Open the project in your browser at:

   `http://localhost/MediCare-Hospital-Management-System/`

The repository includes the `vendor/` directory and Composer configuration for PHPMailer. If dependencies need to be installed or restored, run `composer install` from the project root.

## Configuration Notes

### Database

For local XAMPP use, the connection file defaults to `localhost`, user `root`, an empty password, and database `hospital_db`. For hosted environments, set the `MEDICARE_DB_*` environment variables listed above. Never use default development credentials in production.

### Email

Email delivery requires the server environment variables `MEDICARE_SMTP_USERNAME`, `MEDICARE_SMTP_PASSWORD`, and (optionally) `MEDICARE_ADMIN_EMAIL` / `MEDICARE_ADMIN_NAME`. Do not commit Gmail app passwords or other secrets to GitHub. Without SMTP credentials, the application skips email delivery and records a server-side error.

### Video consultations

The ZEGOCLOUD AppSign was removed from client-side code because it is a signing secret. Video calls are temporarily disabled until a server-side token endpoint is implemented. If an AppSign was previously committed publicly, rotate it in the ZEGOCLOUD console; deleting it from the latest source does not remove it from Git history.

## Important Project Notes

- The payment workflow records submitted JazzCash/Easypaisa/bank references as `pending`; an administrator must verify the transaction in the provider account before marking it paid. It does **not** connect to live payment-provider APIs. Configure and verify recipient account details privately before accepting any real payment.
- The public SQL dump is schema-only and contains no sample patient, payment, appointment, rating, or prescription records.
- The landing page uses descriptive capability labels instead of unverified patient, specialist, or usage statistics.
- GitHub Actions runs PHP syntax checks on application PHP files. This is not an end-to-end test: database workflows, SMTP delivery, payment verification, and video calls still require local integration testing.
- This is an academic project and is not certified as a production-ready clinical system. Complete privacy, clinical safety, deployment, and penetration testing before real-world use.

## Academic Work

A research manuscript based on the MediCare project has been deposited on Zenodo and submitted to the *Sukkur IBA Journal of Computing and Mathematical Sciences (SJCMS)*. The manuscript is currently under peer review.

## Author

**Maaz Ahmad**  
Department of Computer Science  
Government Degree College Hayatabad  
Affiliated with the University of Peshawar, Pakistan

## Repository

[View MediCare on GitHub](https://github.com/maazahmadyousafzai/MediCare-Hospital-Management-System)
