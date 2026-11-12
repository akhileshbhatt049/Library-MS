<?php
/**
 * Logout — Library Management System
 * Logs activity, clears session, destroys session, redirects.
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only log if a session exists, using correct logActivity() signature
if (isLoggedIn()) {
    $conn = getDB();
    logActivity($conn, currentUserId(), 'User logged out', 'Accounts', null);
}

// Destroy all session data
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}
session_destroy();

header('Location: /PHP_programs/Library-MS/Sign_up.php');
exit;
