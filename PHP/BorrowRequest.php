<?php

/**
 * User Borrow Request Handler — Library Management System
 * Accepts multipart/form-data POST from BorrowBooks.php page.
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

$conn = getDB();

// Collect & sanitize inputs
$bookName   = trim($_POST['BookName']   ?? '');
$author     = trim($_POST['Author']     ?? '');
$isbn       = trim($_POST['ISBN']       ?? '');
$studentId  = trim($_POST['StudentID']  ?? '');
$phone      = trim($_POST['phone']      ?? '');
$returnDate = trim($_POST['ReturnDate'] ?? '');

$name  = currentUserName();
$email = currentUserEmail();
$uid   = currentUserId();

// ── Validation ────────────────────────────────────────────────
if (!$bookName || !$author || !$isbn || !$studentId || !$phone || !$returnDate) {
    jsonError('All fields are required');
}

if (!preg_match('/^\d{7,15}$/', $phone)) {
    jsonError('Phone must be 7–15 digits');
}

// Return date: must be tomorrow – 30 days from now
$todayTs      = strtotime('today');
$returnTs     = strtotime($returnDate);
$maxReturnTs  = strtotime('+30 days');
if (!$returnTs || $returnTs <= $todayTs || $returnTs > $maxReturnTs) {
    jsonError('Return date must be between tomorrow and 30 days from now');
}

// ── Book availability check ──────────────────────────────────
$chk = $conn->prepare(
    "SELECT AvailableCopies, is_archived FROM ManageBooksAdmin WHERE ISBN = ? LIMIT 1"
);
$chk->bind_param('s', $isbn);
$chk->execute();
$book = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$book) {
    jsonError('Book not found in our records');
}
if ($book['is_archived']) {
    jsonError('This book is no longer available in our library');
}
if ((int) $book['AvailableCopies'] <= 0) {
    jsonError('No copies are currently available. You may request this book instead.');
}

// ── Duplicate request check ──────────────────────────────────
$dup = $conn->prepare(
    "SELECT id FROM BorrowBook
     WHERE Email = ? AND ISBN = ? AND Status IN ('Pending','Approved')
     LIMIT 1"
);
$dup->bind_param('ss', $email, $isbn);
$dup->execute();
$hasDup = $dup->get_result()->num_rows > 0;
$dup->close();

if ($hasDup) {
    jsonError('You already have a pending or active borrow request for this book');
}

// ── Insert borrow request ────────────────────────────────────
$status = 'Pending';
$stmt   = $conn->prepare(
    "INSERT INTO BorrowBook (BookName, Author, ISBN, Name, StudentID, Email, phone, ReturnDate, Status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'sssssssss',
    $bookName,
    $author,
    $isbn,
    $name,
    $studentId,
    $email,
    $phone,
    $returnDate,
    $status
);

if ($stmt->execute()) {
    $stmt->close();
    createNotification(
        $conn,
        $uid,
        'Borrow Request Submitted',
        "Your request to borrow \"$bookName\" is pending approval. Expected return: $returnDate."
    );
    logActivity($conn, $uid, "Submitted borrow request: $bookName", 'BorrowBook', null);
    jsonSuccess('Borrow request submitted successfully!');
} else {
    $stmt->close();
    jsonError('Failed to submit borrow request. Please try again.');
}
