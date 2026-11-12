<?php
include 'db/connection.php';

echo "<div style='font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 25px; border-radius: 16px; background: #f8fafc; border: 1px solid #cbd5e1;'>";
echo "<h2 style='color:#06b6d4; margin-top:0;'>MediCare Database Seeder</h2>";

// 1. Primary Doctor Account Setup (Dr. Maaz Ahmad)
$main_email = 'maazahmadyousafzai557@gmail.com';
$main_pass  = password_hash('Maazkhan@1', PASSWORD_DEFAULT);
$main_name  = 'Dr. Maaz Ahmad';
$main_phone = '03149661858';

// Check if main account already exists in users table
$check_main = mysqli_query($conn, "SELECT id FROM users WHERE email = '$main_email'");

if (mysqli_num_rows($check_main) > 0) {
    $row = mysqli_fetch_assoc($check_main);
    $main_user_id = $row['id'];
    // Convert existing account to Doctor role & update password
    mysqli_query($conn, "UPDATE users SET fullname='$main_name', password='$main_pass', role='doctor', phone='$main_phone' WHERE id=$main_user_id");
    echo "<p style='color:#10b981;'><strong>✔ Primary Account Updated:</strong> $main_email is now configured as Doctor with password <code>Maazkhan@1</code>.</p>";
} else {
    // Create new doctor account
    mysqli_query($conn, "INSERT INTO users (fullname, email, phone, password, role) VALUES ('$main_name', '$main_email', '$main_phone', '$main_pass', 'doctor')");
    $main_user_id = mysqli_insert_id($conn);
    echo "<p style='color:#10b981;'><strong>✔ Primary Account Created:</strong> $main_email (Password: <code>Maazkhan@1</code>).</p>";
}

