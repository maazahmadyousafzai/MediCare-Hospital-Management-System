# MediCare Hospital Management System

**Final Year Project — BS Computer Science**  
Government Degree College Hayatabad, affiliated with the University of Peshawar, Pakistan  
**Author: Maaz Ahmad**

## Project overview

MediCare is a PHP and MySQL/MariaDB hospital-management web application with separate Patient, Doctor, and Admin workflows. The project includes account registration and login, appointment booking and management, digital prescriptions, doctor ratings and reviews, manual payment-reference submission and administrator review, email notification support, and a ZEGOCLOUD video-consultation workflow.

The application follows a three-tier architecture and uses role-based access for its three user types: Patient, Doctor, and Administrator.

## Main modules

- **Patient:** Registration, login, doctor browsing, appointment booking, appointment history, profile management, payment-reference submission, prescription viewing, and doctor ratings.
- **Doctor:** Doctor account/profile workflows, appointment management, video-consultation access, and prescription creation.
- **Admin:** Dashboard and management workflows for doctors, patients, and appointments.
- **Prescriptions:** Doctors can record diagnosis, medicines, instructions, and follow-up dates; patients can view and print prescriptions.
- **Analytics:** Administrative charts are generated with Chart.js using application data.
- **Email:** PHPMailer-based SMTP notifications. Valid private SMTP configuration is required.

## Video consultations

The patient and doctor video-call pages request a short-lived Token04 token from `api/zego_token.php`. The endpoint checks the authenticated role, appointment ownership, and confirmed video-appointment status before generating a token on the server.

Video calling requires a private configuration file outside the public project directory:

`C:\xampp\secure\medicare-zego.php`

Example structure (replace the placeholders locally; never commit real credentials):

```php
<?php
return [
    'app_id' => 123456789,
    'server_secret' => 'replace-with-your-32-character-secret',
];
```

The ZEGOCLOUD SDK JavaScript must also load successfully in the browser. The integration requires valid credentials and local runtime testing; it is not a claim of production-ready telemedicine service.

## Payment workflow

The project supports manual JazzCash, Easypaisa, and bank-transfer reference submission for administrator verification. It is **not** a direct live payment-gateway integration. The application does not process card payments through a provider API or confirm real funds automatically.

## Technology stack

| Area | Technologies |
|---|---|
| Backend | PHP 8.2 |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, JavaScript |
| UI framework | Bootstrap 5 |
| Local development | XAMPP (Apache and MySQL) |
| Email | PHPMailer / SMTP |
| Video | ZEGOCLOUD UIKit Prebuilt and server-side Token04 generation |
| Charts | Chart.js |
| Dependency management | Composer |

## Repository structure

```text
admin/                  Admin dashboard and management pages
api/zego_token.php      Server-side video-token endpoint
doctor/                 Doctor dashboard and workflows
patient/                Patient dashboard and workflows
db/                     Database connection, email helper, SQL schema
zego/src/ZEGO/          ZEGOCLOUD server-token helper classes
vendor/                 Composer dependencies, including PHPMailer
docs/                   Project submission notes
```

## Local installation (XAMPP)

1. Install XAMPP with Apache, PHP 8.2 or compatible, and MySQL/MariaDB.
2. Extract the project into the XAMPP `htdocs` directory.
3. Start Apache and MySQL.
4. Open phpMyAdmin and create a new database named `hospital_db`.
5. Import `db/hospital_db.sql` into the new database. The SQL file is schema-only and does not include sample patient or appointment records.
6. For local XAMPP defaults, `db/connection.php` uses `localhost`, user `root`, an empty password, and `hospital_db`. For another environment, configure `MEDICARE_DB_HOST`, `MEDICARE_DB_USERNAME`, `MEDICARE_DB_PASSWORD`, and `MEDICARE_DB_NAME`.
7. Configure SMTP only when needed. Set `MEDICARE_SMTP_USERNAME`, `MEDICARE_SMTP_PASSWORD`, and optional `MEDICARE_ADMIN_EMAIL` / `MEDICARE_ADMIN_NAME` environment variables. For local XAMPP use, an optional private file at `C:\xampp\secure\medicare-smtp.php` can return an array with `username`, `password`, `admin_email`, and `admin_name` keys. If neither source is configured, email sending returns `false` and logs that SMTP is not configured; the helper does not expose credentials to the browser.
8. Configure ZEGOCLOUD only when needed by creating the private file described above.
9. Open the project in a browser using the folder name under `http://localhost/`.

Never commit SMTP passwords, ZEGOCLOUD ServerSecret values, API credentials, private keys, or real patient information.

## Testing and verification

The submitted thesis documents **47 test cases** across five primary categories: 12 unit, 12 integration, 9 user-acceptance, 8 security, and 6 performance tests. It reports 47/47 passed in the final documented test phase. These are the thesis's reported results from the project test environment, not independent production benchmarks.

The PHP source files in the final submission archive were checked for PHP syntax errors. A syntax check does not prove that every database workflow, email, payment-reference review, or video call works on another computer. Complete local end-to-end testing after configuring the environment.

## Security and project limitations

- The project is an academic prototype, not a certified production clinical system.
- The login handler retains a legacy MD5 compatibility fallback for older records. Existing legacy hashes should be migrated to `password_hash()` and the fallback removed before production use.
- Prepared statements and request checks are used in important workflows, but a complete security review and consistent parameterization across all handlers are still required.
- SMTP and ZEGOCLOUD credentials must remain outside the public repository.
- Payment submission is a manual reference-review workflow, not a live payment-provider integration.
- Privacy, clinical safety, deployment hardening, penetration testing, and broader user testing are outside the current project scope.

## Academic work

The project thesis is titled **MediCare Hospital Management System**. A related research manuscript has a Zenodo record; publication or journal-review status should be checked against the current record before being described as peer-reviewed or accepted.

## Author

**Maaz Ahmad**  
Department of Computer Science  
Government Degree College Hayatabad  
Affiliated with the University of Peshawar, Pakistan
