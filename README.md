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
5. Select the database and use **Import** to import `db/hospital_db.sql`.
6. Open `db/connection.php` and set the host, database name, username, and password to match your local MySQL/MariaDB configuration.
7. Open the project in your browser at:

   `http://localhost/MediCare-Hospital-Management-System/`

The repository includes the `vendor/` directory and Composer configuration for PHPMailer. If dependencies need to be installed or restored, run `composer install` from the project root.

## Configuration Notes

### Database

The included connection file currently uses a local XAMPP-style configuration (`localhost`, MySQL user `root`, and an empty password). Change these values to match your environment. Do not use default development credentials in a production deployment.

### Email

Email delivery depends on valid SMTP credentials in `db/email.php`. Configure credentials securely for your own environment; do not commit Gmail app passwords or other secrets to GitHub. Review the email configuration before expecting notification emails to work.

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
