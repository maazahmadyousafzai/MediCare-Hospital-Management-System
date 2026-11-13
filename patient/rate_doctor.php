<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'patient') {
    header('Location: ../login.php');
    exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$patientId = (int) $_SESSION['user_id'];
$appointmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$appointmentId || $appointmentId < 1) {
    header('Location: my_appointments.php');
    exit();
}

$appointmentSql = "SELECT a.id, a.doctor_id, a.appointment_date, a.appointment_time,
                          u.fullname AS doctor_name, d.specialization
                   FROM appointments a
                   INNER JOIN users u ON u.id = a.doctor_id AND u.role = 'doctor'
                   LEFT JOIN doctors d ON d.user_id = a.doctor_id
                   WHERE a.id = ? AND a.patient_id = ? AND a.status = 'completed'
                   LIMIT 1";
$appointmentStmt = mysqli_prepare($conn, $appointmentSql);
if (!$appointmentStmt) {
    error_log('MediCare rating lookup preparation failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Unable to load this appointment.');
}
mysqli_stmt_bind_param($appointmentStmt, 'ii', $appointmentId, $patientId);
mysqli_stmt_execute($appointmentStmt);
$appointmentResult = mysqli_stmt_get_result($appointmentStmt);
$apt = $appointmentResult ? mysqli_fetch_assoc($appointmentResult) : null;
mysqli_stmt_close($appointmentStmt);

