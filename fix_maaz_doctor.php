<?php
include 'db/connection.php';

$email = 'maazahmadyousafzai557@gmail.com';

// User account fetch karein
$res = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");

if ($res && mysqli_num_rows($res) > 0) {
    $user = mysqli_fetch_assoc($res);
    $user_id = $user['id'];

    // 1. Users table mein status 'active' aur role 'doctor' confirm karein
    mysqli_query($conn, "UPDATE users SET role = 'doctor', status = 'active', fullname = 'Dr. Maaz Ahmad' WHERE id = $user_id");

    // 2. Doctors table check karke details update/insert karein (experience set to integer 5)
    $doc_check = mysqli_query($conn, "SELECT id FROM doctors WHERE user_id = $user_id");
    
    if (mysqli_num_rows($doc_check) > 0) {
        mysqli_query($conn, "UPDATE doctors SET specialization = 'Cardiologist', qualification = 'MBBS, FCPS', experience = 5, fee = 500, status = 'active' WHERE user_id = $user_id");
    } else {
        mysqli_query($conn, "INSERT INTO doctors (user_id, specialization, qualification, experience, fee, status) VALUES ($user_id, 'Cardiologist', 'MBBS, FCPS', 5, 500, 'active')");
    }

    echo "<div style='font-family:Arial; padding:30px; background:#dcfce7; color:#15803d; border-radius:12px; max-width:600px; margin:40px auto; border:1px solid #bbf7d0;'>";
    echo "<h2>✔ Dr. Maaz Ahmad Account Fixed & Activated!</h2>";
    echo "<p>Ab <strong>Dr. Maaz Ahmad</strong> Patient Portal ke <em>Book Appointment</em> list mein aur Admin Portal ke <em>Manage Doctors</em> table mein perfectly show honge.</p>";
    echo "</div>";
} else {
    echo "<div style='font-family:Arial; padding:30px; background:#fef2f2; color:#b91c1c; border-radius:12px; max-width:600px; margin:40px auto;'>";
    echo "<h2>❌ Account Not Found!</h2>";
    echo "<p>Database mein $email email se koi account nahi mila.</p>";
    echo "</div>";
}
?>