<?php
session_start();
include '../db/connection.php';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

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

// Fetch Appointment details securely (must be completed)
$apt_query = "SELECT a.*, u.fullname as doctor_name, d.specialization
              FROM appointments a
              JOIN users u ON a.doctor_id = u.id
              LEFT JOIN doctors d ON a.doctor_id = d.user_id
              WHERE a.id = $appointment_id 
              AND a.patient_id = $patient_id 
              AND a.status = 'completed'";

$apt_res = mysqli_query($conn, $apt_query);
$apt     = mysqli_fetch_assoc($apt_res);

if (!$apt) {
    echo "<script>
            alert('Invalid appointment record, unauthorized request, or appointment not yet completed!');
            window.location='my_appointments.php';
          </script>";
    exit();
}

// Check if rating already exists for this appointment
$already_res = mysqli_query($conn, "SELECT id FROM ratings WHERE appointment_id = $appointment_id AND patient_id = $patient_id");
if (mysqli_num_rows($already_res) > 0) {
    echo "<script>
            alert('You have already submitted a rating for this consultation!');
            window.location='my_appointments.php';
          </script>";
    exit();
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }
    $rating = intval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, trim($_POST['review']));
    $doc_id = intval($apt['doctor_id']);

    if ($rating < 1 || $rating > 5) {
        echo "<script>alert('Please select a valid star rating between 1 and 5!'); window.history.back();</script>";
        exit();
    }

    $insert_query = "INSERT INTO ratings (doctor_id, patient_id, appointment_id, rating, review)
                     VALUES ($doc_id, $patient_id, $appointment_id, $rating, '$review')";

    if (mysqli_query($conn, $insert_query)) {
        echo "<script>
                alert('Thank you for your valuable feedback!');
                window.location='my_appointments.php';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Database Error: Unable to submit feedback. Please try again.');
                window.history.back();
              </script>";
        exit();
    }
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