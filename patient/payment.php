<?php
session_start();
include '../db/connection.php';

// Access Control: Ensure only authenticated Patients can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if (!isset($_GET['id'])) {
    header("Location: my_appointments.php");
    exit();
}

$appointment_id = intval($_GET['id']);
$patient_id     = intval($_SESSION['user_id']);

// Fetch Appointment details securely
$apt_query = "SELECT a.*, u.fullname as doctor_name, d.specialization
              FROM appointments a
              JOIN users u ON a.doctor_id = u.id
              LEFT JOIN doctors d ON a.doctor_id = d.user_id
              WHERE a.id = $appointment_id AND a.patient_id = $patient_id AND a.status = 'confirmed'";

$apt_res = mysqli_query($conn, $apt_query);
$apt     = mysqli_fetch_assoc($apt_res);

if (!$apt) {
    echo "<script>alert('Invalid appointment record or unauthorized request!');
          window.location='my_appointments.php';</script>";
    exit();
}

// Do not allow another submission while a reference is awaiting verification.
$pending_res = mysqli_query($conn,
    "SELECT id FROM payments
     WHERE appointment_id = $appointment_id AND status = 'pending'
     LIMIT 1");
if ($pending_res && mysqli_num_rows($pending_res) > 0) {
    echo "<script>alert('A payment reference is already awaiting admin verification.'); window.location='my_appointments.php';</script>";
    exit();
}

// Check if payment is already completed
$paid_res = mysqli_query($conn, 
    "SELECT id FROM payments 
     WHERE appointment_id = $appointment_id AND status = 'completed'");

if (mysqli_num_rows($paid_res) > 0) {
    echo "<script>alert('Payment has already been completed for this appointment!');
          window.location='my_appointments.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout — MediCare Telemedicine</title>

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

        /* Summary Banner */
        .apt-summary {
            background: white;
            border-radius: 22px;
            padding: 28px 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            border-left: 6px solid #06b6d4;
        }

        .apt-summary h5 {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 18px;
            letter-spacing: -0.5px;
        }

        /* Payment Form Box */
        .payment-card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
        }

        .payment-card h5 {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 22px;
            letter-spacing: -0.5px;
        }

        .amount-badge {
            background: linear-gradient(135deg, #0f172a 0%, #06b6d4 100%);
            color: white;
            border-radius: 18px;
            padding: 22px 30px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.25);
        }

        .amount-badge h3 {
            margin: 0;
            font-weight: 800;
            font-size: 32px;
            letter-spacing: -1px;
        }

        .amount-badge p {
            margin: 0;
            opacity: 0.85;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Method Selector Cards */
        .method-card {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: white;
        }

        .method-card:hover, .method-card.selected {
            border-color: #06b6d4;
            background: #ecfeff;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.12);
        }

        .method-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            flex-shrink: 0;
        }

        .method-name {
            font-weight: 800;
            font-size: 15px;
            color: #0f172a;
        }

        .method-desc {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        /* Payment Forms */
        .payment-form {
            display: none;
            margin-top: 18px;
            margin-bottom: 20px;
        }

        .payment-form.active {
            display: block;
        }

        .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 12px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
        }

        .btn-pay {
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-pay-jazzcash { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25); }
        .btn-pay-easypaisa { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25); }
        .btn-pay-card { background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%); box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25); }
        .btn-pay-bank { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25); }

        .btn-pay:hover {
            transform: translateY(-2px);
            opacity: 0.95;
            color: white;
        }

        .card-input { letter-spacing: 2px; font-family: monospace; font-size: 16px; }

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
            <h4>Consultation Fee Payment</h4>
            <p>Complete your payment securely to finalize your appointment</p>
        </div>
        <div class="user-pill">
            <div class="avatar-circle"><i class="fas fa-user"></i></div>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Appointment Summary -->
    <div class="apt-summary">
        <h5><i class="fas fa-calendar-check text-cyan me-2"></i> Appointment Details</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <p class="mb-1 text-muted fs-7 font-semibold">Attending Doctor</p>
                <p class="fw-bold mb-0 text-dark">Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1 text-muted fs-7 font-semibold">Specialization</p>
                <p class="fw-bold mb-0 text-cyan"><?php echo htmlspecialchars($apt['specialization'] ?? 'Specialist'); ?></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1 text-muted fs-7 font-semibold">Schedule Date & Time</p>
                <p class="fw-bold mb-0 text-dark">
                    <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?> 
                    <span class="text-muted fw-normal">@</span> 
                    <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                </p>
            </div>
            <div class="col-md-3">
                <p class="mb-1 text-muted fs-7 font-semibold">Visit Type</p>
                <p class="fw-bold mb-0 text-dark">
                    <?php echo $apt['type'] === 'video-call' ? '🎥 Encrypted Video Call' : '🏥 In-Person Physical Visit'; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="payment-card">
                <h5><i class="fas fa-wallet text-cyan me-2"></i> Select Payment Method</h5>

                <!-- Amount Badge -->
                <div class="amount-badge">
                    <p>Total Consultation Payable Fee</p>
                    <h3>Rs. 500</h3>
                </div>

                <!-- JazzCash Card -->
                <div class="method-card" id="card_jazzcash" onclick="selectMethod('jazzcash')">
                    <div class="method-icon" style="background:#ef4444">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div>
                        <div class="method-name">JazzCash</div>
                        <div class="method-desc">Pay via JazzCash Mobile Wallet Account</div>
                    </div>
                    <div class="ms-auto text-muted">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <!-- Manual payment instructions: never collect wallet PINs or card security details. -->
                <div class="alert alert-info">
                    <strong>Manual verification:</strong> Transfer Rs. 500 using your selected method, then enter the transaction/reference ID. Your payment remains pending until an administrator verifies it.
                </div>

                <form method="POST" action="payment_process.php" id="manualPaymentForm">
                    <input type="hidden" name="appointment_id" value="<?php echo (int) $appointment_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label class="form-label" for="payment_method">Payment Method</label>
                        <select class="form-control" name="payment_method" id="payment_method" required>
                            <option value="">Select a method</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">Easypaisa</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="alert alert-light border">
                        <p class="mb-1"><strong>JazzCash / Easypaisa:</strong> Use the official account details provided by the administrator.</p>
                        <p class="mb-0"><strong>Bank transfer:</strong> Use the verified bank account details supplied by the administrator.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="transaction_reference">Transaction / Reference ID</label>
                        <input type="text" class="form-control" id="transaction_reference" name="transaction_reference"
                               minlength="5" maxlength="100" required autocomplete="off"
                               placeholder="Enter the reference shown by your payment provider">
                    </div>
                    <button type="submit" class="btn-pay btn-pay-bank">
                        <i class="fas fa-paper-plane me-1"></i> Submit for Admin Verification
                    </button>
                </form>

        <!-- Right Side Checkout Summary -->
        <div class="col-lg-4">
            <div class="bg-white rounded-4 p-4 border shadow-sm">
                <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-shield-alt text-success me-2"></i> End-to-End Encrypted</h6>
                <p class="text-muted fs-7 mb-3">
                    Your transactions are protected using industry-standard SSL encryption technology.
                </p>
                <hr>
                <h6 class="fw-bold mb-3 text-dark">Payment Breakdown</h6>
                <div class="d-flex justify-content-between mb-2 fs-7 text-muted">
                    <span>Doctor Consultation Fee</span>
                    <span class="fw-bold text-dark">Rs. 500</span>
                </div>
                <div class="d-flex justify-content-between mb-2 fs-7 text-muted">
                    <span>Processing Gateway Fee</span>
                    <span class="fw-bold text-success">Rs. 0 (Free)</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark fs-6">Total Amount</span>
                    <span class="fw-bold text-cyan fs-5">Rs. 500</span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
            <div class="spinner-border text-cyan my-3" style="width: 50px; height: 50px;" role="status"></div>
            <h5 class="fw-bold text-dark">Processing Payment...</h5>
            <p class="text-muted fs-7 mb-0">Please wait while we verify your transaction credentials safely.</p>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('manualPaymentForm').addEventListener('submit', function (event) {
    const method = document.getElementById('payment_method').value;
    const reference = document.getElementById('transaction_reference').value.trim();
    if (!['jazzcash', 'easypaisa', 'bank'].includes(method) || reference.length < 5) {
        event.preventDefault();
        alert('Please select a valid payment method and enter a valid transaction reference.');
    }
});
</script>

</body>
</html>