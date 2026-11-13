<?php
session_start();
require_once '../db/connection.php';
require_once '../db/email.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'patient') {
    header('Location: ../login.php'); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method not allowed.');
}
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403); exit('Invalid or expired request token.');
}

$appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$patientId = (int) $_SESSION['user_id'];
$method = $_POST['payment_method'] ?? '';
$reference = trim($_POST['transaction_reference'] ?? '');
$allowedMethods = ['jazzcash', 'easypaisa', 'bank'];
if (!$appointmentId || !in_array($method, $allowedMethods, true) ||
    strlen($reference) < 5 || strlen($reference) > 100 ||
    !preg_match('/^[A-Za-z0-9\-\/_ ]+$/', $reference)) {
    $_SESSION['payment_notice'] = 'Please enter a valid payment method and transaction reference.';
    header('Location: my_appointments.php'); exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$amount = 500.00;
try {
    mysqli_begin_transaction($conn);

    // Lock the owned appointment row to serialize concurrent payment submissions.
    $appointmentStmt = mysqli_prepare($conn,
        "SELECT id FROM appointments
         WHERE id = ? AND patient_id = ? AND status = 'confirmed'
         LIMIT 1 FOR UPDATE");
    mysqli_stmt_bind_param($appointmentStmt, 'ii', $appointmentId, $patientId);
    mysqli_stmt_execute($appointmentStmt);
    $appointment = mysqli_fetch_assoc(mysqli_stmt_get_result($appointmentStmt));
    mysqli_stmt_close($appointmentStmt);
    if (!$appointment) {
        throw new DomainException('Only your confirmed appointments can receive payment references.');
    }

    $existingStmt = mysqli_prepare($conn,
        "SELECT id FROM payments
         WHERE appointment_id = ? AND status IN ('pending', 'completed')
         LIMIT 1");
    mysqli_stmt_bind_param($existingStmt, 'i', $appointmentId);
    mysqli_stmt_execute($existingStmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existingStmt));
    mysqli_stmt_close($existingStmt);
    if ($existing) {
        throw new DomainException('A payment reference is already pending or has been verified for this appointment.');
    }

    $insertStmt = mysqli_prepare($conn,
        "INSERT INTO payments
         (appointment_id, patient_id, amount, payment_method, transaction_id, status)
         VALUES (?, ?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($insertStmt, 'iidss', $appointmentId, $patientId, $amount, $method, $reference);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
    mysqli_commit($conn);
} catch (DomainException $e) {
    mysqli_rollback($conn);
    $_SESSION['payment_notice'] = $e->getMessage();
    header('Location: my_appointments.php'); exit();
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('MediCare payment reference submission failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to submit your payment reference right now.');
}

$adminEmail = ADMIN_NOTIFY_EMAIL;
$adminName = ADMIN_NOTIFY_NAME;
$methodName = $method === 'bank' ? 'Bank Transfer' : ucfirst($method);
$subject = 'Payment Reference Awaiting Verification - MediCare';
$message = '<h2>Payment submitted for verification</h2>
<p>A patient has submitted a payment reference. No payment has been marked as completed.</p>
<ul>
<li>Appointment ID: ' . (int) $appointmentId . '</li>
<li>Payment method: ' . htmlspecialchars($methodName, ENT_QUOTES, 'UTF-8') . '</li>
<li>Reference: ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</li>
<li>Amount claimed: Rs. ' . number_format($amount, 2) . '</li>
<li>Status: Pending verification</li>
</ul>
<p>Verify the transaction in the provider account before approving it in the MediCare admin panel.</p>';

if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    sendEmail($adminEmail, $adminName, $subject, $message);
}

header('Location: payment_receipt.php?txn=' . rawurlencode($reference) . '&apt=' . $appointmentId);
exit();
?>