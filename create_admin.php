<?php
require_once 'PHP/login/config/database.php';

$db = getDB();

$stmt = $db->query("SELECT COUNT(*) as count FROM Accounts WHERE is_admin = 1");
$row = $stmt->fetch_assoc();

if ($row['count'] > 0) {
    $message = "Admin already exists. Delete this file for security.";
    $color = "var(--warning)";
} else {
    $fullname = 'Administrator';
    $email = 'admin@library.com';
    $phone = '0000000000';
    $password = password_hash('Admin@123', PASSWORD_DEFAULT);
    $is_admin = 1;
    $user_id = 'ADMIN001';
    $status = 'active';

    $insert = $db->prepare("INSERT INTO Accounts (user_id, fullname, email, phone, password, is_admin, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("sssssis", $user_id, $fullname, $email, $phone, $password, $is_admin, $status);

    if ($insert->execute()) {
        $message = "Default Admin created successfully.<br><br>Email: admin@library.com<br>Password: Admin@123";
        $color = "var(--success)";
    } else {
        $message = "Failed to create default admin.";
        $color = "var(--danger)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            text-align: center;
        }

        h1 {
            margin-top: 0;
        }

        .message {
            margin-bottom: 2rem;
            color: #333;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .warning {
            color: #dc2626;
            font-weight: bold;
            padding: 1rem;
            background: #fee2e2;
            border-radius: 4px;
            border: 1px solid #fecaca;
        }

        a {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #1e3a5f;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1 style="color: <?= $color ?>">Admin Setup</h1>
        <div class="message"><?= $message ?></div>
        <div class="warning">DELETE THIS FILE (create_admin.php) after first use!</div>
        <a href="Sign_up.php">Go to Login</a>
    </div>
</body>

</html>