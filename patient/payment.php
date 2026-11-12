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

$appointment_id = intval($_GET['id']);
$patient_id     = intval($_SESSION['user_id']);

// Fetch Appointment details securely
$apt_query = "SELECT a.*, u.fullname as doctor_name, d.specialization
              FROM appointments a
              JOIN users u ON a.doctor_id = u.id
              LEFT JOIN doctors d ON a.doctor_id = d.user_id
              WHERE a.id = $appointment_id AND a.patient_id = $patient_id";

$apt_res = mysqli_query($conn, $apt_query);
$apt     = mysqli_fetch_assoc($apt_res);

if (!$apt) {
    echo "<script>alert('Invalid appointment record or unauthorized request!');
          window.location='my_appointments.php';</script>";
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

                <!-- JazzCash Form -->
                <div class="payment-form" id="form_jazzcash">
                    <div class="p-3 border rounded-3 bg-light">
                        <label class="form-label">JazzCash Mobile Number</label>
                        <input type="text" class="form-control mb-3" placeholder="03XX-XXXXXXX" maxlength="11" id="jazzcash_number">

                        <label class="form-label">Account MPIN</label>
                        <input type="password" class="form-control mb-3" placeholder="Enter 4-digit MPIN" maxlength="4" id="jazzcash_mpin">

                        <button class="btn-pay btn-pay-jazzcash" onclick="processPayment('jazzcash')">
                            <i class="fas fa-lock me-1"></i> Pay Rs. 500 via JazzCash
                        </button>
                    </div>
                </div>

                <!-- Easypaisa Card -->
                <div class="method-card" id="card_easypaisa" onclick="selectMethod('easypaisa')">
                    <div class="method-icon" style="background:#10b981">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <div class="method-name">Easypaisa</div>
                        <div class="method-desc">Pay via Easypaisa Mobile Wallet Account</div>
                    </div>
                    <div class="ms-auto text-muted">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <!-- Easypaisa Form -->
                <div class="payment-form" id="form_easypaisa">
                    <div class="p-3 border rounded-3 bg-light">
                        <label class="form-label">Easypaisa Mobile Number</label>
                        <input type="text" class="form-control mb-3" placeholder="03XX-XXXXXXX" maxlength="11" id="easypaisa_number">

                        <label class="form-label">Account PIN</label>
                        <input type="password" class="form-control mb-3" placeholder="Enter 4-digit PIN" maxlength="4" id="easypaisa_pin">

                        <button class="btn-pay btn-pay-easypaisa" onclick="processPayment('easypaisa')">
                            <i class="fas fa-lock me-1"></i> Pay Rs. 500 via Easypaisa
                        </button>
                    </div>
                </div>

                <!-- Card Payment Card -->
                <div class="method-card" id="card_card" onclick="selectMethod('card')">
                    <div class="method-icon" style="background:#2563eb">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <div class="method-name">Debit / Credit Card</div>
                        <div class="method-desc">Visa, Mastercard, UnionPay, PayPak</div>
                    </div>
                    <div class="ms-auto text-muted">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <!-- Card Form -->
                <div class="payment-form" id="form_card">
                    <div class="p-3 border rounded-3 bg-light">
                        <label class="form-label">16-Digit Card Number</label>
                        <input type="text" class="form-control card-input mb-3" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" id="card_number" oninput="formatCard(this)">

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" placeholder="MM/YY" maxlength="5" id="card_expiry">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV Code</label>
                                <input type="password" class="form-control" placeholder="3 Digits" maxlength="3" id="card_cvv">
                            </div>
                        </div>

                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control mb-3" placeholder="Name on card" id="card_name">

                        <button class="btn-pay btn-pay-card" onclick="processPayment('card')">
                            <i class="fas fa-lock me-1"></i> Pay Rs. 500 via Card
                        </button>
                    </div>
                </div>

                <!-- Bank Transfer Card -->
                <div class="method-card" id="card_bank" onclick="selectMethod('bank')">
                    <div class="method-icon" style="background:#6366f1">
                        <i class="fas fa-university"></i>
                    </div>
                    <div>
                        <div class="method-name">Direct Bank Transfer</div>
                        <div class="method-desc">HBL, Allied Bank, Meezan Bank, UBL</div>
                    </div>
                    <div class="ms-auto text-muted">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <!-- Bank Form -->
                <div class="payment-form" id="form_bank">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="bg-white p-3 rounded-3 border mb-3 fs-7">
                            <p class="mb-1"><strong>Bank Name:</strong> HBL (Habib Bank Limited)</p>
                            <p class="mb-1"><strong>Account Title:</strong> MediCare Health Services</p>
                            <p class="mb-1"><strong>Account IBAN:</strong> PK36HABB0001234567890102</p>
                            <p class="mb-0"><strong>Branch:</strong> Hayatabad, Peshawar</p>
                        </div>

                        <label class="form-label">Bank Transaction Reference / TRX ID</label>
                        <input type="text" class="form-control mb-3" placeholder="Enter bank transaction reference" id="bank_ref">

                        <button class="btn-pay btn-pay-bank" onclick="processPayment('bank')">
                            <i class="fas fa-check-circle me-1"></i> Confirm Bank Transfer
                        </button>
                    </div>
                </div>

            </div>
        </div>

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
let selectedMethod = '';

function selectMethod(method) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.payment-form').forEach(f => f.classList.remove('active'));
    
    document.getElementById('card_' + method).classList.add('selected');
    document.getElementById('form_' + method).classList.add('active');
    selectedMethod = method;
}

