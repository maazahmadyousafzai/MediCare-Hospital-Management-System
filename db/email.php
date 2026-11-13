<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Configure these values in the server environment. Never commit SMTP secrets.
$smtpUsername = getenv('MEDICARE_SMTP_USERNAME') ?: '';
$smtpPassword = getenv('MEDICARE_SMTP_PASSWORD') ?: '';

if (!defined('ADMIN_NOTIFY_EMAIL')) {
    define('ADMIN_NOTIFY_EMAIL', getenv('MEDICARE_ADMIN_EMAIL') ?: 'admin@medicare.local');
}
if (!defined('ADMIN_NOTIFY_NAME')) {
    define('ADMIN_NOTIFY_NAME', getenv('MEDICARE_ADMIN_NAME') ?: 'MediCare Admin');
}

/**
 * Send an HTML email through the configured SMTP account.
 *
 * Returns false when SMTP configuration is missing or delivery fails.
 */
function sendEmail($to_email, $to_name, $subject, $message) {
    global $smtpUsername, $smtpPassword;

    if ($smtpUsername === '' || $smtpPassword === '') {
        error_log('MediCare email not sent: SMTP credentials are not configured.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtpUsername, 'MediCare Platform');
        $mail->addReplyTo($smtpUsername, 'MediCare Platform');
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#0d6efd,#0a58ca);padding:25px;text-align:center;border-radius:10px 10px 0 0;'>
                <h1 style='color:white;margin:0;font-size:24px;'>MediCare</h1>
                <p style='color:#AACCFF;margin:5px 0 0;'>MediCare 3-Tier Telemedicine Platform</p>
            </div>
            <div style='background:#f8f9fa;padding:30px;border-radius:0 0 10px 10px;'>
                <p style='font-size:16px;color:#333;'>Dear <strong>" . htmlspecialchars($to_name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                $message
                <hr style='border:1px solid #e0e0e0;margin:20px 0;'>
                <p style='font-size:12px;color:#888;text-align:center;'>
                    This is an automated email from MediCare 3-Tier Telemedicine Platform.<br>
                    Government Degree College Hayatabad — FYP Project
                </p>
            </div>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('MediCare email delivery failed: ' . $mail->ErrorInfo);
        return false;
    }
}
?>