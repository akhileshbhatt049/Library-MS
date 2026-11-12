<?php

/**
 * Session Management — Library Management System
 * Secure session configuration: SameSite, HttpOnly, session fixation prevention.
 */

// ── Secure session cookie settings ──────────────────────────
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0,           // Session cookie (expires when browser closes)
    'path'     => '/',
    'domain'   => $cookieParams['domain'],
    'secure'   => false,       // Set true when HTTPS is enabled
    'httponly' => true,        // Block JS access to session cookie
    'samesite' => 'Strict',    // CSRF cookie protection
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Helpers ───────────────────────────────────────────────────

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['_sess_ua'])
        && $_SESSION['_sess_ua'] === md5($_SERVER['HTTP_USER_AGENT'] ?? '');
}

function isAdmin(): bool
{
    return isLoggedIn() && (int) ($_SESSION['is_admin'] ?? 0) === 1;
}

function requireUser(): void
{
    if (!isLoggedIn()) {
        header('Location: /PHP_programs/Library-MS/Sign_up.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: /PHP_programs/Library-MS/Sign_up.php');
        exit;
    }
}

function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        header(
            isAdmin()
                ? 'Location: /PHP_programs/Library-MS/ADMIN/AdministratorArea.php'
                : 'Location: /PHP_programs/Library-MS/Home.php'
        );
        exit;
    }
}

/**
 * Called on successful login. Regenerates session ID to prevent session fixation.
 */
function loginUser(array $user): void
{
    // Regenerate ID before writing sensitive data
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['fullname']  = $user['fullname'];
    $_SESSION['is_admin']  = (int) $user['is_admin'];
    // Store UA fingerprint to bind session to browser
    $_SESSION['_sess_ua']  = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
}

function currentUserId(): ?string
{
    return $_SESSION['user_id']  ?? null;
}
function currentUserName(): string
{
    return $_SESSION['fullname'] ?? '';
}
function currentUserEmail(): string
{
    return $_SESSION['email']    ?? '';
}
function isAdminUser(): bool
{
    return isAdmin();
}