function formatCard(input) {
    let value = input.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let matches = value.match(/\d{4,16}/g);
    let match = matches && matches[0] || '';
    let parts = [];
    for (let i = 0, len = match.length; i < len; i += 4) {
        parts.push(match.substring(i, i + 4));
    }
    input.value = parts.length ? parts.join(' ') : value;
}

function validatePakistaniNumber(num) {
    let clean = num.replace(/[-\s]/g, '');
    return /^03[0-9]{9}$/.test(clean);
}

function processPayment(method) {
    if (method === 'jazzcash') {
        let num = document.getElementById('jazzcash_number').value.trim();
        let mpin = document.getElementById('jazzcash_mpin').value.trim();

        if (!num || !mpin) {
            alert('Please fill in all JazzCash payment details!');
            return;
        }
        if (!validatePakistaniNumber(num)) {
            alert('Invalid JazzCash number format!\nExample: 03001234567');
            return;
        }
        if (mpin.length !== 4 || !/^\d{4}$/.test(mpin)) {
            alert('MPIN must be exactly 4 numeric digits!');
            return;
        }

    } else if (method === 'easypaisa') {
        let num = document.getElementById('easypaisa_number').value.trim();
        let pin = document.getElementById('easypaisa_pin').value.trim();

        if (!num || !pin) {
            alert('Please fill in all Easypaisa payment details!');
            return;
        }
        if (!validatePakistaniNumber(num)) {
            alert('Invalid Easypaisa number format!\nExample: 03001234567');
            return;
        }
        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            alert('PIN must be exactly 4 numeric digits!');
            return;
        }

    } else if (method === 'card') {
        let cardNum = document.getElementById('card_number').value.replace(/\s/g,'').trim();
        let expiry  = document.getElementById('card_expiry').value.trim();
        let cvv     = document.getElementById('card_cvv').value.trim();
        let name    = document.getElementById('card_name').value.trim();

        if (!cardNum || !expiry || !cvv || !name) {
            alert('Please complete all card payment fields!');
            return;
        }
        if (cardNum.length !== 16 || !/^\d{16}$/.test(cardNum)) {
            alert('Card number must be 16 digits!');
            return;
        }
        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            alert('Invalid expiry date format! Use MM/YY');
            return;
        }
        if (cvv.length !== 3 || !/^\d{3}$/.test(cvv)) {
            alert('CVV code must be exactly 3 digits!');
            return;
        }

    } else if (method === 'bank') {
        let ref = document.getElementById('bank_ref').value.trim();
        if (!ref || ref.length < 5) {
            alert('Please enter a valid bank transaction reference ID!');
            return;
        }
    } else {
        alert('Please select a payment method first!');
        return;
    }

    // Show processing modal animation
    var modal = new bootstrap.Modal(document.getElementById('processingModal'));
    modal.show();

    // Simulating secure gateway authorization delay (2.5 seconds)
    setTimeout(function() {
        window.location.href = 'payment_process.php?id=<?php echo $appointment_id; ?>&method=' + method;
    }, 2500);
}
</script>

</body>
</html>