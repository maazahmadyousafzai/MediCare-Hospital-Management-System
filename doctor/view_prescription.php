<?php
session_start();
require_once '../db/connection.php';

$role = $_SESSION['user_role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId < 1 || !in_array($role, ['patient', 'doctor', 'admin'], true)) {
    header('Location: ../login.php');
    exit();
}
$presId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$presId) {
    http_response_code(400);
    exit('Invalid prescription request.');
}

$sql = "SELECT p.*,
               doc.fullname AS doctor_name,
               doc.email AS doctor_email,
               pat.fullname AS patient_name,
               pat.phone AS patient_phone,
               d.specialization, d.qualification,
               a.appointment_date, a.appointment_time
        FROM prescriptions p
        JOIN users doc ON p.doctor_id = doc.id
        JOIN users pat ON p.patient_id = pat.id
        LEFT JOIN doctors d ON p.doctor_id = d.user_id
        JOIN appointments a ON p.appointment_id = a.id
        WHERE p.id = ?
          AND ((? = 'patient' AND p.patient_id = ?)
            OR (? = 'doctor' AND p.doctor_id = ?)
            OR ? = 'admin')
        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'isissi', $presId, $role, $userId, $role, $userId, $role);
mysqli_stmt_execute($stmt);
$pres = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pres) {
    http_response_code(404);
    exit('Prescription record not found or access denied.');
}
$medicines = json_decode($pres['medicines'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Prescription #Rx-<?php echo intval($pres['id']); ?> — MediCare</title>

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
            --dark-bg: #0f172a;
            --light-bg: #f8fafc;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: #334155;
            padding: 40px 15px;
        }

        /* Action Bar */
        .action-bar {
            max-width: 850px;
            margin: 0 auto 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-action-custom {
            border-radius: 12px;
            padding: 12px 26px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-print {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: white;
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.25);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 12px 25px rgba(13, 148, 136, 0.35);
        }

        .btn-back {
            background: #64748b;
            color: white;
        }

        .btn-back:hover {
            background: #475569;
            color: white;
        }

        /* Prescription Container */
        .prescription-card {
            background: white;
            max-width: 850px;
            margin: 0 auto;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* Header Section */
        .pres-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 35px 45px;
            position: relative;
        }

        .hospital-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hospital-brand .icon-box {
            width: 52px;
            height: 52px;
            background: rgba(45, 212, 191, 0.12);
            color: #2dd4bf;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 26px;
            border: 1px solid rgba(45, 212, 191, 0.2);
        }

        .hospital-name {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .hospital-sub {
            opacity: 0.8;
            font-size: 13px;
            font-weight: 500;
            margin-top: 2px;
        }

        .rx-badge {
            font-size: 58px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.15);
            font-style: italic;
            line-height: 1;
            user-select: none;
        }

        /* Doctor Info Bar */
        .doctor-info-bar {
            background: #f0fdf4;
            padding: 18px 45px;
            border-bottom: 2px dashed #bbf7d0;
        }

        /* Body Section */
        .pres-body {
            padding: 40px 45px;
        }

        /* Patient Box */
        .patient-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .info-label {
            font-size: 11px;
            color: #64748b;
            text-uppercase: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 800;
            color: #0f172a;
            font-size: 15px;
        }

        /* Diagnosis Box */
        .diagnosis-box {
            background: #fffbeb;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 30px;
            border-left: 5px solid #f59e0b;
            border-top: 1px solid #fde68a;
            border-right: 1px solid #fde68a;
            border-bottom: 1px solid #fde68a;
        }

        .section-head {
            font-weight: 800;
            font-size: 15px;
            color: #0f172a;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.3px;
        }

        /* Medicine Table */
        .med-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .med-table th {
            background: #0f172a;
            color: white;
            padding: 14px 18px;
            font-size: 12px;
            font-weight: 700;
            text-uppercase: uppercase;
            letter-spacing: 0.5px;
        }

        .med-table td {
            padding: 16px 18px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .med-table tr:last-child td {
            border-bottom: none;
        }

        .med-num {
            background: var(--primary-color);
            color: white;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
        }

        /* Instructions & Follow-up */
        .instruction-box {
            background: #f0f9ff;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 30px;
            border-left: 5px solid #0284c7;
            border-top: 1px solid #bae6fd;
            border-right: 1px solid #bae6fd;
            border-bottom: 1px solid #bae6fd;
        }

        .followup-box {
            background: #f0fdf4;
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 30px;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #166534;
            font-weight: 700;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #f1f5f9;
        }

        .digital-stamp {
            border: 2px dashed #cbd5e1;
            padding: 12px 20px;
            border-radius: 12px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            background: #f8fafc;
        }

        .signature-box {
            text-align: right;
        }

        .signature-line {
            border-top: 2px solid #0f172a;
            width: 220px;
            margin-left: auto;
            margin-bottom: 8px;
        }

        /* Footer Section */
        .pres-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 20px 45px;
            text-align: center;
            font-size: 12px;
            font-weight: 500;
        }

        /* High Precision Print Stylesheet */
        @media print {
            .action-bar {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
            }
            .prescription-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
                max-width: 100% !important;
            }
            .pres-header {
                background: #0f172a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .doctor-info-bar {
                background: #f0fdf4 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<!-- Top Action Navigation Bar -->
<div class="action-bar">
    <button class="btn-action-custom btn-back" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Return
    </button>
    <button class="btn-action-custom btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Medical Prescription
    </button>
</div>

<!-- Main Printable Prescription Document -->
<div class="prescription-card">

    <!-- Clinical Letterhead Header -->
    <div class="pres-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="hospital-brand">
                <div class="icon-box">
                    <i class="fas fa-hospital-user"></i>
                </div>
                <div>
                    <h1 class="hospital-name">Medi<span style="color:#38bdf8;">Care</span> Hospital</h1>
                    <div class="hospital-sub">Online Hospital Management & Telemedicine Platform</div>
                    <div class="hospital-sub opacity-75">Government Degree College Hayatabad, Peshawar</div>
                </div>
            </div>
            <div class="rx-badge">Rx</div>
        </div>
    </div>

    <!-- Doctor Metadata Bar -->
    <div class="doctor-info-bar">
        <div class="row align-items-center">
            <div class="col-md-6">
                <strong class="text-dark fs-6">Dr. <?php echo htmlspecialchars($pres['doctor_name']); ?></strong>
                <span class="text-teal font-semibold ms-2" style="color: var(--primary-color);">
                    &bull; <?php echo !empty($pres['specialization']) ? htmlspecialchars($pres['specialization']) : 'Medical Practitioner'; ?>
                </span>
            </div>
            <div class="col-md-3">
                <span class="text-secondary fs-7 font-medium">
                    <i class="fas fa-graduation-cap me-1"></i><?php echo htmlspecialchars($pres['qualification']); ?>
                </span>
            </div>
            <div class="col-md-3 text-end">
                <span class="text-secondary fs-7 font-bold">
                    <i class="far fa-calendar-alt me-1"></i>Date: <?php echo date('d M Y', strtotime($pres['appointment_date'])); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Clinical Body -->
    <div class="pres-body">

        <!-- Patient Demographics Block -->
        <div class="patient-box">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-label">Patient Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($pres['patient_name']); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Phone Contact</div>
                    <div class="info-value"><?php echo htmlspecialchars($pres['patient_phone']); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Prescription Date</div>
                    <div class="info-value"><?php echo date('d M Y', strtotime($pres['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Clinical Diagnosis Block -->
        <div class="diagnosis-box">
            <div class="section-head">
                <i class="fas fa-stethoscope text-warning"></i>
                Clinical Diagnosis & Observations
            </div>
            <p class="m-0 text-dark font-medium fs-6" style="line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($pres['diagnosis'])); ?>
            </p>
        </div>

        <!-- Prescribed Medications Table -->
        <div class="section-head mb-3">
            <i class="fas fa-pills text-teal" style="color: var(--primary-color);"></i>
            Prescribed Medications & Dosage Instructions
        </div>

        <table class="med-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 35%;">Medicine Name</th>
                    <th style="width: 20%;">Dosage</th>
                    <th style="width: 22%;">Timing Routine</th>
                    <th style="width: 15%;">Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($medicines) && is_array($medicines)): ?>
                    <?php foreach ($medicines as $i => $med): ?>
                    <tr>
                        <td>
                            <span class="med-num"><?php echo $i + 1; ?></span>
                        </td>
                        <td>
                            <strong class="text-dark fs-6"><?php echo htmlspecialchars($med['name']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 font-semibold"><?php echo htmlspecialchars($med['dose']); ?></span>
                        </td>
                        <td>
                            <span class="fw-semibold text-secondary"><?php echo htmlspecialchars($med['timing']); ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-teal" style="color: var(--primary-color);"><?php echo htmlspecialchars($med['days']); ?> Days</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No specific medications prescribed.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Special Instructions & Advice -->
        <?php if (!empty($pres['instructions'])): ?>
        <div class="instruction-box">
            <div class="section-head">
                <i class="fas fa-notes-medical text-primary"></i>
                Special Instructions & Lifestyle Advice
            </div>
            <p class="m-0 text-dark fs-7" style="line-height: 1.7;">
                <?php echo nl2br(htmlspecialchars($pres['instructions'])); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Follow-up Appointment Slot -->
        <?php if (!empty($pres['follow_up_date']) && $pres['follow_up_date'] != '0000-00-00'): ?>
        <div class="followup-box">
            <i class="fas fa-calendar-check fa-lg"></i>
            <div>
                Follow-Up Visit Scheduled: <span class="text-dark font-extrabold"><?php echo date('d M Y', strtotime($pres['follow_up_date'])); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Digital Signature & Validation -->
        <div class="signature-section">
            <div class="digital-stamp">
                <i class="fas fa-check-circle text-success me-1"></i> Digitally Verified Rx<br>
                <small class="text-muted">MediCare Telemedicine Network</small>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="fw-bold text-dark">Dr. <?php echo htmlspecialchars($pres['doctor_name']); ?></div>
                <div class="text-muted fs-7">
                    <?php echo htmlspecialchars($pres['specialization']); ?> | <?php echo htmlspecialchars($pres['qualification']); ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Printable Document Footer -->
    <div class="pres-footer">
        <p class="m-0">
            This is an official digitally authenticated prescription generated by the MediCare Health Portal.<br>
            Department of Computer Science — Government Degree College Hayatabad, Peshawar.
        </p>
    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>