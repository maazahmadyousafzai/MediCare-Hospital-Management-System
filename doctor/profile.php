<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only logged-in Doctors can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = intval($_SESSION['user_id']);

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname       = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $phone          = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $specialization = mysqli_real_escape_string($conn, trim($_POST['specialization']));
    $qualification  = mysqli_real_escape_string($conn, trim($_POST['qualification']));
    $experience     = intval($_POST['experience']);

    // Update Users Table
    mysqli_query($conn, "UPDATE users SET fullname='$fullname', phone='$phone' WHERE id=$doctor_id");

    // Update Doctors Table
    mysqli_query($conn, "UPDATE doctors SET 
                         specialization='$specialization',
                         qualification='$qualification',
                         experience='$experience'
                         WHERE user_id=$doctor_id");

    // Optional Password Update
    if (!empty($_POST['new_password'])) {
        $new_pass = md5($_POST['new_password']);
        mysqli_query($conn, "UPDATE users SET password='$new_pass' WHERE id=$doctor_id");
    }

    $_SESSION['user_name'] = $fullname;

    echo "<script>alert('Profile Updated Successfully!'); window.location='profile.php';</script>";
    exit();
}

// Fetch Doctor Profile Information
$user_res = mysqli_query($conn,
    "SELECT u.*, d.specialization, d.qualification, d.experience
     FROM users u
     LEFT JOIN doctors d ON u.id = d.user_id
     WHERE u.id = $doctor_id");
$user = mysqli_fetch_assoc($user_res);

// Fetch Patient Reviews & Ratings
$ratings = mysqli_query($conn,
    "SELECT r.*, u.fullname as patient_name,
            DATE_FORMAT(r.created_at, '%d %b %Y') as date
     FROM ratings r
     JOIN users u ON r.patient_id = u.id
     WHERE r.doctor_id = $doctor_id
     ORDER BY r.created_at DESC");

// Fetch Average Rating Statistics
$avg = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT ROUND(AVG(rating), 1) as avg,
            COUNT(*) as total
     FROM ratings WHERE doctor_id = $doctor_id"));
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

        /* Profile Main Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 24px;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
            color: white;
            margin: 0 auto 18px;
            box-shadow: 0 12px 25px rgba(13, 148, 136, 0.25);
        }

        .profile-name {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }

        .profile-spec {
            text-align: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 25px;
        }

        .info-badge {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 16px 22px;
            margin-bottom: 30px;
            text-align: center;
        }

        .info-badge p {
            margin: 0;
            color: #166534;
            font-size: 14px;
            font-weight: 600;
        }

        /* Form Controls */
        .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 13px;
            margin-bottom: 8px;
            text-uppercase: uppercase;
            letter-spacing: 0.3px;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            font-size: 15px;
            color: #0f172a;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
        }

        .section-title {
            font-weight: 800;
            color: #0f172a;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
            margin-bottom: 22px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
        }

        .btn-update {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 40px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.25);
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(13, 148, 136, 0.35);
            color: white;
        }

        /* Review Cards & Average Summary */
        .avg-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 16px;
            padding: 16px 28px;
            text-align: center;
        }

        .review-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #f59e0b;
            height: 100%;
        }

        .patient-initial {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 16px;
            flex-shrink: 0;
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
        <a href="video_call.php">
            <i class="fas fa-video"></i> Video Consultations
        </a>
        <a href="profile.php" class="active">
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
            <i class="fas fa-user-cog text-teal me-2" style="color: var(--primary-color);"></i> Doctor Profile & Review Management
        </h5>
        <div class="doctor-badge">
            <i class="fas fa-stethoscope text-teal" style="color: var(--accent-color);"></i>
            <span>Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Profile Form Card -->
    <div class="profile-card">

        <div class="profile-avatar">
            <i class="fas fa-user-md"></i>
        </div>
        <div class="profile-name">
            Dr. <?php echo htmlspecialchars($user['fullname']); ?>
        </div>
        <div class="profile-spec">
            <?php echo !empty($user['specialization']) ? htmlspecialchars($user['specialization']) : 'General Practitioner'; ?>
        </div>

        <!-- Info Badge -->
        <div class="info-badge">
            <p>
                <i class="far fa-envelope me-1"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?> &nbsp;&bull;&nbsp; 
                <i class="far fa-calendar-alt me-1"></i> <strong>Registered:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?> &nbsp;&bull;&nbsp; 
                <i class="fas fa-shield-alt me-1"></i> <strong>Account Status:</strong> Active Practitioner
            </p>
        </div>

        <!-- Update Form -->
        <form method="POST">

            <div class="section-title">
                <i class="fas fa-id-card text-teal" style="color: var(--primary-color);"></i>
                Personal Information
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
            </div>

            <div class="section-title">
                <i class="fas fa-stethoscope text-teal" style="color: var(--primary-color);"></i>
                Professional Clinical Details
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Specialization</label>
                    <select class="form-select" name="specialization">
                        <?php
                        $specs = ['Cardiologist','Dermatologist','General Physician','Neurologist','Orthopedic','Pediatrician','Psychiatrist','Surgeon'];
                        foreach ($specs as $spec):
                        ?>
                        <option value="<?php echo $spec; ?>" <?php echo ($user['specialization'] == $spec) ? 'selected' : ''; ?>>
                            <?php echo $spec; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Medical Qualification</label>
                    <input type="text" class="form-control" name="qualification" value="<?php echo htmlspecialchars($user['qualification']); ?>" placeholder="e.g. MBBS, FCPS">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Clinical Experience (Years)</label>
                    <input type="number" class="form-control" name="experience" value="<?php echo htmlspecialchars($user['experience']); ?>" min="0">
                </div>
            </div>

            <div class="section-title">
                <i class="fas fa-lock text-teal" style="color: var(--primary-color);"></i>
                Security & Password Update
                <small class="text-muted fw-normal ms-2" style="font-size:12px;">(Leave blank to retain current password)</small>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" placeholder="Enter new secure password">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-update">
                    <i class="fas fa-save me-2"></i> Save Profile Changes
                </button>
            </div>

        </form>
    </div>

    <!-- RATINGS SECTION -->
    <div class="profile-card">

        <!-- Header & Average Rating Box -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-star text-warning me-2"></i>
                    Patient Reviews & Consult Feedback
                </h5>
                <p class="text-muted mb-0 fs-7">
                    Verified feedback and star ratings submitted by patients following consultations.
                </p>
            </div>

            <?php if ($avg['total'] > 0): ?>
            <div class="avg-box">
                <div style="font-size:32px; font-weight:800; color:#b45309; line-height:1;">
                    <?php echo $avg['avg']; ?>
                </div>
                <div class="my-1">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <span style="color:<?php echo $s <= $avg['avg'] ? '#f59e0b' : '#cbd5e1'; ?>; font-size:18px;">★</span>
                    <?php endfor; ?>
                </div>
                <div style="font-size:12px; color:#78350f; font-weight:600;">
                    Based on <?php echo $avg['total']; ?> Patient Reviews
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Reviews Grid -->
        <?php if (mysqli_num_rows($ratings) == 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-star fa-4x mb-3 text-secondary opacity-50"></i>
                <h6 class="fw-bold">No patient reviews recorded yet.</h6>
                <p class="small">Patients can rate your service after an appointment is completed.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
            <?php while ($r = mysqli_fetch_assoc($ratings)): ?>
                <div class="col-md-6">
                    <div class="review-card">

                        <!-- Patient Details & Star Rating -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="patient-initial">
                                    <?php echo strtoupper(substr($r['patient_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($r['patient_name']); ?></div>
                                    <div class="text-muted fs-7"><i class="far fa-clock me-1"></i><?php echo $r['date']; ?></div>
                                </div>
                            </div>

                            <div>
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <span style="color:<?php echo $s <= $r['rating'] ? '#f59e0b' : '#cbd5e1'; ?>; font-size:16px;">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Rating Label Soft Badge -->
                        <?php
                        $labels = [
                            1 => ['Poor', '#ef4444', '#fef2f2'],
                            2 => ['Fair', '#f97316', '#fff7ed'],
                            3 => ['Good', '#f59e0b', '#fffbeb'],
                            4 => ['Very Good', '#0284c7', '#f0f9ff'],
                            5 => ['Excellent', '#10b981', '#f0fdf4']
                        ];
                        $lbl = $labels[$r['rating']];
                        ?>
                        <span style="background:<?php echo $lbl[2]; ?>; color:<?php echo $lbl[1]; ?>; border: 1px solid <?php echo $lbl[1]; ?>30; padding:4px 14px; border-radius:50px; font-size:11px; font-weight:700; display:inline-block; margin-bottom:12px;">
                            <?php echo $lbl[0]; ?>
                        </span>

                        <!-- Review Content -->
                        <?php if (!empty($r['review'])): ?>
                            <p class="m-0 text-secondary fs-7 fst-italic">
                                "<?php echo htmlspecialchars($r['review']); ?>"
                            </p>
                        <?php else: ?>
                            <p class="m-0 text-muted fs-7 fst-italic">
                                No written review comments submitted.
                            </p>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>