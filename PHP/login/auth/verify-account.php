<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo jsonError('Invalid request method');
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo jsonError('Invalid JSON input');
    exit;
}

requireCSRF($input);

$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');

if (empty($email) || empty($phone)) {
    echo jsonError('Email and phone are required');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT user_id FROM Accounts WHERE email = ? AND phone = ?");
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo jsonError('No matching account found for the provided details');
    exit;
}

$user = $result->fetch_assoc();
$_SESSION['reset_user_id'] = $user['user_id'];

echo jsonSuccess('Identity verified. You can now reset your password.');
