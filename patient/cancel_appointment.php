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
    exit('Invalid request token.');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$patientId = (int) $_SESSION['user_id'];
if (!$id) {
    header('Location: my_appointments.php');
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE appointments SET status='cancelled' WHERE id=? AND patient_id=? AND status='pending'");
mysqli_stmt_bind_param($stmt, 'ii', $id, $patientId);
mysqli_stmt_execute($stmt);
$changed = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

$_SESSION['appointment_notice'] = $changed === 1
    ? 'Appointment cancellation submitted.'
    : 'Only your pending appointments can be cancelled.';
header('Location: my_appointments.php');
exit();
?>