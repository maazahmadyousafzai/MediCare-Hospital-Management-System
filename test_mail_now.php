<?php
include 'db/connection.php';
include 'db/email.php';

$test_email = "maazahmadyousafzai557@gmail.com";
$test_name  = "Maaz Ahmad";
$subject    = "MediCare Test Email — Instant Verification";
$message    = "<h3 style='color:#0066ff;'>Email System Test Successful!</h3><p>Aapka MediCare email delivery system ab perfectly active hai aur instant emails bhej raha hai.</p>";

echo "<h3>Sending test email to $test_email...</h3>";

if (sendEmail($test_email, $test_name, $subject, $message)) {
    echo "<div style='padding:20px; background:#dcfce7; color:#15803d; border-radius:10px; font-weight:bold;'>";
    echo "✔ SUCCESS: Email sent successfully! Apne Inbox / Spam folder mein check karein.";
    echo "</div>";
} else {
    echo "<div style='padding:20px; background:#fef2f2; color:#b91c1c; border-radius:10px; font-weight:bold;'>";
    echo "❌ FAILED: Email bhejte waqt issue aaya.<br>";
    if (file_exists('db/email_error_log.txt')) {
        echo "<strong>Error Log:</strong> " . nl2br(file_get_contents('db/email_error_log.txt'));
    }
    echo "</div>";
}
?>