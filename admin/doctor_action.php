<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
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
$allowed = ['approve', 'block', 'unblock', 'delete', 'reject'];
if (!$id || !in_array($action, $allowed, true)) {
    http_response_code(400);
    exit('Invalid doctor action.');
}
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? AND role = 'doctor' LIMIT 1");
mysqli_stmt_bind_param($check, 'i', $id);
mysqli_stmt_execute($check);
$found = mysqli_stmt_get_result($check);
$doctor = $found ? mysqli_fetch_assoc($found) : null;
mysqli_stmt_close($check);
if (!$doctor) {
    http_response_code(404);
    exit('Doctor not found.');
}

if ($action === 'approve') {
    $u = mysqli_prepare($conn, "UPDATE users SET status='active' WHERE id=? AND role='doctor'");
    mysqli_stmt_bind_param($u, 'i', $id); mysqli_stmt_execute($u); mysqli_stmt_close($u);
    $d = mysqli_prepare($conn, "UPDATE doctors SET status='active' WHERE user_id=?");
    mysqli_stmt_bind_param($d, 'i', $id); mysqli_stmt_execute($d); mysqli_stmt_close($d);
} elseif ($action === 'block' || $action === 'unblock') {
    $status = $action === 'block' ? 'blocked' : 'active';
    $u = mysqli_prepare($conn, "UPDATE users SET status=? WHERE id=? AND role='doctor'");
    mysqli_stmt_bind_param($u, 'si', $status, $id); mysqli_stmt_execute($u); mysqli_stmt_close($u);
} else {
    mysqli_begin_transaction($conn);
    try {
        foreach (['payments','ratings','prescriptions'] as $table) {
            $sql = "DELETE t FROM `$table` t INNER JOIN appointments a ON t.appointment_id=a.id WHERE a.doctor_id=?";
            $s = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        }
        $s = mysqli_prepare($conn, 'DELETE FROM appointments WHERE doctor_id=?');
        mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        $s = mysqli_prepare($conn, 'DELETE FROM doctors WHERE user_id=?');
        mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        $s = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role='doctor'");
        mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('MediCare doctor deletion failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Unable to delete doctor records.');
    }
}
header('Location: manage_doctors.php');
exit();
?>