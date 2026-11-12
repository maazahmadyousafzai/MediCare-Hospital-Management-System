<?php
session_start();
include '../db/connection.php';
include '../db/email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$id     = $_GET['id'];
$action = $_GET['action'];

// Patient info fetch karo
$patient = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE id='$id'"));

if (!$patient) {
    echo "<script>alert('Patient not found!');
          window.location='manage_patients.php';</script>";
    exit();
}

$admin_email = ADMIN_NOTIFY_EMAIL;

if ($action === 'block') {

    mysqli_query($conn,
        "UPDATE users SET status='blocked' WHERE id='$id'");

    $subject = "Account Blocked - MediCare";
    $message = "
    <h2 style='color:#dc3545;'>Account Blocked</h2>
    <p>Dear <strong>" . $patient['fullname'] . "</strong>,</p>
    <p>Your patient account has been blocked by admin.
       Please contact support for more information.</p>
    <div style='background:#f8d7da;border-radius:10px;padding:15px;'>
        <p style='margin:0;color:#842029;'>
            Contact: luqman.ahmad.cs@gmail.com
        </p>
    </div>";

    sendEmail($patient['email'], $patient['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Patient Blocked - MediCare";
    $message_adm = "
    <h2 style='color:#dc3545;'>Patient Blocked</h2>
    <p>You blocked the following patient:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $patient['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $patient['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    echo "<script>alert('Patient Blocked! Email sent.');
          window.location='manage_patients.php';</script>";

} elseif ($action === 'unblock') {

    mysqli_query($conn,
        "UPDATE users SET status='active' WHERE id='$id'");

    $subject = "Account Unblocked - MediCare";
    $message = "
    <h2 style='color:#28a745;'>Account Unblocked!</h2>
    <p>Dear <strong>" . $patient['fullname'] . "</strong>,</p>
    <p>Your account has been unblocked.
       You can login and book appointments again!</p>
    <a href='http://localhost:8080/hospital_project/login.php'
       style='display:block;background:#0d6efd;color:white;padding:14px;
              border-radius:8px;text-decoration:none;
              font-weight:bold;text-align:center;'>
        Login Now
    </a>";

    sendEmail($patient['email'], $patient['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Patient Unblocked - MediCare";
    $message_adm = "
    <h2 style='color:#28a745;'>Patient Unblocked</h2>
    <p>You unblocked the following patient:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $patient['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $patient['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    echo "<script>alert('Patient Unblocked! Email sent.');
          window.location='manage_patients.php';</script>";

} elseif ($action === 'delete') {

    // Email bhejo pehle
    $subject = "Account Removed - MediCare";
    $message = "
    <h2 style='color:#dc3545;'>Account Removed</h2>
    <p>Dear <strong>" . $patient['fullname'] . "</strong>,</p>
    <p>Your patient account has been removed from MediCare.</p>
    <div style='background:#f8d7da;border-radius:10px;padding:15px;'>
        <p style='margin:0;color:#842029;'>
            Contact: luqman.ahmad.cs@gmail.com
        </p>
    </div>";

    sendEmail($patient['email'], $patient['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Patient Deleted - MediCare";
    $message_adm = "
    <h2 style='color:#dc3545;'>Patient Deleted</h2>
    <p>You deleted the following patient account:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $patient['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $patient['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    // Sahi order mein delete karo (foreign key safe)
    $apts = mysqli_query($conn,
        "SELECT id FROM appointments
         WHERE patient_id='$id'");
    while ($apt = mysqli_fetch_assoc($apts)) {
        mysqli_query($conn,
            "DELETE FROM payments
             WHERE appointment_id='" . $apt['id'] . "'");
        mysqli_query($conn,
            "DELETE FROM ratings
             WHERE appointment_id='" . $apt['id'] . "'");
        mysqli_query($conn,
            "DELETE FROM prescriptions
             WHERE appointment_id='" . $apt['id'] . "'");
    }

    mysqli_query($conn,
        "DELETE FROM appointments WHERE patient_id='$id'");

    mysqli_query($conn,
        "DELETE FROM users WHERE id='$id'");

    echo "<script>alert('Patient Deleted Successfully!');
          window.location='manage_patients.php';</script>";
}
?>