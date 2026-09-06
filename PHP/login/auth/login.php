<?php
/**
 * Login Handler — Library Management System
 * Features: rate limiting (5 attempts → 15 min lockout),
 *           session fixation prevention, brute-force protection.
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
if (!is_array($input)) { jsonError('Invalid request body'); }

requireCSRF($input);

$email    = trim($input['email']    ?? '');
$password = $input['password']      ?? '';

if (!$email || !$password) { jsonError('Email and password are required'); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { jsonError('Invalid email format'); }

$conn = getDB();

// ── Fetch user ────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT user_id, fullname, email, password, is_admin, status,
            failed_login_attempts, login_locked_until
     FROM Accounts WHERE email = ? LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Use identical timing to prevent user enumeration
if (!$user) {
    // Fake verify to waste attacker time
    password_verify($password, '$2y$12$fakehashfakehashfakehashfakehashfakehash');
    jsonError('Invalid email or password');
}

// ── Check account lock ────────────────────────────────────────
if ($user['login_locked_until'] && strtotime($user['login_locked_until']) > time()) {
    $remaining = ceil((strtotime($user['login_locked_until']) - time()) / 60);
    jsonError("Account temporarily locked due to too many failed attempts. Try again in {$remaining} minute(s).");
}

// ── Check status ──────────────────────────────────────────────
if ($user['status'] === 'suspended') {
    jsonError('Your account has been suspended. Contact the administrator.');
}

// ── Verify password ───────────────────────────────────────────
if (!password_verify($password, $user['password'])) {
    // Increment failed attempts
    $attempts = (int) $user['failed_login_attempts'] + 1;
    $lockUntil = null;
    $lockMsg   = '';

    if ($attempts >= 5) {
        $lockUntil = date('Y-m-d H:i:s', time() + 15 * 60); // 15 min
        $lockMsg   = ' Account locked for 15 minutes.';
        $attempts  = 0; // reset counter after locking
    }

    $upd = $conn->prepare(
        "UPDATE Accounts SET failed_login_attempts = ?, login_locked_until = ? WHERE email = ? LIMIT 1"
    );
    $upd->bind_param('iss', $attempts, $lockUntil, $email);
    $upd->execute();
    $upd->close();

    $left = max(0, 5 - (int) $user['failed_login_attempts'] - 1);
    jsonError("Invalid email or password." . ($left > 0 ? " {$left} attempt(s) remaining." : $lockMsg));
}

// ── Successful login ──────────────────────────────────────────
// Reset failed attempts, update last_login
$upd = $conn->prepare(
    "UPDATE Accounts SET failed_login_attempts = 0, login_locked_until = NULL, last_login = NOW()
     WHERE email = ? LIMIT 1"
);
$upd->bind_param('s', $email);
$upd->execute();
$upd->close();

// Regenerate session and set vars (prevents session fixation)
loginUser($user);

logActivity($conn, $user['user_id'], 'User logged in', 'Accounts', null);

$redirect = $user['is_admin']
    ? '/PHP_programs/Library-Management-System/ADMIN/AdministratorArea.php'
    : '/PHP_programs/Library-Management-System/Home.php';

jsonSuccess('Login successful', ['redirect' => $redirect]);
