<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only authenticated Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = intval($_SESSION['user_id']);

// Handle Profile & Password Update Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $new_password = $_POST['new_password'];

    // Update Basic User Information
    $update_query = "UPDATE users SET fullname = '$fullname', phone = '$phone' WHERE id = $patient_id";
    if (mysqli_query($conn, $update_query)) {
        // Update Session name dynamically
        $_SESSION['user_name'] = $fullname;
    }

    // Optional Password Update
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            echo "<script>alert('Password must be at least 6 characters long!'); window.history.back();</script>";
            exit();
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE id = $patient_id");
    }

    echo "<script>
            alert('Profile updated successfully!');
            window.location='profile.php';
          </script>";
    exit();
}

// Fetch Patient Info Securely
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id = $patient_id");
$user     = mysqli_fetch_assoc($user_res);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Fetch Live Appointment Statistics
$total_res     = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = $patient_id");
$completed_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = $patient_id AND status = 'completed'");

$total     = mysqli_fetch_assoc($total_res)['cnt'];
$completed = mysqli_fetch_assoc($completed_res)['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — MediCare Telemedicine</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0b0f19;
            --sidebar-bg: #111827;
            --card-bg: #ffffff;
            --accent-cyan: #06b6d4;
            --accent-indigo: #6366f1;
            --text-main: #1e293b;
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

        body {
            background-color: #f3f4f6;
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Upgraded Floating Sidebar */
        .sidebar {
            width: 270px;
            height: calc(100vh - 30px);
            background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
            position: fixed;
            left: 15px;
            top: 15px;
            border-radius: 24px;
            padding: 30px 20px;
            z-index: 100;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #06b6d4, #6366f1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
        }

        .brand-title {
            color: white;
            font-weight: 800;
            font-size: 20px;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-menu {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #9ca3af;
            text-decoration: none;
            padding: 13px 16px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-item-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateX(4px);
        }

        .nav-item-link.active {
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
        }

        .nav-item-link i { font-size: 18px; width: 22px; }

        .btn-logout-sidebar {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            padding: 13px;
            width: 100%;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-logout-sidebar:hover {
            background: #ef4444;
            color: white;
        }

        /* Workspace Layout */
        .workspace {
            margin-left: 310px;
            padding: 25px 35px 40px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-title h4 { font-weight: 800; font-size: 26px; color: #0f172a; margin: 0; }
        .header-title p { color: #64748b; font-size: 14px; margin: 0; font-weight: 500; }

        .user-pill {
            background: white;
            padding: 8px 18px;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            font-weight: 700;
            font-size: 14px;
        }

        .avatar-circle {
            width: 34px;
            height: 34px;
            background: #ecfeff;
            color: #0891b2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        /* Profile Card Container */
        .profile-card {
            background: white;
            border-radius: 24px;
            padding: 38px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
        }

        .profile-avatar {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: linear-gradient(135deg, #06b6d4 0%, #6366f1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 16px;
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.3);
        }

        .profile-name {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }

        .profile-role {
            text-align: center;
            color: #0891b2;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .stat-mini {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-mini h4 {
            font-weight: 800;
            color: #06b6d4;
            margin: 0;
            font-size: 26px;
        }

        .stat-mini p {
            color: #64748b;
            margin: 4px 0 0;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .info-badge {
            background: #ecfeff;
            border: 1px solid #cffaff;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .info-badge p {
            margin: 0;
            color: #0e7490;
            font-size: 14px;
            font-weight: 600;
        }

        .section-title {
            font-weight: 800;
            color: #0f172a;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 12px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
        }

        .btn-update {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 36px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.35);
            color: white;
        }

        @media (max-width: 991px) {
            .sidebar { position: relative; width: 100%; height: auto; left: 0; top: 0; margin-bottom: 20px; }
            .workspace { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div>
        <div class="brand-box">
            <div class="brand-icon">
                <i class="fas fa-heartpulse"></i>
            </div>
            <div>
                <h1 class="brand-title">MediCare</h1>
                <span class="brand-subtitle">Patient Experience</span>
            </div>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item-link">
                <i class="fas fa-grid-2 me-1"></i> Dashboard
            </a>
            <a href="book_appointment.php" class="nav-item-link">
                <i class="fas fa-calendar-plus me-1"></i> Book Appointment
            </a>
            <a href="my_appointments.php" class="nav-item-link">
                <i class="fas fa-calendar-check me-1"></i> My Appointments
            </a>
            <a href="video_call.php" class="nav-item-link">
                <i class="fas fa-video me-1"></i> Video Consultations
            </a>
            <a href="profile.php" class="nav-item-link active">
                <i class="fas fa-user-gear me-1"></i> My Profile
            </a>
        </div>
    </div>

    <div>
        <a href="../logout.php" class="btn-logout-sidebar">
            <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
        </a>
    </div>
</div>

<!-- WORKSPACE -->
<div class="workspace">

    <!-- Topbar -->
    <div class="header-bar">
        <div class="header-title">
            <h4>Patient Profile Management</h4>
            <p>Manage your personal credentials, contact info, and security settings</p>
        </div>
        <div class="user-pill">
            <div class="avatar-circle"><i class="fas fa-user"></i></div>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="profile-card">

                <!-- Avatar -->
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                <div class="profile-role">
                    <i class="fas fa-shield-alt me-1 text-cyan"></i> Verified Patient Account
                </div>

                <!-- Stats Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="stat-mini">
                            <h4><?php echo $total; ?></h4>
                            <p>Total Consultations</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-mini">
                            <h4><?php echo $completed; ?></h4>
                            <p>Completed Visits</p>
                        </div>
                    </div>
                </div>

                <!-- Info Badge -->
                <div class="info-badge">
                    <p class="mb-0">
                        <i class="fas fa-envelope text-cyan me-1"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                        &nbsp;&bull;&nbsp;
                        <i class="fas fa-calendar-alt text-cyan me-1"></i> <strong>Member Since:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>

                <!-- Update Form -->
                <form method="POST">

                    <div class="section-title">
                        <i class="fas fa-user-edit text-cyan"></i> Personal Details
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="e.g. 03001234567">
                        </div>
                    </div>

                    <div class="section-title mt-4">
                        <i class="fas fa-key text-cyan"></i> Account Security
                        <small class="text-muted fw-normal fs-7 ms-2">(Leave blank if you do not want to modify password)</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Enter new password">
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <button type="submit" class="btn-update">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>