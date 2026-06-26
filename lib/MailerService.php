<?php

class MailerService
{
    /**
     * Send email via Mailtrap SMTP.
     */
    public static function sendOTP(string $toEmail, string $toName, string $otp): array
    {
        $subject = "Verify Your UKMInvolve Account - OTP";
        
        $message = "
        <html>
        <head>
            <title>Verify Your UKMInvolve Account</title>
            <style>
                body { font-family: 'Outfit', 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px 20px; margin: 0; }
                .card { max-width: 480px; margin: 0 auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
                .logo { display: block; width: 48px; height: 48px; margin: 0 auto 16px; object-fit: contain; }
                .header { text-align: center; font-size: 24px; font-weight: 800; margin-bottom: 8px; color: #1e3a8a; }
                .subheader { text-align: center; font-size: 14px; color: #64748b; margin-bottom: 24px; }
                .otp-box { background: #eff6ff; border: 1px dashed #2563eb; color: #2563eb; font-size: 32px; font-weight: 900; text-align: center; padding: 16px; border-radius: 8px; letter-spacing: 6px; margin-bottom: 24px; }
                .expiry { text-align: center; font-size: 12px; color: #94a3b8; font-weight: 600; }
                .footer { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <img src='https://vfcaovmuirxjgwjpesyg.supabase.co/storage/v1/object/public/posters/UKM.png' class='logo' alt='UKM Logo'>
                <div class='header'>Verify Your Account</div>
                <div class='subheader'>Hi " . htmlspecialchars($toName) . ", use the One-Time Password (OTP) below to activate your UKMInvolve profile.</div>
                <div class='otp-box'>" . htmlspecialchars($otp) . "</div>
                <div class='expiry'>This code is valid for 15 minutes and can only be used once.</div>
                <div class='footer'>UKMInvolve Campus Portal &copy; " . date('Y') . " Universiti Kebangsaan Malaysia</div>
            </div>
        </body>
        </html>
        ";

        return self::sendSmtp($toEmail, $subject, $message);
    }

    /**
     * Send password reset link email via Mailtrap SMTP.
     */
    public static function sendPasswordResetLink(string $toEmail, string $toName, string $resetLink): array
    {
        $subject = "Reset Your UKMInvolve Password";
        
        $message = "
        <html>
        <head>
            <title>Reset Your UKMInvolve Password</title>
            <style>
                body { font-family: 'Outfit', 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px 20px; margin: 0; }
                .card { max-width: 480px; margin: 0 auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
                .logo { display: block; width: 48px; height: 48px; margin: 0 auto 16px; object-fit: contain; }
                .header { text-align: center; font-size: 24px; font-weight: 800; margin-bottom: 8px; color: #1e3a8a; }
                .subheader { text-align: center; font-size: 14px; color: #64748b; margin-bottom: 24px; }
                .btn { display: inline-block; background: #2563eb; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-align: center; margin: 0 auto; display: table; }
                .expiry { text-align: center; font-size: 12px; color: #94a3b8; font-weight: 600; margin-top: 24px; }
                .footer { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <img src='https://vfcaovmuirxjgwjpesyg.supabase.co/storage/v1/object/public/posters/UKM.png' class='logo' alt='UKM Logo'>
                <div class='header'>Reset Password</div>
                <div class='subheader'>Hi " . htmlspecialchars($toName) . ", you recently requested to reset your password for your UKMInvolve account. Click the button below to reset it.</div>
                <a href='" . htmlspecialchars($resetLink) . "' class='btn'>Reset Password</a>
                <div class='expiry'>If you did not request a password reset, please ignore this email or reply to let us know. This password reset is only valid for the next 60 minutes.</div>
                <div class='footer'>UKMInvolve Campus Portal &copy; " . date('Y') . " Universiti Kebangsaan Malaysia</div>
            </div>
        </body>
        </html>
        ";

        return self::sendSmtp($toEmail, $subject, $message);
    }

    private static function sendSmtp(string $to, string $subject, string $message): array
    {
        $host = $_ENV['MAILTRAP_HOST'] ?? 'sandbox.smtp.mailtrap.io';
        $port = (int)($_ENV['MAILTRAP_PORT'] ?? 2525);
        $username = $_ENV['MAILTRAP_USERNAME'] ?? '';
        $password = $_ENV['MAILTRAP_PASSWORD'] ?? '';

        if (empty($username) || empty($password)) {
            // Graceful success fallback for local setups without configured .env keys
            return ['ok' => true, 'warning' => 'Mailtrap credentials not configured. OTP stored in database.'];
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) {
            return ['ok' => false, 'error' => "Failed to connect to SMTP server: $errstr ($errno)"];
        }

        // Set stream timeout to prevent infinite blocking
        stream_set_timeout($socket, 10);

        // Read greeting
        $greeting = fgets($socket, 512);
        if ($greeting === false) {
            fclose($socket);
            return ['ok' => false, 'error' => "SMTP Server did not respond (timeout)"];
        }

        fwrite($socket, "EHLO localhost\r\n");
        $res = fgets($socket, 512);
        if ($res === false) {
            fclose($socket);
            return ['ok' => false, 'error' => "SMTP connection timed out on EHLO"];
        }
        
        while ($res && strlen($res) >= 4 && substr($res, 3, 1) === '-') {
            $res = fgets($socket, 512);
        }

        // Auth login
        fwrite($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);

        fwrite($socket, base64_encode($username) . "\r\n");
        fgets($socket, 512);

        fwrite($socket, base64_encode($password) . "\r\n");
        $res = fgets($socket, 512);
        if (!str_starts_with($res, '235')) {
            fclose($socket);
            return ['ok' => false, 'error' => "SMTP Authentication failed: " . trim($res)];
        }

        // Mail From
        fwrite($socket, "MAIL FROM: <noreply@ukminvolve.edu.my>\r\n");
        fgets($socket, 512);

        // Rcpt To
        fwrite($socket, "RCPT TO: <$to>\r\n");
        $res = fgets($socket, 512);
        if (!str_starts_with($res, '250') && !str_starts_with($res, '251')) {
            fclose($socket);
            return ['ok' => false, 'error' => "Recipient rejected: " . trim($res)];
        }

        // Data
        fwrite($socket, "DATA\r\n");
        fgets($socket, 512);

        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: UKMInvolve <noreply@ukminvolve.edu.my>",
            "To: <$to>",
            "Subject: $subject"
        ];

        $emailContent = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.\r\n";
        fwrite($socket, $emailContent);
        $res = fgets($socket, 512);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return ['ok' => true];
    }
}
