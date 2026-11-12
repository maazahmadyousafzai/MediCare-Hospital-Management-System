<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only logged-in Doctors can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = intval($_SESSION['user_id']);

// Filter Handling & Sanitization
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';
$allowed_filters = ['pending', 'confirmed', 'completed', 'cancelled'];

if (in_array($filter, $allowed_filters)) {
    $filter_condition = "AND a.status = '$filter'";
} else {
    $filter = 'all';
    $filter_condition = "";
}

// Query Appointments for Logged-In Doctor
$query = "SELECT a.*, u.fullname as patient_name, u.phone
          FROM appointments a
          JOIN users u ON a.patient_id = u.id
          WHERE a.doctor_id = $doctor_id
          $filter_condition
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$appointments = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments — MediCare Telemedicine</title>

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

        /* Filter Tabs */
        .filter-tabs {
            background: white;
            border-radius: 20px;
            padding: 15px 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 50px;
            padding: 9px 22px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .filter-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
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

        /* Action Buttons */
        .action-btn {
            border: none;
            border-radius: 10px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-confirm { background: #10b981; color: white; }
        .btn-confirm:hover { background: #059669; color: white; }

        .btn-complete { background: #0284c7; color: white; }
        .btn-complete:hover { background: #0369a1; color: white; }

        .btn-prescribe { background: #8b5cf6; color: white; }
        .btn-prescribe:hover { background: #7c3aed; color: white; }

        .btn-rx { background: #0d9488; color: white; }
        .btn-rx:hover { background: #0f766e; color: white; }
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
        <a href="dashboard.php">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="my_appointments.php" class="active">
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
            <i class="fas fa-calendar-check text-teal me-2" style="color: var(--primary-color);"></i> Appointment Records & Schedule
        </h5>
        <div class="doctor-badge">
            <i class="fas fa-user-md text-teal" style="color: var(--accent-color);"></i>
            <span>Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <span class="text-muted fw-bold me-2 fs-7 uppercase"><i class="fas fa-filter me-1"></i> Filter By:</span>
        <a href="my_appointments.php?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">
            <i class="fas fa-border-all"></i> All Bookings
        </a>
        <a href="my_appointments.php?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="my_appointments.php?filter=confirmed" class="filter-btn <?php echo $filter == 'confirmed' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Confirmed
        </a>
        <a href="my_appointments.php?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">
            <i class="fas fa-flag-checkered"></i> Completed
        </a>
        <a href="my_appointments.php?filter=cancelled" class="filter-btn <?php echo $filter == 'cancelled' ? 'active' : ''; ?>">
            <i class="fas fa-times-circle"></i> Cancelled
        </a>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <h5>
            <i class="fas fa-list text-primary"></i>
            Patient Appointments Directory
            <span class="badge bg-secondary bg-opacity-10 text-secondary fs-7 ms-2">
                <?php echo mysqli_num_rows($appointments); ?> Records Shown
            </span>
        </h5>

        <?php if (mysqli_num_rows($appointments) == 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-calendar-times fa-4x mb-3 text-secondary opacity-50"></i>
                <p class="fw-semibold">No appointment records found for the selected filter.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient Details</th>
                        <th>Phone</th>
                        <th>Date & Time</th>
                        <th>Mode</th>
                        <th>Symptom Notes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $i = 1;
                while ($row = mysqli_fetch_assoc($appointments)):
                    $apt_id = intval($row['id']);
                    // Check if prescription exists for this appointment
                    $pres_res = mysqli_query($conn, "SELECT id FROM prescriptions WHERE appointment_id = $apt_id");
                    $pres     = mysqli_fetch_assoc($pres_res);
                ?>
                    <tr>
                        <td><strong><?php echo $i++; ?></strong></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['phone']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></div>
                            <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></small>
                        </td>
                        <td>
                            <?php if ($row['type'] == 'video-call'): ?>
                                <span class="badge-pill badge-video"><i class="fas fa-video me-1"></i> Video</span>
                            <?php else: ?>
                                <span class="badge-pill badge-inperson"><i class="fas fa-clinic-medical me-1"></i> In-Person</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-secondary"><?php echo !empty($row['notes']) ? htmlspecialchars($row['notes']) : '—'; ?></small>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'pending'): ?>
                                <span class="badge-pill badge-pending"><i class="fas fa-clock"></i> Pending</span>
                            <?php elseif ($row['status'] == 'confirmed'): ?>
                                <span class="badge-pill badge-confirmed"><i class="fas fa-check"></i> Confirmed</span>
                            <?php elseif ($row['status'] == 'completed'): ?>
                                <span class="badge-pill badge-completed"><i class="fas fa-check-double"></i> Completed</span>
                            <?php else: ?>
                                <span class="badge-pill badge-cancelled"><i class="fas fa-times"></i> Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'pending'): ?>
                                <a href="confirm_appointment.php?id=<?php echo $row['id']; ?>" class="action-btn btn-confirm">
                                    <i class="fas fa-check"></i> Confirm
                                </a>

                            <?php elseif ($row['status'] == 'confirmed'): ?>
                                <a href="complete_appointment.php?id=<?php echo $row['id']; ?>" class="action-btn btn-complete">
                                    <i class="fas fa-flag-checkered"></i> Complete
                                </a>
                                <?php if (!$pres): ?>
                                    <a href="write_prescription.php?id=<?php echo $row['id']; ?>" class="action-btn btn-prescribe">
                                        <i class="fas fa-pills"></i> Prescribe
                                    </a>
                                <?php else: ?>
                                    <a href="view_prescription.php?id=<?php echo $pres['id']; ?>" class="action-btn btn-rx">
                                        <i class="fas fa-file-medical"></i> View Rx
                                    </a>
                                <?php endif; ?>

                            <?php elseif ($row['status'] == 'completed'): ?>
                                <?php if (!$pres): ?>
                                    <a href="write_prescription.php?id=<?php echo $row['id']; ?>" class="action-btn btn-prescribe">
                                        <i class="fas fa-pills"></i> Prescribe
                                    </a>
                                <?php else: ?>
                                    <a href="view_prescription.php?id=<?php echo $pres['id']; ?>" class="action-btn btn-rx">
                                        <i class="fas fa-file-medical"></i> View Rx
                                    </a>
                                <?php endif; ?>

                            <?php else: ?>
                                <span class="text-muted small">—</span>
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