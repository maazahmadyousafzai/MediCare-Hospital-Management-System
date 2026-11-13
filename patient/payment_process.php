<?php
session_start();
require_once '../db/connection.php';
require_once '../db/email.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'patient') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_appointments.php');
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    exit('Invalid or expired request. Please return to the payment page and try again.');
}

$appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$patientId = (int) $_SESSION['user_id'];
$method = trim($_POST['payment_method'] ?? '');
$reference = trim($_POST['transaction_reference'] ?? '');
$allowedMethods = ['jazzcash', 'easypaisa', 'bank'];

if (!$appointmentId || !in_array($method, $allowedMethods, true) ||
    strlen($reference) < 5 || strlen($reference) > 100 ||
    !preg_match('/^[A-Za-z0-9\-\/_ ]+$/', $reference)) {
    header('Location: my_appointments.php');
    exit();
}

// Only the owner of a confirmed appointment may submit a payment reference.
$appointmentStmt = mysqli_prepare(
    $conn,
    "SELECT id, appointment_date, appointment_time
     FROM appointments
     WHERE id = ? AND patient_id = ? AND status = 'confirmed'
     LIMIT 1"
);
mysqli_stmt_bind_param($appointmentStmt, 'ii', $appointmentId, $patientId);
mysqli_stmt_execute($appointmentStmt);
$appointmentResult = mysqli_stmt_get_result($appointmentStmt);
$appointment = $appointmentResult ? mysqli_fetch_assoc($appointmentResult) : null;
mysqli_stmt_close($appointmentStmt);

if (!$appointment) {
    header('Location: my_appointments.php');
    exit();
}

// Do not allow multiple active or completed payment submissions for one appointment.
$existingStmt = mysqli_prepare(
    $conn,
    "SELECT id, status FROM payments
     WHERE appointment_id = ? AND status IN ('pending', 'completed')
     LIMIT 1"
);
mysqli_stmt_bind_param($existingStmt, 'i', $appointmentId);
mysqli_stmt_execute($existingStmt);
$existingResult = mysqli_stmt_get_result($existingStmt);
$existingPayment = $existingResult ? mysqli_fetch_assoc($existingResult) : null;
mysqli_stmt_close($existingStmt);

if ($existingPayment) {
    header('Location: my_appointments.php');
    exit();
}

// Store the provider's reference, not any PIN, password, CVV or card number.
$amount = 500.00;
$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO payments
        (appointment_id, patient_id, amount, payment_method, transaction_id, status)
     VALUES (?, ?, ?, ?, ?, 'pending')"
);

if (!$insertStmt) {
    error_log('MediCare payment submission prepare failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Unable to submit payment details right now. Please try again later.');
}

mysqli_stmt_bind_param($insertStmt, 'iidss', $appointmentId, $patientId, $amount, $method, $reference);
if (!mysqli_stmt_execute($insertStmt)) {
    error_log('MediCare payment submission failed: ' . mysqli_stmt_error($insertStmt));
    mysqli_stmt_close($insertStmt);
    http_response_code(500);
    exit('Unable to submit payment details right now. Please try again later.');
}
mysqli_stmt_close($insertStmt);

// Notify administrator that a reference is awaiting manual verification.
$adminEmail = ADMIN_NOTIFY_EMAIL;
$adminName = ADMIN_NOTIFY_NAME;
$methodName = ucfirst($method === 'bank' ? 'Bank Transfer' : $method);
$subject = 'Payment Reference Awaiting Verification - MediCare';
$message = '
<h2>Payment submitted for verification</h2>
<p>A patient has submitted a payment reference. No payment has been marked as completed.</p>
<ul>
<li>Appointment ID: ' . (int) $appointmentId . '</li>
<li>Payment method: ' . htmlspecialchars($methodName, ENT_QUOTES, 'UTF-8') . '</li>
<li>Reference: ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</li>
<li>Amount claimed: Rs. 500</li>
<li>Status: Pending verification</li>
</ul>
<p>Please verify the transaction in the provider account before approving it in the MediCare admin panel.</p>';

if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    sendEmail($adminEmail, $adminName, $subject, $message);
}

header('Location: payment_receipt.php?txn=' . rawurlencode($reference) . '&apt=' . $appointmentId);
exit();
?>