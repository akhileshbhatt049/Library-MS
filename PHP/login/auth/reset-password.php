<?php
/**
 * Reset Password — Library Management System
 * Requires successful OTP verification (session token set by verify-otp.php).
 */

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// ── Verify OTP session token ──────────────────────────────────
if (empty($_SESSION['pwd_reset_verified'])
    || empty($_SESSION['pwd_reset_email'])
    || ($_SESSION['pwd_reset_expires'] ?? 0) < time()
) {
    // Clear any partial session data
    unset($_SESSION['pwd_reset_verified'], $_SESSION['pwd_reset_email'], $_SESSION['pwd_reset_expires']);
    jsonError('Your reset session has expired. Please request a new OTP.', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { jsonError('Invalid request body'); }

requireCSRF($input);

$password        = $input['password']        ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

if (strlen($password) < 8) {
    jsonError('Password must be at least 8 characters long');
}
if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    jsonError('Password must contain at least one letter and one number');
}
if ($password !== $confirmPassword) {
    jsonError('Passwords do not match');
}

$conn   = getDB();
$email  = $_SESSION['pwd_reset_email'];
$hashed = password_hash($password, PASSWORD_DEFAULT);

// ── Update password ───────────────────────────────────────────
$stmt = $conn->prepare(
    "UPDATE Accounts SET password = ?, failed_login_attempts = 0, login_locked_until = NULL
     WHERE email = ? LIMIT 1"
);
$stmt->bind_param('ss', $hashed, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();

    // Fetch user_id for activity log
    $u = $conn->prepare("SELECT user_id FROM Accounts WHERE email = ? LIMIT 1");
    $u->bind_param('s', $email);
    $u->execute();
    $uid = $u->get_result()->fetch_assoc()['user_id'] ?? null;
    $u->close();

    logActivity($conn, $uid, 'Password reset via OTP', 'Accounts', null);

    // Clear reset session keys
    unset($_SESSION['pwd_reset_verified'], $_SESSION['pwd_reset_email'], $_SESSION['pwd_reset_expires']);

    jsonSuccess('Password reset successful! You can now log in.');
} else {
    $stmt->close();
    jsonError('Failed to reset password. Please try again.');
}
