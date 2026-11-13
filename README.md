# MediCare – Hospital Management & Telemedicine System

MediCare is a web-based hospital management and telemedicine project developed as a final-year Computer Science project at Government Degree College Hayatabad, affiliated with the University of Peshawar, Pakistan.

The application provides separate workflows for patients, doctors, and administrators, including appointment booking and management, digital prescriptions, doctor feedback, payment demonstrations, email notifications, and one-to-one video consultations.

## Main Features

- **Patient module:** Registration, login, doctor browsing, appointment booking, appointment history, profile management, payment workflow, video consultation access, prescription viewing, and doctor ratings.
- **Doctor module:** Doctor registration and approval workflow, profile and appointment management, patient-related actions, video consultations, and prescription creation.
- **Admin module:** Dashboard and management workflows for doctors, patients, and appointments.
- **Video consultations:** One-to-one video-call interface using the ZEGOCLOUD UIKit Prebuilt JavaScript SDK.
- **Digital prescriptions:** Doctors can create prescriptions with diagnosis, medicines, instructions, and follow-up dates.
- **Email notifications:** PHPMailer-based SMTP email functionality (requires valid SMTP configuration).
- **Payment demonstration:** Payment-method selection and application-side transaction/receipt records for JazzCash, Easypaisa, bank transfer, and card options. These flows do not establish live payment-provider integration.

## Technology Stack

| Area | Technologies |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| UI framework | Bootstrap 5 |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Local development | XAMPP (Apache and MySQL) |
| Video consultation | ZEGOCLOUD UIKit Prebuilt |
| Email | PHPMailer |
| Dependency management | Composer |

## Repository Structure

```text
MediCare-Hospital-Management-System/
├── admin/                  # Admin dashboard and management pages
├── db/
│   ├── connection.php      # Database connection settings
│   ├── email.php           # Email helper
│   └── hospital_db.sql     # Database schema and included sample records
├── doctor/                 # Doctor dashboard and workflows
├── patient/                # Patient dashboard and workflows
├── vendor/                 # Composer dependencies, including PHPMailer
├── index.php               # Public landing page
├── login.php               # Login page
├── login_process.php       # Login processing
├── register.php            # Registration page
├── register_process.php    # Registration processing
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
5. Select the database and use **Import** to import `db/hospital_db.sql`. This SQL file creates the tables and relationships only; sample user and medical records have been removed from the public dump for privacy.
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

The video-call pages load the ZEGOCLOUD UIKit Prebuilt SDK from an external CDN and generate a test kit token in the client-side code. Test-token configurations are for development and should be replaced with an appropriate secure token-generation setup before any production use. Do not publish server secrets or production credentials.

## Important Project Notes

- The payment pages currently demonstrate application-side payment selection, transaction recording, and receipt workflows. They do **not** verify payments through live JazzCash, Easypaisa, bank, or card-provider APIs.
- The SQL dump includes sample/test records. Review and anonymize or remove any personal or test data before redistributing the database dump or using it outside a local demonstration.
- The landing page currently displays illustrative figures (for example, specialist and patient counts). These should not be interpreted as verified live platform statistics.
- This is an academic project and has not been presented as a production-ready clinical system. A full security, privacy, and deployment review is required before real-world use.

## Academic Work

A research manuscript based on the MediCare project has been deposited on Zenodo and submitted to the *Sukkur IBA Journal of Computing and Mathematical Sciences (SJCMS)*. The manuscript is currently under peer review.

## Author

**Maaz Ahmad**  
Department of Computer Science  
Government Degree College Hayatabad  
Affiliated with the University of Peshawar, Pakistan

## Repository

[View MediCare on GitHub](https://github.com/maazahmadyousafzai/MediCare-Hospital-Management-System)
