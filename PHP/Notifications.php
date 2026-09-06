<?php

/**
 * Notifications API — Library Management System
 * Endpoints:
 *   GET  ?action=count      → {count: N}
 *   POST action=mark_read   → mark single notification read
 *   POST action=mark_all    → mark all notifications read
 *   GET  (default)          → {data: [...notifications]}
 */

require_once 'login/includes/session.php';
require_once 'login/config/database.php';
require_once 'login/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

requireUser();

$conn   = getDB();
$userId = currentUserId(); // VARCHAR, always use 's' bind_param
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

// ── COUNT UNREAD ──────────────────────────────────────────────
if ($action === 'count') {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM Notifications WHERE user_id = ? AND is_read = 0"
    );
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode(['success' => true, 'count' => (int) $row['cnt']]);
    exit;
}

// ── MARK SINGLE READ ─────────────────────────────────────────
if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'login/includes/csrf.php';
    requireCSRF();
    $notifId = (int) ($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $stmt = $conn->prepare(
            "UPDATE Notifications SET is_read = 1 WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->bind_param('is', $notifId, $userId); // id is int, user_id is string
        $stmt->execute();
        $stmt->close();
    }
    jsonSuccess('Marked as read');
}

// ── MARK ALL READ ─────────────────────────────────────────────
if ($action === 'mark_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'login/includes/csrf.php';
    requireCSRF();
    $stmt = $conn->prepare(
        "UPDATE Notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
    );
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->close();
    jsonSuccess('All notifications marked as read');
}

// ── DEFAULT: GET LIST ─────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT id, title, message, is_read, created_at
     FROM Notifications
     WHERE user_id = ?
     ORDER BY created_at ASC
     LIMIT 50"
);
$stmt->bind_param('s', $userId);
$stmt->execute();
$res    = $stmt->get_result();
$items  = [];
while ($row = $res->fetch_assoc()) {
    $row['time_ago'] = timeAgo($row['created_at']);
    $items[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $items]);
exit;
