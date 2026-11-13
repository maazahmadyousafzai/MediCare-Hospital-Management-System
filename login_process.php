<?php
session_start();
require_once 'db/connection.php';

function loginRedirect($message) {
    echo '<script>alert(' . json_encode($message) . '); window.location="login.php";</script>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');

$allowedRoles = ['admin', 'doctor', 'patient'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || !in_array($role, $allowedRoles, true)) {
    loginRedirect('Please enter valid login details.');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT u.id, u.fullname, u.email, u.password, u.role, u.status,
            d.status AS doctor_status
     FROM users u
     LEFT JOIN doctors d ON d.user_id = u.id
     WHERE u.email = ? AND u.role = ?
     LIMIT 1"
);

if (!$stmt) {
    error_log('MediCare login query preparation failed: ' . mysqli_error($conn));
    loginRedirect('Login is temporarily unavailable. Please try again.');
}

mysqli_stmt_bind_param($stmt, 'ss', $email, $role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$user) {
    loginRedirect('Invalid email, password or account type.');
}

$storedHash = $user['password'];
$passwordIsValid = password_verify($password, $storedHash);
$needsHashUpgrade = false;

// Temporary compatibility for legacy MD5 accounts.
// Successful legacy login upgrades the password to PASSWORD_DEFAULT.
if (!$passwordIsValid && hash_equals(md5($password), (string) $storedHash)) {
    $passwordIsValid = true;
    $needsHashUpgrade = true;
}

if (!$passwordIsValid) {
    loginRedirect('Invalid email, password or account type.');
}

if ($user['status'] !== 'active') {
    if ($user['status'] === 'pending') {
        loginRedirect('Your account is pending approval. Please wait for admin approval.');
    }
    loginRedirect('Your account is not active. Please contact the administrator.');
}

// Doctor approval is stored separately in the doctors table.
if ($role === 'doctor' && $user['doctor_status'] !== 'active') {
    loginRedirect('Your doctor account is pending approval or inactive.');
}

if ($needsHashUpgrade || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $update = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
    if ($update) {
        mysqli_stmt_bind_param($update, 'si', $newHash, $user['id']);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
    } else {
        error_log('MediCare password hash upgrade failed: ' . mysqli_error($conn));
    }
}

// Prevent session fixation after successful authentication.
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = $user['fullname'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_email'] = $user['email'];

$dashboards = [
    'admin' => 'admin/dashboard.php',
    'doctor' => 'doctor/dashboard.php',
    'patient' => 'patient/dashboard.php'
];

header('Location: ' . $dashboards[$user['role']]);
exit();
?>