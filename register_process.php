<?php
include 'db/connection.php';
include 'db/email.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = md5($_POST['password']);
    $confirm  = $_POST['confirm_password'];
    $role     = trim($_POST['role']);

    // 1. Password Match Check
    if ($_POST['password'] !== $confirm) {
        echo "<script>alert('Passwords do not match!');
              window.history.back();</script>";
        exit();
    }

    // 2. Email Duplication Check
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Email already registered!');
              window.location='login.php';</script>";
        exit();
    }

    // Doctor status is 'pending', Patient status is 'active'
    $status = ($role === 'doctor') ? 'pending' : 'active';

    // 3. Insert into users table
    $sql = "INSERT INTO users (fullname, email, phone, password, role, status)
            VALUES ('$fullname', '$email', '$phone', '$password', '$role', '$status')";

    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);

        // ==========================================
        // DOCTOR REGISTRATION FLOW
        // ==========================================
        if ($role === 'doctor') {
            $spec = trim($_POST['specialization']);
            $qual = trim($_POST['qualification']);
            $exp  = trim($_POST['experience']);

            // Insert into doctors table
            mysqli_query($conn, "INSERT INTO doctors (user_id, specialization, qualification, experience, status)
                                 VALUES ('$user_id', '$spec', '$qual', '$exp', 'pending')");

            // Doctor ko registration email
            $subject_doc = "Registration Received - MediCare 3-Tier Telemedicine Platform";
            $message_doc = "
            <h2 style='color:#0f5132;'>Application Submitted!</h2>
            <p>Dear <strong>$fullname</strong>,</p>
            <p>Your doctor registration has been submitted to <strong>MediCare 3-Tier Telemedicine Platform</strong>.</p>
            <div style='background:#f8f9fa;border-radius:10px;padding:20px;margin:15px 0;'>
                <table style='width:100%;border-collapse:collapse;'>
                    <tr style='background:#e8f5e9;'>
                        <td style='padding:10px;color:#666;width:40%;'>Full Name</td>
                        <td style='padding:10px;font-weight:600;'>$fullname</td>
                    </tr>
                    <tr>
                        <td style='padding:10px;color:#666;'>Email</td>
                        <td style='padding:10px;'>$email</td>
                    </tr>
                    <tr style='background:#e8f5e9;'>
                        <td style='padding:10px;color:#666;'>Specialization</td>
                        <td style='padding:10px;'>$spec</td>
                    </tr>
                    <tr>
                        <td style='padding:10px;color:#666;'>Qualification</td>
                        <td style='padding:10px;'>$qual</td>
                    </tr>
                    <tr style='background:#e8f5e9;'>
                        <td style='padding:10px;color:#666;'>Status</td>
                        <td style='padding:10px;font-weight:700;color:#856404;'>Pending Admin Approval</td>
                    </tr>
                </table>
            </div>
            <div style='background:#fff3cd;border-radius:10px;padding:15px;border-left:5px solid #ffc107;'>
                <p style='margin:0;color:#856404;'>You will receive another email once approved by the administrator!</p>
            </div>";

            sendEmail($email, $fullname, $subject_doc, $message_doc);

            // Admin ko email notification
            $admin_email = ADMIN_NOTIFY_EMAIL;
            $admin_name  = 'Admin';

            $subject_adm = "New Doctor Registration - MediCare 3-Tier Telemedicine Platform";
            $message_adm = "
            <h2 style='color:#0d6efd;'>New Doctor Registration!</h2>
            <p>A new doctor has registered on MediCare 3-Tier Telemedicine Platform. Please review and approve.</p>
            <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
                <tr>
                    <td style='padding:10px;color:#666;'>Name</td>
                    <td style='padding:10px;font-weight:600;'>$fullname</td>
                </tr>
                <tr>
                    <td style='padding:10px;color:#666;'>Email</td>
                    <td style='padding:10px;'>$email</td>
                </tr>
                <tr>
                    <td style='padding:10px;color:#666;'>Specialization</td>
                    <td style='padding:10px;'>$spec</td>
                </tr>
                <tr>
                    <td style='padding:10px;color:#666;'>Qualification</td>
                    <td style='padding:10px;'>$qual</td>
                </tr>
                <tr>
                    <td style='padding:10px;color:#666;'>Experience</td>
                    <td style='padding:10px;'>$exp years</td>
                </tr>
            </table>
            <br>
            <a href='http://localhost:8080/hospital_project/admin/manage_doctors.php'
               style='display:block;background:#0d6efd;color:white;padding:14px;border-radius:8px;text-decoration:none;font-weight:bold;text-align:center;'>
                Review Doctor Application
            </a>";

            sendEmail($admin_email, $admin_name, $subject_adm, $message_adm);

            echo "<script>
                    alert('Doctor registration submitted! Wait for admin approval.');
                    window.location='login.php';
                  </script>";

        // ==========================================
        // PATIENT REGISTRATION FLOW
        // ==========================================
        } else {

            // Patient ko email
            $subject = "Welcome to MediCare 3-Tier Telemedicine Platform - Registration Successful!";
            $message = "
            <h2 style='color:#0d6efd;'>Registration Successful!</h2>
            <p>Dear <strong>$fullname</strong>,</p>
            <p>Your account on <strong>MediCare 3-Tier Telemedicine Platform</strong> has been created successfully.</p>
            <table style='width:100%;border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px;color:#666;'>Name</td>
                    <td style='padding:8px;'><strong>$fullname</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px;color:#666;'>Email</td>
                    <td style='padding:8px;'><strong>$email</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px;color:#666;'>Role</td>
                    <td style='padding:8px;'><strong>Patient</strong></td>
                </tr>
            </table>
            <br>
            <a href='http://localhost:8080/hospital_project/login.php'
               style='display:block;background:#0d6efd;color:white;padding:12px 25px;border-radius:8px;text-decoration:none;font-weight:bold;text-align:center;'>
                Login Now
            </a>";

            sendEmail($email, $fullname, $subject, $message);

            // Admin ko patient registration notification
            $admin_email = ADMIN_NOTIFY_EMAIL;
            $admin_name  = 'Admin';

            $subject_p_adm = "New Patient Registration - MediCare 3-Tier Telemedicine Platform";
            $message_p_adm = "
            <h2 style='color:#0d6efd;'>New Patient Registered!</h2>
            <table style='width:100%;border-collapse:collapse;background:#f8f9fa;'>
                <tr>
                    <td style='padding:10px;color:#666;'>Name</td>
                    <td style='padding:10px;font-weight:600;'>$fullname</td>
                </tr>
                <tr>
                    <td style='padding:10px;color:#666;'>Email</td>
                    <td style='padding:10px;'>$email</td>
                </tr>
                <tr>
                    <td style='padding:10px;color:#666;'>Phone</td>
                    <td style='padding:10px;'>$phone</td>
                </tr>
            </table>";

            sendEmail($admin_email, $admin_name, $subject_p_adm, $message_p_adm);

            echo "<script>
                    alert('Registration successful! Please login.');
                    window.location='login.php';
                  </script>";
        }

    } else {
        echo "<script>alert('Error! Try again.');
              window.history.back();</script>";
    }
}
?>