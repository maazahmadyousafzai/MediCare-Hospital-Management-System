<?php
session_start();
include '../db/connection.php';
include '../db/email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $patient_id       = $_SESSION['user_id'];
    $doctor_id        = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $type             = $_POST['type'];
    $notes            = $_POST['notes'];

    // Same slot check
    $check = mysqli_query($conn,
        "SELECT id FROM appointments
         WHERE doctor_id='$doctor_id'
         AND appointment_date='$appointment_date'
         AND appointment_time='$appointment_time'
         AND status != 'cancelled'");

    if (mysqli_num_rows($check) > 0) {
        echo "<script>
                alert('This time slot is already booked!');
                window.history.back();
              </script>";
        exit();
    }

    // Appointment insert
    $sql = "INSERT INTO appointments
            (patient_id, doctor_id, appointment_date,
             appointment_time, type, notes, status)
            VALUES
            ('$patient_id','$doctor_id','$appointment_date',
             '$appointment_time','$type','$notes','pending')";

    if (mysqli_query($conn, $sql)) {

        $new_appointment_id = mysqli_insert_id($conn);

        // Doctor info fetch
        $doctor = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT fullname, email
             FROM users WHERE id='$doctor_id'"));

        $patient_name  = $_SESSION['user_name'];
        $patient_email = $_SESSION['user_email'];
        $apt_date = date('d M Y', strtotime($appointment_date));
        $apt_time = date('h:i A', strtotime($appointment_time));

        $admin_email = ADMIN_NOTIFY_EMAIL;

        // ── Patient ko email ──
        $subject_p = "Appointment Booked - MediCare 3-Tier Telemedicine Platform";
        $message_p = "
        <h2 style='color:#0d6efd;'>Appointment Booked!</h2>
        <p>Dear <strong>$patient_name</strong>,</p>
        <p>Your appointment has been booked successfully on <strong>MediCare 3-Tier Telemedicine Platform</strong>.</p>

        <table style='width:100%;border-collapse:collapse;
                      background:#f8f9fa;border-radius:8px;'>
            <tr>
                <td style='padding:10px;color:#666;width:40%;'>Doctor</td>
                <td style='padding:10px;font-weight:600;'>
                    Dr. " . $doctor['fullname'] . "
                </td>
            </tr>
            <tr style='background:#e8f0fe;'>
                <td style='padding:10px;color:#666;'>Date</td>
                <td style='padding:10px;font-weight:600;'>
                    $apt_date
                </td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;'>Time</td>
                <td style='padding:10px;font-weight:600;'>
                    $apt_time
                </td>
            </tr>
            <tr style='background:#e8f0fe;'>
                <td style='padding:10px;color:#666;'>Type</td>
                <td style='padding:10px;'>$type</td>
            </tr>
        </table>

        <br>
        <div style='background:#fff3cd;border-radius:10px;
                    padding:20px;border-left:5px solid #ffc107;'>
            <h3 style='color:#856404;margin:0 0 15px;'>
                Payment Instructions
            </h3>
            <p style='color:#666;margin-bottom:15px;'>
                Please pay <strong>Rs. 500</strong> 
                consultation fee using any method below:
            </p>
            <table style='width:100%;border-collapse:collapse;'>
                <tr style='background:#fff;'>
                    <td style='padding:10px;font-weight:700;
                               color:#EF3737;width:40%;'>
                        JazzCash
                    </td>
                    <td style='padding:10px;'>
                        <strong>0336-1576134</strong><br>
                        <small style='color:#888;'>
                            Account: MAAZ AHMAD
                        </small>
                    </td>
                </tr>
                <tr style='background:#f8f9fa;'>
                    <td style='padding:10px;font-weight:700;
                               color:#4CAF50;'>
                        Easypaisa
                    </td>
                    <td style='padding:10px;'>
                        <strong>0336-1576134</strong><br>
                        <small style='color:#888;'>
                            Account: MAAZ AHMAD
                        </small>
                    </td>
                </tr>
                <tr style='background:#fff;'>
                    <td style='padding:10px;font-weight:700;
                               color:#1565C0;'>
                        Bank Transfer
                    </td>
                    <td style='padding:10px;'>
                        <strong>Allied Bank Limited (ABL)</strong><br>
                        <small style='color:#888;'>
                            Account Title: Maaz Ahmad
                        </small><br>
                        <small style='color:#888;'>
                            Branch: Hayatabad, Peshawar
                        </small>
                    </td>
                </tr>
                <tr style='background:#f8f9fa;'>
                    <td style='padding:10px;font-weight:700;
                               color:#6A1B9A;'>
                        Amount
                    </td>
                    <td style='padding:10px;'>
                        <strong style='font-size:18px;
                                       color:#28a745;'>
                            Rs. 500
                        </strong>
                    </td>
                </tr>
            </table>
            <p style='color:#888;font-size:12px;
                      margin-top:15px;margin-bottom:0;'>
                After payment please confirm via the system portal.
            </p>
        </div>

        <br>
        <a href='http://localhost:8080/hospital_project/patient/my_appointments.php'
           style='display:block;background:#0d6efd;color:white;
                  padding:14px;border-radius:8px;
                  text-decoration:none;font-weight:bold;
                  text-align:center;'>
            View My Appointments
        </a>";

        sendEmail($patient_email, $patient_name,
            $subject_p, $message_p);

        // ── Doctor ko email ──
        $subject_d = "New Appointment Request - MediCare 3-Tier Telemedicine Platform";
        $message_d = "
        <h2 style='color:#0f5132;'>New Appointment Request!</h2>
        <p>Dear <strong>Dr. " . $doctor['fullname'] . "</strong>,</p>
        <p>You have a new appointment request on MediCare 3-Tier Telemedicine Platform.</p>

        <table style='width:100%;border-collapse:collapse;
                      background:#f8f9fa;border-radius:8px;'>
            <tr>
                <td style='padding:10px;color:#666;width:40%;'>
                    Patient
                </td>
                <td style='padding:10px;font-weight:600;'>
                    $patient_name
                </td>
            </tr>
            <tr style='background:#e8f5e9;'>
                <td style='padding:10px;color:#666;'>Date</td>
                <td style='padding:10px;font-weight:600;'>
                    $apt_date
                </td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;'>Time</td>
                <td style='padding:10px;font-weight:600;'>
                    $apt_time
                </td>
            </tr>
            <tr style='background:#e8f5e9;'>
                <td style='padding:10px;color:#666;'>Type</td>
                <td style='padding:10px;'>$type</td>
            </tr>
        </table>

        <br>
        <a href='http://localhost:8080/hospital_project/doctor/my_appointments.php'
           style='display:block;background:#0f5132;color:white;
                  padding:14px;border-radius:8px;
                  text-decoration:none;font-weight:bold;
                  text-align:center;'>
            View Appointments
        </a>";

        sendEmail($doctor['email'], $doctor['fullname'],
            $subject_d, $message_d);

        // ── Admin ko email ──
        $subject_a = "New Appointment Booked - MediCare 3-Tier Telemedicine Platform";
        $message_a = "
        <h2 style='color:#0d6efd;'>New Appointment Booked!</h2>
        <p>A patient has booked a new appointment on MediCare 3-Tier Telemedicine Platform.</p>

        <table style='width:100%;border-collapse:collapse;
                      background:#f8f9fa;border-radius:8px;'>
            <tr>
                <td style='padding:10px;color:#666;width:40%;'>Appointment ID</td>
                <td style='padding:10px;font-weight:600;'>#$new_appointment_id</td>
            </tr>
            <tr style='background:#e8f0fe;'>
                <td style='padding:10px;color:#666;'>Patient</td>
                <td style='padding:10px;font-weight:600;'>$patient_name</td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;'>Doctor</td>
                <td style='padding:10px;font-weight:600;'>Dr. " . $doctor['fullname'] . "</td>
            </tr>
            <tr style='background:#e8f0fe;'>
                <td style='padding:10px;color:#666;'>Date</td>
                <td style='padding:10px;font-weight:600;'>$apt_date</td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;'>Time</td>
                <td style='padding:10px;font-weight:600;'>$apt_time</td>
            </tr>
            <tr style='background:#e8f0fe;'>
                <td style='padding:10px;color:#666;'>Type</td>
                <td style='padding:10px;'>$type</td>
            </tr>
            <tr style='background:#fff3cd;'>
                <td style='padding:10px;color:#666;'>Status</td>
                <td style='padding:10px;font-weight:700;color:#856404;'>Pending Doctor Confirmation</td>
            </tr>
        </table>";

        sendEmail($admin_email, 'Admin', $subject_a, $message_a);

        echo "<script>
                alert('Appointment Booked! Check email for payment instructions.');
                window.location='my_appointments.php';
              </script>";

    } else {
        echo "<script>
                alert('Error! Please try again.');
                window.history.back();
              </script>";
    }
}
?>