<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only authenticated Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['txn']) || !isset($_GET['apt'])) {
    header("Location: my_appointments.php");
    exit();
}

$txn_id         = mysqli_real_escape_string($conn, trim($_GET['txn']));
$appointment_id = intval($_GET['apt']);
$patient_id     = intval($_SESSION['user_id']);

// Fetch Payment and Appointment details securely
$query = "SELECT p.*, a.appointment_date, a.appointment_time, a.type,
                 u.fullname as doctor_name,
                 pat.fullname as patient_name,
                 d.specialization
          FROM payments p
          JOIN appointments a ON p.appointment_id = a.id
          JOIN users u ON a.doctor_id = u.id
          JOIN users pat ON p.patient_id = pat.id
          LEFT JOIN doctors d ON a.doctor_id = d.user_id
          WHERE p.transaction_id = '$txn_id' AND p.appointment_id = $appointment_id AND p.patient_id = $patient_id";

$result  = mysqli_query($conn, $query);
$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    echo "<script>
            alert('Invalid receipt record or unauthorized request!');
            window.location='my_appointments.php';
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Payment Receipt #<?php echo htmlspecialchars($payment['transaction_id']); ?> — MediCare</title>

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
            --text-main: #1e293b;
            --light-bg: #f3f4f6;
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--light-bg);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 30px 15px;
        }

        .receipt-card {
            background: white;
            border-radius: 28px;
            max-width: 620px;
            width: 100%;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .receipt-header {
            background: linear-gradient(135deg, #0f172a 0%, #06b6d4 100%);
            padding: 38px 30px;
            text-align: center;
            color: white;
            position: relative;
        }

        .receipt-header .check-icon {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: #38bdf8;
            margin: 0 auto 16px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.3);
        }

        .receipt-header h3 {
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
            font-size: 24px;
        }

        .receipt-header p {
            opacity: 0.9;
            margin: 6px 0 0;
            font-size: 14px;
            font-weight: 500;
        }

        .receipt-body {
            padding: 35px;
        }

        .txn-box {
            background: #ecfeff;
            border-radius: 16px;
            padding: 18px 20px;
            text-align: center;
            margin-bottom: 25px;
            border: 2px dashed #a5f3fc;
        }

        .txn-box p {
            margin: 0;
            color: #0891b2;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .txn-box h5 {
            margin: 4px 0 0;
            color: #0e7490;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: 0.5px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .receipt-row:last-of-type {
            border-bottom: none;
        }

        .receipt-row .label {
            color: #64748b;
            font-weight: 600;
        }

        .receipt-row .value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .total-row {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px 24px;
            margin: 22px 0 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
        }

        .total-row .label {
            font-weight: 800;
            font-size: 16px;
            color: #0f172a;
        }

        .total-row .value {
            font-weight: 800;
            font-size: 26px;
            color: #0891b2;
            letter-spacing: -0.5px;
        }

        .status-badge {
            background: #dcfce7;
            color: #15803d;
            padding: 6px 18px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 12px;
            display: inline-block;
            border: 1px solid #bbf7d0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.35);
            color: white;
        }

        .btn-print {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .receipt-footer {
            background: #f8fafc;
            padding: 22px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .receipt-footer p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        /* Print Styling */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-card {
                box-shadow: none;
                border: none;
                max-width: 100%;
            }
            .btn-dashboard, .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="receipt-card">

    <!-- Header -->
    <div class="receipt-header">
        <div class="check-icon">
            <i class="fas fa-check"></i>
        </div>
        <h3>Payment Successful!</h3>
        <p>Your consultation fee transaction has been securely processed.</p>
    </div>

    <!-- Body -->
    <div class="receipt-body">

        <!-- Transaction ID Box -->
        <div class="txn-box">
            <p>Secure Transaction Reference ID</p>
            <h5><?php echo htmlspecialchars($payment['transaction_id']); ?></h5>
        </div>

        <!-- Status -->
        <div class="text-center mb-3">
            <span class="status-badge">
                <i class="fas fa-check-circle me-1"></i> Verified & Paid
            </span>
        </div>

        <!-- Details Rows -->
        <div class="receipt-row">
            <span class="label">Patient Name</span>
            <span class="value"><?php echo htmlspecialchars($payment['patient_name']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="label">Attending Doctor</span>
            <span class="value">Dr. <?php echo htmlspecialchars($payment['doctor_name']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="label">Specialization</span>
            <span class="value"><?php echo htmlspecialchars($payment['specialization'] ?? 'Specialist'); ?></span>
        </div>
        <div class="receipt-row">
            <span class="label">Appointment Date</span>
            <span class="value"><?php echo date('d M Y', strtotime($payment['appointment_date'])); ?></span>
        </div>
        <div class="receipt-row">
            <span class="label">Appointment Time Slot</span>
            <span class="value"><?php echo date('h:i A', strtotime($payment['appointment_time'])); ?></span>
        </div>
        <div class="receipt-row">
            <span class="label">Payment Method</span>
            <span class="value"><?php echo ucfirst(htmlspecialchars($payment['payment_method'])); ?></span>
        </div>
        <div class="receipt-row">
            <span class="label">Transaction Timestamp</span>
            <span class="value"><?php echo date('d M Y — h:i A', strtotime($payment['created_at'])); ?></span>
        </div>

        <!-- Total Row -->
        <div class="total-row">
            <span class="label">Total Paid Amount</span>
            <span class="value">Rs. <?php echo number_format($payment['amount'], 0); ?></span>
        </div>

        <!-- Action Buttons -->
        <a href="my_appointments.php" class="btn-dashboard">
            <i class="fas fa-arrow-left me-2"></i> Return to My Appointments
        </a>
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print Official Receipt
        </button>

    </div>

    <!-- Footer -->
    <div class="receipt-footer">
        <p class="fw-bold text-dark mb-1">MediCare Telemedicine & Hospital Management System</p>
        <p>Government Degree College Hayatabad, Peshawar &bull; Electronically Generated Receipt</p>
    </div>

</div>

</body>
</html>