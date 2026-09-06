<?php
require_once 'login/includes/session.php';
header('Content-Type: application/json');

echo json_encode([
    'is_admin' => isAdmin(),
    'logged_in' => isLoggedIn()
]);
