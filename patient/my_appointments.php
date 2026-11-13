<?php
session_start();
include '../db/connection.php';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Access Control: Ensure only logged-in Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = intval($_SESSION['user_id']);

// Fetch all appointments for the logged-in patient with doctor details
$query = "SELECT a.*, 
                 u.fullname as doctor_name, 
                 d.specialization,
                 p.id as payment_id, p.status as payment_status,
                 r.id as rating_id,
                 pr.id as prescription_id
          FROM appointments a
          JOIN users u ON a.doctor_id = u.id
          LEFT JOIN doctors d ON a.doctor_id = d.user_id
          LEFT JOIN payments p ON p.id = (SELECT MAX(p2.id) FROM payments p2 WHERE p2.appointment_id = a.id)
          LEFT JOIN ratings r ON a.id = r.appointment_id AND r.patient_id = $patient_id
          LEFT JOIN prescriptions pr ON a.id = pr.appointment_id
          WHERE a.patient_id = $patient_id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$appointments = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments — MediCare 3-Tier Telemedicine Platform</title>

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

        /* Table Card Container */
        .table-card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
        }

        .btn-book-new {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 11px 24px;
            font-weight: 700;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-book-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.35);
            color: white;
        }

        /* Status & Type Badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }

        .badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-confirmed { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-completed { background: #ecfeff; color: #0369a1; border: 1px solid #cffaff; }
        .badge-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .type-badge-video { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; }
        .type-badge-inperson { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; }

        /* Action Buttons */
        .action-btn {
            border-radius: 10px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            margin: 2px;
            border: none;
        }

        .btn-action-pay {
            background: #10b981;
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .btn-action-pay:hover {
            background: #059669;
            color: white;
        }

        .btn-action-cancel {
            background: #ef4444;
            color: white;
        }

        .btn-action-cancel:hover {
            background: #dc2626;
            color: white;
        }

        .btn-action-rate {
            background: #f59e0b;
            color: white;
        }

        .btn-action-rate:hover {
            background: #d97706;
            color: white;
        }

        .btn-action-rx {
            background: #6366f1;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn-action-rx:hover {
            background: #4f46e5;
            color: white;
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

        .table td {
            padding: 16px;
            font-size: 14px;
            vertical-align: middle;
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
            <a href="my_appointments.php" class="nav-item-link active">
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
            <h4>Appointments & Consultation Records</h4>
            <p>Track, pay, review, or access prescriptions for your medical visits</p>
        </div>
        <div class="user-pill">
            <div class="avatar-circle"><i class="fas fa-user"></i></div>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-list text-cyan me-2"></i>
                    Clinical Appointments History
                </h5>
                <p class="text-muted mb-0 fs-7">
                    Track the status of your visits, submit payments, and view digital prescriptions.
                </p>
            </div>
            <a href="book_appointment.php" class="btn-book-new">
                <i class="fas fa-plus"></i> Book New Consultation
            </a>
        </div>

        <?php if (mysqli_num_rows($appointments) == 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-calendar-times fa-4x mb-3 text-secondary opacity-50"></i>
                <h5 class="fw-bold text-dark">No Appointments Found</h5>
                <p class="small">You haven't booked any medical consultations yet.</p>
                <a href="book_appointment.php" class="btn-book-new mt-2">
                    <i class="fas fa-calendar-plus"></i> Schedule First Visit
                </a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 22%;">Attending Doctor</th>
                        <th style="width: 15%;">Specialization</th>
                        <th style="width: 15%;">Date & Time</th>
                        <th style="width: 12%;">Type</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 10%;">Payment</th>
                        <th style="width: 10%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $i = 1;
                while ($row = mysqli_fetch_assoc($appointments)):
                    $apt_id = intval($row['id']);
                    // Dynamic Specialization Fallback Check
                    $specialization_text = !empty($row['specialization']) ? $row['specialization'] : 'General Physician';
                ?>
                    <tr>
                        <td class="fw-bold text-secondary"><?php echo $i++; ?></td>
                        <td>
                            <strong class="text-dark fs-6">
                                Dr. <?php echo htmlspecialchars($row['doctor_name']); ?>
                            </strong>
                        </td>
                        <td>
                            <span class="badge bg-cyan-subtle text-cyan fw-bold border border-cyan-subtle px-3 py-2 rounded-pill">
                                <?php echo htmlspecialchars($specialization_text); ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-7">
                                <?php echo date('d M Y', strtotime($row['appointment_date'])); ?>
                            </div>
                            <small class="text-muted fs-7">
                                <?php echo date('h:i A', strtotime($row['appointment_time'])); ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($row['type'] === 'video-call'): ?>
                                <span class="type-badge-video">
                                    <i class="fas fa-video me-1"></i> Video
                                </span>
                            <?php else: ?>
                                <span class="type-badge-inperson">
                                    <i class="fas fa-hospital me-1"></i> In-Person
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status = strtolower($row['status']);
                            if ($status === 'pending'): ?>
                                <span class="status-badge badge-pending">
                                    <i class="far fa-clock me-1"></i> Pending
                                </span>
                            <?php elseif ($status === 'confirmed'): ?>
                                <span class="status-badge badge-confirmed">
                                    <i class="fas fa-check-circle me-1"></i> Confirmed
                                </span>
                            <?php elseif ($status === 'completed'): ?>
                                <span class="status-badge badge-completed">
                                    <i class="fas fa-check-double me-1"></i> Completed
                                </span>
                            <?php else: ?>
                                <span class="status-badge badge-cancelled">
                                    <i class="fas fa-times-circle me-1"></i> Cancelled
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Payment Status -->
                        <td>
                            <?php if (!empty($row['payment_id']) && $row['payment_status'] === 'completed'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-2 fw-bold fs-7">
                                    <i class="fas fa-check-circle me-1"></i> Verified & Paid
                                </span>
                            <?php elseif (!empty($row['payment_id']) && $row['payment_status'] === 'pending'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-2 fw-semibold fs-7">
                                    <i class="far fa-hourglass me-1"></i> Awaiting Verification
                                </span>
                            <?php elseif ($status === 'confirmed'): ?>
                                <a href="payment.php?id=<?php echo $apt_id; ?>" class="action-btn btn-action-pay">
                                    <i class="fas fa-credit-card"></i> Pay Fee
                                </a>
                            <?php elseif ($status === 'pending'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-2 fw-semibold fs-7">
                                    <i class="far fa-hourglass me-1"></i> Pending
                                </span>
                            <?php else: ?>
                                <span class="text-muted fs-7">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Action Buttons -->
                        <td class="text-end">
                            <div class="d-flex justify-content-end align-items-center gap-1">
                                <?php if ($status === 'pending'): ?>
                                    <form method="POST" action="cancel_appointment.php" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this appointment request?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="id" value="<?php echo $apt_id; ?>">
                                        <button type="submit" class="action-btn btn-action-cancel" title="Cancel Appointment"><i class="fas fa-times me-1"></i> Cancel</button>
                                    </form>

                                <?php elseif ($status === 'completed'): ?>

                                    <?php if (empty($row['rating_id'])): ?>
                                        <a href="rate_doctor.php?id=<?php echo $apt_id; ?>" class="action-btn btn-action-rate" title="Rate Doctor">
                                            <i class="fas fa-star me-1"></i> Rate
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-2 fw-semibold fs-7 me-1">
                                            <i class="fas fa-star text-warning me-1"></i> Rated
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($row['prescription_id'])): ?>
                                        <a href="../doctor/view_prescription.php?id=<?php echo intval($row['prescription_id']); ?>" class="action-btn btn-action-rx" title="View Prescription">
                                            <i class="fas fa-file-prescription me-1"></i> Rx
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span class="text-muted fs-7">—</span>
                                <?php endif; ?>
                            </div>
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