<?php
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db/connection.php';

// Stats Queries
$total_patients = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM users WHERE role='patient'"));
$total_doctors = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM users WHERE role='doctor' AND status='active'"));
$pending_doctors = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM users WHERE role='doctor' AND status='pending'"));
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM appointments"));
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM payments WHERE status='completed'"));

// Monthly Appointments (Last 6 months)
$monthly_apts = mysqli_query($conn,
    "SELECT DATE_FORMAT(appointment_date, '%b %Y') as month,
            COUNT(*) as total
     FROM appointments
     WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
     ORDER BY appointment_date ASC");

$apt_labels = [];
$apt_data   = [];
while ($row = mysqli_fetch_assoc($monthly_apts)) {
    $apt_labels[] = $row['month'];
    $apt_data[]   = $row['total'];
}

// Monthly Revenue (Last 6 months)
$monthly_rev = mysqli_query($conn,
    "SELECT DATE_FORMAT(created_at, '%b %Y') as month,
            SUM(amount) as total
     FROM payments
     WHERE status='completed'
     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY created_at ASC");

$rev_labels = [];
$rev_data   = [];
while ($row = mysqli_fetch_assoc($monthly_rev)) {
    $rev_labels[] = $row['month'];
    $rev_data[]   = $row['total'];
}

// Appointment Status Distribution
$status_data = mysqli_query($conn,
    "SELECT status, COUNT(*) as cnt FROM appointments GROUP BY status");
$status_labels = [];
$status_counts = [];
while ($row = mysqli_fetch_assoc($status_data)) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = $row['cnt'];
}

// Top Rated Doctors
$top_doctors = mysqli_query($conn,
    "SELECT u.fullname, d.specialization,
            ROUND(AVG(r.rating),1) as avg_rating,
            COUNT(r.id) as total_reviews
     FROM ratings r
     JOIN users u ON r.doctor_id = u.id
     JOIN doctors d ON r.doctor_id = d.user_id
     GROUP BY r.doctor_id
     ORDER BY avg_rating DESC
     LIMIT 5");

// Pending Doctors
$pending = mysqli_query($conn,
    "SELECT u.id, u.fullname, u.email, u.created_at,
            d.specialization, d.qualification, d.experience
     FROM users u
     JOIN doctors d ON u.id = d.user_id
     WHERE u.role='doctor' AND u.status='pending'
     ORDER BY u.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — MediCare Telemedicine Platform</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #0284c7;
            --secondary-color: #0369a1;
            --accent-color: #38bdf8;
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
            background: rgba(56, 189, 248, 0.1);
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
            background: rgba(2, 132, 199, 0.15);
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
            margin-bottom: 35px;
        }

        .topbar h5 {
            margin: 0;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .admin-badge {
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

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.2);
        }

        .stat-info h3 {
            font-weight: 800;
            margin: 0;
            font-size: 28px;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .stat-info p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
        }

        /* Chart Cards */
        .chart-card, .table-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .chart-card h5, .table-card h5 {
            font-weight: 800;
            margin-bottom: 25px;
            color: #0f172a;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tables & Lists */
        .table th {
            background: #f8fafc;
            font-weight: 700;
            color: #475569;
            font-size: 13px;
            text-uppercase: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table td {
            vertical-align: middle;
            padding: 16px;
            font-size: 14px;
            color: #334155;
        }

        .btn-action {
            border: none;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .doctor-rank-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .doctor-rank-card:hover {
            border-color: #f59e0b;
            background: white;
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.08);
        }

        .rank-num {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            flex-shrink: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="icon-box">
            <i class="fas fa-heartbeat"></i>
        </div>
        <h4>Medi<span>Care</span></h4>
        <small class="text-info fw-semibold">Admin Workspace</small>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="manage_doctors.php">
            <i class="fas fa-user-md"></i> Manage Doctors
        </a>
        <a href="manage_patients.php">
            <i class="fas fa-users"></i> Manage Patients
        </a>
        <a href="manage_appointments.php">
            <i class="fas fa-calendar-alt"></i> Appointments
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
            <i class="fas fa-tachometer-alt text-info me-2"></i> System Analytics & Control Center
        </h5>
        <div class="admin-badge">
            <i class="fas fa-user-shield text-info"></i>
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #0284c7, #0369a1)">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_patients['cnt']; ?></h3>
                    <p>Total Patients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #047857)">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_doctors['cnt']; ?></h3>
                    <p>Active Doctors</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4338ca)">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_appointments['cnt']; ?></h3>
                    <p>Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #0d9488, #0f766e)">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>Rs. <?php echo number_format($total_revenue['total'] ?? 0); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">

        <!-- Monthly Appointments Chart -->
        <div class="col-md-8">
            <div class="chart-card">
                <h5>
                    <i class="fas fa-chart-bar text-primary"></i> Monthly Appointment Trends
                </h5>
                <canvas id="appointmentsChart" height="130"></canvas>
            </div>
        </div>

        <!-- Appointment Status Pie -->
        <div class="col-md-4">
            <div class="chart-card">
                <h5>
                    <i class="fas fa-chart-pie text-success"></i> Status Breakdown
                </h5>
                <canvas id="statusChart" height="230"></canvas>
            </div>
        </div>

    </div>

    <!-- Revenue Chart -->
    <div class="chart-card">
        <h5>
            <i class="fas fa-chart-line text-info"></i> Revenue Growth (PKR)
        </h5>
        <canvas id="revenueChart" height="80"></canvas>
    </div>

    <!-- Top Doctors + Pending Row -->
    <div class="row g-4 mb-4">

        <!-- Top Rated Doctors -->
        <div class="col-md-5">
            <div class="table-card">
                <h5>
                    <i class="fas fa-star text-warning"></i> Top Rated Medical Specialists
                </h5>
                <?php
                $rank = 1;
                $top_doctors_arr = [];
                while($doc = mysqli_fetch_assoc($top_doctors)) {
                    $top_doctors_arr[] = $doc;
                }
                ?>
                <?php if(empty($top_doctors_arr)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-star fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0 fw-semibold">No rating reviews recorded yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach($top_doctors_arr as $doc): ?>
                    <div class="doctor-rank-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rank-num">
                                <?php echo $rank++; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6">
                                    Dr. <?php echo htmlspecialchars($doc['fullname']); ?>
                                </div>
                                <div class="text-muted fs-7">
                                    <?php echo htmlspecialchars($doc['specialization']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-warning fs-5">
                                <?php echo $doc['avg_rating']; ?> <span class="fs-6">★</span>
                            </div>
                            <div class="text-muted fs-7">
                                <?php echo $doc['total_reviews']; ?> reviews
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Doctors -->
        <div class="col-md-7">
            <div class="table-card">
                <h5>
                    <i class="fas fa-clock text-warning"></i> Pending Practitioner Verification
                    <?php if(mysqli_num_rows($pending) > 0): ?>
                    <span class="badge bg-danger rounded-pill fs-7 ms-2">
                        <?php echo mysqli_num_rows($pending); ?> Pending
                    </span>
                    <?php endif; ?>
                </h5>

                <?php if(mysqli_num_rows($pending) == 0): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-75"></i>
                        <p class="mb-0 fw-semibold">All practitioner accounts are reviewed!</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Doctor Info</th>
                                <th>Specialization</th>
                                <th>Exp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($row = mysqli_fetch_assoc($pending)): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?php echo htmlspecialchars($row['fullname']); ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-info bg-opacity-10 text-info fw-semibold px-2 py-1"><?php echo htmlspecialchars($row['specialization']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($row['experience']); ?></strong> yrs</td>
                                <td>
                                    <form method="POST" action="doctor_action.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button name="action" value="approve" class="btn-action bg-success text-white"><i class="fas fa-check"></i> Approve</button>
                                        <button name="action" value="reject" class="btn-action bg-danger text-white" onclick="return confirm('Reject and remove doctor registration?')"><i class="fas fa-times"></i> Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- Chart.js Scripts -->
<script>
// 1. Monthly Appointments Bar Chart
const aptCtx = document.getElementById('appointmentsChart').getContext('2d');
new Chart(aptCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(empty($apt_labels) ? ['No Data'] : $apt_labels); ?>,
        datasets: [{
            label: 'Appointments',
            data: <?php echo json_encode(empty($apt_data) ? [0] : $apt_data); ?>,
            backgroundColor: 'rgba(2, 132, 199, 0.85)',
            borderColor: '#0284c7',
            borderWidth: 2,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// 2. Appointment Status Pie Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(empty($status_labels) ? ['No Data'] : $status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode(empty($status_counts) ? [1] : $status_counts); ?>,
            backgroundColor: ['#f59e0b','#10b981','#0284c7','#ef4444'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } }
        }
    }
});

// 3. Monthly Revenue Line Chart
const revCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(empty($rev_labels) ? ['No Data'] : $rev_labels); ?>,
        datasets: [{
            label: 'Revenue (PKR)',
            data: <?php echo json_encode(empty($rev_data) ? [0] : $rev_data); ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            borderWidth: 3,
            pointBackgroundColor: '#10b981',
            pointRadius: 6,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return 'Rs. ' + value; }
                }
            }
        }
    }
});
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>