<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function sendJson($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

if (
    !isset($_SESSION['user_id'], $_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['patient', 'doctor'], true)
) {
    sendJson(401, ['success' => false, 'message' => 'Authentication required']);
}

$loggedInId = (int) $_SESSION['user_id'];
$role = $_SESSION['user_role'];

$input = json_decode(file_get_contents('php://input'), true);
$appointmentId = filter_var(
    $input['appointment_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$appointmentId || $appointmentId < 1) {
    sendJson(400, ['success' => false, 'message' => 'Invalid appointment ID']);
}

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../zego/src/ZEGO/ZegoAssistantToken.php';
require_once __DIR__ . '/../zego/src/ZEGO/ZegoErrorCodes.php';
require_once __DIR__ . '/../zego/src/ZEGO/ZegoServerAssistant.php';

$configPath = dirname(__DIR__, 3) . '/secure/medicare-zego.php';

if (!is_file($configPath)) {
    sendJson(500, ['success' => false, 'message' => 'Video configuration unavailable']);
}

$zegoConfig = require $configPath;
$appId = $zegoConfig['app_id'] ?? null;
$serverSecret = $zegoConfig['server_secret'] ?? null;

if (
    !is_numeric($appId) ||
    (int) $appId < 1 ||
    !is_string($serverSecret) ||
    strlen($serverSecret) !== 32
) {
    sendJson(500, ['success' => false, 'message' => 'Invalid video configuration']);
}

$sql = "SELECT
            a.id,
            a.patient_id,
            a.doctor_id,
            a.type,
            a.status,
            p.fullname AS patient_name,
            d.fullname AS doctor_name
        FROM appointments a
        INNER JOIN users p ON p.id = a.patient_id
        INNER JOIN users d ON d.id = a.doctor_id
        WHERE a.id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    sendJson(500, ['success' => false, 'message' => 'Database error']);
}

mysqli_stmt_bind_param($stmt, 'i', $appointmentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$appointment) {
    sendJson(404, ['success' => false, 'message' => 'Appointment not found']);
}

if (
    ($role === 'patient' && (int) $appointment['patient_id'] !== $loggedInId) ||
    ($role === 'doctor' && (int) $appointment['doctor_id'] !== $loggedInId)
) {
    sendJson(403, ['success' => false, 'message' => 'Appointment access denied']);
}

if (
    $appointment['type'] !== 'video-call' ||
    $appointment['status'] !== 'confirmed'
) {
    sendJson(403, ['success' => false, 'message' => 'Video appointment is not available']);
}

$userId = $role . '_' . $loggedInId;
$userName = ($role === 'patient')
    ? $appointment['patient_name']
    : 'Dr. ' . $appointment['doctor_name'];
$roomId = (string) $appointment['id'];

$tokenResult = \ZEGO\ZegoServerAssistant::generateToken04(
    (int) $appId,
    $userId,
    $serverSecret,
    3600,
    ''
);

if (
    $tokenResult->code !== \ZEGO\ZegoErrorCodes::success ||
    empty($tokenResult->token)
) {
    error_log('ZEGOCLOUD token generation failed. Code: ' . $tokenResult->code);
    sendJson(500, ['success' => false, 'message' => 'Could not generate video token']);
}

sendJson(200, [
    'success' => true,
    'app_id' => (int) $appId,
    'token' => $tokenResult->token,
    'room_id' => $roomId,
    'user_id' => $userId,
    'user_name' => $userName
]);
