<?php

require "SMTP.php";
require "PHPMailer.php";
require "Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{

    // Send password reset code
    public static function sendResetCode($email, $name, $code)
    {
        try {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';      // Configure as needed
            $mail->SMTPAuth = true;
            $mail->Username = 'gangulibandara7@gmail.com';                 // Add your email
            $mail->Password = 'tval vgkp tvrq vpps';                 // Add your password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('noreply@skillshop.com', 'SkillShop');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your SkillShop Password Reset Code';

            $html = "
                <html>
                <body style='font-family:Arial;'>
                    <div style='max-width:500px;margin:20px auto;background:#fff;padding:30px;border-radius:8px;'>
                        <div style='background:linear-gradient(to right, #2563eb, #4f46e5);color:white;padding:20px;border-radius:8px;text-align:center;'>
                            <h1 style='margin:0;'>SkillShop</h1>
                        </div>
                        <p>Hi " . htmlspecialchars($name) . ",</p>
                        <p>Your verification code is:</p>
                        <div style='font-size:32px;font-weight:bold;letter-spacing:5px;text-align:center;background:#f0f0f0;padding:15px;border-radius:8px;margin:20px 0;color:#2563eb;font-family:monospace;'>$code</div>
                        <p>Code expires in 10 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p style='color:#999;font-size:12px;'>© 2026 SkillShop</p>
                    </div>
                </body>
                </html>
            ";

            $mail->Body = $html;

            // Send via SMTP
            if ($mail->send()) {
                error_log("Email sent to $email");
                return true;
            } else {
                error_log("Email failed: " . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }

    // Send Admin verification code
    public static function sendAdminVerificationCode($email, $name, $code)
    {
        try {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';      // Configure as needed
            $mail->SMTPAuth = true;
            $mail->Username = 'gangulibandara7@gmail.com';                 // Add your email
            $mail->Password = 'tval vgkp tvrq vpps';                 // Add your password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('noreply@skillshop.com', 'SkillShop');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'SkillShop Admin Login Code';

            $html = "
                <html>
                <body style='font-family:Arial;'>
                    <div style='max-width:500px;margin:20px auto;background:#fff;padding:30px;border-radius:8px;'>
                        <div style='background:linear-gradient(to right, #2563eb, #4f46e5);color:white;padding:20px;border-radius:8px;text-align:center;'>
                            <h1 style='margin:0;'>SkillShop</h1>
                        </div>
                        <p>Hi " . htmlspecialchars($name) . ",</p>
                        <p>Your verification code is:</p>
                        <div style='font-size:32px;font-weight:bold;letter-spacing:5px;text-align:center;background:#f0f0f0;padding:15px;border-radius:8px;margin:20px 0;color:#2563eb;font-family:monospace;'>$code</div>
                        <p>Code expires in 10 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p style='color:#999;font-size:12px;'>© 2026 SkillShop</p>
                    </div>
                </body>
                </html>
            ";

            $mail->Body = $html;

            // Send via SMTP
            if ($mail->send()) {
                error_log("Admin Email sent to $email");
                return true;
            } else {
                error_log("Admin Email failed: " . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log("Admin Email error: " . $e->getMessage());
            return false;
        }
    }
}
