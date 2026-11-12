<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only logged-in Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = intval($_SESSION['user_id']);

// Fetch Patient Analytics Counters
$total_query     = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = $patient_id");
$pending_query   = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = $patient_id AND status = 'pending'");
$completed_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = $patient_id AND status = 'completed'");
$pres_query      = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM prescriptions WHERE patient_id = $patient_id");

$total     = mysqli_fetch_assoc($total_query)['cnt'] ?? 0;
$pending   = mysqli_fetch_assoc($pending_query)['cnt'] ?? 0;
$completed = mysqli_fetch_assoc($completed_query)['cnt'] ?? 0;
$pres_cnt  = mysqli_fetch_assoc($pres_query)['cnt'] ?? 0;

// Fetch Recent / Upcoming Appointments
$recent_apt_query = "SELECT a.*, u.fullname as doctor_name, d.specialization
                     FROM appointments a
                     JOIN users u ON a.doctor_id = u.id
                     LEFT JOIN doctors d ON a.doctor_id = d.user_id
                     WHERE a.patient_id = $patient_id
                     ORDER BY a.appointment_date DESC, a.appointment_time DESC
                     LIMIT 5";
$recent_apts = mysqli_query($conn, $recent_apt_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard — MediCare 3-Tier Telemedicine Platform</title>

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

        /* Custom Cyan Badge Classes */
        .bg-cyan-subtle { background-color: #ecfeff !important; }
        .text-cyan { color: #0891b2 !important; }
        .border-cyan-subtle { border-color: #a5f3fc !important; }

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

        /* Banner Card */
        .welcome-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #06b6d4 100%);
            border-radius: 24px;
            padding: 35px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.15);
            margin-bottom: 30px;
        }

        .welcome-card h4 {
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .welcome-card p {
            opacity: 0.9;
            margin: 0;
            font-size: 15px;
            max-width: 650px;
            line-height: 1.6;
        }

        /* Analytics Stat Cards */
        .stat-card {
            background: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            height: 100%;
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
            font-size: 28px;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .stat-info p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Action Cards */
        .action-card {
            background: white;
            border-radius: 22px;
            padding: 28px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            color: inherit;
            height: 100%;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: #06b6d4;
            box-shadow: 0 15px 35px rgba(6, 182, 212, 0.12);
            color: inherit;
        }

        .action-icon-box {
            width: 65px;
            height: 65px;
            border-radius: 20px;
            background: #ecfeff;
            color: #0891b2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 18px;
            border: 1px solid #cffaff;
        }

        .action-card h5 {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .action-card p {
            color: #64748b;
            font-size: 13px;
            margin: 0;
            font-weight: 500;
        }

        /* Activity Card & Table */
        .activity-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            margin-top: 35px;
        }

        .activity-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .activity-card-header h5 {
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table td { padding: 16px; font-size: 14px; vertical-align: middle; }

        .status-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }

        .status-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .status-approved, .status-confirmed { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .status-completed { background: #ecfeff; color: #0369a1; border: 1px solid #cffaff; }
        .status-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

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
            <a href="dashboard.php" class="nav-item-link active">
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
            <a href="profile.php" class="nav-item-link">
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
            <h4>Patient Overview</h4>
            <p>Welcome to your central healthcare management dashboard</p>
        </div>
        <div class="user-pill">
            <div class="avatar-circle"><i class="fas fa-user"></i></div>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Welcome Banner -->
    <div class="welcome-card">
        <h4>👋 Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h4>
        <p>Access your healthcare portal to schedule clinical consultations, join telemedicine video calls, and inspect digital prescriptions safely.</p>
    </div>

    <!-- Analytics Stat Grid -->
    <div class="row g-4 mb-4">

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0284c7);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total; ?></h3>
                    <p>Total Visits</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                    <i class="fas fa-file-medical"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pres_cnt; ?></h3>
                    <p>Prescriptions</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Navigation Actions -->
    <h5 class="fw-bold mb-3 text-dark">Quick Actions</h5>
    <div class="row g-4">

        <div class="col-md-4">
            <a href="book_appointment.php" class="action-card">
                <div class="action-icon-box">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h5>Book Appointment</h5>
                <p>Schedule a visit with your preferred specialist doctor</p>
            </a>
        </div>

        <div class="col-md-4">
            <a href="my_appointments.php" class="action-card">
                <div class="action-icon-box">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h5>My Appointments</h5>
                <p>Track, review, or modify your upcoming clinical slots</p>
            </a>
        </div>

        <div class="col-md-4">
            <a href="video_call.php" class="action-card">
                <div class="action-icon-box">
                    <i class="fas fa-video"></i>
                </div>
                <h5>Video Consultations</h5>
                <p>Join encrypted live video consultation rooms</p>
            </a>
        </div>

    </div>

    <!-- Recent Appointments Table Card -->
    <div class="activity-card">
        <div class="activity-card-header">
            <h5><i class="fas fa-history text-cyan me-2"></i> Recent Consultation Activity</h5>
            <a href="my_appointments.php" class="btn btn-sm btn-outline-dark fw-bold rounded-3 fs-7">View All</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Doctor Name</th>
                        <th>Specialization</th>
                        <th>Date & Time</th>
                        <th>Consultation Type</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($recent_apts) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No recent appointment history found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($apt = mysqli_fetch_assoc($recent_apts)): 
                            $spec_text = !empty($apt['specialization']) ? $apt['specialization'] : 'Dermatologist';
                        ?>
                            <tr>
                                <td class="fw-bold text-dark">Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                <td>
                                    <span class="badge bg-cyan-subtle text-cyan fw-bold border border-cyan-subtle px-3 py-2 rounded-pill">
                                        <?php echo htmlspecialchars($spec_text); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></div>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary">
                                        <?php echo ($apt['type'] === 'video-call') ? '🎥 Video Call' : '🏥 In-Person'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($apt['status']); ?>">
                                        <?php echo htmlspecialchars($apt['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="my_appointments.php" class="btn btn-sm btn-light border fw-semibold rounded-2">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>