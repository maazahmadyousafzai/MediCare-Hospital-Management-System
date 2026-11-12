<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// System Level Admin Constants
if (!defined('ADMIN_NOTIFY_EMAIL')) define('ADMIN_NOTIFY_EMAIL', 'maazahmadyousafzai557@gmail.com');
if (!defined('ADMIN_NOTIFY_NAME')) define('ADMIN_NOTIFY_NAME', 'Maaz Ahmad');

function sendEmail($to_email, $to_name, $subject, $message) {
    $mail = new PHPMailer(true);
    $log_file = __DIR__ . '/email_log.txt';

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'maazahmadyousafzai557@gmail.com';
        $mail->Password   = 'kugjxmkmqgbsmcnq'; // Updated Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('maazahmadyousafzai557@gmail.com', 'MediCare Platform');
        $mail->addReplyTo('maazahmadyousafzai557@gmail.com', 'MediCare Platform');
        $mail->addAddress($to_email, $to_name);
        $mail->XMailer = ' ';

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#0d6efd,#0a58ca);padding:25px;text-align:center;border-radius:10px 10px 0 0;'>
                <h1 style='color:white;margin:0;font-size:24px;'>🏥 MediCare</h1>
                <p style='color:#AACCFF;margin:5px 0 0;'>MediCare 3-Tier Telemedicine Platform</p>
            </div>
            <div style='background:#f8f9fa;padding:30px;border-radius:0 0 10px 10px;'>
                <p style='font-size:16px;color:#333;'>Dear <strong>$to_name</strong>,</p>
                $message
                <hr style='border:1px solid #e0e0e0;margin:20px 0;'>
                <p style='font-size:12px;color:#888;text-align:center;'>
                    This is an automated email from MediCare 3-Tier Telemedicine Platform.<br>
                    Government Degree College Hayatabad — FYP Project
                </p>
            </div>
        </div>";

        $mail->send();

        // Success log
        $log_entry = date('Y-m-d H:i:s') . " | SUCCESS | To: $to_email | Subject: $subject\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);

        return true;

    } catch (Exception $e) {

        // Error log
        $log_entry = date('Y-m-d H:i:s') . " | FAILED | To: $to_email | Subject: $subject | Error: " . $mail->ErrorInfo . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);

        return false;
    }
}
?>