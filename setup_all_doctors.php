<?php
include 'db/connection.php';

echo "<div style='font-family: Arial, sans-serif; max-width: 750px; margin: 40px auto; padding: 25px; border-radius: 16px; background: #f8fafc; border: 1px solid #cbd5e1;'>";
echo "<h2 style='color:#06b6d4; margin-top:0;'>MediCare Doctor Account & Specialization Setup</h2>";

$target_email = 'maazahmadyousafzai557@gmail.com';
$target_pass  = password_hash('Maazkhan@1', PASSWORD_DEFAULT);
$target_name  = 'Dr. Maaz Ahmad';
$target_phone = '03149661858';

// 1. Fix Dr. Maaz Ahmad Email & Password Linkage
$old_email_check = mysqli_query($conn, "SELECT id FROM users WHERE email = 'maazthefighterxd@gmail.com'");
if (mysqli_num_rows($old_email_check) > 0) {
    $old_row = mysqli_fetch_assoc($old_email_check);
    $old_id = $old_row['id'];
    
    $target_check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$target_email'");
    if (mysqli_num_rows($target_check) > 0) {
        $t_row = mysqli_fetch_assoc($target_check);
        $t_id = $t_row['id'];
        mysqli_query($conn, "UPDATE users SET fullname='$target_name', password='$target_pass', role='doctor', phone='$target_phone' WHERE id=$t_id");
        mysqli_query($conn, "DELETE FROM doctors WHERE user_id=$old_id");
        mysqli_query($conn, "DELETE FROM users WHERE id=$old_id");
        $final_doc_user_id = $t_id;
    } else {
        mysqli_query($conn, "UPDATE users SET fullname='$target_name', email='$target_email', password='$target_pass', role='doctor', phone='$target_phone' WHERE id=$old_id");
        $final_doc_user_id = $old_id;
    }
} else {
    $target_check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$target_email'");
    if (mysqli_num_rows($target_check) > 0) {
        $t_row = mysqli_fetch_assoc($target_check);
        $final_doc_user_id = $t_row['id'];
        mysqli_query($conn, "UPDATE users SET fullname='$target_name', password='$target_pass', role='doctor', phone='$target_phone' WHERE id=$final_doc_user_id");
    } else {
        mysqli_query($conn, "INSERT INTO users (fullname, email, phone, password, role) VALUES ('$target_name', '$target_email', '$target_phone', '$target_pass', 'doctor')");
        $final_doc_user_id = mysqli_insert_id($conn);
    }
}

// Ensure doctor entry exists in doctors table
$doc_check = mysqli_query($conn, "SELECT id FROM doctors WHERE user_id = $final_doc_user_id");
if (mysqli_num_rows($doc_check) == 0) {
    mysqli_query($conn, "INSERT INTO doctors (user_id, specialization, qualification, experience, fee, available_days, available_time)
                         VALUES ($final_doc_user_id, 'Dermatology & Telemedicine Specialist', 'MBBS, FCPS (Dermatology)', '5 Years', 500, 'Mon, Tue, Wed, Thu, Fri, Sat', '09:00 AM - 05:00 PM')");
} else {
    mysqli_query($conn, "UPDATE doctors SET specialization='Dermatology & Telemedicine Specialist', qualification='MBBS, FCPS (Dermatology)', experience='5 Years', fee=500 WHERE user_id=$final_doc_user_id");
}

echo "<p style='color:#10b981;'><strong>✔ Dr. Maaz Ahmad Account Configured:</strong> Linked to email <code>$target_email</code> with Password <code>Maazkhan@1</code>.</p>";

// 2. Insert All Medical Specializations for Admin & Patient View
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

echo "<p style='color:#0284c7;'><strong>✔ All Specializations Inserted:</strong> Total 16 medical specialists active in system.</p>";
echo "<hr style='border:0; border-top:1px solid #cbd5e1;'>";
echo "<p style='color:#0f172a; font-weight:bold;'>Configuration Complete! Testing Instructions:</p>";
echo "<ol>";
echo "<li>Browser par <strong>Patient Portal</strong> Refresh karein: Complete 16 specialists dikhenge.</li>";
echo "<li>Patient account se <strong>Dr. Maaz Ahmad</strong> ko appointment book karein.</li>";
echo "<li>Logout karke Doctor Portal par <code>$target_email</code> aur Password <code>Maazkhan@1</code> se login karein. Book kiya hua appointment live Dr. Maaz ke dashboard par show hoga!</li>";
echo "</ol>";
echo "<a href='login.php' style='display:inline-block; padding:10px 20px; background:#06b6d4; color:white; border-radius:8px; text-decoration:none; font-weight:bold;'>Go to Login Page</a>";
echo "</div>";
?>