<?php
session_start();
include '../db/connection.php';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Access Control: Ensure only authenticated Doctors can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = intval($_SESSION['user_id']);

// Fetch Doctor Profile Information
$doc_info_res = mysqli_query($conn, "SELECT d.specialization, d.qualification, d.experience FROM doctors d WHERE d.user_id = $doctor_id");
$doc_info     = mysqli_fetch_assoc($doc_info_res);

// Fetch Consultation Statistics
$total     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = $doctor_id"));
$pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = $doctor_id AND status = 'pending'"));
$confirmed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = $doctor_id AND status = 'confirmed'"));
$completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = $doctor_id AND status = 'completed'"));

// Fetch Today's Scheduled Appointments
$today        = date('Y-m-d');
$todays_query = "SELECT a.*, u.fullname as patient_name, u.phone
                 FROM appointments a
                 JOIN users u ON a.patient_id = u.id
                 WHERE a.doctor_id = $doctor_id 
                 AND a.appointment_date = '$today'
                 ORDER BY a.appointment_time ASC";
$todays       = mysqli_query($conn, $todays_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard — MediCare Telemedicine</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d9488;
            --secondary-color: #0f766e;
            --accent-color: #2dd4bf;
            --dark-bg: #0f172a;
            --light-bg: #f8fafc;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: #334155;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 270px;
            height: 100vh;
            background: #0f172a;
            position: fixed;
            left: 0;
            top: 0;
            padding: 30px 0;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-logo {
            text-align: center;
            color: white;
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-logo .icon-box {
            width: 55px;
            height: 55px;
            background: rgba(45, 212, 191, 0.1);
            color: var(--accent-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 26px;
            margin-bottom: 10px;
        }

        .sidebar-logo h4 {
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-top: 5px;
            margin-bottom: 2px;
        }

        .sidebar-menu {
            padding: 25px 15px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: #94a3b8;
            text-decoration: none;
            padding: 13px 18px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(13, 148, 136, 0.15);
            color: var(--accent-color);
        }

        .sidebar-menu a i {
            margin-right: 12px;
            font-size: 18px;
            width: 22px;
        }

        .sidebar-logout {
            position: absolute;
            bottom: 25px;
            width: 100%;
            padding: 0 20px;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: white;
        }

        /* Main Content Layout */
        .main-content {
            margin-left: 270px;
            padding: 35px;
        }

        /* Topbar Styling */
        .topbar {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .topbar h5 {
            margin: 0;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .doctor-badge {
            background: #0f172a;
            color: white;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Welcome Banner Card */
        .welcome-card {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #0d9488 100%);
            color: white;
            border-radius: 24px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(13, 148, 136, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::after {
            content: "\f0f1";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 180px;
            opacity: 0.08;
            color: white;
        }

        .welcome-card h4 {
            font-weight: 800;
            font-size: 26px;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .welcome-card p {
            opacity: 0.9;
            margin: 0;
            font-size: 15px;
            font-weight: 500;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 22px 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-weight: 800;
            margin: 0;
            font-size: 26px;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .stat-info p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }

        .table-card h5 {
            font-weight: 800;
            margin-bottom: 25px;
            color: #0f172a;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table th {
            background: #f8fafc;
            font-weight: 700;
            color: #475569;
            font-size: 12px;
            text-uppercase: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table td {
            vertical-align: middle;
            padding: 16px;
            font-size: 14px;
            color: #334155;
        }

        /* Badges */
        .badge-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-confirmed { background: #dcfce7; color: #15803d; }
        .badge-completed { background: #e0f2fe; color: #0369a1; }
        .badge-video { background: #f3e8ff; color: #7e22ce; }
        .badge-inperson { background: #ccfbf1; color: #0f766e; }

        .btn-confirm {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-confirm:hover {
            background: #059669;
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="icon-box">
            <i class="fas fa-stethoscope"></i>
        </div>
        <h4>Medi<span>Care</span></h4>
        <small class="text-info fw-semibold">Doctor Portal</small>
    </div>

    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="my_appointments.php">
            <i class="fas fa-calendar-check"></i> My Appointments
        </a>
        <a href="video_call.php">
            <i class="fas fa-video"></i> Video Consultations
        </a>
        <a href="profile.php">
            <i class="fas fa-user-md"></i> My Profile
        </a>
    </div>

    <div class="sidebar-logout">
        <a href="../logout.php" class="text-decoration-none">
            <button class="btn-logout">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </button>
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <h5>
            <i class="fas fa-user-md text-teal me-2" style="color: var(--primary-color);"></i> Doctor Consultation Workspace
        </h5>
        <div class="doctor-badge">
            <i class="fas fa-stethoscope text-teal" style="color: var(--accent-color);"></i>
            <span>Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Welcome Banner Card -->
    <div class="welcome-card">
        <h4>Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h4>
        <p>
            <?php if ($doc_info): ?>
                <i class="fas fa-user-tag me-1"></i> <?php echo htmlspecialchars($doc_info['specialization']); ?> &bull; 
                <i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($doc_info['qualification']); ?> &bull; 
                <i class="fas fa-briefcase me-1"></i> <?php echo htmlspecialchars($doc_info['experience']); ?> Years Clinical Practice
            <?php else: ?>
                Manage your patient consultations, schedule, and digital prescriptions seamlessly.
            <?php endif; ?>
        </p>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #0284c7, #0369a1)">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total['cnt']; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706)">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending['cnt']; ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #047857)">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $confirmed['cnt']; ?></h3>
                    <p>Confirmed Slots</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4338ca)">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed['cnt']; ?></h3>
                    <p>Completed Visits</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="table-card">
        <h5>
            <i class="fas fa-calendar-day text-success me-1"></i>
            Today's Consultation Schedule
            <span class="badge bg-secondary bg-opacity-10 text-secondary fs-7 ms-2">
                <?php echo date('d M Y'); ?>
            </span>
        </h5>

        <?php if (mysqli_num_rows($todays) == 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-calendar-check fa-4x mb-3 text-secondary opacity-50"></i>
                <p class="fw-semibold">No patient consultations scheduled for today.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient Name</th>
                        <th>Contact</th>
                        <th>Time Slot</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($todays)): ?>
                    <tr>
                        <td><strong><?php echo $i++; ?></strong></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['phone']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><i class="far fa-clock me-1 text-muted"></i><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></div>
                        </td>
                        <td>
                            <?php if ($row['type'] == 'video-call'): ?>
                                <span class="badge-pill badge-video"><i class="fas fa-video me-1"></i> Video Call</span>
                            <?php else: ?>
                                <span class="badge-pill badge-inperson"><i class="fas fa-clinic-medical me-1"></i> In-Person</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'pending'): ?>
                                <span class="badge-pill badge-pending"><i class="fas fa-clock"></i> Pending</span>
                            <?php elseif ($row['status'] == 'confirmed'): ?>
                                <span class="badge-pill badge-confirmed"><i class="fas fa-check"></i> Confirmed</span>
                            <?php else: ?>
                                <span class="badge-pill badge-completed"><i class="fas fa-check-double"></i> Completed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'pending'): ?>
                                <form method="POST" action="appointment_action.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button type="submit" name="action" value="confirm" class="btn-confirm"><i class="fas fa-check"></i> Confirm</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>