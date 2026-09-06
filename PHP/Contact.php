<?php

/**
 * Contact Form Handler — Library Management System
 * DB columns: Name, Email, Message (not FullName)
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
requireCSRF();

$conn    = getDB();
$uid     = currentUserId();

// Match DB column names: Name, Email, Message
$name    = trim($_POST['Name']    ?? '');
$email   = trim($_POST['Email']   ?? '');
$message = trim($_POST['Message'] ?? '');

if (!$name || !$email || !$message) {
    jsonError('All fields are required');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email address');
}
if (strlen($message) < 10) {
    jsonError('Message must be at least 10 characters');
}
if (strlen($name) > 100 || strlen($message) > 5000) {
    jsonError('Input exceeds allowed length');
}

$stmt = $conn->prepare(
    "INSERT INTO Contacts (Name, Email, Message) VALUES (?, ?, ?)"
);
$stmt->bind_param('sss', $name, $email, $message);

if ($stmt->execute()) {
    $stmt->close();
    logActivity($conn, $uid, 'Submitted contact message', 'Contacts', null);
    jsonSuccess('Your message has been sent! We will get back to you soon.');
} else {
    $stmt->close();
    jsonError('Failed to send message. Please try again.');
}
