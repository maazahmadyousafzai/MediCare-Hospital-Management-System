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

// Fetch list of active verified doctors
$doctors_query = "SELECT u.id, u.fullname, d.specialization, d.qualification, d.experience
                  FROM users u
                  JOIN doctors d ON u.id = d.user_id
                  WHERE u.role='doctor' AND u.status='active'
                  ORDER BY u.fullname ASC";
$doctors = mysqli_query($conn, $doctors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment — MediCare Telemedicine</title>

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

        /* Page Banner */
        .page-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 24px;
            padding: 28px 32px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-banner h4 {
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px;
            letter-spacing: -0.5px;
            font-size: 22px;
        }

        .page-banner p {
            color: #94a3b8;
            margin: 0;
            font-size: 14px;
            font-weight: 500;
        }

        /* Doctor Selection Cards */
        .doctor-card {
            background: white;
            border-radius: 22px;
            padding: 26px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            position: relative;
        }

        .doctor-card:hover {
            transform: translateY(-4px);
            border-color: #06b6d4;
            box-shadow: 0 15px 35px rgba(6, 182, 212, 0.12);
        }

        .doctor-card.selected {
            border-color: #06b6d4;
            background: rgba(6, 182, 212, 0.04);
            box-shadow: 0 15px 35px rgba(6, 182, 212, 0.18);
        }

        .doctor-avatar {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
            margin-bottom: 18px;
            box-shadow: 0 10px 20px rgba(6, 182, 212, 0.25);
        }

        .doctor-card h5 {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .spec-badge {
            background: #ecfeff;
            color: #0891b2;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 14px;
            border: 1px solid #cffaff;
        }

        .doctor-info-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .doctor-info-text p {
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doctor-info-text i {
            color: #06b6d4;
            width: 16px;
        }

        /* Booking Form Card */
        .booking-form-card {
            background: white;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-top: 35px;
            display: none;
        }

        .booking-form-card h5 {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 25px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .selected-doctor-info {
            background: #f0fdf4;
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 25px;
            border-left: 5px solid #10b981;
            border-top: 1px solid #dcfce7;
            border-right: 1px solid #dcfce7;
            border-bottom: 1px solid #dcfce7;
        }

        .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 12px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
        }

        .btn-book {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 36px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.4);
            color: white;
        }

        .btn-cancel {
            background: #64748b;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #475569;
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
            <a href="book_appointment.php" class="nav-item-link active">
                <i class="fas fa-calendar-plus me-1"></i> Book Appointment
            </a>
            <a href="my_appointments.php" class="nav-item-link">
                <i class="fas fa-calendar-check me-1"></i> My Consultations
            </a>
            <a href="prescriptions.php" class="nav-item-link">
                <i class="fas fa-file-prescription me-1"></i> Digital Records
            </a>
            <a href="profile.php" class="nav-item-link">
                <i class="fas fa-user-gear me-1"></i> Account Settings
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

    <!-- Header Bar -->
    <div class="header-bar">
        <div class="header-title">
            <h4>Schedule New Consultation</h4>
            <p>Select a specialist and specify your preferred date & time</p>
        </div>
        <div class="user-pill">
            <div class="avatar-circle"><i class="fas fa-user"></i></div>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Page Banner -->
    <div class="page-banner">
        <div>
            <h4><i class="fas fa-user-md text-cyan me-2"></i> Select Medical Specialist</h4>
            <p>Choose your preferred doctor from our verified panel to unlock appointment scheduling.</p>
        </div>
    </div>

    <!-- Doctors Grid -->
    <div class="row g-4" id="doctorsGrid">
        <?php if (mysqli_num_rows($doctors) == 0): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fas fa-user-md fa-4x mb-3 text-secondary opacity-50"></i>
                <h5 class="fw-bold text-dark">No Active Doctors Available</h5>
                <p>Please check back later or contact hospital administration.</p>
            </div>
        <?php else: ?>
            <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
                <?php $doc_id = intval($doc['id']); ?>
                <div class="col-md-6 col-lg-4">
                    <div class="doctor-card" 
                         onclick="selectDoctor(<?php echo $doc_id; ?>, '<?php echo htmlspecialchars(addslashes($doc['fullname'])); ?>', '<?php echo htmlspecialchars(addslashes($doc['specialization'])); ?>')"
                         id="card_<?php echo $doc_id; ?>">

                        <div class="doctor-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>

                        <h5>Dr. <?php echo htmlspecialchars($doc['fullname']); ?></h5>
                        <span class="spec-badge"><?php echo htmlspecialchars($doc['specialization']); ?></span>

                        <div class="doctor-info-text">
                            <p><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($doc['qualification']); ?></p>
                            <p><i class="fas fa-history"></i> <?php echo intval($doc['experience']); ?> Years Experience</p>
                            
                            <?php
                            // Fetch Doctor's average rating
                            $avg_res = mysqli_query($conn,
                                "SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total
                                 FROM ratings WHERE doctor_id = $doc_id");
                            $avg = mysqli_fetch_assoc($avg_res);
                            ?>

                            <div class="mt-2 pt-2 border-top">
                                <?php if($avg['total'] > 0): ?>
                                    <?php
                                    $stars = round($avg['avg_rating']);
                                    for($s = 1; $s <= 5; $s++) {
                                        echo ($s <= $stars) ? '<span class="text-warning">★</span>' : '<span class="text-muted opacity-50">★</span>';
                                    }
                                    ?>
                                    <span class="ms-1 fw-bold text-dark fs-7">
                                        <?php echo $avg['avg_rating']; ?>/5
                                        <small class="text-muted fw-normal">(<?php echo $avg['total']; ?> reviews)</small>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted opacity-50">★★★★★</span>
                                    <small class="text-muted ms-1">(No reviews yet)</small>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Booking Form Section -->
    <div class="booking-form-card" id="bookingForm">

        <h5>
            <i class="fas fa-clock text-cyan"></i>
            Appointment Scheduling Details
        </h5>

        <!-- Selected Doctor Banner -->
        <div class="selected-doctor-info">
            <span class="text-muted fw-semibold">Chosen Specialist:</span>
            <strong class="text-dark fs-6 ms-1" id="selectedDoctorName"></strong> &bull; 
            <span class="text-success fw-bold" id="selectedDoctorSpec"></span>
        </div>

        <form action="book_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
            <input type="hidden" name="doctor_id" id="doctor_id_input">

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="far fa-calendar-alt me-1 text-cyan"></i> Appointment Date
                    </label>
                    <input type="date" class="form-control" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="far fa-clock me-1 text-cyan"></i> Preferred Time Slot
                    </label>
                    <select class="form-select" name="appointment_time" required>
                        <option value="">-- Select Preferred Slot --</option>
                        <option value="09:00">09:00 AM</option>
                        <option value="09:30">09:30 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="10:30">10:30 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="11:30">11:30 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="14:00">02:00 PM</option>
                        <option value="14:30">02:30 PM</option>
                        <option value="15:00">03:00 PM</option>
                        <option value="15:30">03:30 PM</option>
                        <option value="16:00">04:00 PM</option>
                        <option value="16:30">04:30 PM</option>
                        <option value="17:00">05:00 PM</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-video me-1 text-cyan"></i> Consultation Type
                    </label>
                    <select class="form-select" name="type" required>
                        <option value="in-person">🏥 In-Person Physical Visit</option>
                        <option value="video-call">🎥 Encrypted Video Call</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="far fa-sticky-note me-1 text-cyan"></i> Symptoms / Notes (Optional)
                    </label>
                    <input type="text" class="form-control" name="notes" placeholder="e.g. Mild fever, persistent cough for 2 days">
                </div>

            </div>

            <div class="mt-4 pt-2 d-flex align-items-center gap-3">
                <button type="submit" class="btn-book">
                    <i class="fas fa-calendar-check"></i> Confirm Appointment Request
                </button>
                <button type="button" class="btn-cancel" onclick="cancelSelection()">
                    Cancel
                </button>
            </div>

        </form>
    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function selectDoctor(id, name, spec) {
        // Remove highlight from all cards
        document.querySelectorAll('.doctor-card').forEach(c => {
            c.classList.remove('selected');
        });

        // Highlight selected doctor card
        const card = document.getElementById('card_' + id);
        if (card) {
            card.classList.add('selected');
        }

        // Set values in the form
        document.getElementById('doctor_id_input').value = id;
        document.getElementById('selectedDoctorName').innerText = 'Dr. ' + name;
        document.getElementById('selectedDoctorSpec').innerText = spec;

        // Show the booking form
        const bookingForm = document.getElementById('bookingForm');
        bookingForm.style.display = 'block';

        // Smooth scroll to the form
        bookingForm.scrollIntoView({
            behavior: 'smooth'
        });
    }

    function cancelSelection() {
        document.querySelectorAll('.doctor-card').forEach(c => {
            c.classList.remove('selected');
        });
        document.getElementById('bookingForm').style.display = 'none';
    }
</script>

</body>
</html>