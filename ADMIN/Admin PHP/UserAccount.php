<?php
/**
 * Admin User Account Handler — Library Management System
 * Actions: toggle_status (active ↔ suspended)
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

if (!$id) { jsonError('Invalid user ID'); }

// ── TOGGLE STATUS ─────────────────────────────────────────────
if ($action === 'toggle_status') {
    // Prevent suspending admins — double safety check
    $verify = $conn->prepare(
        "SELECT user_id, fullname, status FROM Accounts WHERE id = ? AND is_admin = 0 LIMIT 1"
    );
    $verify->bind_param('i', $id);
    $verify->execute();
    $user = $verify->get_result()->fetch_assoc();
    $verify->close();

    if (!$user) {
        jsonError('User not found or cannot modify admin accounts', 403);
    }

    $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
    $stmt = $conn->prepare(
        "UPDATE Accounts SET status = ? WHERE id = ? AND is_admin = 0 LIMIT 1"
    );
    $stmt->bind_param('si', $newStatus, $id);

    if ($stmt->execute()) {
        $stmt->close();
        $action_ASC = $newStatus === 'suspended' ? 'Suspended user' : 'Activated user';
        logActivity($conn, $adminId, "$action_ASC: {$user['fullname']}", 'Accounts', $id);
        jsonSuccess("Account $newStatus successfully", ['new_status' => $newStatus]);
    } else {
        $stmt->close();
        jsonError('Failed to update account status');
    }
}

else {
    jsonError('Unknown action');
}
