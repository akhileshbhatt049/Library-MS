<?php
require_once 'PHP/login/config/database.php';
require_once 'PHP/login/includes/session.php';
require_once 'PHP/login/includes/functions.php';

requireUser();
$conn   = getDB();
$userId = currentUserId(); // VARCHAR — use 's' bind

// Mark all as read when page is opened (correct 's' bind)
$stmt = $conn->prepare("UPDATE Notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('s', $userId);
$stmt->execute();
$stmt->close();

// Fetch notifications (correct 's' bind)
$stmt = $conn->prepare(
    "SELECT id, title, message, is_read, created_at
     FROM Notifications WHERE user_id = ? ORDER BY created_at ASC LIMIT 100"
);
$stmt->bind_param('s', $userId);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — LibraryMS</title>
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/Accounts.css">
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="container page-wrapper" style="max-width:760px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <h1>🔔 Notifications</h1>
            <a href="/PHP_programs/Library-MS/Home.php" class="btn btn-secondary btn-sm">← Back Home</a>
        </div>

        <div class="card">
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($n = $notifications->fetch_assoc()): ?>
                    <div style="display:flex;gap:1rem;padding:1rem;border-bottom:1px solid var(--border);">
                        <div style="flex-shrink:0;width:36px;height:36px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1rem;">🔔</div>
                        <div style="flex:1;">
                            <h4 style="margin:0 0 .25rem;font-size:.95rem;"><?= e($n['title']) ?></h4>
                            <p style="color:var(--text-muted);font-size:.87rem;margin:0 0 .35rem;"><?= e($n['message']) ?></p>
                            <span style="font-size:.76rem;color:var(--text-muted);"><?= timeAgo($n['created_at']) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:3rem;">
                    <div style="font-size:3.5rem;">🔕</div>
                    <h3>No Notifications</h3>
                    <p>You're all caught up! Notifications will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/PHP_programs/Library-MS/Assets/JS/global.js"></script>
    <script>
        // Reset the badge in navbar
        const badge = document.getElementById('notif-count');
        if (badge) {
            badge.style.display = 'none';
        }
    </script>
</body>

</html>