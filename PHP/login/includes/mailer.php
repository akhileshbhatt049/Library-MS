<?php

/**
 * Mailer Helper — Library Management System
 * Uses PHPMailer via Composer if installed, otherwise falls back to PHP mail().
 *
 * SETUP (one-time):
 *   1. Open terminal in project root
 *   2. Run:  composer require phpmailer/phpmailer
 *   3. Edit the CONFIG section below with your Gmail credentials
 *
 * For Gmail:
 *   - Enable 2-Step Verification on your Google account
 *   - Go to: https://myaccount.google.com/apppasswords
 *   - Create an App Password and paste it as MAIL_PASSWORD below
 */

// ── MAIL CONFIG — EDIT THESE ───────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your_email@gmail.com');   // ← your Gmail
define('MAIL_PASSWORD', 'your_app_password_here'); // ← Gmail App Password
define('MAIL_FROM',     'your_email@gmail.com');   // ← sender address
define('MAIL_FROM_NAME', 'LibraryMS');
// ──────────────────────────────────────────────────────────────

/**
 * Send an email.
 *
 * @param string $toEmail    Recipient email
 * @param string $toName     Recipient name
 * @param string $subject    Email subject
 * @param string $htmlBody   HTML message body
 * @return bool              True on success
 * @throws RuntimeException  On failure
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
  $autoload = __DIR__ . '/../../../../vendor/autoload.php';

  // ── Use PHPMailer if Composer is installed ──────────────────
  if (file_exists($autoload)) {
    require_once $autoload;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

    return $mail->send();
  }

  // ── Fallback: PHP mail() ────────────────────────────────────
  // For XAMPP: configure C:\xampp\php\php.ini:
  //   SMTP=smtp.gmail.com
  //   smtp_port=587
  //   sendmail_from=your_email@gmail.com
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";

  $result = mail($toEmail, $subject, $htmlBody, $headers);
  if (!$result) {
    throw new RuntimeException(
      'PHP mail() failed. Install PHPMailer via Composer or configure XAMPP SMTP in php.ini.'
    );
  }
  return true;
}

/**
 * Build a nicely styled OTP email HTML body.
 */
function buildOtpEmailHtml(string $otp, string $recipientName): string
{
  return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Inter, Arial, sans-serif; background: #f4f6fa; margin: 0; padding: 20px;">
  <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.1);">
    <div style="background: #1e3a5f; padding: 28px 32px;">
      <h1 style="color: #fff; margin: 0; font-size: 1.4rem;">🔐 LibraryMS Password Reset</h1>
    </div>
    <div style="padding: 32px;">
      <p style="color: #1a2332; font-size: 1rem; margin: 0 0 16px;">Hi <strong>{$recipientName}</strong>,</p>
      <p style="color: #4b5563; margin: 0 0 24px;">
        We received a request to reset your password. Use the OTP below — it expires in <strong>60 seconds</strong>.
      </p>
      <div style="background: #f0f4ff; border: 2px dashed #1e3a5f; border-radius: 10px; text-align: center; padding: 24px; margin-bottom: 24px;">
        <span style="font-size: 2.5rem; font-weight: 700; letter-spacing: 10px; color: #1e3a5f; font-family: monospace;">{$otp}</span>
        <p style="color: #6b7280; margin: 8px 0 0; font-size: 0.85rem;">⏱ Valid for 60 seconds only</p>
      </div>
      <p style="color: #6b7280; font-size: 0.85rem; margin: 0;">
        If you did not request this, please ignore this email. Your account is safe.
      </p>
    </div>
    <div style="background: #f8fafc; padding: 16px 32px; border-top: 1px solid #e2e8f0;">
      <p style="color: #9ca3af; font-size: 0.8rem; margin: 0;">
        &copy; LibraryMS &mdash; This is an automated message, please do not reply.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}
