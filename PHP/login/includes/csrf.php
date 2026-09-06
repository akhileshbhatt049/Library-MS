<?php

/**
 * CSRF Token Helpers
 * Library Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate (or reuse) a CSRF token stored in the session.
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token.
 */
function validateCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Return an HTML hidden input containing the CSRF token.
 */
function csrfField(): string
{
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF from either $_POST['csrf_token'] or a JSON-decoded input array.
 * Aborts with 403 JSON response if invalid.
 *
 * @param array|null $jsonInput  Pass the decoded JSON body when handling JSON POST requests.
 */
function requireCSRF(?array $jsonInput = null): void
{
    // Support both JSON body and multipart/form POST
    $submitted = $jsonInput['csrf_token']
        ?? $_POST['csrf_token']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!validateCSRFToken($submitted)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid or expired request token. Please refresh and try again.']);
        exit;
    }
}
