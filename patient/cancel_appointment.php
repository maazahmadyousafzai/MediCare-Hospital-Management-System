<?php
session_start();
include '../db/connection.php';
include '../db/email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$id         = $_GET['id'];
$patient_id = $_SESSION['user_id'];

$apt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*,
            doc.fullname as doctor_name,
            doc.email    as doctor_email,
            pat.fullname as patient_name
     FROM appointments a
     JOIN users doc ON a.doctor_id  = doc.id
     JOIN users pat ON a.patient_id = pat.id
     WHERE a.id = '$id'"));

mysqli_query($conn,
    "UPDATE appointments SET status='cancelled'
     WHERE id='$id' AND patient_id='$patient_id'");

$apt_date = date('d M Y', strtotime($apt['appointment_date']));
$apt_time = date('h:i A', strtotime($apt['appointment_time']));

$subject = "Appointment Cancelled - MediCare";
$message = "
<h2 style='color:#dc3545;'>Appointment Cancelled</h2>
<p>Dear <strong>Dr. " . $apt['doctor_name'] . "</strong>,</p>
<p>A patient has cancelled their appointment.</p>
<table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
    <tr>
        <td style='padding:10px;color:#666;'>Patient</td>
        <td style='padding:10px;font-weight:600;'>
            " . $apt['patient_name'] . "
        </td>
    </tr>
    <tr style='background:#f0f0f0;'>
        <td style='padding:10px;color:#666;'>Date</td>
        <td style='padding:10px;'>$apt_date</td>
    </tr>
    <tr>
        <td style='padding:10px;color:#666;'>Time</td>
        <td style='padding:10px;'>$apt_time</td>
    </tr>
    <tr style='background:#f8d7da;'>
        <td style='padding:10px;color:#666;'>Status</td>
        <td style='padding:10px;font-weight:700;color:#dc3545;'>
            CANCELLED
        </td>
    </tr>
</table>
<br>
<a href='http://localhost:8080/hospital_project/doctor/my_appointments.php'
   style='display:block;background:#0f5132;color:white;padding:14px;
          border-radius:8px;text-decoration:none;
          font-weight:bold;text-align:center;'>
    View My Appointments
</a>";

sendEmail($apt['doctor_email'], $apt['doctor_name'], $subject, $message);

echo "<script>
        alert('Appointment Cancelled!');
        window.location='my_appointments.php';
      </script>";
?>