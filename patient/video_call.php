<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only authenticated Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = intval($_SESSION['user_id']);

// Fetch confirmed video-call appointments securely
$appointments_query = "SELECT a.*, u.fullname as doctor_name, d.specialization
                       FROM appointments a
                       JOIN users u ON a.doctor_id = u.id
                       LEFT JOIN doctors d ON a.doctor_id = d.user_id
                       WHERE a.patient_id = $patient_id
                       AND a.type = 'video-call'
                       AND a.status = 'confirmed'
                       ORDER BY a.appointment_date ASC";

$appointments_res = mysqli_query($conn, $appointments_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Consultations — MediCare Telemedicine</title>

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
            --accent-emerald: #10b981;
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

        /* Appointment Card */
        .appointment-card {
            background: white;
            border-radius: 22px;
            padding: 25px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-left: 6px solid #06b6d4;
            transition: all 0.3s ease;
        }

        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }

        .doctor-avatar {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            background: linear-gradient(135deg, #06b6d4 0%, #6366f1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
        }

        .btn-join {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.35);
            color: white;
        }

        /* Video Call Room (Full Screen Overlay) */
        #videoCallRoom {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: #0f172a;
            z-index: 99999;
        }

        #videoCallRoom .call-header {
            background: #1e293b;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            border-bottom: 1px solid #334155;
        }

        #videoCallRoom .call-header h5 {
            margin: 0;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.5px;
        }

        #myCallContainer {
            width: 100%;
            height: calc(100vh - 70px);
        }

        .btn-end-call {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 22px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-end-call:hover {
            background: #dc2626;
            color: white;
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 24px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
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
            <a href="video_call.php" class="nav-item-link active">
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

<!-- VIDEO CALL ROOM (Full Screen Modal Overlay) -->
<div id="videoCallRoom">
    <div class="call-header">
        <h5><i class="fas fa-video text-emerald me-2"></i> MediCare Secure Video Consultation</h5>
        <button class="btn-end-call" onclick="endCall()">
            <i class="fas fa-phone-slash"></i> End Consultation Call
        </button>
    </div>
    <div id="myCallContainer"></div>
</div>

<!-- WORKSPACE -->
<div class="workspace">

    <!-- Topbar -->
    <div class="header-bar">
        <div class="header-title">
            <h4>Video Consultations</h4>
            <p>Access your end-to-end encrypted video call sessions with doctors</p>
        </div>
        <div class="user-pill">
            <div class="avatar-circle"><i class="fas fa-user"></i></div>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <?php if (mysqli_num_rows($appointments_res) === 0): ?>
        <div class="empty-state">
            <div class="text-cyan opacity-50 mb-3" style="font-size: 50px;">
                <i class="fas fa-video-slash"></i>
            </div>
            <h5 class="fw-bold text-dark">No Confirmed Video Consultations</h5>
            <p class="text-muted fs-7 mb-4">
                You currently do not have any confirmed video call appointments. Book a video appointment and wait for administrative confirmation.
            </p>
            <a href="book_appointment.php" class="btn-join">
                <i class="fas fa-calendar-plus me-1"></i> Book Video Appointment
            </a>
        </div>
    <?php else: ?>

        <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-check-circle text-emerald me-2"></i> Confirmed Video Call Sessions</h5>

        <?php while ($apt = mysqli_fetch_assoc($appointments_res)): ?>
            <div class="appointment-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                    <div class="d-flex align-items-center gap-3">
                        <div class="doctor-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">
                                Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?>
                            </h5>
                            <p class="mb-1 text-cyan fw-semibold" style="font-size: 13px;">
                                <?php echo htmlspecialchars($apt['specialization'] ?? 'Specialist'); ?>
                            </p>
                            <p class="mb-0 text-muted" style="font-size: 13px;">
                                <i class="fas fa-calendar-alt text-dark me-1"></i>
                                <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>
                                &nbsp;&bull;&nbsp;
                                <i class="fas fa-clock text-dark me-1"></i>
                                <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                            </p>
                        </div>
                    </div>

                    <button class="btn-join" onclick="startCall('<?php echo $apt['id']; ?>', '<?php echo addslashes($apt['doctor_name']); ?>')">
                        <i class="fas fa-video"></i> Join Encrypted Call
                    </button>

                </div>
            </div>
        <?php endwhile; ?>

    <?php endif; ?>
</div>

<!-- ZegoCloud WebRTC UI Kit SDK -->
<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>

<script>
    const USER_ID   = "patient_<?php echo $_SESSION['user_id']; ?>";
    const USER_NAME = "<?php echo addslashes($_SESSION['user_name']); ?>";

    let zpInstance = null;

    function startCall(appointmentId, doctorName) {
        // Calls remain disabled until a server-side Zego token endpoint is configured.
        alert('Video calling is temporarily unavailable while secure server-side call authentication is configured.');
    }

    function endCall() {
        if (zpInstance) {
            zpInstance.destroy();
            zpInstance = null;
        }
        document.getElementById('videoCallRoom').style.display = 'none';
    }
</script>

</body>
</html>