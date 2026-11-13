<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'doctor') {
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
$action = $_POST['action'] ?? '';
$map = ['confirm' => ['pending','confirmed'], 'complete' => ['confirmed','completed'], 'cancel' => ['pending','cancelled']];
if (!$id || !isset($map[$action])) {
    http_response_code(400);
    exit('Invalid appointment action.');
}
[$from, $to] = $map[$action];
$s = mysqli_prepare($conn, 'UPDATE appointments SET status=? WHERE id=? AND doctor_id=? AND status=?');
$doctorId = (int) $_SESSION['user_id'];
mysqli_stmt_bind_param($s, 'siis', $to, $id, $doctorId, $from);
mysqli_stmt_execute($s);
$changed = mysqli_stmt_affected_rows($s);
mysqli_stmt_close($s);
if ($changed !== 1) {
    $_SESSION['action_notice'] = 'Appointment was not changed. It may have already been updated.';
} else {
    $_SESSION['action_notice'] = 'Appointment status updated.';
}
header('Location: my_appointments.php');
exit();
?>