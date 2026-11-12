<?php
include 'db/connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Inputs ko clean aur escape karein
    $email    = trim($_POST['email']);
    $password = $_POST['password']; // Raw password for verification
    $role     = trim($_POST['role']);

    // SQL Injection se bachane ke liye escape karein
    $email_clean = mysqli_real_escape_string($conn, $email);
    $role_clean  = mysqli_real_escape_string($conn, $role);

    // 1. Database se Email aur Role ke mutabiq User fetch karein
    $sql = "SELECT * FROM users 
            WHERE email='$email_clean' 
            AND role='$role_clean'";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // 2. Password Check (Bcrypt hash support + legacy MD5 fallback)
        $isPasswordCorrect = false;

        if (password_verify($password, $user['password'])) {
            $isPasswordCorrect = true; // Bcrypt Hash Match (New Users / Doctors)
        } elseif ($user['password'] === md5($password)) {
            $isPasswordCorrect = true; // Legacy MD5 Match (Old Users / Admin)
        }

        if ($isPasswordCorrect) {

            // 3. Status Check: Pending Admin Approval
            if ($user['status'] === 'pending') {
                echo "<script>
                        alert('Your account is pending admin approval! Please wait for approval.');
                        window.location='login.php';
                      </script>";
                exit();
            }

            // 4. Status Check: Blocked User
            if ($user['status'] === 'blocked') {
                echo "<script>
                        alert('Your account is blocked! Contact admin.');
                        window.location='login.php';
                      </script>";
                exit();
            }

            // 5. Complete Session Set
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['fullname'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            // 6. Role-Based Dashboard Redirection
            if ($role === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($role === 'doctor') {
                header("Location: doctor/dashboard.php");
            } else {
                header("Location: patient/dashboard.php");
            }
            exit();

        } else {
            echo "<script>
                    alert('Invalid password! Please try again.');
                    window.location='login.php';
                  </script>";
        }

    } else {
        echo "<script>
                alert('Invalid email or role selection!');
                window.location='login.php';
              </script>";
    }
}
?>