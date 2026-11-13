<?php
session_start();
require_once '../db/connection.php';
require_once '../db/email.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'patient') {
    header('Location: ../login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Invalid request token. Please reload the booking form.');
}

$patientId = (int) $_SESSION['user_id'];
$doctorId = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
$date = trim($_POST['appointment_date'] ?? '');
$time = trim($_POST['appointment_time'] ?? '');
$type = $_POST['type'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$dateObject = DateTime::createFromFormat('!Y-m-d', $date);
$dateValid = $dateObject && $dateObject->format('Y-m-d') === $date;
$timeValid = (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);

if (!$doctorId || !$dateValid || !$timeValid ||
    !in_array($type, ['in-person', 'video-call'], true) ||
    strlen($notes) > 1000 || $date < date('Y-m-d') ||
    ($date === date('Y-m-d') && $time <= date('H:i'))) {
    http_response_code(400);
    exit('Please provide a valid doctor, future date/time, consultation type, and notes under 1,000 characters.');
}
if ($type === 'video-call') {
    $_SESSION['booking_error'] = 'Video consultations are temporarily unavailable. Please choose an in-person appointment.';
    header('Location: book_appointment.php');
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    mysqli_begin_transaction($conn);

    $doctorStmt = mysqli_prepare($conn,
        "SELECT u.id, u.fullname, u.email
         FROM users u INNER JOIN doctors d ON d.user_id = u.id
         WHERE u.id = ? AND u.role = 'doctor'
           AND u.status = 'active' AND d.status = 'active'
         LIMIT 1 FOR UPDATE");
    mysqli_stmt_bind_param($doctorStmt, 'i', $doctorId);
    mysqli_stmt_execute($doctorStmt);
    $doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($doctorStmt));
    mysqli_stmt_close($doctorStmt);
    if (!$doctor) {
        throw new DomainException('The selected doctor is not available.');
    }

    $sqlTime = $time . ':00';
    $slotStmt = mysqli_prepare($conn,
        "SELECT id FROM appointments
         WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
           AND status <> 'cancelled'
         LIMIT 1 FOR UPDATE");
    mysqli_stmt_bind_param($slotStmt, 'iss', $doctorId, $date, $sqlTime);
    mysqli_stmt_execute($slotStmt);
    $slotTaken = mysqli_fetch_assoc(mysqli_stmt_get_result($slotStmt));
    mysqli_stmt_close($slotStmt);
    if ($slotTaken) {
        throw new DomainException('This appointment time is already booked. Please choose another slot.');
    }

    $insertStmt = mysqli_prepare($conn,
        "INSERT INTO appointments
            (patient_id, doctor_id, appointment_date, appointment_time, type, notes, status)
         VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($insertStmt, 'iissss', $patientId, $doctorId, $date, $sqlTime, $type, $notes);
    mysqli_stmt_execute($insertStmt);
    $appointmentId = mysqli_insert_id($conn);
    mysqli_stmt_close($insertStmt);
    mysqli_commit($conn);
} catch (DomainException $e) {
    mysqli_rollback($conn);
    $_SESSION['booking_error'] = $e->getMessage();
    header('Location: book_appointment.php');
    exit();
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('MediCare appointment booking failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to book this appointment right now. Please try again later.');
}

$patientStmt = mysqli_prepare($conn,
    'SELECT fullname, email FROM users WHERE id = ? AND role = "patient" LIMIT 1');
mysqli_stmt_bind_param($patientStmt, 'i', $patientId);
mysqli_stmt_execute($patientStmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($patientStmt));
mysqli_stmt_close($patientStmt);

$dateLabel = date('d M Y', strtotime($date));
$timeLabel = date('h:i A', strtotime($sqlTime));
$doctorName = htmlspecialchars($doctor['fullname'], ENT_QUOTES, 'UTF-8');
if ($patient && filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
    $patientName = htmlspecialchars($patient['fullname'], ENT_QUOTES, 'UTF-8');
    $message = "<h2>Appointment request received</h2>
        <p>Dear <strong>{$patientName}</strong>, your appointment request has been recorded.</p>
        <ul><li>Appointment ID: " . (int) $appointmentId . "</li>
        <li>Doctor: Dr. {$doctorName}</li><li>Date: {$dateLabel}</li>
        <li>Time: {$timeLabel}</li><li>Type: In-person</li>
        <li>Status: Pending doctor confirmation</li></ul>
        <p>Check your MediCare account for updates. Payment instructions are provided separately after confirmation.</p>";
    sendEmail($patient['email'], $patient['fullname'], 'Appointment Request Received - MediCare', $message);
}
if (filter_var(ADMIN_NOTIFY_EMAIL, FILTER_VALIDATE_EMAIL)) {
    $safePatient = htmlspecialchars($patient['fullname'] ?? 'Patient', ENT_QUOTES, 'UTF-8');
    $adminMessage = "<h2>New appointment request</h2>
        <p>Appointment ID: " . (int) $appointmentId . "</p>
        <p>Patient: {$safePatient}</p><p>Doctor: Dr. {$doctorName}</p>
        <p>Date: {$dateLabel} at {$timeLabel}</p><p>Status: Pending doctor confirmation</p>";
    sendEmail(ADMIN_NOTIFY_EMAIL, ADMIN_NOTIFY_NAME, 'New Appointment Request - MediCare', $adminMessage);
}
$_SESSION['booking_success'] = 'Appointment request submitted. It is awaiting doctor confirmation.';
header('Location: my_appointments.php');
exit();
?>