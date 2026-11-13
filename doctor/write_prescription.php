<?php
session_start();
include '../db/connection.php';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Access Control: Ensure only authenticated Doctors can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my_appointments.php");
    exit();
}

$doctor_id      = intval($_SESSION['user_id']);
$appointment_id = intval($_GET['id']);

// Fetch Appointment and Patient Details
$apt_query = "SELECT a.*, u.fullname as patient_name,
                     u.email as patient_email,
                     u.phone as patient_phone,
                     d.specialization
              FROM appointments a
              JOIN users u ON a.patient_id = u.id
              LEFT JOIN doctors d ON a.doctor_id = d.user_id
              WHERE a.id = $appointment_id
              AND a.doctor_id = $doctor_id";

$apt_res = mysqli_query($conn, $apt_query);
$apt     = mysqli_fetch_assoc($apt_res);

if (!$apt) {
    echo "<script>alert('Invalid or unauthorized appointment request!'); window.location='my_appointments.php';</script>";
    exit();
}

// Check if Prescription already exists for this appointment
$already_res = mysqli_query($conn, "SELECT id FROM prescriptions WHERE appointment_id = $appointment_id");
$already     = mysqli_fetch_assoc($already_res);

if ($already) {
    header("Location: view_prescription.php?id=" . intval($already['id']));
    exit();
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }
    $diagnosis    = mysqli_real_escape_string($conn, trim($_POST['diagnosis']));
    $medicines    = mysqli_real_escape_string($conn, trim($_POST['medicines'])); // JSON encoded string
    $instructions = mysqli_real_escape_string($conn, trim($_POST['instructions']));
    $follow_up    = !empty($_POST['follow_up_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['follow_up_date']) . "'" : "NULL";
    $patient_id   = intval($apt['patient_id']);

    $insert_sql = "INSERT INTO prescriptions
                   (appointment_id, doctor_id, patient_id, diagnosis, medicines, instructions, follow_up_date)
                   VALUES
                   ($appointment_id, $doctor_id, $patient_id, '$diagnosis', '$medicines', '$instructions', $follow_up)";

    if (mysqli_query($conn, $insert_sql)) {
        $pres_id = mysqli_insert_id($conn);

        // Update appointment status to completed automatically
        mysqli_query($conn, "UPDATE appointments SET status='completed' WHERE id = $appointment_id");

        echo "<script>
                alert('Prescription created and saved successfully!');
                window.location='view_prescription.php?id=$pres_id';
              </script>";
        exit();
    } else {
        echo "<script>alert('Database Error: Failed to save prescription.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Prescription — MediCare Telemedicine</title>

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

        /* Card Layout */
        .pres-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }

        /* Patient Banner */
        .patient-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 30px;
            border-left: 6px solid var(--primary-color);
        }

        .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 13px;
            margin-bottom: 8px;
            text-uppercase: uppercase;
            letter-spacing: 0.3px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            font-size: 15px;
            color: #0f172a;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
        }

        .section-title {
            font-weight: 800;
            color: #0f172a;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
            margin-bottom: 22px;
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
        }

        /* Dynamic Medicine Rows */
        .medicine-row {
            background: #f8fafc;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
        }

        .btn-add-medicine {
            background: #0284c7;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-medicine:hover {
            background: #0369a1;
            color: white;
        }

        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-remove:hover {
            background: #dc2626;
        }

        .btn-write {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 45px;
            font-size: 16px;
            font-weight: 800;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-write:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(13, 148, 136, 0.35);
            color: white;
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
            <i class="fas fa-file-medical text-teal me-2" style="color: var(--primary-color);"></i> Generate Digital Rx Prescription
        </h5>
        <div class="doctor-badge">
            <i class="fas fa-stethoscope text-teal" style="color: var(--accent-color);"></i>
            <span>Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <div class="pres-card">

        <!-- Patient Demographics Banner -->
        <div class="patient-banner">
            <div class="row align-items-center g-3">
                <div class="col-md-3">
                    <p class="mb-1 text-muted fs-7 font-semibold">Patient Name</p>
                    <p class="fw-bold mb-0 text-dark fs-6"><?php echo htmlspecialchars($apt['patient_name']); ?></p>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted fs-7 font-semibold">Contact Number</p>
                    <p class="fw-bold mb-0 text-dark fs-6"><?php echo htmlspecialchars($apt['patient_phone']); ?></p>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted fs-7 font-semibold">Consultation Date</p>
                    <p class="fw-bold mb-0 text-dark fs-6"><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></p>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted fs-7 font-semibold">Attending Doctor</p>
                    <p class="fw-bold mb-0 text-dark fs-6">
                        Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        <span class="d-block text-teal fs-7 fw-semibold" style="color: var(--primary-color);">
                            <?php echo !empty($apt['specialization']) ? htmlspecialchars($apt['specialization']) : 'General Physician'; ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Prescription Form -->
        <form method="POST" id="presForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Diagnosis -->
            <div class="section-title">
                <i class="fas fa-stethoscope text-teal" style="color: var(--primary-color);"></i>
                Clinical Diagnosis
            </div>

            <div class="mb-4">
                <label class="form-label">Patient Diagnosis & Primary Conditions</label>
                <textarea class="form-control" name="diagnosis" rows="3" required placeholder="e.g. Acute bronchitis, Viral fever with upper respiratory tract infection..."></textarea>
            </div>

            <!-- Medicines -->
            <div class="section-title">
                <i class="fas fa-pills text-teal" style="color: var(--primary-color);"></i>
                Prescribed Medications
            </div>

            <div id="medicinesContainer">
                <div class="medicine-row" id="med_1">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label d-md-none">Medicine Name</label>
                            <input type="text" class="form-control" name="med_name[]" placeholder="Medicine Name (e.g. Paracetamol 500mg)" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-md-none">Dosage</label>
                            <input type="text" class="form-control" name="med_dose[]" placeholder="Dosage (e.g. 1 Tablet)" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-md-none">Timing</label>
                            <input type="text" class="form-control" name="med_timing[]" placeholder="Timing (e.g. 3x Daily After Meals)" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-md-none">Duration (Days)</label>
                            <input type="number" class="form-control" name="med_days[]" placeholder="Days (e.g. 5)" min="1" required>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-add-medicine mt-2 mb-4" onclick="addMedicine()">
                <i class="fas fa-plus"></i> Add Another Medicine
            </button>

            <!-- Instructions -->
            <div class="section-title">
                <i class="fas fa-notes-medical text-teal" style="color: var(--primary-color);"></i>
                Special Advice & Instructions
            </div>

            <div class="mb-4">
                <label class="form-label">Dietary & Lifestyle Instructions</label>
                <textarea class="form-control" name="instructions" rows="3" placeholder="e.g. Take medicines strictly after meals. Ensure adequate fluid intake and rest for 3 days..."></textarea>
            </div>

            <!-- Follow Up -->
            <div class="section-title">
                <i class="fas fa-calendar-alt text-teal" style="color: var(--primary-color);"></i>
                Follow-Up Appointment
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Follow-Up Date (Optional)</label>
                    <input type="date" class="form-control" name="follow_up_date" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-write">
                    <i class="fas fa-file-signature"></i> Finalize & Generate Prescription
                </button>
            </div>

        </form>
    </div>
</div>

<script>
let medCount = 1;

function addMedicine() {
    medCount++;
    const container = document.getElementById('medicinesContainer');
    const div = document.createElement('div');
    div.className = 'medicine-row';
    div.id = 'med_' + medCount;
    div.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" class="form-control" name="med_name[]" placeholder="Medicine Name" required>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="med_dose[]" placeholder="Dosage" required>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="med_timing[]" placeholder="Timing Routine" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="med_days[]" placeholder="Days" min="1" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn-remove" onclick="removeMed('med_${medCount}')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    container.appendChild(div);
}

function removeMed(id) {
    document.getElementById(id).remove();
}

// Convert medication rows to JSON string on submission
document.getElementById('presForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const names   = [...document.querySelectorAll('[name="med_name[]"]')].map(i => i.value);
    const doses   = [...document.querySelectorAll('[name="med_dose[]"]')].map(i => i.value);
    const timings = [...document.querySelectorAll('[name="med_timing[]"]')].map(i => i.value);
    const days    = [...document.querySelectorAll('[name="med_days[]"]')].map(i => i.value);

    const medicines = names.map((name, i) => ({
        name: name,
        dose: doses[i],
        timing: timings[i],
        days: days[i]
    }));

    let hidden = document.getElementById('medicines_json');
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'medicines_json';
        hidden.name = 'medicines';
        this.appendChild(hidden);
    }
    hidden.value = JSON.stringify(medicines);

    this.submit();
});
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>