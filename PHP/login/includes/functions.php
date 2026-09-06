<?php

/**
 * Shared Utility Functions — Library Management System
 *
 * CANONICAL version. All other files depend on this.
 * Provides: output escaping, JSON responses, activity logging,
 * notifications, file uploads, pagination, date helpers.
 */

// ── Output / Sanitization ──────────────────────────────────────

/** HTML-escape a value for safe output. */
function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── JSON Response Helpers ─────────────────────────────────────
// NOTE: These echo AND exit — do not wrap in echo yourself.

function jsonSuccess(string $message = '', array $data = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}

function jsonError(string $message = 'An error occurred', int $httpCode = 400): never
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── Activity Logging ──────────────────────────────────────────

/**
 * Write a record to the ActivityLog table.
 *
 * @param mysqli      $conn
 * @param string|null $userId       Session user_id (or null for system actions)
 * @param string      $action       Human-readable ASCription
 * @param string|null $targetTable  DB table affected (optional)
 * @param int|null    $targetId     Row ID affected (optional)
 */
function logActivity(
    mysqli $conn,
    ?string $userId,
    string $action,
    ?string $targetTable = null,
    ?int $targetId = null
): void {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare(
        "INSERT INTO ActivityLog (user_id, action, target_table, target_id, ip_address)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssss', $userId, $action, $targetTable, $targetId, $ip);
    $stmt->execute();
    $stmt->close();
}

// ── Notifications ─────────────────────────────────────────────

/**
 * Insert an in-app notification for a user.
 *
 * @param mysqli $conn
 * @param string $userId   Recipient user_id from Accounts table
 * @param string $title    Short notification title
 * @param string $message  Full notification body
 */
function createNotification(
    mysqli $conn,
    string $userId,
    string $title,
    string $message
): void {
    $stmt = $conn->prepare(
        "INSERT INTO Notifications (user_id, title, message) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('sss', $userId, $title, $message);
    $stmt->execute();
    $stmt->close();
}

/**
 * Count unread notifications for a user.
 */
function countUnreadNotifications(mysqli $conn, string $userId): int
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM Notifications WHERE user_id = ? AND is_read = 0"
    );
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['cnt'] ?? 0);
}

// ── File Upload ───────────────────────────────────────────────

/**
 * Validate and move an uploaded file.
 * Uses finfo for MIME detection — not just the file extension.
 *
 * @param array  $file         $_FILES element
 * @param string $uploadDir    Absolute directory path (no trailing slash)
 * @param array  $allowedMimes Allowed MIME types
 * @param int    $maxBytes     Max size in bytes (default 5 MB)
 * @return string              Stored filename (not full path)
 * @throws RuntimeException    On any validation or move failure
 */
function handleUpload(
    array $file,
    string $uploadDir,
    array $allowedMimes,
    int $maxBytes = 5242880
): string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension.',
        ];
        throw new RuntimeException($errors[$file['error']] ?? 'Unknown upload error.');
    }

    if ($file['size'] > $maxBytes) {
        $mb = round($maxBytes / 1048576, 1);
        throw new RuntimeException("File exceeds the maximum allowed size of {$mb} MB.");
    }

    // Validate MIME via finfo (resistant to extension spoofing)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException("File type '$mime' is not allowed.");
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Failed to move uploaded file to destination.');
    }

    return $safeName;
}

// ── Pagination ────────────────────────────────────────────────

/**
 * Compute pagination metadata from the current request.
 * @return array{page: int, perPage: int, offset: int}
 */
function paginate(int $perPage = 10): array
{
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;
    return ['page' => $page, 'perPage' => $perPage, 'offset' => $offset];
}

/**
 * Render Bootstrap-free pagination HTML.
 */
function renderPagination(int $currentPage, int $totalRows, int $perPage, string $baseUrl): string
{
    $totalPages = (int) ceil($totalRows / $perPage);
    if ($totalPages <= 1) return '';
    $sep  = strpos($baseUrl, '?') !== false ? '&' : '?';
    $html = '<div class="pagination">';
    // Prev
    if ($currentPage > 1) {
        $html .= '<a href="' . e($baseUrl . $sep . 'page=' . ($currentPage - 1)) . '" class="page-btn">‹</a>';
    }
    // Pages (show max 7 with ellipsis)
    $start = max(1, $currentPage - 3);
    $end   = min($totalPages, $currentPage + 3);
    if ($start > 1)           $html .= '<a href="' . e($baseUrl . $sep . 'page=1') . '" class="page-btn">1</a>' . ($start > 2 ? '<span class="page-btn" style="cursor:default;">…</span>' : '');
    for ($i = $start; $i <= $end; $i++) {
        $cls   = $i === $currentPage ? ' active' : '';
        $html .= '<a href="' . e($baseUrl . $sep . 'page=' . $i) . '" class="page-btn' . $cls . '">' . $i . '</a>';
    }
    if ($end < $totalPages)   $html .= ($end < $totalPages - 1 ? '<span class="page-btn" style="cursor:default;">…</span>' : '') . '<a href="' . e($baseUrl . $sep . 'page=' . $totalPages) . '" class="page-btn">' . $totalPages . '</a>';
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . e($baseUrl . $sep . 'page=' . ($currentPage + 1)) . '" class="page-btn">›</a>';
    }
    $html .= '</div>';
    return $html;
}

// ── Date / Time ───────────────────────────────────────────────

/** Human-readable "X time ago" string. */
function timeAgo(string $datetime): string
{
    $diff      = time() - strtotime($datetime);
    $intervals = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
        1        => 'second',
    ];
    foreach ($intervals as $secs => $label) {
        $n = (int) floor($diff / $secs);
        if ($n >= 1) {
            return $n . ' ' . $label . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

/** Days until a date. Negative = overdue. */
function daysUntil(string $dateStr): int
{
    $now  = new DateTime('today');
    $then = new DateTime($dateStr);
    $d    = (int) $now->diff($then)->days;
    return $now <= $then ? $d : -$d;
}
