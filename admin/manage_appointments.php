<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle Appointment Status Update Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $apt_id = intval($_GET['id']);
    $action = mysqli_real_escape_string($conn, trim($_GET['action']));

    $allowed_statuses = ['confirmed', 'cancelled', 'completed'];

    if (in_array($action, $allowed_statuses)) {
        $update_stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "si", $action, $apt_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            echo "<script>
                    alert('Appointment status updated to " . ucfirst($action) . " successfully!');
                    window.location='manage_appointments.php';
                  </script>";
            exit();
        }
    }
}

// Fetch all appointments securely
$appointments_query = "SELECT a.*, 
                               p.fullname as patient_name, p.phone as patient_phone,
                               d.fullname as doctor_name,
                               doc.specialization
                        FROM appointments a
                        JOIN users p ON a.patient_id = p.id
                        JOIN users d ON a.doctor_id = d.id
                        LEFT JOIN doctors doc ON a.doctor_id = doc.user_id
                        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$appointments = mysqli_query($conn, $appointments_query);

// Summary Statistics Queries
$total_cnt     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments"))['cnt'];
$pending_cnt   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE status='pending'"))['cnt'];
$confirmed_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE status='confirmed'"))['cnt'];
$completed_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE status='completed'"))['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments — MediCare Telemedicine Platform</title>

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
            background: rgba(56, 189, 248, 0.1);
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
            background: rgba(2, 132, 199, 0.15);
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
            margin-bottom: 35px;
        }

        .topbar h5 {
            margin: 0;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .admin-badge {
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
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }
        
        .badge-video { background: #f3e8ff; color: #7e22ce; }
        .badge-inperson { background: #ccfbf1; color: #0f766e; }

        .btn-action {
            border: none;
            border-radius: 10px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-confirm { background: #10b981; color: white; }
        .btn-confirm:hover { background: #059669; color: white; }
        .btn-complete { background: #0284c7; color: white; }
        .btn-complete:hover { background: #0369a1; color: white; }
        .btn-cancel { background: #ef4444; color: white; }
        .btn-cancel:hover { background: #dc2626; color: white; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="icon-box">
            <i class="fas fa-heartbeat"></i>
        </div>
        <h4>Medi<span>Care</span></h4>
        <small class="text-info fw-semibold">Admin Workspace</small>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="manage_doctors.php">
            <i class="fas fa-user-md"></i> Manage Doctors
        </a>
        <a href="manage_patients.php">
            <i class="fas fa-users"></i> Manage Patients
        </a>
        <a href="manage_appointments.php" class="active">
            <i class="fas fa-calendar-alt"></i> Appointments
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

    <div class="topbar">
        <h5>
            <i class="fas fa-calendar-alt text-info me-2"></i> Appointment Management Console
        </h5>
        <div class="admin-badge">
            <i class="fas fa-user-shield text-info"></i>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #0284c7, #0369a1)">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_cnt; ?></h3>
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
                    <h3><?php echo $pending_cnt; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669)">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $confirmed_cnt; ?></h3>
                    <p>Confirmed Slots</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5)">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed_cnt; ?></h3>
                    <p>Completed Visits</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments Table Card -->
    <div class="table-card">
        <h5><i class="fas fa-list text-primary"></i> Master Appointments Directory</h5>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Patient Name</th>
                        <th>Doctor Name</th>
                        <th>Specialization</th>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($appointments) > 0): ?>
                        <?php while ($apt = mysqli_fetch_assoc($appointments)): ?>
                            <tr>
                                <td><span class="fw-bold text-dark">#APT-<?php echo $apt['id']; ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($apt['patient_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($apt['patient_phone'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></div>
                                </td>
                                <td>
                                    <span class="text-muted fw-semibold"><?php echo htmlspecialchars($apt['specialization'] ?? 'Specialist'); ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></div>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td>
                                    <?php if ($apt['type'] === 'video-call'): ?>
                                        <span class="badge-pill badge-video"><i class="fas fa-video me-1"></i> Video</span>
                                    <?php else: ?>
                                        <span class="badge-pill badge-inperson"><i class="fas fa-user-nurse me-1"></i> In-Person</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-pill badge-<?php echo strtolower($apt['status']); ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($apt['status'] === 'pending'): ?>
                                        <a href="manage_appointments.php?action=confirmed&id=<?php echo $apt['id']; ?>" class="btn-action btn-confirm">
                                            <i class="fas fa-check"></i> Confirm
                                        </a>
                                        <a href="manage_appointments.php?action=cancelled&id=<?php echo $apt['id']; ?>" class="btn-action btn-cancel" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php elseif ($apt['status'] === 'confirmed'): ?>
                                        <a href="manage_appointments.php?action=completed&id=<?php echo $apt['id']; ?>" class="btn-action btn-complete">
                                            <i class="fas fa-check-circle"></i> Complete
                                        </a>
                                        <a href="manage_appointments.php?action=cancelled&id=<?php echo $apt['id']; ?>" class="btn-action btn-cancel" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted fs-7 fw-semibold">No Actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted fw-semibold">
                                No appointment records found in the database.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>