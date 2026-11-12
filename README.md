# 🏥 MediCare — Online Hospital Appointment Management System with Video Call

An enterprise-grade, web-based 3-tier telemedicine and hospital management platform developed as a Final Year Project (FYP) for Bachelor Studies in Computer Science (BSCS). The system bridges the gap between patients and healthcare providers by offering seamless appointment scheduling, secure digital payments, real-time WebRTC video consultations, and digital prescriptions.

---

## 🌟 Key Features

* **Multi-Role User Authentication & Management:** Secure role-based access control for Patients, Doctors, and Administrators with hashed password protection and session validation.
* **Smart Appointment Booking:** Supports both in-person physical visits and encrypted video consultations with real-time slot selection.
* **Integrated Payment Simulation:** Secure gateway workflows for JazzCash, Easypaisa, Direct Bank Transfers (HBL), and Credit/Debit Cards, complete with automated transaction verification and printable digital receipts.
* **Real-Time Video Telemedicine:** Built-in 1-on-1 secure video consultation rooms powered by **ZegoCloud SDK** (WebRTC).
* **Digital Medical Prescriptions (Rx):** Doctors can generate detailed medical prescriptions featuring clinical diagnosis notes, medicine formulations, dosages, timings, durations, and follow-up tracking, fully printable as professional clinic slips.
* **Automated Email Notifications:** Instant email dispatch for payment receipts, appointment updates, and system alerts via **PHPMailer**.
* **Doctor Ratings & Feedback:** Patient-driven review and rating system featuring interactive star ratings and quick feedback tags.

---

## 🛠️ Technology Stack

* **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3, Google Fonts (Plus Jakarta Sans), Font Awesome Icons.
* **Backend:** Core PHP (Modular procedural architecture).
* **Database:** MySQL / phpMyAdmin (Relational database design with strict foreign key constraints and SQL injection protection).
* **APIs & SDKs:** ZegoCloud UIKit Prebuilt for WebRTC video streaming, PHPMailer for SMTP email integration.
* **Environment:** XAMPP (Apache & MySQL).

---

## 📂 Project Directory Structure

```text
hospital_project/
│
├── admin/                  # Administrative dashboard, user oversight, and records
├── doctor/                 # Doctor schedule management, patient appointments, and Rx builder
├── patient/                # Patient portal, appointment booking, payments, video rooms, and profile
├── db/                     # Database connection (connection.php) and email configuration (email.php)
├── vendor/                 # Third-party dependencies (PHPMailer, autoload, etc.)
├── assets/                 # Stylesheets, client-side scripts, and images
├── login.php               # Secure entry portal
├── signup.php              # Patient & user registration
└── README.md               # Project documentation