// Link Primary Account to Doctors table
$check_doc = mysqli_query($conn, "SELECT id FROM doctors WHERE user_id = $main_user_id");
if (mysqli_num_rows($check_doc) == 0) {
    mysqli_query($conn, "INSERT INTO doctors (user_id, specialization, qualification, experience, fee, available_days, available_time)
                         VALUES ($main_user_id, 'Dermatology & Telemedicine Specialist', 'MBBS, FCPS (Dermatology)', '5 Years', 500, 'Mon, Tue, Wed, Thu, Fri, Sat', '09:00 AM - 05:00 PM')");
} else {
    mysqli_query($conn, "UPDATE doctors SET specialization='Dermatology & Telemedicine Specialist', qualification='MBBS, FCPS (Dermatology)', fee=500 WHERE user_id=$main_user_id");
}


// 2. Comprehensive Medical Specializations & Display Dummy Doctors
$specialists = [
    ['name' => 'Dr. Ayesha Khan', 'email' => 'ayesha.cardio@medicare.com', 'spec' => 'Cardiology', 'qual' => 'MBBS, MD (Cardiology)', 'exp' => '8 Years', 'fee' => 1000],
    ['name' => 'Dr. Usman Ali', 'email' => 'usman.neuro@medicare.com', 'spec' => 'Neurology', 'qual' => 'MBBS, FCPS (Neurology)', 'exp' => '10 Years', 'fee' => 1200],
    ['name' => 'Dr. Fatima Zahra', 'email' => 'fatima.peds@medicare.com', 'spec' => 'Pediatrics', 'qual' => 'MBBS, DCH, FCPS', 'exp' => '6 Years', 'fee' => 800],
    ['name' => 'Dr. Bilal Hassan', 'email' => 'bilal.ortho@medicare.com', 'spec' => 'Orthopedics', 'qual' => 'MBBS, MS (Orthopedics)', 'exp' => '7 Years', 'fee' => 900],
    ['name' => 'Dr. Sana Tariq', 'email' => 'sana.gyn@medicare.com', 'spec' => 'Gynecology & Obstetrics', 'qual' => 'MBBS, FCPS (OBGYN)', 'exp' => '9 Years', 'fee' => 1000],
    ['name' => 'Dr. Hamza Malik', 'email' => 'hamza.psych@medicare.com', 'spec' => 'Psychiatry', 'qual' => 'MBBS, FCPS (Psychiatry)', 'exp' => '5 Years', 'fee' => 1500],
    ['name' => 'Dr. Zainab Bibi', 'email' => 'zainab.ent@medicare.com', 'spec' => 'ENT (Ear, Nose, Throat)', 'qual' => 'MBBS, MCPS (ENT)', 'exp' => '4 Years', 'fee' => 700],
    ['name' => 'Dr. Saad Rafique', 'email' => 'saad.gastro@medicare.com', 'spec' => 'Gastroenterology', 'qual' => 'MBBS, MRCP (UK)', 'exp' => '11 Years', 'fee' => 1200],
    ['name' => 'Dr. Maria Imran', 'email' => 'maria.eye@medicare.com', 'spec' => 'Ophthalmology (Eye)', 'qual' => 'MBBS, FCPS (Ophthalmology)', 'exp' => '6 Years', 'fee' => 800],
    ['name' => 'Dr. Tariq Mahmood', 'email' => 'tariq.pulmo@medicare.com', 'spec' => 'Pulmonology (Chest)', 'qual' => 'MBBS, DTCD, FCPS', 'exp' => '12 Years', 'fee' => 1100],
    ['name' => 'Dr. Nida Yasmeen', 'email' => 'nida.uro@medicare.com', 'spec' => 'Urology', 'qual' => 'MBBS, MS (Urology)', 'exp' => '7 Years', 'fee' => 1000],
    ['name' => 'Dr. Kamran Ahmed', 'email' => 'kamran.surge@medicare.com', 'spec' => 'General Surgery', 'qual' => 'MBBS, FCPS (Surgery)', 'exp' => '10 Years', 'fee' => 1000],
    ['name' => 'Dr. Hina Shah', 'email' => 'hina.nephro@medicare.com', 'spec' => 'Nephrology (Kidney)', 'qual' => 'MBBS, FCPS (Nephrology)', 'exp' => '8 Years', 'fee' => 1200],
    ['name' => 'Dr. Omer Farooq', 'email' => 'omer.onco@medicare.com', 'spec' => 'Oncology (Cancer)', 'qual' => 'MBBS, FCPS (Oncology)', 'exp' => '14 Years', 'fee' => 1500],
    ['name' => 'Dr. Asma Parveen', 'email' => 'asma.endo@medicare.com', 'spec' => 'Endocrinology (Diabetes)', 'qual' => 'MBBS, MD (Endocrinology)', 'exp' => '9 Years', 'fee' => 1100]
];

$dummy_pass = password_hash('DoctorPass@123', PASSWORD_DEFAULT);
$added_count = 0;

foreach ($specialists as $s) {
    $email = $s['email'];
    $check_s = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_s) == 0) {
        mysqli_query($conn, "INSERT INTO users (fullname, email, phone, password, role) VALUES ('{$s['name']}', '$email', '03001234567', '$dummy_pass', 'doctor')");
        $u_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO doctors (user_id, specialization, qualification, experience, fee, available_days, available_time)
                             VALUES ($u_id, '{$s['spec']}', '{$s['qual']}', '{$s['exp']}', {$s['fee']}, 'Mon, Wed, Fri', '10:00 AM - 04:00 PM')");
        $added_count++;
    }
}

echo "<p style='color:#0284c7;'><strong>✔ Specializations Added:</strong> $added_count specialized doctors populated into database.</p>";
echo "<hr style='border:0; border-top:1px solid #cbd5e1;'>";
echo "<p style='color:#0f172a; font-weight:bold;'>All set! Now you can log in to Doctor Portal with:</p>";
echo "<ul>";
echo "<li><strong>Email:</strong> maazahmadyousafzai557@gmail.com</li>";
echo "<li><strong>Password:</strong> Maazkhan@1</li>";
echo "</ul>";
echo "<a href='login.php' style='display:inline-block; padding:10px 20px; background:#06b6d4; color:white; border-radius:8px; text-decoration:none; font-weight:bold;'>Go to Login Page</a>";
echo "</div>";
?>