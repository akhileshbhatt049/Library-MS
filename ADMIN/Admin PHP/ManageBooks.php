<?php
/**
 * Admin Book Management Handler — Library Management System
 * Handles: add, edit, archive, delete book actions
 * Accepts multipart/form-data POST (for file uploads)
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

// CSRF from multipart POST
requireCSRF();

$conn   = getDB();
$action = trim($_POST['action'] ?? '');
$userId = currentUserId();

// ── ADD BOOK ──────────────────────────────────────────────────
if ($action === 'add') {
    $bookName = trim($_POST['BookName']     ?? '');
    $author   = trim($_POST['Author']       ?? '');
    $isbn     = trim($_POST['ISBN']         ?? '');
    $category = trim($_POST['Category']     ?? 'General');
    $ASC     = trim($_POST['ASCription']  ?? '');
    $copies   = max(1, (int) ($_POST['TotalCopies'] ?? 1));

    if (!$bookName || !$author || !$isbn) {
        jsonError('Book Name, Author, and ISBN are required');
    }

    // Handle cover image upload
    $cover = null;
    if (isset($_FILES['CoverImage']) && $_FILES['CoverImage']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-MS/Assets/uploads/books/';
            $filename  = handleUpload(
                $_FILES['CoverImage'],
                $uploadDir,
                ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                2 * 1024 * 1024 // 2 MB
            );
            $cover = $filename;
        } catch (RuntimeException $e) {
            jsonError('Image upload failed: ' . $e->getMessage());
        }
    }

    // Check ISBN uniqueness
    $chk = $conn->prepare("SELECT id FROM ManageBooksAdmin WHERE ISBN = ? LIMIT 1");
    $chk->bind_param('s', $isbn);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        jsonError('A book with this ISBN already exists');
    }
    $chk->close();

    $stmt = $conn->prepare(
        "INSERT INTO ManageBooksAdmin (BookName, Author, ISBN, Category, ASCription, CoverImage, TotalCopies, AvailableCopies)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssssii', $bookName, $author, $isbn, $category, $ASC, $cover, $copies, $copies);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        logActivity($conn, $userId, "Added book: $bookName", 'ManageBooksAdmin', $newId);
        jsonSuccess('Book added successfully', ['id' => $newId]);
    } else {
        $stmt->close();
        jsonError('Failed to add book. Please try again.');
    }
}

// ── EDIT BOOK ────────────────────────────────────────────────
elseif ($action === 'edit') {
    $id       = (int) ($_POST['id']          ?? 0);
    $bookName = trim($_POST['BookName']       ?? '');
    $author   = trim($_POST['Author']         ?? '');
    $isbn     = trim($_POST['ISBN']           ?? '');
    $category = trim($_POST['Category']       ?? 'General');
    $ASC     = trim($_POST['ASCription']    ?? '');
    $copies   = max(1, (int) ($_POST['TotalCopies'] ?? 1));

    if (!$id || !$bookName || !$author || !$isbn) {
        jsonError('Missing required fields');
    }

    // Fetch current record
    $cur = $conn->prepare("SELECT TotalCopies, AvailableCopies, CoverImage FROM ManageBooksAdmin WHERE id = ? LIMIT 1");
    $cur->bind_param('i', $id);
    $cur->execute();
    $row = $cur->get_result()->fetch_assoc();
    $cur->close();
    if (!$row) { jsonError('Book not found', 404); }

    // Recalculate AvailableCopies proportionally
    $borrowed  = $row['TotalCopies'] - $row['AvailableCopies'];
    $available = max(0, $copies - $borrowed);

    // Handle optional cover re-upload
    $cover = $row['CoverImage'];
    if (isset($_FILES['CoverImage']) && $_FILES['CoverImage']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-MS/Assets/uploads/books/';
            $filename  = handleUpload(
                $_FILES['CoverImage'],
                $uploadDir,
                ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                2 * 1024 * 1024
            );
            // Delete old image
            if ($row['CoverImage']) {
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-MS/Assets/uploads/books/' . $row['CoverImage'];
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
            $cover = $filename;
        } catch (RuntimeException $e) {
            jsonError('Image upload failed: ' . $e->getMessage());
        }
    }

    $stmt = $conn->prepare(
        "UPDATE ManageBooksAdmin
         SET BookName=?, Author=?, ISBN=?, Category=?, ASCription=?, CoverImage=?, TotalCopies=?, AvailableCopies=?
         WHERE id=? LIMIT 1"
    );
    $stmt->bind_param('ssssssiis', $bookName, $author, $isbn, $category, $ASC, $cover, $copies, $available, $id);

    if ($stmt->execute()) {
        $stmt->close();
        logActivity($conn, $userId, "Updated book: $bookName", 'ManageBooksAdmin', $id);
        jsonSuccess('Book updated successfully');
    } else {
        $stmt->close();
        jsonError('Failed to update book');
    }
}

// ── ARCHIVE / RESTORE ────────────────────────────────────────
elseif ($action === 'archive') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) { jsonError('Invalid book ID'); }

    $stmt = $conn->prepare(
        "UPDATE ManageBooksAdmin SET is_archived = NOT is_archived WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $stmt->close();
        logActivity($conn, $userId, 'Toggled archive status', 'ManageBooksAdmin', $id);
        jsonSuccess('Book status updated');
    } else {
        $stmt->close();
        jsonError('Failed to update book status');
    }
}

// ── DELETE BOOK ──────────────────────────────────────────────
elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) { jsonError('Invalid book ID'); }

    // Fetch cover image to delete from disk
    $cur = $conn->prepare("SELECT BookName, CoverImage FROM ManageBooksAdmin WHERE id = ? LIMIT 1");
    $cur->bind_param('i', $id);
    $cur->execute();
    $row = $cur->get_result()->fetch_assoc();
    $cur->close();
    if (!$row) { jsonError('Book not found', 404); }

    $stmt = $conn->prepare("DELETE FROM ManageBooksAdmin WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $stmt->close();
        // Remove cover file
        if ($row['CoverImage']) {
            $imgPath = $_SERVER['DOCUMENT_ROOT'] . '/PHP_programs/Library-MS/Assets/uploads/books/' . $row['CoverImage'];
            if (is_file($imgPath)) { @unlink($imgPath); }
        }
        logActivity($conn, $userId, "Deleted book: {$row['BookName']}", 'ManageBooksAdmin', $id);
        jsonSuccess('Book deleted successfully');
    } else {
        $stmt->close();
        jsonError('Failed to delete book');
    }
}

else {
    jsonError('Unknown action');
}
