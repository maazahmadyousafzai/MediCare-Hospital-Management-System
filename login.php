<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MediCare 3-Tier Telemedicine Platform</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary: #0f172a;
            --accent: #38bdf8;
            --bg-light: #f8fafc;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            box-sizing: border-box;
        }

        body {
            background-color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
            display: flex;
            min-height: 650px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-side {
            flex: 1;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #0f172a 100%);
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-side::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hero-brand-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .hero-brand h3 {
            font-weight: 800;
            margin: 0;
            font-size: 24px;
            letter-spacing: -0.5px;
        }

        .hero-content {
            margin: 40px 0;
            z-index: 2;
        }

        .hero-content h1 {
            font-weight: 800;
            font-size: 32px;
            line-height: 1.2;
            letter-spacing: -1px;
            margin-bottom: 18px;
        }

        .hero-content p {
            opacity: 0.85;
            font-size: 15px;
            line-height: 1.6;
            max-width: 420px;
            font-weight: 400;
        }

        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 25px;
        }

        .feature-pill {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-footer {
            font-size: 13px;
            opacity: 0.7;
            font-weight: 500;
        }

        .form-side {
            flex: 1.1;
            padding: 55px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-header h4 {
            font-weight: 800;
            color: #0f172a;
            font-size: 26px;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .form-header p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .role-tabs-label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            display: block;
        }

        .role-tabs {
            display: flex;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 14px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .role-tab-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 700;
            font-size: 13px;
            color: #64748b;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .role-tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .form-group-custom {
            margin-bottom: 20px;
        }

        .form-group-custom label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.left-icon {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .input-wrapper .form-control {
            padding: 14px 45px 14px 45px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            width: 100%;
            transition: all 0.3s ease;
        }

        .input-wrapper .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
            outline: none;
        }

        .input-wrapper .form-control:focus + i.left-icon {
            color: var(--primary);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #0f172a;
        }

        .btn-submit-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-submit-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(13, 110, 253, 0.35);
            color: white;
        }

        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .form-footer a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 20px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--primary);
        }

        @media (max-width: 991px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            .hero-side {
                padding: 35px 30px;
            }
            .form-side {
                padding: 35px 30px;
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">

        <!-- Left Visual Side -->
        <div class="hero-side">
            <div class="hero-brand">
                <div class="hero-brand-icon">
                    <i class="fas fa-hospital-alt text-white"></i>
                </div>
                <h3>MediCare</h3>
            </div>

            <div class="hero-content">
                <h1>MediCare 3-Tier Telemedicine Platform</h1>
                <p>Access encrypted WebRTC video consultations, automated digital prescriptions, and seamless appointment booking in one unified platform.</p>
                
                <div class="feature-pills">
                    <div class="feature-pill"><i class="fas fa-video text-info"></i> WebRTC Telemedicine</div>
                    <div class="feature-pill"><i class="fas fa-file-medical text-warning"></i> Digital Rx Engine</div>
                    <div class="feature-pill"><i class="fas fa-shield-alt text-success"></i> 3-Tier Security</div>
                </div>
            </div>

            <div class="hero-footer">
                &copy; MediCare 3-Tier Telemedicine Platform
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="form-side">
            <div class="form-header">
                <h4>Welcome Back</h4>
                <p>Please enter your account details to sign in</p>
            </div>

            <form action="login_process.php" method="POST" id="loginForm">

                <!-- Hidden Input for Selected Role -->
                <input type="hidden" name="role" id="selectedRole" value="patient">

                <!-- Role Selection Pills -->
                <span class="role-tabs-label">Select Account Role</span>
                <div class="role-tabs">
                    <button type="button" class="role-tab-btn active" onclick="selectRole('patient', this)">
                        <i class="fas fa-user-injured"></i> Patient
                    </button>
                    <button type="button" class="role-tab-btn" onclick="selectRole('doctor', this)">
                        <i class="fas fa-user-md"></i> Doctor
                    </button>
                    <button type="button" class="role-tab-btn" onclick="selectRole('admin', this)">
                        <i class="fas fa-user-shield"></i> Admin
                    </button>
                </div>

                <!-- Email Address Field -->
                <div class="form-group-custom">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" class="form-control" name="email" placeholder="name@example.com" required autocomplete="email">
                        <i class="fas fa-envelope left-icon"></i>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group-custom">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Enter your password" required autocomplete="current-password">
                        <i class="fas fa-lock left-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit-custom">
                    <i class="fas fa-sign-in-alt"></i> Sign In to Account
                </button>

            </form>

            <!-- Links -->
            <div class="form-footer">
                Don't have an account? <a href="register.php">Register Now</a>
                <div>
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Return to Homepage
                    </a>
                </div>
            </div>

        </div>

    </div>

    <script>
        function selectRole(role, element) {
            document.getElementById('selectedRole').value = role;
            const buttons = document.querySelectorAll('.role-tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
        }

        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');

            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>