<?php

/**
 * Upload / Update Profile Avatar — Library Management System
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
requireCSRF();   // multipart form POST — reads $_POST['csrf_token']

$conn   = getDB();
$userId = currentUserId();

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Please select a valid image file');
}

try {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-MS/Assets/uploads/avatars/';
    $filename  = handleUpload(
        $_FILES['avatar'],
        $uploadDir,
        ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        2 * 1024 * 1024  // 2 MB
    );
} catch (RuntimeException $e) {
    jsonError($e->getMessage());
}

// ── Fetch & delete old avatar ────────────────────────────────
$old = $conn->prepare("SELECT profile_image FROM Accounts WHERE user_id = ? LIMIT 1");
$old->bind_param('s', $userId);
$old->execute();
$row = $old->get_result()->fetch_assoc();
$old->close();

if (!empty($row['profile_image'])) {
    $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-MS/Assets/uploads/avatars/' . $row['profile_image'];
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

// ── Update DB ─────────────────────────────────────────────────
$stmt = $conn->prepare("UPDATE Accounts SET profile_image = ? WHERE user_id = ? LIMIT 1");
$stmt->bind_param('ss', $filename, $userId);

if ($stmt->execute()) {
    $stmt->close();

    // Update session name display if needed
    logActivity($conn, $userId, 'Updated profile picture', 'Accounts', null);

    $avatarUrl = '/PHP_programs/Library-MS/Assets/uploads/avatars/' . $filename;
    jsonSuccess('Profile picture updated!', ['avatar_url' => $avatarUrl]);
} else {
    $stmt->close();
    // Clean up orphaned file
    @unlink($uploadDir . $filename);
    jsonError('Failed to save profile picture. Please try again.');
}
