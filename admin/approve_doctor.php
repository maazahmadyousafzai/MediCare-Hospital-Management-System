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

// Doctor ki details fetch karo (email/name ke liye)
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT u.fullname, u.email, d.specialization
     FROM users u
     JOIN doctors d ON u.id = d.user_id
     WHERE u.id = '$id'"));

if ($action === 'approve') {
    mysqli_query($conn,
        "UPDATE users SET status='active' WHERE id='$id'");
    mysqli_query($conn,
        "UPDATE doctors SET status='active' WHERE user_id='$id'");

    // Doctor ko approval email
    $subject_doc = "Account Approved - MediCare";
    $message_doc = "
    <h2 style='color:#28a745;'>
        Congratulations! Your Account is Approved!
    </h2>
    <p>Dear <strong>$doc[fullname]</strong>,</p>
    <p>Your doctor account has been reviewed and approved by the admin.
       You can now login and start accepting appointments.</p>
    <div style='background:#d1e7dd;border-radius:10px;
                padding:20px;margin:15px 0;'>
        <table style='width:100%;border-collapse:collapse;'>
            <tr>
                <td style='padding:10px;color:#666;width:40%;'>
                    Specialization
                </td>
                <td style='padding:10px;font-weight:600;'>
                    {$doc['specialization']}
                </td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;'>
                    Status
                </td>
                <td style='padding:10px;font-weight:700;color:#28a745;'>
                    Active - You can login now
                </td>
            </tr>
        </table>
    </div>
    <a href='http://localhost:8080/hospital_project/login.php'
       style='display:block;background:#28a745;color:white;padding:14px;
              border-radius:8px;text-decoration:none;font-weight:bold;
              text-align:center;'>
        Login Now
    </a>";

    sendEmail($doc['email'], $doc['fullname'], $subject_doc, $message_doc);

    // Admin ko bhi confirmation
    $admin_email = ADMIN_NOTIFY_EMAIL;
    $subject_adm = "Doctor Approved - MediCare";
    $message_adm = "
    <h2 style='color:#28a745;'>Doctor Approved</h2>
    <p>You have approved the following doctor:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr>
            <td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>{$doc['fullname']}</td>
        </tr>
        <tr>
            <td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>{$doc['email']}</td>
        </tr>
        <tr>
            <td style='padding:10px;color:#666;'>Specialization</td>
            <td style='padding:10px;'>{$doc['specialization']}</td>
        </tr>
    </table>";

    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    echo "<script>alert('Doctor Approved Successfully!');
          window.location='dashboard.php';</script>";

} elseif ($action === 'reject') {

    // Doctor ko rejection email (delete se pehle bhejna zaroori hai)
    $subject_doc = "Application Rejected - MediCare";
    $message_doc = "
    <h2 style='color:#dc3545;'>Application Not Approved</h2>
    <p>Dear <strong>$doc[fullname]</strong>,</p>
    <p>We regret to inform you that your doctor registration
       application has not been approved at this time.</p>
    <p>If you believe this is a mistake, please contact the
       hospital administration for more information.</p>";

    sendEmail($doc['email'], $doc['fullname'], $subject_doc, $message_doc);

    // Admin ko bhi confirmation
    $admin_email = ADMIN_NOTIFY_EMAIL;
    $subject_adm = "Doctor Rejected - MediCare";
    $message_adm = "
    <h2 style='color:#dc3545;'>Doctor Rejected</h2>
    <p>You have rejected and removed the following doctor application:</p>
    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
        <tr>
            <td style='padding:10px;color:#666;'>Name</td>
            <td style='padding:10px;font-weight:600;'>{$doc['fullname']}</td>
        </tr>
        <tr>
            <td style='padding:10px;color:#666;'>Email</td>
            <td style='padding:10px;'>{$doc['email']}</td>
        </tr>
    </table>";

    sendEmail($admin_email, 'Admin', $subject_adm, $message_adm);

    mysqli_query($conn,
        "DELETE FROM doctors WHERE user_id='$id'");
    mysqli_query($conn,
        "DELETE FROM users WHERE id='$id'");

    echo "<script>alert('Doctor Rejected & Removed!');
          window.location='dashboard.php';</script>";
}
?>