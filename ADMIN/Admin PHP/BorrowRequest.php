<?php

/**
 * Admin Borrow Request Handler — Library Management System
 * Actions: approve, reject, return
 */

require_once '../../PHP/login/includes/session.php';
requireAdmin();
require_once '../../PHP/login/config/database.php';
require_once '../../PHP/login/includes/functions.php';
require_once '../../PHP/login/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

requireCSRF();

$conn   = getDB();
$action = trim($_POST['action'] ?? '');
$id     = (int) ($_POST['id']     ?? 0);

if (!$id) {
    jsonError('Invalid request ID');
}

// Fetch request using prepared statement (no direct interpolation)
$fetch = $conn->prepare("SELECT * FROM BorrowBook WHERE id = ? LIMIT 1");
$fetch->bind_param('i', $id);
$fetch->execute();
$borrow = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$borrow) {
    jsonError('Borrow request not found', 404);
}

$adminId  = currentUserId();
$bookName = $borrow['BookName'];
$isbn     = $borrow['ISBN'];
$email    = $borrow['Email'];     // used to find user_id for notification

// Look up the user_id from their email so we can create a notification
$userFetch = $conn->prepare("SELECT user_id FROM Accounts WHERE email = ? LIMIT 1");
$userFetch->bind_param('s', $email);
$userFetch->execute();
$userRow = $userFetch->get_result()->fetch_assoc();
$userFetch->close();
$studentUserId = $userRow['user_id'] ?? null;

// ── APPROVE ──────────────────────────────────────────────────
if ($action === 'approve') {
    $stmt = $conn->prepare(
        "UPDATE BorrowBook SET Status='Approved', BorrowDate=CURDATE() WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $stmt->close();

        // Decrement available copies using prepared statement
        $upd = $conn->prepare(
            "UPDATE ManageBooksAdmin
     SET AvailableCopies = AvailableCopies - 1
     WHERE ISBN = ? AND AvailableCopies > 0
     LIMIT 1"
        );

        $upd->bind_param('s', $isbn);
        $upd->execute();

        if ($upd->affected_rows !== 1) {
            $upd->close();
            $conn->rollback();
            jsonError('No available copy of this book.');
        }

        if ($studentUserId) {
            createNotification(
                $conn,
                $studentUserId,
                'Borrow Request Approved',
                "Your borrow request for \"$bookName\" has been approved! Please collect it within 2 working days."
            );
        }

        logActivity($conn, $adminId, "Approved borrow request for: $bookName", 'BorrowBook', $id);
        jsonSuccess('Borrow request approved');
    } else {
        $stmt->close();
        jsonError('Failed to approve request');
    }
}

// ── REJECT ───────────────────────────────────────────────────
elseif ($action === 'reject') {
    $reason = trim($_POST['reason'] ?? 'No reason provided');

    $stmt = $conn->prepare(
        "UPDATE BorrowBook SET Status='Rejected', RejectReason=? WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('si', $reason, $id);

    if ($stmt->execute()) {
        $stmt->close();

        if ($studentUserId) {
            createNotification(
                $conn,
                $studentUserId,
                'Borrow Request Rejected',
                "Your borrow request for \"$bookName\" was not approved. Reason: $reason"
            );
        }

        logActivity($conn, $adminId, "Rejected borrow request for: $bookName", 'BorrowBook', $id);
        jsonSuccess('Borrow request rejected');
    } else {
        $stmt->close();
        jsonError('Failed to reject request');
    }
}

// ── MARK RETURNED ────────────────────────────────────────────
elseif ($action === 'return') {
    $stmt = $conn->prepare(
        "UPDATE BorrowBook SET Status='Returned' WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $stmt->close();

        // Increment available copies
        $upd = $conn->prepare(
            "UPDATE ManageBooksAdmin SET AvailableCopies = AvailableCopies + 1 WHERE ISBN = ? LIMIT 1"
        );
        $upd->bind_param('s', $isbn);
        $upd->execute();
        $upd->close();

        if ($studentUserId) {
            createNotification(
                $conn,
                $studentUserId,
                'Book Returned',
                "\"$bookName\" has been marked as returned. Thank you!"
            );
        }

        logActivity($conn, $adminId, "Marked returned: $bookName", 'BorrowBook', $id);
        jsonSuccess('Book marked as returned');
    } else {
        $stmt->close();
        jsonError('Failed to mark as returned');
    }
} else {
    jsonError('Unknown action');
}