if (!$apt) {
    header('Location: my_appointments.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $review = trim((string) ($_POST['review'] ?? ''));
    if ($rating === false || $rating === null || $rating < 1 || $rating > 5) {
        http_response_code(400);
        exit('Please select a rating between 1 and 5.');
    }
    if (strlen($review) > 2000) {
        http_response_code(400);
        exit('Your review must be 2,000 characters or fewer.');
    }

    // Lock the appointment row so simultaneous submissions cannot create duplicate ratings.
    mysqli_begin_transaction($conn);
    try {
        $lockStmt = mysqli_prepare(
            $conn,
            "SELECT id FROM appointments
             WHERE id = ? AND patient_id = ? AND status = 'completed'
             FOR UPDATE"
        );
        if (!$lockStmt) {
            throw new RuntimeException('Unable to validate appointment.');
        }
        mysqli_stmt_bind_param($lockStmt, 'ii', $appointmentId, $patientId);
        mysqli_stmt_execute($lockStmt);
        $lockResult = mysqli_stmt_get_result($lockStmt);
        $lockedAppointment = $lockResult ? mysqli_fetch_assoc($lockResult) : null;
        mysqli_stmt_close($lockStmt);
        if (!$lockedAppointment) {
            throw new RuntimeException('This appointment is no longer eligible for a rating.');
        }

        $existingStmt = mysqli_prepare(
            $conn,
            'SELECT id FROM ratings WHERE appointment_id = ? AND patient_id = ? LIMIT 1'
        );
        if (!$existingStmt) {
            throw new RuntimeException('Unable to check existing feedback.');
        }
        mysqli_stmt_bind_param($existingStmt, 'ii', $appointmentId, $patientId);
        mysqli_stmt_execute($existingStmt);
        $existingResult = mysqli_stmt_get_result($existingStmt);
        $alreadyRated = $existingResult && mysqli_fetch_assoc($existingResult);
        mysqli_stmt_close($existingStmt);
        if ($alreadyRated) {
            throw new RuntimeException('You have already rated this appointment.');
        }

        $insertStmt = mysqli_prepare(
            $conn,
            'INSERT INTO ratings (doctor_id, patient_id, appointment_id, rating, review)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$insertStmt) {
            throw new RuntimeException('Unable to prepare your feedback.');
        }
        $doctorId = (int) $apt['doctor_id'];
        mysqli_stmt_bind_param($insertStmt, 'iiiis', $doctorId, $patientId, $appointmentId, $rating, $review);
        if (!mysqli_stmt_execute($insertStmt)) {
            mysqli_stmt_close($insertStmt);
            throw new RuntimeException('Unable to save your feedback.');
        }
        mysqli_stmt_close($insertStmt);
        mysqli_commit($conn);
        header('Location: my_appointments.php?rating=success');
        exit();
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('MediCare rating submission failed: ' . $e->getMessage());
        http_response_code(400);
        exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}

$alreadyStmt = mysqli_prepare(
    $conn,
    'SELECT id FROM ratings WHERE appointment_id = ? AND patient_id = ? LIMIT 1'
);
if (!$alreadyStmt) {
    error_log('MediCare existing-rating check failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Unable to check existing feedback.');
}
mysqli_stmt_bind_param($alreadyStmt, 'ii', $appointmentId, $patientId);
mysqli_stmt_execute($alreadyStmt);
$alreadyResult = mysqli_stmt_get_result($alreadyStmt);
$alreadyRated = $alreadyResult && mysqli_fetch_assoc($alreadyResult);
mysqli_stmt_close($alreadyStmt);
if ($alreadyRated) {
    header('Location: my_appointments.php?rating=already-submitted');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Doctor — MediCare Telemedicine</title>

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
            --accent-amber: #f59e0b;
            --text-main: #1e293b;
            --light-bg: #f3f4f6;
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--light-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }

        .rating-card {
            background: white;
            border-radius: 28px;
            max-width: 620px;
            width: 100%;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.07);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        /* Header Styling */
        .card-header-custom {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 38px 30px;
            text-align: center;
            color: white;
            position: relative;
        }

        .doctor-avatar {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 3px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            color: #38bdf8;
            margin: 0 auto 16px;
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.25);
        }

        .card-header-custom h4 {
            font-weight: 800;
            margin: 0;
            font-size: 23px;
            letter-spacing: -0.5px;
        }

        .card-header-custom p {
            margin: 4px 0 0;
            opacity: 0.85;
            font-size: 14px;
            font-weight: 500;
            color: #94a3b8;
        }

        .apt-badge {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 6px 18px;
            font-size: 13px;
            display: inline-block;
            margin-top: 14px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #e2e8f0;
        }

        /* Body Styling */
        .card-body-custom {
            padding: 35px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .section-desc {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 25px;
            line-height: 1.6;
            font-weight: 500;
        }

        /* Star Rating */
        .star-section {
            background: #f8fafc;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .star-label {
            font-weight: 700;
            color: #475569;
            font-size: 13px;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stars {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-direction: row-reverse;
        }

        .stars input {
            display: none;
        }

        .stars label {
            font-size: 48px;
            color: #cbd5e1;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: #f59e0b;
            transform: scale(1.15);
            text-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        .rating-desc {
            margin-top: 14px;
            font-size: 15px;
            font-weight: 700;
            min-height: 24px;
            color: #64748b;
        }

        /* Rating Pills */
        .rating-pills {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .rating-pill {
            background: white;
            border-radius: 50px;
            padding: 5px 14px;
            font-size: 12px;
            color: #475569;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
            font-weight: 600;
        }

        /* Review Section */
        .review-label {
            font-weight: 700;
            color: #0f172a;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .review-sublabel {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .form-control {
            border-radius: 14px;
            padding: 14px 16px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            transition: all 0.3s ease;
            resize: none;
            color: #0f172a;
            font-weight: 500;
        }

        .form-control:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
        }

        /* Quick Tags */
        .quick-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .quick-tag {
            background: #ecfeff;
            border: 1px solid #cffaff;
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 12px;
            color: #0891b2;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 700;
        }

        .quick-tag:hover {
            background: #06b6d4;
            color: white;
            border-color: #06b6d4;
        }

        /* Buttons */
        .btn-submit {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(6, 182, 212, 0.35);
            color: white;
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 25px 0;
        }
    </style>
</head>
<body>

<div class="rating-card">

    <!-- Header -->
    <div class="card-header-custom">
        <div class="doctor-avatar">
            <i class="fas fa-user-md"></i>
        </div>
        <h4>Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></h4>
        <p><?php echo htmlspecialchars($apt['specialization'] ?? 'Specialist'); ?></p>
        <div class="apt-badge">
            <i class="fas fa-calendar-alt me-1"></i>
            <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?> @ <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body-custom">

        <div class="section-title">How was your experience?</div>
        <div class="section-desc">
            Your honest feedback helps us maintain top-tier healthcare standards and assists fellow patients in making informed decisions.
        </div>

        <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Star Rating Section -->
            <div class="star-section">
                <div class="star-label">
                    <i class="fas fa-star text-warning me-1"></i> Tap to Rate Consultation
                </div>

                <div class="stars">
                    <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
                    <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                    <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                    <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                    <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
                </div>

                <div class="rating-desc" id="ratingDesc">
                    Select a star rating above
                </div>

                <div class="rating-pills">
                    <span class="rating-pill">😞 Poor</span>
                    <span class="rating-pill">😐 Fair</span>
                    <span class="rating-pill">🙂 Good</span>
                    <span class="rating-pill">😊 Very Good</span>
                    <span class="rating-pill">🌟 Excellent</span>
                </div>
            </div>

            <hr class="divider">

            <!-- Review Section -->
            <div class="mb-4">
                <div class="review-label">
                    <i class="fas fa-comment-dots text-cyan me-1"></i> Written Review & Comments
                </div>
                <div class="review-sublabel">
                    Share details about the doctor's attentiveness, diagnosis clarity, and friendliness.
                </div>

                <!-- Quick Tags -->
                <div class="quick-tags">
                    <span class="quick-tag" onclick="addTag('Very professional and polite')"><i class="fas fa-check me-1"></i> Very Professional</span>
                    <span class="quick-tag" onclick="addTag('Explained the medical condition clearly')"><i class="fas fa-check me-1"></i> Clear Explanation</span>
                    <span class="quick-tag" onclick="addTag('Highly recommended doctor')"><i class="fas fa-check me-1"></i> Highly Recommended</span>
                    <span class="quick-tag" onclick="addTag('Consultation started right on time')"><i class="fas fa-check me-1"></i> On Time</span>
                </div>

                <textarea class="form-control" name="review" id="reviewText" rows="4" placeholder="Write your feedback here... e.g., The doctor was very patient, listened carefully, and gave accurate medical advice."></textarea>
            </div>

            <!-- Submit Buttons -->
            <button type="submit" class="btn-submit" onclick="return validateRating()">
                <i class="fas fa-paper-plane"></i> Submit Official Review
            </button>

            <a href="my_appointments.php" class="btn-back">
                <i class="fas fa-arrow-left me-1"></i> Back to Appointments
            </a>

        </form>
    </div>
</div>

<script>
const ratingData = {
    1: { text: "Poor Experience 😞", color: "#ef4444" },
    2: { text: "Fair Experience 😐", color: "#f97316" },
    3: { text: "Good Consultation 🙂", color: "#eab308" },
    4: { text: "Very Good Visit 😊", color: "#10b981" },
    5: { text: "Excellent & Professional! 🌟", color: "#059669" }
};

document.querySelectorAll('.stars input').forEach(input => {
    input.addEventListener('change', function() {
        const data = ratingData[this.value];
        const desc = document.getElementById('ratingDesc');
        desc.textContent = data.text;
        desc.style.color = data.color;
    });
});

function addTag(text) {
    const ta = document.getElementById('reviewText');
    if (ta.value && !ta.value.endsWith(' ')) {
        ta.value += ' ';
    }
    ta.value += text + '. ';
    ta.focus();
}

function validateRating() {
    const selected = document.querySelector('.stars input:checked');
    if (!selected) {
        alert('Please select a star rating (1 to 5 stars) before submitting!');
        return false;
    }
    return true;
}
</script>

</body>
</html>