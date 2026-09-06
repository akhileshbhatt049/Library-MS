<?php

/**
 * Verify OTP — Library Management System
 * Validates submitted OTP against hashed DB record.
 * Max 3 wrong attempts before OTP is invalidated.
 */

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonError('Invalid request body');
}

requireCSRF($input);

$email = trim(strtolower($input['email'] ?? ''));
$otp   = trim($input['otp'] ?? '');

if (!$email || !$otp) {
    jsonError('Email and OTP are required');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email');
}
if (!preg_match('/^\d{6}$/', $otp)) {
    jsonError('OTP must be exactly 6 digits');
}

$conn = getDB();

// ── Fetch the latest valid OTP record ─────────────────────────
$stmt = $conn->prepare(
    "SELECT id, otp_hash, expires_at, attempts
     FROM PasswordResetOTP
     WHERE email = ? AND used = 0
     ORDER BY created_at ASC
     LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    jsonError('No active OTP found. Please request a new one.');
}

// ── Check expiry (60 seconds) ─────────────────────────────────
if (strtotime($record['expires_at']) < time()) {
    // Mark as used
    $exp = $conn->prepare("UPDATE PasswordResetOTP SET used = 1 WHERE id = ? LIMIT 1");
    $exp->bind_param('i', $record['id']);
    $exp->execute();
    $exp->close();
    jsonError('OTP has expired. Please request a new one.');
}

// ── Check attempt limit ───────────────────────────────────────
if ((int) $record['attempts'] >= 3) {
    $inv = $conn->prepare("UPDATE PasswordResetOTP SET used = 1 WHERE id = ? LIMIT 1");
    $inv->bind_param('i', $record['id']);
    $inv->execute();
    $inv->close();
    jsonError('Too many wrong attempts. Please request a new OTP.');
}

// ── Verify OTP (constant-time hash compare) ───────────────────
if (!password_verify($otp, $record['otp_hash'])) {
    $attempts = (int) $record['attempts'] + 1;
    $upd = $conn->prepare("UPDATE PasswordResetOTP SET attempts = ? WHERE id = ? LIMIT 1");
    $upd->bind_param('ii', $attempts, $record['id']);
    $upd->execute();
    $upd->close();

    $remaining = 3 - $attempts;
    jsonError("Incorrect OTP. {$remaining} attempt(s) remaining.");
}

// ── OTP valid — mark as used and store reset token in session ─
$inv = $conn->prepare("UPDATE PasswordResetOTP SET used = 1 WHERE id = ? LIMIT 1");
$inv->bind_param('i', $record['id']);
$inv->execute();
$inv->close();

// Store verified email in session — reset-password.php checks this
$_SESSION['pwd_reset_email']    = $email;
$_SESSION['pwd_reset_verified'] = true;
$_SESSION['pwd_reset_expires']  = time() + 300; // 5 min to set new password

jsonSuccess('OTP verified! You may now set a new password.');
