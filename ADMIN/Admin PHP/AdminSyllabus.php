<?php
/**
 * Admin Syllabus Handler — Library Management System
 * Actions: upload, delete
 * Uses Course_PDF column (matches DB schema)
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
$userId = currentUserId();

// ── UPLOAD SYLLABUS ──────────────────────────────────────────
if ($action === 'upload') {
    $courseName  = trim($_POST['Course_Name']  ?? '');
    $semester    = trim($_POST['Semester']     ?? '');
    $subjectName = trim($_POST['Subject_Name'] ?? '');

    if (!$courseName || !$semester || !$subjectName) {
        jsonError('Course Name, Semester, and Subject Name are required');
    }

    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        jsonError('A valid PDF file is required');
    }

    try {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-Management-System/ADMIN/uploads/syllabus/';
        $filename  = handleUpload(
            $_FILES['pdf'],
            $uploadDir,
            ['application/pdf'],
            20 * 1024 * 1024 // 20 MB
        );
    } catch (RuntimeException $e) {
        jsonError('PDF upload failed: ' . $e->getMessage());
    }

    $stmt = $conn->prepare(
        "INSERT INTO ADMIN_SYLLABUS (Course_Name, Semester, Subject_Name, Course_PDF) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('ssss', $courseName, $semester, $subjectName, $filename);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        logActivity($conn, $userId, "Uploaded syllabus: $subjectName ($courseName)", 'ADMIN_SYLLABUS', $newId);
        jsonSuccess('Syllabus uploaded successfully', ['id' => $newId, 'filename' => $filename]);
    } else {
        $stmt->close();
        // Clean up orphaned upload on DB failure
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-Management-System/ADMIN/uploads/syllabus/' . $filename;
        if (is_file($fullPath)) { @unlink($fullPath); }
        jsonError('Failed to save syllabus record');
    }
}

// ── DELETE SYLLABUS ──────────────────────────────────────────
elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) { jsonError('Invalid ID'); }

    // Fetch record first (prepared, no injection)
    $fetch = $conn->prepare("SELECT Course_PDF, Subject_Name FROM ADMIN_SYLLABUS WHERE id = ? LIMIT 1");
    $fetch->bind_param('i', $id);
    $fetch->execute();
    $row = $fetch->get_result()->fetch_assoc();
    $fetch->close();

    if (!$row) { jsonError('Syllabus record not found', 404); }

    $del = $conn->prepare("DELETE FROM ADMIN_SYLLABUS WHERE id = ? LIMIT 1");
    $del->bind_param('i', $id);

    if ($del->execute()) {
        $del->close();
        // Delete the PDF file from disk
        $pdfPath = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-Management-System/ADMIN/uploads/syllabus/' . $row['Course_PDF'];
        if (is_file($pdfPath)) { @unlink($pdfPath); }
        logActivity($conn, $userId, "Deleted syllabus: {$row['Subject_Name']}", 'ADMIN_SYLLABUS', $id);
        jsonSuccess('Syllabus deleted successfully');
    } else {
        $del->close();
        jsonError('Failed to delete syllabus record');
    }
}

else {
    jsonError('Unknown action');
}
