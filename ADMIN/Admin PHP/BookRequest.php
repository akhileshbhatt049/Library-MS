<?php
/**
 * Admin Book Request Handler — Library Management System
 * Actions: approve, reject
 * Notifies the requesting user via in-app notification.
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

$conn    = getDB();
$action  = trim($_POST['action'] ?? '');
$id      = (int) ($_POST['id']     ?? 0);
$adminId = currentUserId();

if (!$id) { jsonError('Invalid request ID'); }

// Fetch request using prepared statement — no interpolation
$fetch = $conn->prepare(
    "SELECT r.*, a.user_id AS account_user_id
     FROM Request_Book r
     LEFT JOIN Accounts a ON a.email = r.Email
     WHERE r.id = ? LIMIT 1"
);
$fetch->bind_param('i', $id);
$fetch->execute();
$req = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$req) { jsonError('Book request not found', 404); }

$bookTitle      = $req['BookTitle'];
$requesterUserId = $req['account_user_id']; // may be null if account deleted

// ── APPROVE ──────────────────────────────────────────────────
if ($action === 'approve') {
    $stmt = $conn->prepare(
        "UPDATE Request_Book SET Status='Approved' WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $stmt->close();
        if ($requesterUserId) {
            createNotification(
                $conn,
                $requesterUserId,
                'Book Request Approved',
                "Great news! Your request for \"$bookTitle\" has been approved. We will add it to our collection soon."
            );
        }
        logActivity($conn, $adminId, "Approved book request: $bookTitle", 'Request_Book', $id);
        jsonSuccess('Book request approved');
    } else {
        $stmt->close();
        jsonError('Failed to approve book request');
    }
}

// ── REJECT ───────────────────────────────────────────────────
elseif ($action === 'reject') {
    $reason = trim($_POST['reason'] ?? 'No reason provided');

    $stmt = $conn->prepare(
        "UPDATE Request_Book SET Status='Rejected', RejectReason=? WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('si', $reason, $id);

    if ($stmt->execute()) {
        $stmt->close();
        if ($requesterUserId) {
            createNotification(
                $conn,
                $requesterUserId,
                'Book Request Not Approved',
                "Your request for \"$bookTitle\" could not be fulfilled. Reason: $reason"
            );
        }
        logActivity($conn, $adminId, "Rejected book request: $bookTitle", 'Request_Book', $id);
        jsonSuccess('Book request rejected');
    } else {
        $stmt->close();
        jsonError('Failed to reject book request');
    }
}

else {
    jsonError('Unknown action');
}
