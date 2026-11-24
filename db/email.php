<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Keep SMTP credentials outside the public repository. Environment variables
// take precedence; the optional private file supports local XAMPP setup.
$smtpConfig = [];
$configPath = dirname(__DIR__, 3) . '/secure/medicare-smtp.php';

if (is_file($configPath)) {
    $loadedConfig = require $configPath;
    if (is_array($loadedConfig)) {
        $smtpConfig = $loadedConfig;
    }
}

$smtpUsername = getenv('MEDICARE_SMTP_USERNAME') ?: ($smtpConfig['username'] ?? '');
$smtpPassword = getenv('MEDICARE_SMTP_PASSWORD') ?: ($smtpConfig['password'] ?? '');

if (!defined('ADMIN_NOTIFY_EMAIL')) {
    define(
        'ADMIN_NOTIFY_EMAIL',
        getenv('MEDICARE_ADMIN_EMAIL') ?: ($smtpConfig['admin_email'] ?? '')
    );
}

if (!defined('ADMIN_NOTIFY_NAME')) {
    define(
        'ADMIN_NOTIFY_NAME',
        getenv('MEDICARE_ADMIN_NAME') ?: ($smtpConfig['admin_name'] ?? 'MediCare Admin')
    );
}

/**
 * Send an HTML email through Gmail SMTP.
 * Returns false when private SMTP settings are not configured.
 */
function sendEmail($to_email, $to_name, $subject, $message) {
    global $smtpUsername, $smtpPassword;

    if ($smtpUsername === '' || $smtpPassword === '') {
        error_log('MediCare email: SMTP credentials are not configured.');
        return false;
    }

    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        error_log('MediCare email: Invalid recipient address.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 15;

        $mail->setFrom($smtpUsername, 'MediCare Platform');
        $mail->addReplyTo($smtpUsername, 'MediCare Platform');
        $mail->addAddress($to_email, $to_name);

        $safeName = htmlspecialchars($to_name, ENT_QUOTES, 'UTF-8');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#0d6efd,#0a58ca);
                        padding:25px;text-align:center;
                        border-radius:10px 10px 0 0;'>
                <h1 style='color:white;margin:0;font-size:24px;'>MediCare</h1>
                <p style='color:#AACCFF;margin:5px 0 0;'>
                    MediCare Hospital Management System
                </p>
            </div>

            <div style='background:#f8f9fa;padding:30px;
                        border-radius:0 0 10px 10px;'>
                <p style='font-size:16px;color:#333;'>
                    Dear <strong>{$safeName}</strong>,
                </p>

                {$message}

                <hr style='border:1px solid #e0e0e0;margin:20px 0;'>

                <p style='font-size:12px;color:#888;text-align:center;'>
                    This is an automated email from MediCare Hospital <br>
                    Government Degree College Hayatabad — FYP Project
                </p>
            </div>
        </div>";

        $mail->AltBody = strip_tags(
            str_replace(['<br>', '<br/>', '<br />'], "\n", $message)
        );

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('MediCare email delivery failed: ' . $mail->ErrorInfo);
        return false;
    }
}
?>