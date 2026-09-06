<?php

/**
 * Change Password (from Account page) — Library Management System
 * Requires current password for verification.
 */

require_once 'login/includes/session.php';
require_once 'login/config/database.php';
require_once 'login/includes/functions.php';
require_once 'login/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

requireUser();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonError('Invalid request body');
}

requireCSRF($input);

$currentPwd = $input['current_password'] ?? '';
$newPwd     = $input['new_password']     ?? '';
$confirmPwd = $input['confirm_password'] ?? '';
$userId     = currentUserId();

if (!$currentPwd || !$newPwd || !$confirmPwd) {
    jsonError('All fields are required');
}
if (strlen($newPwd) < 8) {
    jsonError('New password must be at least 8 characters');
}
if (!preg_match('/[a-zA-Z]/', $newPwd) || !preg_match('/[0-9]/', $newPwd)) {
    jsonError('New password must contain at least one letter and one number');
}
if ($newPwd !== $confirmPwd) {
    jsonError('New passwords do not match');
}
if ($currentPwd === $newPwd) {
    jsonError('New password must be different from current password');
}

$conn = getDB();

// ── Fetch current password hash ───────────────────────────────
$stmt = $conn->prepare("SELECT password FROM Accounts WHERE user_id = ? LIMIT 1");
$stmt->bind_param('s', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($currentPwd, $row['password'])) {
    jsonError('Current password is incorrect');
}

// ── Update password ───────────────────────────────────────────
$hashed = password_hash($newPwd, PASSWORD_DEFAULT);
$upd    = $conn->prepare("UPDATE Accounts SET password = ? WHERE user_id = ? LIMIT 1");
$upd->bind_param('ss', $hashed, $userId);

if ($upd->execute()) {
    $upd->close();
    logActivity($conn, $userId, 'Changed account password', 'Accounts', null);
    jsonSuccess('Password changed successfully!');
} else {
    $upd->close();
    jsonError('Failed to change password. Please try again.');
}
