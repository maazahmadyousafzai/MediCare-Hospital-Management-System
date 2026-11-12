<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare: A Web-Based 3-Tier Telemedicine & Hospital Management Platform</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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

        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 16px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 26px;
            font-weight: 800;
            color: white !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand span {
            color: var(--accent-color);
        }

        .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500;
            margin: 0 8px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent-color) !important;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0284c7 100%);
            color: white;
            padding: 120px 0 100px 0;
            position: relative;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to top, var(--light-bg), transparent);
        }

        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero h1 span {
            color: var(--accent-color);
        }

        .hero p {
            font-size: 18px;
            color: #cbd5e1;
            margin-bottom: 35px;
            font-weight: 400;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-hero-primary {
            background: var(--accent-color);
            color: #0f172a;
            font-weight: 700;
            padding: 14px 36px;
            border-radius: 50px;
            font-size: 16px;
            box-shadow: 0 10px 25px -5px rgba(56, 189, 248, 0.4);
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-hero-primary:hover {
            background: white;
            color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(255, 255, 255, 0.3);
        }

        .btn-hero-outline {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
            color: white;
            font-weight: 600;
            padding: 14px 36px;
            border-radius: 50px;
            font-size: 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-hero-outline:hover {
            background: white;
            color: #0f172a;
            border-color: white;
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: var(--light-bg);
        }

        .section-title {
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 45px 35px;
            text-align: left;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(2, 132, 199, 0.1);
            border-color: transparent;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: #e0f2fe;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 28px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .feature-card h4 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .feature-card p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* Stats Section */
        .stats {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 80px 0;
            position: relative;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 5px;
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 16px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Advanced Footer */
        footer {
            background: #090d16;
            color: #94a3b8;
            text-align: center;
            padding: 40px 0 30px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dev-profile {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            padding: 12px 25px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .dev-profile:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-color);
        }

        .dev-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-color);
            margin-right: 15px;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
        }

        .dev-info {
            text-align: left;
        }

        .dev-name {
            color: white;
            font-weight: 700;
            font-size: 16px;
            margin: 0;
            line-height: 1.2;
        }

        .dev-role {
            color: var(--accent-color);
            font-size: 13px;
            font-weight: 500;
            margin: 0;
        }

        footer .copyright {
            font-size: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 20px;
            margin-top: 10px;
        }

        footer span {
            color: var(--accent-color);
            font-weight: 700;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heartbeat text-info me-2"></i>Medi<span>Care</span>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link px-3" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="login.php">Login</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-hero-primary py-2 px-4 fs-6" href="register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero text-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-10 mx-auto">
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
                        <i class="fas fa-shield-alt me-1"></i> 3-Tier Web Architecture & Telemedicine Platform
                    </span>
                    <h1>Next-Gen Healthcare & <span>Video Telemedicine</span></h1>
                    <p>Experience seamless hospital appointment management, real-time doctor scheduling, electronic prescriptions, and secure WebRTC video consultations.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="register.php" class="btn btn-hero-primary">
                            <i class="fas fa-user-plus me-2"></i> Register as Patient
                        </a>
                        <a href="login.php" class="btn btn-hero-outline">
                            <i class="fas fa-sign-in-alt me-2"></i> Portal Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features">
        <div class="container">
            <div class="text-center mx-auto mb-5">
                <h6 class="text-info fw-bold text-uppercase tracking-wider mb-2">Core System Capabilities</h6>
                <h2 class="section-title display-6">Why Choose MediCare Platform?</h2>
            </div>
            <div class="row g-4">

                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                        <h4>Smart Appointment Booking</h4>
                        <p>Advanced scheduling system enabling instant doctor appointments, queue tracking, and automated status notifications.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-video"></i></div>
                        <h4>WebRTC Video Telemedicine</h4>
                        <p>High-definition, secure peer-to-peer video consultations bridging distance between patients and specialized doctors.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-file-medical"></i></div>
                        <h4>Electronic Prescriptions</h4>
                        <p>Instant digital prescription generation, medical history tracking, and seamless 3-tier database management.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- STATS SECTION -->
    <section class="stats text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-3 stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Verified Specialists</div>
                </div>
                <div class="col-md-3 stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Active Patients</div>
                </div>
                <div class="col-md-3 stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Medical Specialities</div>
                </div>
                <div class="col-md-3 stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Secure Access</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <!-- Developer Profile Box -->
            <div class="dev-profile">
                <img src="maaz.jpg" alt="Maaz Ahmad" class="dev-img">
                <div class="dev-info">
                    <h6 class="dev-name">Maaz Ahmad</h6>
                    <p class="dev-role">System Developer</p>
                </div>
            </div>
            
            <p class="copyright mb-0">© 2026 <span>MediCare</span> — A Web-Based 3-Tier Telemedicine & Hospital Management Platform</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>