<?php
session_start();
include '../db/connection.php';
include '../db/email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$appointment_id = $_GET['id'];
$payment_method = $_GET['method'];
$patient_id     = $_SESSION['user_id'];
$amount         = 500;

// Unique Transaction ID
$transaction_id = 'MC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

// Payment save karo
$sql = "INSERT INTO payments
        (appointment_id, patient_id, amount,
         payment_method, transaction_id, status)
        VALUES
        ('$appointment_id','$patient_id','$amount',
         '$payment_method','$transaction_id','completed')";

if (mysqli_query($conn, $sql)) {

    // Appointment + patient + doctor details fetch
    $apt = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*,
                doc.fullname as doctor_name,
                pat.fullname as patient_name,
                pat.email    as patient_email,
                d.specialization
         FROM appointments a
         JOIN users doc ON a.doctor_id  = doc.id
         JOIN users pat ON a.patient_id = pat.id
         JOIN doctors d  ON a.doctor_id = d.user_id
         WHERE a.id = '$appointment_id'"));

    // Admin notification email
    $admin_email = ADMIN_NOTIFY_EMAIL;
    $admin_name  = ADMIN_NOTIFY_NAME;

    $apt_date  = date('d M Y', strtotime($apt['appointment_date']));
    $apt_time  = date('h:i A', strtotime($apt['appointment_time']));
    $paid_at   = date('d M Y - h:i A');

    // Payment method name & updated account info
    if ($payment_method == 'jazzcash') {
        $method_name    = 'JazzCash';
        $method_account = '0336-1576134 (MAAZ AHMAD)';
        $method_color   = '#EF3737';
    } elseif ($payment_method == 'easypaisa') {
        $method_name    = 'Easypaisa';
        $method_account = '0336-1576134 (MAAZ AHMAD)';
        $method_color   = '#4CAF50';
    } elseif ($payment_method == 'bank') {
        $method_name    = 'Bank Transfer';
        $method_account = 'Allied Bank Limited - ABL (Maaz Ahmad)';
        $method_color   = '#1565C0';
    } else {
        $method_name    = 'Debit/Credit Card';
        $method_account = 'Card Payment';
        $method_color   = '#6A1B9A';
    }

    // ── Patient ko Receipt Email ──
    $subject_p = "Payment Receipt - MediCare 3-Tier Telemedicine Platform";
    $message_p = "
    <h2 style='color:#28a745;text-align:center;'>Payment Successful!</h2>

    <div style='background:#f8f9fa;border-radius:10px;padding:20px;margin:15px 0;'>
        <h3 style='color:#0d6efd;text-align:center;margin-bottom:15px;'>
            Payment Receipt
        </h3>
        <table style='width:100%;border-collapse:collapse;'>
            <tr style='background:#e8f0fe;'>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Transaction ID</td>
                <td style='padding:10px;font-weight:700;border-bottom:1px solid #ddd;color:#0d6efd;'>
                    $transaction_id
                </td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Patient Name</td>
                <td style='padding:10px;font-weight:600;border-bottom:1px solid #ddd;'>
                    " . $apt['patient_name'] . "
                </td>
            </tr>
            <tr style='background:#f8f9fa;'>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Doctor</td>
                <td style='padding:10px;font-weight:600;border-bottom:1px solid #ddd;'>
                    Dr. " . $apt['doctor_name'] . "
                </td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Specialization</td>
                <td style='padding:10px;border-bottom:1px solid #ddd;'>
                    " . $apt['specialization'] . "
                </td>
            </tr>
            <tr style='background:#f8f9fa;'>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Appointment Date</td>
                <td style='padding:10px;border-bottom:1px solid #ddd;'>$apt_date</td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Appointment Time</td>
                <td style='padding:10px;border-bottom:1px solid #ddd;'>$apt_time</td>
            </tr>
            <tr style='background:#f8f9fa;'>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Payment Method</td>
                <td style='padding:10px;font-weight:600;border-bottom:1px solid #ddd;'>
                    $method_name
                </td>
            </tr>
            <tr>
                <td style='padding:10px;color:#666;border-bottom:1px solid #ddd;'>Amount Paid</td>
                <td style='padding:10px;font-weight:700;color:#28a745;
                           font-size:18px;border-bottom:1px solid #ddd;'>
                    Rs. 500
                </td>
            </tr>
            <tr style='background:#d1e7dd;'>
                <td style='padding:10px;color:#666;'>Payment Status</td>
                <td style='padding:10px;font-weight:700;color:#28a745;'>
                    PAID
                </td>
            </tr>
        </table>
        <p style='text-align:center;color:#888;font-size:12px;margin-top:15px;'>
            Paid on: $paid_at
        </p>
    </div>

    <a href='http://localhost:8080/hospital_project/patient/my_appointments.php'
       style='display:block;background:#0d6efd;color:white;padding:14px;
              border-radius:8px;text-decoration:none;font-weight:bold;
              text-align:center;margin-top:10px;'>
        View My Appointments
    </a>";

    sendEmail($apt['patient_email'], $apt['patient_name'], $subject_p, $message_p);

    // ── Admin ko Notification Email ──
    $subject_a = "New Payment Received - MediCare 3-Tier Telemedicine Platform";
    $message_a = "
    <h2 style='color:#0d6efd;'>New Payment Received!</h2>
    <p style='color:#666;'>
        A patient has made a payment on MediCare 3-Tier Telemedicine Platform.
        Please verify in your account.
    </p>

    <table style='width:100%;border-collapse:collapse;background:#f8f9fa;border-radius:8px;'>
        <tr style='background:#e8f0fe;'>
            <td style='padding:12px;color:#666;font-weight:600;width:40%;'>Transaction ID</td>
            <td style='padding:12px;font-weight:700;color:#0d6efd;'>$transaction_id</td>
        </tr>
        <tr>
            <td style='padding:12px;color:#666;font-weight:600;'>Patient Name</td>
            <td style='padding:12px;font-weight:600;'>" . $apt['patient_name'] . "</td>
        </tr>
        <tr style='background:#f0f0f0;'>
            <td style='padding:12px;color:#666;font-weight:600;'>Patient ID</td>
            <td style='padding:12px;'>#$patient_id</td>
        </tr>
        <tr>
            <td style='padding:12px;color:#666;font-weight:600;'>Doctor</td>
            <td style='padding:12px;'>Dr. " . $apt['doctor_name'] . "</td>
        </tr>
        <tr style='background:#f0f0f0;'>
            <td style='padding:12px;color:#666;font-weight:600;'>Appointment Date</td>
            <td style='padding:12px;'>$apt_date at $apt_time</td>
        </tr>
        <tr>
            <td style='padding:12px;color:#666;font-weight:600;'>Payment Method</td>
            <td style='padding:12px;font-weight:700;color:$method_color;'>$method_name</td>
        </tr>
        <tr style='background:#f0f0f0;'>
            <td style='padding:12px;color:#666;font-weight:600;'>Account Used</td>
            <td style='padding:12px;font-weight:700;'>$method_account</td>
        </tr>
        <tr>
            <td style='padding:12px;color:#666;font-weight:600;'>Amount</td>
            <td style='padding:12px;font-weight:700;color:#28a745;font-size:20px;'>Rs. 500</td>
        </tr>
        <tr style='background:#d1e7dd;'>
            <td style='padding:12px;color:#666;font-weight:600;'>Status</td>
            <td style='padding:12px;font-weight:700;color:#28a745;'>PAID</td>
        </tr>
        <tr style='background:#f0f0f0;'>
            <td style='padding:12px;color:#666;font-weight:600;'>Payment Date</td>
            <td style='padding:12px;'>$paid_at</td>
        </tr>
    </table>

    <br>
    <div style='background:#fff3cd;border-radius:10px;
                padding:15px 20px;border-left:5px solid #ffc107;'>
        <p style='margin:0;color:#856404;font-weight:600;'>Action Required!</p>
        <p style='margin:8px 0 0;color:#856404;'>
            Please verify this payment in your
            $method_name account and confirm the transaction manually.
        </p>
    </div>

    <br>
    <div style='background:#f8f9fa;border-radius:10px;
                padding:15px;border:1px solid #e0e0e0;'>
        <p style='margin:0;font-weight:700;color:#333;'>Your Payment Accounts:</p>
        <p style='margin:8px 0 0;'>JazzCash: 0336-1576134 (MAAZ AHMAD)</p>
        <p style='margin:5px 0 0;'>Easypaisa: 0336-1576134 (MAAZ AHMAD)</p>
        <p style='margin:5px 0 0;'>Allied Bank Limited (ABL): Maaz Ahmad (Branch: Hayatabad, Peshawar)</p>
    </div>";

    sendEmail($admin_email, $admin_name, $subject_a, $message_a);

    // Receipt page pe bhejo
    header("Location: payment_receipt.php?txn=$transaction_id&apt=$appointment_id");
    exit();

} else {
    echo "<script>
            alert('Payment failed! Please try again.');
            window.history.back();
          </script>";
}
?>