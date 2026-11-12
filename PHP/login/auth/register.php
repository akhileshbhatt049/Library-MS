<?php

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Method not allowed', 405); }

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { jsonError('Invalid request body'); }

requireCSRF($input);

$fullname        = trim($input['fullname']         ?? '');
$email           = trim(strtolower($input['email'] ?? ''));
$phone           = trim($input['phone']            ?? '');
$password        = $input['password']              ?? '';
$confirmPassword = $input['confirmPassword']       ?? '';

// Validate 
if (strlen($fullname) < 2 || strlen($fullname) > 100) { jsonError('Full name must be 2–100 characters'); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        { jsonError('Invalid email address'); }
if (!preg_match('/^[0-9]{7,15}$/', $phone))            { jsonError('Phone must be 7–15 digits only'); }
if (strlen($password) < 8)                             { jsonError('Password must be at least 8 characters'); }
if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    jsonError('Password must contain both letters and numbers');
}
if ($password !== $confirmPassword) { jsonError('Passwords do not match'); }

$db = getDB();

// Duplicate checks 
$chk = $db->prepare("SELECT user_id FROM Accounts WHERE email = ? OR phone = ? LIMIT 1");
$chk->bind_param('ss', $email, $phone);
$chk->execute();
$dup = $chk->get_result()->fetch_assoc();
$chk->close();

if ($dup) {
    // Determine which field is duplicated
    $e = $db->prepare("SELECT user_id FROM Accounts WHERE email = ? LIMIT 1");
    $e->bind_param('s', $email); $e->execute();
    $emailExists = $e->get_result()->num_rows > 0; $e->close();
    jsonError($emailExists ? 'Email is already registered' : 'Phone number is already registered');
}

// ── Insert 
$user_id  = 'USR' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$status   = 'active';
$is_admin = 0;

$stmt = $db->prepare(
    "INSERT INTO Accounts (user_id, fullname, email, phone, password, is_admin, status) VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssssis', $user_id, $fullname, $email, $phone, $hashed, $is_admin, $status);

if ($stmt->execute()) {
    $stmt->close();
    jsonSuccess('Registration successful! You can now log in.');
} else {
    $stmt->close();
    jsonError('Registration failed. Please try again.');
}