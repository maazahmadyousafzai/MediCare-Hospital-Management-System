<?php
session_start();
require_once 'db/connection.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    $specialization = trim($_POST['specialization'] ?? 'General Physician');
    $qualification = trim($_POST['qualification'] ?? 'MBBS');
    $fee = isset($_POST['fee']) ? (float) $_POST['fee'] : 0.0;

    // Public registration must never allow creation of administrator accounts.
    if (!in_array($role, ['patient', 'doctor'], true)) {
        $error_msg = 'Please select a valid account type.';
    } elseif ($fullname === '' || $email === '' || $phone === '' || $password === '') {
        $error_msg = 'All required fields must be filled!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address!';
    } elseif ($password !== $cpassword) {
        $error_msg = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error_msg = 'Password must be at least 6 characters long!';
    } elseif (strlen($fullname) > 100 || strlen($email) > 100 || strlen($phone) > 20) {
        $error_msg = 'One or more fields exceed the allowed length.';
    } else {
        $checkStmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');

        if (!$checkStmt) {
            error_log('MediCare registration check failed: ' . mysqli_error($conn));
            $error_msg = 'Registration is temporarily unavailable. Please try again.';
        } else {
            mysqli_stmt_bind_param($checkStmt, 's', $email);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            $emailExists = mysqli_stmt_num_rows($checkStmt) > 0;
            mysqli_stmt_close($checkStmt);

            if ($emailExists) {
                $error_msg = 'An account with this email address is already registered!';
            } else {
                mysqli_begin_transaction($conn);

                try {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt = mysqli_prepare(
                        $conn,
                        'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
                    );

                    if (!$insertStmt) {
                        throw new Exception('Unable to prepare user registration.');
                    }

                    mysqli_stmt_bind_param($insertStmt, 'sssss', $fullname, $email, $phone, $hashedPassword, $role);
                    if (!mysqli_stmt_execute($insertStmt)) {
                        throw new Exception('Unable to create user account.');
                    }

                    $userId = mysqli_insert_id($conn);
                    mysqli_stmt_close($insertStmt);

                    if ($role === 'doctor') {
                        $feeColumn = mysqli_query($conn, "SHOW COLUMNS FROM doctors LIKE 'fee'");
                        $hasFeeColumn = $feeColumn && mysqli_num_rows($feeColumn) > 0;

                        if ($hasFeeColumn) {
                            $doctorStmt = mysqli_prepare(
                                $conn,
                                'INSERT INTO doctors (user_id, specialization, qualification, fee) VALUES (?, ?, ?, ?)'
                            );
                            if (!$doctorStmt) {
                                throw new Exception('Unable to prepare doctor profile.');
                            }
                            mysqli_stmt_bind_param($doctorStmt, 'issd', $userId, $specialization, $qualification, $fee);
                        } else {
                            $doctorStmt = mysqli_prepare(
                                $conn,
                                'INSERT INTO doctors (user_id, specialization, qualification) VALUES (?, ?, ?)'
                            );
                            if (!$doctorStmt) {
                                throw new Exception('Unable to prepare doctor profile.');
                            }
                            mysqli_stmt_bind_param($doctorStmt, 'iss', $userId, $specialization, $qualification);
                        }

                        if (!mysqli_stmt_execute($doctorStmt)) {
                            throw new Exception('Unable to save doctor profile.');
                        }
                        mysqli_stmt_close($doctorStmt);
                    }

                    mysqli_commit($conn);
                    echo "<script>
                            alert('Registration successful! Please log in with your credentials.');
                            window.location='login.php';
                          </script>";
                    exit();
                } catch (Throwable $exception) {
                    mysqli_rollback($conn);
                    error_log('MediCare registration failed: ' . $exception->getMessage());
                    $error_msg = 'Registration failed. Please check your details and try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — MediCare Telemedicine Portal</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0284c7;
            --secondary-color: #0369a1;
            --accent-color: #38bdf8;
            --dark-bg: #0f172a;
            --light-bg: #f8fafc;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0284c7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 45px 40px;
            width: 100%;
            max-width: 540px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .register-logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .register-logo .icon-box {
            width: 70px;
            height: 70px;
            background: #e0f2fe;
            color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            font-size: 30px;
            margin-bottom: 12px;
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.15);
        }

        .register-logo h3 {
            font-weight: 800;
            color: var(--dark-bg);
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }

        .register-logo p {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            font-size: 13px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            font-size: 14px;
            background-color: #f8fafc;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #0f172a;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12);
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-right: none;
            color: var(--primary-color);
            padding-left: 16px;
            padding-right: 16px;
        }

        .input-group .form-control {
            border-radius: 0 12px 12px 0;
            border-left: none;
        }

        /* Role Cards */
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .role-card {
            flex: 1;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .role-card:hover {
            border-color: var(--primary-color);
            background: #e0f2fe;
        }

        .role-card.active {
            border-color: var(--primary-color);
            background: #e0f2fe;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.15);
        }

        .role-card i {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 4px;
        }

        .role-card p {
            font-size: 13px;
            font-weight: 700;
            margin: 0;
            color: #334155;
        }

        #doctorFields {
            display: none;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px dashed #cbd5e1;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            margin-top: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.25);
        }

        .btn-register:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(2, 132, 199, 0.35);
            color: white;
        }

        .divider {
            text-align: center;
            color: #94a3b8;
            margin: 20px 0;
            position: relative;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e2e8f0;
        }

        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .login-link, .back-home {
            text-align: center;
            font-size: 14px;
            font-weight: 500;
        }

        .login-link { margin-top: 15px; color: #64748b; }
        .login-link a { color: var(--primary-color); font-weight: 700; text-decoration: none; }

        .back-home { margin-top: 12px; }
        .back-home a { color: #64748b; text-decoration: none; font-weight: 600; }
        .back-home a:hover { color: var(--primary-color); }
    </style>
</head>
<body>

    <div class="register-card">

        <!-- Logo -->
        <div class="register-logo">
            <div class="icon-box">
                <i class="fas fa-hospital-alt"></i>
            </div>
            <h3>Medi<span>Care</span></h3>
            <p>Create your personal portal account</p>
        </div>

        <!-- Alert Message -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger py-2 px-3 rounded-3 fs-7 fw-semibold mb-3">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">

            <!-- Hidden Input for Selected Role -->
            <input type="hidden" name="role" id="selectedRole" value="patient">

            <!-- Role Selector -->
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user-tag me-1"></i> Register As</label>
                <div class="role-selector">
                    <div class="role-card active" onclick="setRole('patient', this)">
                        <i class="fas fa-user-injured"></i>
                        <p>Patient Account</p>
                    </div>
                    <div class="role-card" onclick="setRole('doctor', this)">
                        <i class="fas fa-user-md"></i>
                        <p>Doctor Account</p>
                    </div>
                </div>
            </div>

            <!-- Full Name -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="fullname" placeholder="e.g. John Doe" required>
                </div>
            </div>

            <!-- Email Address -->
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                </div>
            </div>

            <!-- Phone Number -->
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="text" class="form-control" name="phone" placeholder="e.g. 03001234567" required>
                </div>
            </div>

            <!-- Doctor Extra Fields -->
            <div id="doctorFields">
                <div class="mb-3">
                    <label class="form-label text-dark">Medical Specialization</label>
                    <select class="form-select" name="specialization">
                        <option value="General Physician">General Physician</option>
                        <option value="Cardiologist">Cardiologist</option>
                        <option value="Dermatologist">Dermatologist</option>
                        <option value="Neurologist">Neurologist</option>
                        <option value="Pediatrician">Pediatrician</option>
                        <option value="Orthopedic Surgeon">Orthopedic Surgeon</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Qualification</label>
                    <input type="text" class="form-control" name="qualification" placeholder="e.g. MBBS, FCPS (Cardiology)">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Consultation Fee (PKR)</label>
                    <input type="number" class="form-control" name="fee" placeholder="e.g. 1500">
                </div>
            </div>

            <!-- Passwords Row -->
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                        <input type="password" class="form-control" name="cpassword" placeholder="Confirm" required>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus me-1"></i> Register Account
            </button>

        </form>

        <div class="divider">or</div>

        <!-- Links -->
        <div class="login-link">
            Already have an account? <a href="login.php">Sign In Here</a>
        </div>

        <div class="back-home">
            <a href="index.php"><i class="fas fa-arrow-left me-1"></i> Return to Homepage</a>
        </div>

    </div>

    <script>
        function setRole(role, element) {
            document.getElementById('selectedRole').value = role;
            
            document.querySelectorAll('.role-card').forEach(card => card.classList.remove('active'));
            element.classList.add('active');

            const doctorFields = document.getElementById('doctorFields');
            if (role === 'doctor') {
                doctorFields.style.display = 'block';
            } else {
                doctorFields.style.display = 'none';
            }
        }
    </script>

</body>
</html>