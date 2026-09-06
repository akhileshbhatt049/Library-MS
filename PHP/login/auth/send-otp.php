<?php
/**
 * Send OTP — Library Management System
 * Generates a 6-digit OTP, hashes it, stores in DB, emails it.
 * Rate limit: max 3 OTP requests per email per 5 minutes.
 */

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { jsonError('Invalid request body'); }

requireCSRF($input);

$email = trim(strtolower($input['email'] ?? ''));
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('A valid email address is required');
}

$conn = getDB();

// ── Verify email exists in accounts ──────────────────────────
$chk = $conn->prepare(
    "SELECT user_id, fullname, status FROM Accounts WHERE email = ? LIMIT 1"
);
$chk->bind_param('s', $email);
$chk->execute();
$account = $chk->get_result()->fetch_assoc();
$chk->close();

// Do NOT reveal whether email exists (timing attack prevention)
// Always respond with same message
if (!$account) {
    // Still delay to prevent enumeration
    sleep(1);
    jsonSuccess('If that email is registered, an OTP has been sent.');
}

if ($account['status'] === 'suspended') {
    jsonError('This account has been suspended. Contact the administrator.');
}

// ── Rate limit: max 3 OTP requests per 5 minutes ─────────────
$rateChk = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM PasswordResetOTP
     WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
);
$rateChk->bind_param('s', $email);
$rateChk->execute();
$rate = $rateChk->get_result()->fetch_assoc();
$rateChk->close();

if ((int) $rate['cnt'] >= 3) {
    jsonError('Too many OTP requests. Please wait 5 minutes before trying again.');
}

// ── Invalidate any previous unused OTPs for this email ───────
$inv = $conn->prepare(
    "UPDATE PasswordResetOTP SET used = 1 WHERE email = ? AND used = 0"
);
$inv->bind_param('s', $email);
$inv->execute();
$inv->close();

// ── Generate OTP ──────────────────────────────────────────────
$otp       = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$otpHash   = password_hash($otp, PASSWORD_DEFAULT);
$expiresAt = date('Y-m-d H:i:s', time() + 60); // 60 seconds

// ── Store hashed OTP ──────────────────────────────────────────
$ins = $conn->prepare(
    "INSERT INTO PasswordResetOTP (email, otp_hash, expires_at) VALUES (?, ?, ?)"
);
$ins->bind_param('sss', $email, $otpHash, $expiresAt);
if (!$ins->execute()) {
    $ins->close();
    jsonError('Failed to generate OTP. Please try again.');
}
$ins->close();

// ── Send email ────────────────────────────────────────────────
try {
    $html = buildOtpEmailHtml($otp, $account['fullname']);
    sendMail($email, $account['fullname'], 'LibraryMS — Your Password Reset OTP', $html);
} catch (Throwable $e) {
    // Rollback: mark OTP as used so it can't be exploited if mail fails
    $rb = $conn->prepare("UPDATE PasswordResetOTP SET used = 1 WHERE email = ? AND otp_hash = ? LIMIT 1");
    $rb->bind_param('ss', $email, $otpHash);
    $rb->execute();
    $rb->close();

    error_log('OTP mail error: ' . $e->getMessage());
    jsonError('Failed to send OTP email. Check mailer configuration in PHP/login/includes/mailer.php.');
}

jsonSuccess('OTP sent! Check your email. It expires in 60 seconds.');
