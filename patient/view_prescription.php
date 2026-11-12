<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only authenticated Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my_appointments.php");
    exit();
}

$pres_id    = intval($_GET['id']);
$patient_id = intval($_SESSION['user_id']);

// Fetch Prescription and related doctor/patient details securely
$pres_query = "SELECT p.*,
                      doc.fullname as doctor_name,
                      pat.fullname as patient_name,
                      pat.phone as patient_phone,
                      d.specialization, d.qualification,
                      a.appointment_date
               FROM prescriptions p
               JOIN users doc ON p.doctor_id = doc.id
               JOIN users pat ON p.patient_id = pat.id
               LEFT JOIN doctors d ON p.doctor_id = d.user_id
               JOIN appointments a ON p.appointment_id = a.id
               WHERE p.id = $pres_id AND p.patient_id = $patient_id";

$pres_res = mysqli_query($conn, $pres_query);
$pres     = mysqli_fetch_assoc($pres_res);

if (!$pres) {
    echo "<script>
            alert('Prescription record not found or unauthorized access!');
            window.location='my_appointments.php';
          </script>";
    exit();
}

$medicines = json_decode($pres['medicines'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Prescription #<?php echo $pres_id; ?> — MediCare Telemedicine</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0b0f19;
            --card-bg: #ffffff;
            --accent-cyan: #06b6d4;
            --accent-indigo: #6366f1;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
            --text-main: #1e293b;
            --light-bg: #f3f4f6;
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--light-bg);
            color: var(--text-main);
            padding: 40px 15px;
        }

        .action-bar {
            max-width: 840px;
            margin: 0 auto 22px;
            display: flex;
            gap: 12px;
        }

        .prescription {
            background: white;
            max-width: 840px;
            margin: 0 auto;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .pres-header {
            background: linear-gradient(135deg, #0f172a 0%, #06b6d4 100%);
            color: white;
            padding: 38px 42px;
            position: relative;
        }

        .hospital-name {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .hospital-sub {
            opacity: 0.88;
            font-size: 13px;
            font-weight: 500;
        }

        .rx-symbol {
            font-size: 58px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.2);
            font-style: italic;
            line-height: 1;
            user-select: none;
        }

        .doctor-info {
            background: #ecfeff;
            padding: 20px 42px;
            border-bottom: 2px dashed #a5f3fc;
        }

        .pres-body {
            padding: 38px 42px;
        }

        .patient-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 26px;
            border: 1px solid #e2e8f0;
        }

        .info-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
        }

        .diagnosis-box {
            background: #fffbeb;
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 26px;
            border: 1px solid #fde68a;
            border-left: 5px solid #f59e0b;
        }

        .section-head {
            font-weight: 800;
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.3px;
        }

        .med-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .med-table th {
            background: #0f172a;
            color: white;
            padding: 15px 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .med-table td {
            padding: 15px 20px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 500;
        }

        .med-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .med-table tr:last-child td {
            border-bottom: none;
        }

        .med-num {
            background: #06b6d4;
            color: white;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .instruction-box {
            background: #f0f9ff;
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 26px;
            border: 1px solid #bae6fd;
            border-left: 5px solid #0284c7;
        }

        .followup-box {
            background: #f0fdf4;
            border-radius: 16px;
            padding: 20px 26px;
            margin-bottom: 26px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .signature-box {
            text-align: right;
            margin-top: 42px;
            padding-top: 25px;
            border-top: 2px dashed #e2e8f0;
        }

        .signature-line {
            border-top: 2px solid #0f172a;
            width: 220px;
            margin-left: auto;
            margin-bottom: 8px;
        }

        .pres-footer {
            background: #f8fafc;
            padding: 22px 42px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .btn-print {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 13px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.35);
            color: white;
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 13px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        @media print {
            .action-bar { display: none; }
            body { background: white; padding: 0; }
            .prescription { box-shadow: none; border-radius: 0; border: none; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <button class="btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Official Prescription
    </button>
    <button class="btn-back" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Back to Appointments
    </button>
</div>

<div class="prescription">

    <!-- Header -->
    <div class="pres-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="hospital-name">
                    <i class="fas fa-hospital-alt me-2"></i> MediCare Hospital
                </div>
                <div class="hospital-sub mt-1">
                    Online Hospital Appointment Management System &bull; Telemedicine Portal
                </div>
                <div class="hospital-sub mt-1 opacity-75">
                    Government Degree College Hayatabad, Peshawar
                </div>
            </div>
            <div class="rx-symbol">Rx</div>
        </div>
    </div>

    <!-- Doctor Info Bar -->
    <div class="doctor-info">
        <div class="row align-items-center">
            <div class="col-md-5">
                <strong class="text-dark fs-6">Dr. <?php echo htmlspecialchars($pres['doctor_name']); ?></strong>
                <span class="text-cyan fw-semibold fs-7 d-block">
                    <?php echo htmlspecialchars($pres['specialization'] ?? 'Specialist'); ?>
                </span>
            </div>
            <div class="col-md-4">
                <span class="text-muted fs-7">
                    <i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($pres['qualification'] ?? 'MBBS / Specialist'); ?>
                </span>
            </div>
            <div class="col-md-3 text-md-end">
                <span class="text-muted fs-7">
                    <i class="fas fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($pres['appointment_date'])); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Body Content -->
    <div class="pres-body">

        <!-- Patient Details Box -->
        <div class="patient-box">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-label">Patient Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($pres['patient_name']); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($pres['patient_phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Prescription Date</div>
                    <div class="info-value"><?php echo date('d M Y — h:i A', strtotime($pres['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Diagnosis Box -->
        <div class="diagnosis-box">
            <div class="section-head text-warning-emphasis">
                <i class="fas fa-stethoscope text-warning"></i> Clinical Diagnosis
            </div>
            <p class="mb-0 text-dark fw-medium fs-6">
                <?php echo nl2br(htmlspecialchars($pres['diagnosis'])); ?>
            </p>
        </div>

        <!-- Medicines Table -->
        <div class="section-head">
            <i class="fas fa-pills text-emerald"></i> Prescribed Medications
        </div>

        <table class="med-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Medicine Name & Formulation</th>
                    <th>Dosage</th>
                    <th>Timing</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($medicines && is_array($medicines)): ?>
                    <?php foreach ($medicines as $i => $med): ?>
                        <tr>
                            <td><span class="med-num"><?php echo $i + 1; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($med['dose']); ?></td>
                            <td><?php echo htmlspecialchars($med['timing']); ?></td>
                            <td><?php echo htmlspecialchars($med['days']); ?> days</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No medications recorded.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Instructions & Advice -->
        <?php if (!empty($pres['instructions'])): ?>
            <div class="instruction-box">
                <div class="section-head text-primary">
                    <i class="fas fa-notes-medical me-1"></i> Doctor Instructions & Medical Advice
                </div>
                <p class="mb-0 text-dark" style="line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($pres['instructions'])); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Follow-up Date -->
        <?php if (!empty($pres['follow_up_date'])): ?>
            <div class="followup-box">
                <i class="fas fa-calendar-check fa-lg"></i>
                <div>
                    <strong class="d-block">Scheduled Follow-Up Appointment:</strong>
                    <span>Please visit or consult again on <strong><?php echo date('d M Y', strtotime($pres['follow_up_date'])); ?></strong>.</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Doctor Signature -->
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="fw-bold text-dark">Dr. <?php echo htmlspecialchars($pres['doctor_name']); ?></div>
            <div class="text-muted fs-7">
                <?php echo htmlspecialchars($pres['specialization'] ?? 'Specialist'); ?> &bull; <?php echo htmlspecialchars($pres['qualification'] ?? 'MBBS'); ?>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="pres-footer">
        <p class="mb-0">
            Official Electronically Generated Medical Prescription &bull; MediCare Hospital System &bull; Government Degree College Hayatabad, Peshawar
        </p>
    </div>

</div>

</body>
</html>