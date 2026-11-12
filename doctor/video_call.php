<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only logged-in Doctors can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = intval($_SESSION['user_id']);

// Fetch Confirmed Video Call Appointments
$query = "SELECT a.*, u.fullname as patient_name, u.phone
          FROM appointments a
          JOIN users u ON a.patient_id = u.id
          WHERE a.doctor_id = $doctor_id
          AND a.type = 'video-call'
          AND a.status = 'confirmed'
          ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$appointments = mysqli_query($conn, $query);
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

        /* Appointment Video Cards */
        .appointment-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            border-left: 6px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .appointment-card:hover {
            transform: translateY(-3px);
        }

        .patient-avatar {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.2);
            flex-shrink: 0;
        }

        .btn-join {
            background: linear-gradient(135deg, #10b981, #047857);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.35);
            color: white;
        }

        /* Full Screen Video Call Room */
        #videoCallRoom {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #090d16;
            z-index: 99999;
        }

        #videoCallRoom .call-header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.08);
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
            border-radius: 10px;
            padding: 9px 22px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-end-call:hover {
            background: #dc2626;
            color: white;
        }

        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
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
        <a href="dashboard.php">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="my_appointments.php">
            <i class="fas fa-calendar-check"></i> My Appointments
        </a>
        <a href="video_call.php" class="active">
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

<!-- FULLSCREEN VIDEO CALL OVERLAY -->
<div id="videoCallRoom">
    <div class="call-header">
        <h5>
            <i class="fas fa-video text-teal me-2" style="color: var(--accent-color);"></i>
            MediCare Encrypted Telemedicine Session
        </h5>
        <button class="btn-end-call" onclick="endCall()">
            <i class="fas fa-phone-slash"></i> Leave Session
        </button>
    </div>
    <div id="myCallContainer"></div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <h5>
            <i class="fas fa-video text-teal me-2" style="color: var(--primary-color);"></i> Live Video Consultation Center
        </h5>
        <div class="doctor-badge">
            <i class="fas fa-stethoscope text-teal" style="color: var(--accent-color);"></i>
            <span>Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <?php if (mysqli_num_rows($appointments) == 0): ?>
        <div class="empty-state">
            <i class="fas fa-video-slash fa-4x mb-3 text-secondary opacity-50"></i>
            <h5 class="fw-bold text-dark">No Video Call Appointments Scheduled</h5>
            <p class="text-muted fs-7 max-w-md mx-auto">
                Confirmed video call appointments will appear here automatically when booked by patients.
            </p>
        </div>
    <?php else: ?>

        <h5 class="fw-bold mb-4 text-dark">
            <i class="fas fa-calendar-check text-success me-2"></i>
            Confirmed Video Telemedicine Slots
        </h5>

        <?php while ($apt = mysqli_fetch_assoc($appointments)): ?>
        <div class="appointment-card">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="patient-avatar">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold text-dark fs-5">
                            <?php echo htmlspecialchars($apt['patient_name']); ?>
                        </h5>
                        <p class="mb-1 text-muted fs-7 fw-semibold">
                            <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($apt['phone']); ?>
                        </p>
                        <p class="mb-0 text-dark fs-7 fw-semibold">
                            <span class="text-teal me-3" style="color: var(--primary-color);">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>
                            </span>
                            <span class="text-teal" style="color: var(--primary-color);">
                                <i class="far fa-clock me-1"></i>
                                <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div>
                    <button class="btn-join" onclick="startCall('<?php echo intval($apt['id']); ?>', '<?php echo htmlspecialchars(addslashes($apt['patient_name'])); ?>')">
                        <i class="fas fa-video"></i> Launch Video Session
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

    <?php endif; ?>
</div>

<!-- ZegoUIKit WebRTC Prebuilt SDK -->
<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>

<script>
    const APP_ID   = 2101106645;
    const APP_SIGN = "94910155912595ae481875c2e881b62d9ecd6ce846adefcc7d708ce74b9f6302";

    const USER_ID   = "doctor_<?php echo intval($_SESSION['user_id']); ?>";
    const USER_NAME = "Dr. <?php echo htmlspecialchars(addslashes($_SESSION['user_name'])); ?>";

    let zpInstance = null;

    function startCall(appointmentId, patientName) {
        const ROOM_ID = "medicare_room_" + appointmentId;

        document.getElementById('videoCallRoom').style.display = 'block';

        const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(
            APP_ID,
            APP_SIGN,
            ROOM_ID,
            USER_ID,
            USER_NAME
        );

        zpInstance = ZegoUIKitPrebuilt.create(kitToken);

        zpInstance.joinRoom({
            container: document.getElementById('myCallContainer'),
            scenario: {
                mode: ZegoUIKitPrebuilt.OneONoneCall,
            },
            showPreJoinView: true,
            turnOnCameraWhenJoining: true,
            turnOnMicrophoneWhenJoining: true,
            showLeavingView: false,
            onLeaveRoom: () => {
                endCall();
            }
        });
    }

    function endCall() {
        if (zpInstance) {
            zpInstance.destroy();
            zpInstance = null;
        }
        document.getElementById('videoCallRoom').style.display = 'none';
    }
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>