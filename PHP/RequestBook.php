<?php

/**
 * User Book Request Handler — Library Management System
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

$conn   = getDB();
$uid    = currentUserId();

// Collect inputs — match DB column names: Name, Email, BookTitle, Author, Reason
$bookTitle = trim($_POST['BookTitle'] ?? '');
$author    = trim($_POST['Author']    ?? '');
$reason    = trim($_POST['Reason']    ?? '');
$name      = currentUserName();
$email     = currentUserEmail();

// Validate
if (!$bookTitle || !$author || !$reason) {
    jsonError('All fields are required');
}
if (strlen($reason) < 20) {
    jsonError('Reason must be at least 20 characters');
}
if (strlen($bookTitle) > 200 || strlen($author) > 150) {
    jsonError('Input exceeds maximum length');
}

$status = 'Pending';
$stmt   = $conn->prepare(
    "INSERT INTO Request_Book (Name, Email, BookTitle, Author, Reason, Status)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('ssssss', $name, $email, $bookTitle, $author, $reason, $status);

if ($stmt->execute()) {
    $stmt->close();
    createNotification(
        $conn,
        $uid,
        'Book Request Received',
        "We received your request for \"$bookTitle\". Our team will review it soon."
    );
    logActivity($conn, $uid, "Submitted book request: $bookTitle", 'Request_Book', null);
    jsonSuccess('Book request submitted successfully!');
} else {
    $stmt->close();
    jsonError('Failed to submit book request. Please try again.');
}
