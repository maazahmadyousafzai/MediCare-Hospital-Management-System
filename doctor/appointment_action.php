<?php
session_start();
include '../db/connection.php';
include '../db/email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$id        = intval($_GET['id']);
$action    = $_GET['action'];
$doctor_id = intval($_SESSION['user_id']);

// Appointment + patient + doctor details fetch
$apt_query = mysqli_query($conn,
    "SELECT a.*,
            p.fullname as patient_name,
            p.email    as patient_email,
            d.fullname as doctor_name
     FROM appointments a
     JOIN users p ON a.patient_id = p.id
     JOIN users d ON a.doctor_id  = d.id
     WHERE a.id = '$id' AND a.doctor_id = '$doctor_id'");

$apt = mysqli_fetch_assoc($apt_query);

if (!$apt) {
    echo "<script>alert('Appointment record not found!'); window.location='my_appointments.php';</script>";
    exit();
}

$apt_date    = date('d M Y', strtotime($apt['appointment_date']));
$apt_time    = date('h:i A', strtotime($apt['appointment_time']));
$admin_email = ADMIN_NOTIFY_EMAIL;

// ── 1. CONFIRM ACTION ──
if ($action === 'confirm') {

    mysqli_query($conn, "UPDATE appointments SET status='confirmed' WHERE id='$id'");

    $subject = "Appointment Confirmed - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#28a745;'>Appointment Confirmed!</h2>
    <p>Dear <strong>" . htmlspecialchars($apt['patient_name']) . "</strong>,</p>
    <p>Your appointment has been confirmed by the doctor on <strong>MediCare 3-Tier Telemedicine Platform</strong>.</p>

    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;border-radius:8px;'>
        <tr style='background:#d1e7dd;'>
            <td style='padding:12px;color:#666;width:40%;'>Doctor</td>
            <td style='padding:12px;font-weight:600;'>Dr. " . htmlspecialchars($apt['doctor_name']) . "</td>
        </tr>
        <tr>
            <td style='padding:12px;color:#666;'>Date</td>
            <td style='padding:12px;font-weight:600;'>$apt_date</td>
        </tr>
        <tr style='background:#f8f9fa;'>
            <td style='padding:12px;color:#666;'>Time</td>
            <td style='padding:12px;font-weight:600;'>$apt_time</td>
        </tr>
        <tr>
            <td style='padding:12px;color:#666;'>Type</td>
            <td style='padding:12px;'>" . htmlspecialchars($apt['type']) . "</td>
        </tr>
    </table>

    <br>
    <div style='background:#fff3cd;border-radius:10px;padding:20px;border-left:5px solid #ffc107;margin-top:15px;'>
        <h3 style='color:#856404;margin:0 0 15px;'>Payment Instructions</h3>
        <p style='color:#666;margin-bottom:15px;'>
            Please pay <strong>Rs. 500</strong> consultation fee using any method below:
        </p>
        <table style='width:100%;border-collapse:collapse;'>
            <tr style='background:#fff;'>
                <td style='padding:10px;font-weight:700;color:#EF3737;width:40%;'>JazzCash</td>
                <td style='padding:10px;'>
                    <strong>0336-1576134</strong><br>
                    <small style='color:#888;'>Account: MAAZ AHMAD</small>
                </td>
            </tr>
            <tr style='background:#f8f9fa;'>
                <td style='padding:10px;font-weight:700;color:#4CAF50;'>Easypaisa</td>
                <td style='padding:10px;'>
                    <strong>0336-1576134</strong><br>
                    <small style='color:#888;'>Account: MAAZ AHMAD</small>
                </td>
            </tr>
            <tr style='background:#fff;'>
                <td style='padding:10px;font-weight:700;color:#1565C0;'>Bank Transfer</td>
                <td style='padding:10px;'>
                    <strong>Allied Bank Limited (ABL)</strong><br>
                    <small style='color:#888;'>Account Title: Maaz Ahmad</small><br>
                    <small style='color:#888;'>Branch: Hayatabad, Peshawar</small>
                </td>
            </tr>
            <tr style='background:#f8f9fa;'>
                <td style='padding:10px;font-weight:700;color:#6A1B9A;'>Amount</td>
                <td style='padding:10px;'>
                    <strong style='font-size:18px;color:#28a745;'>Rs. 500</strong>
                </td>
            </tr>
        </table>
    </div>

    <br>
    <a href='http://localhost:8080/hospital_project/patient/my_appointments.php'
       style='display:block;background:#28a745;color:white;padding:14px;border-radius:8px;text-decoration:none;font-weight:bold;text-align:center;'>
        View My Appointments
    </a>";

    sendEmail($apt['patient_email'], $apt['patient_name'], $subject, $message);

    // Admin Notification
    $subject_a = "Appointment Confirmed by Doctor - MediCare 3-Tier Telemedicine Platform";
    $message_a = "
    <h2 style='color:#28a745;'>Appointment Confirmed!</h2>
    <p>Dr. " . htmlspecialchars($apt['doctor_name']) . " confirmed appointment #$id for patient " . htmlspecialchars($apt['patient_name']) . ".</p>";
    sendEmail($admin_email, 'Admin', $subject_a, $message_a);

    echo "<script>alert('Appointment Confirmed! Patient notified via email.'); window.location='my_appointments.php';</script>";

// ── 2. CANCEL ACTION ──
} elseif ($action === 'cancel') {

    mysqli_query($conn, "UPDATE appointments SET status='cancelled' WHERE id='$id'");

    $subject = "Appointment Cancelled - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#dc3545;'>Appointment Cancelled</h2>
    <p>Dear <strong>" . htmlspecialchars($apt['patient_name']) . "</strong>,</p>
    <p>Your appointment with Dr. " . htmlspecialchars($apt['doctor_name']) . " scheduled for $apt_date at $apt_time has been cancelled by the doctor.</p>
    <div style='background:#f8d7da;border-radius:10px;padding:15px;margin-top:15px;'>
        <p style='margin:0;color:#842029;'>For queries, please contact support: <strong>maazahmadyousafzai557@gmail.com</strong></p>
    </div>";

    sendEmail($apt['patient_email'], $patient_name, $subject, $message);

    echo "<script>alert('Appointment Cancelled!'); window.location='my_appointments.php';</script>";

// ── 3. COMPLETE ACTION ──
} elseif ($action === 'complete') {

    mysqli_query($conn, "UPDATE appointments SET status='completed' WHERE id='$id'");

    $subject = "Consultation Completed - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#0d6efd;'>Consultation Completed</h2>
    <p>Dear <strong>" . htmlspecialchars($apt['patient_name']) . "</strong>,</p>
    <p>Your consultation session with Dr. " . htmlspecialchars($apt['doctor_name']) . " is marked completed. You can now access your digital prescription on the patient portal.</p>
    <br>
    <a href='http://localhost:8080/hospital_project/patient/my_appointments.php'
       style='display:block;background:#0d6efd;color:white;padding:14px;border-radius:8px;text-decoration:none;font-weight:bold;text-align:center;'>
        View Digital Rx & Rate Doctor
    </a>";

    sendEmail($apt['patient_email'], $apt['patient_name'], $subject, $message);

    echo "<script>alert('Appointment Marked Completed!'); window.location='my_appointments.php';</script>";
}
?>