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
if (!$id || !in_array($action, ['block','unblock','delete'], true)) {
    http_response_code(400);
    exit('Invalid patient action.');
}
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE id=? AND role='patient' LIMIT 1");
mysqli_stmt_bind_param($check, 'i', $id); mysqli_stmt_execute($check);
$r = mysqli_stmt_get_result($check); $patient = $r ? mysqli_fetch_assoc($r) : null; mysqli_stmt_close($check);
if (!$patient) { http_response_code(404); exit('Patient not found.'); }

if ($action === 'block' || $action === 'unblock') {
    $status = $action === 'block' ? 'blocked' : 'active';
    $s = mysqli_prepare($conn, "UPDATE users SET status=? WHERE id=? AND role='patient'");
    mysqli_stmt_bind_param($s, 'si', $status, $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
} else {
    mysqli_begin_transaction($conn);
    try {
        foreach (['payments','ratings','prescriptions'] as $table) {
            $sql = "DELETE t FROM `$table` t INNER JOIN appointments a ON t.appointment_id=a.id WHERE a.patient_id=?";
            $s = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        }
        $s = mysqli_prepare($conn, 'DELETE FROM appointments WHERE patient_id=?');
        mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        $s = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role='patient'");
        mysqli_stmt_bind_param($s, 'i', $id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('MediCare patient deletion failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Unable to delete patient records.');
    }
}
header('Location: manage_patients.php');
exit();
?>