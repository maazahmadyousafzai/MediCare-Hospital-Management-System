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

// Doctor info fetch
$doctor = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT u.*, d.specialization, d.qualification
     FROM users u
     LEFT JOIN doctors d ON u.id = d.user_id
     WHERE u.id = '$id'"));

if (!$doctor) {
    echo "<script>alert('Doctor not found!');
          window.location='manage_doctors.php';</script>";
    exit();
}

$admin_email = ADMIN_NOTIFY_EMAIL;

if ($action === 'approve') {

    mysqli_query($conn,
        "UPDATE users SET status='active' WHERE id='$id'");
    mysqli_query($conn,
        "UPDATE doctors SET status='active' WHERE user_id='$id'");

    // Doctor ko approval email
    $subject = "Congratulations! Account Approved - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#28a745;'>Account Approved!</h2>
    <p>Dear <strong>Dr. " . $doctor['fullname'] . "</strong>,</p>
    <p>Your doctor account has been approved by admin on <strong>MediCare 3-Tier Telemedicine Platform</strong>.
       You can now login and start accepting appointments!</p>
    <div style='background:#d1e7dd;border-radius:10px;
                padding:20px;margin:15px 0;'>
        <table style='width:100%;border-collapse:collapse;'>
            <tr>
                <td style='padding:10px;color:#666;width:40%;'>Name</td>
                <td style='padding:10px;font-weight:600;'>
                    Dr. " . $doctor['fullname'] . "
                </td>
            </tr>
            <tr style='background:#c3e6cb;'>
                <td style='padding:10px;color:#666;'>Email</td>
                <td style='padding:10px;'>" . $doctor['email'] . "</td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;'>Specialization</td>
                <td style='padding:10px;'>
                    " . $doctor['specialization'] . "
                </td>
            </tr>
            <tr style='background:#c3e6cb;'>
                <td style='padding:10px;color:#666;'>Status</td>
                <td style='padding:10px;font-weight:700;color:#28a745;'>
                    APPROVED
                </td>
            </tr>
        </table>
    </div>
    <a href='http://localhost:8080/hospital_project/login.php'
       style='display:block;background:#28a745;color:white;padding:14px;
              border-radius:8px;text-decoration:none;
              font-weight:bold;text-align:center;'>
        Login Now
    </a>";

    sendEmail($doctor['email'], $doctor['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Doctor Approved - MediCare 3-Tier Telemedicine Platform";
    $message_adm = "
    <h2 style='color:#28a745;'>Doctor Approved</h2>
    <p>You approved the following doctor on MediCare 3-Tier Telemedicine Platform:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $doctor['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $doctor['email'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Specialization</td>
            <td style='padding:10px;'>" . $doctor['specialization'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    echo "<script>alert('Doctor Approved! Email sent to: " .
         $doctor['email'] . "');
          window.location='manage_doctors.php';</script>";

} elseif ($action === 'block') {

    mysqli_query($conn,
        "UPDATE users SET status='blocked' WHERE id='$id'");

    $subject = "Account Blocked - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#dc3545;'>Account Blocked</h2>
    <p>Dear <strong>Dr. " . $doctor['fullname'] . "</strong>,</p>
    <p>Your doctor account on MediCare 3-Tier Telemedicine Platform has been blocked by admin.
       Please contact support for more information.</p>
    <div style='background:#f8d7da;border-radius:10px;padding:15px;'>
        <p style='margin:0;color:#842029;'>
            Contact Support: maazahmadyousafzai557@gmail.com
        </p>
    </div>";

    sendEmail($doctor['email'], $doctor['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Doctor Blocked - MediCare 3-Tier Telemedicine Platform";
    $message_adm = "
    <h2 style='color:#dc3545;'>Doctor Blocked</h2>
    <p>You blocked the following doctor:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $doctor['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $doctor['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    echo "<script>alert('Doctor Blocked! Email sent.');
          window.location='manage_doctors.php';</script>";

} elseif ($action === 'unblock') {

    mysqli_query($conn,
        "UPDATE users SET status='active' WHERE id='$id'");

    $subject = "Account Unblocked - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#28a745;'>Account Unblocked!</h2>
    <p>Dear <strong>Dr. " . $doctor['fullname'] . "</strong>,</p>
    <p>Your account on MediCare 3-Tier Telemedicine Platform has been unblocked.
       You can login and accept appointments again!</p>
    <a href='http://localhost:8080/hospital_project/login.php'
       style='display:block;background:#28a745;color:white;padding:14px;
              border-radius:8px;text-decoration:none;
              font-weight:bold;text-align:center;'>
        Login Now
    </a>";

    sendEmail($doctor['email'], $doctor['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Doctor Unblocked - MediCare 3-Tier Telemedicine Platform";
    $message_adm = "
    <h2 style='color:#28a745;'>Doctor Unblocked</h2>
    <p>You unblocked the following doctor:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $doctor['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $doctor['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    echo "<script>alert('Doctor Unblocked! Email sent.');
          window.location='manage_doctors.php';</script>";

} elseif ($action === 'reject') {

    $subject = "Application Rejected - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#dc3545;'>Application Not Approved</h2>
    <p>Dear <strong>" . $doctor['fullname'] . "</strong>,</p>
    <p>We regret to inform you that your doctor
       registration on MediCare 3-Tier Telemedicine Platform has not been approved at this time.</p>
    <div style='background:#f8d7da;border-radius:10px;padding:15px;'>
        <p style='margin:0;color:#842029;'>
            For more information contact: maazahmadyousafzai557@gmail.com
        </p>
    </div>";

    sendEmail($doctor['email'], $doctor['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Doctor Rejected - MediCare 3-Tier Telemedicine Platform";
    $message_adm = "
    <h2 style='color:#dc3545;'>Doctor Rejected</h2>
    <p>You rejected and removed the following doctor application:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $doctor['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $doctor['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    // Pehle payments, ratings, prescriptions delete karo
    $apts = mysqli_query($conn,
        "SELECT id FROM appointments WHERE doctor_id='$id'");
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
        "DELETE FROM appointments WHERE doctor_id='$id'");
    mysqli_query($conn,
        "DELETE FROM doctors WHERE user_id='$id'");
    mysqli_query($conn,
        "DELETE FROM users WHERE id='$id'");

    echo "<script>alert('Doctor Rejected & Removed! Email sent.');
          window.location='manage_doctors.php';</script>";

} elseif ($action === 'delete') {

    // Email bhejo pehle
    $subject = "Account Removed - MediCare 3-Tier Telemedicine Platform";
    $message = "
    <h2 style='color:#dc3545;'>Account Removed</h2>
    <p>Dear <strong>Dr. " . $doctor['fullname'] . "</strong>,</p>
    <p>Your doctor account has been removed from MediCare 3-Tier Telemedicine Platform.</p>
    <div style='background:#f8d7da;border-radius:10px;padding:15px;'>
        <p style='margin:0;color:#842029;'>
            Contact Support: maazahmadyousafzai557@gmail.com
        </p>
    </div>";

    sendEmail($doctor['email'], $doctor['fullname'], $subject, $message);

    // Admin ko bhi confirmation
    $subject_adm = "Doctor Deleted - MediCare 3-Tier Telemedicine Platform";
    $message_adm = "
    <h2 style='color:#dc3545;'>Doctor Deleted</h2>
    <p>You deleted the following doctor account:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr><td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>" . $doctor['fullname'] . "</td></tr>
        <tr><td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>" . $doctor['email'] . "</td></tr>
    </table>";
    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    // Pehle payments delete karo phir appointments phir doctor phir user
    $apts = mysqli_query($conn,
        "SELECT id FROM appointments WHERE doctor_id='$id'");
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
        "DELETE FROM appointments WHERE doctor_id='$id'");
    mysqli_query($conn,
        "DELETE FROM doctors WHERE user_id='$id'");
    mysqli_query($conn,
        "DELETE FROM users WHERE id='$id'");

    echo "<script>alert('Doctor Deleted Successfully!');
          window.location='manage_doctors.php';</script>";
}
?>