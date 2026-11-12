<?php
// Fetch avatar for navbar — only if $conn is available and user is logged in
$_navbar_avatar = null;
if (isset($conn) && isLoggedIn()) {
  $nav_av = $conn->prepare("SELECT profile_image FROM Accounts WHERE user_id = ? LIMIT 1");
  $nav_av->bind_param('s', $_SESSION['user_id']);
  $nav_av->execute();
  $_navbar_avatar = $nav_av->get_result()->fetch_assoc()['profile_image'] ?? null;
  $nav_av->close();
}
$_navbar_initials = strtoupper(mb_substr(currentUserName(), 0, 1));
?>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/PHP_programs/Library-MS/Home.php" class="navbar-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
            </svg>
            <span>LibraryMS</span>
        </a>
        <div class="navbar-links" id="navbar-links">
            <a href="/PHP_programs/Library-MS/Home.php" class="nav-link">Home</a>
            <a href="/PHP_programs/Library-MS/BorrowBooks.php" class="nav-link">Borrow Books</a>
            <a href="/PHP_programs/Library-MS/Syllabus.php" class="nav-link">Syllabus</a>
            <a href="/PHP_programs/Library-MS/RequestBook.php" class="nav-link">Request Book</a>
            <a href="/PHP_programs/Library-MS/Contact.php" class="nav-link">Contact</a>
        </div>
        <div class="navbar-actions">
            <!-- Notification Bell -->
            <a href="/PHP_programs/Library-MS/Notifications.php" class="notification-bell"
                title="Notifications">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                <span class="notif-count" id="notif-count" style="display:none;"></span>
            </a>
            <!-- Dark Mode Toggle -->
            <button class="theme-toggle" id="theme-toggle" title="Toggle Dark Mode">
                <svg id="theme-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5" />
                    <line x1="12" y1="1" x2="12" y2="3" />
                    <line x1="12" y1="21" x2="12" y2="23" />
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                    <line x1="1" y1="12" x2="3" y2="12" />
                    <line x1="21" y1="12" x2="23" y2="12" />
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                </svg>
            </button>
            <!-- User Menu with Avatar -->
            <div class="user-menu " style="margin-right: 20px;">
                <button class="user-menu-trigger" type="button" aria-label="Account">
                    <div class="user-avatar">
                        <?php if (!empty($account['profile_image'])): ?>
                        <img src="/PHP_Programs/Library-MS/Assets/uploads/avatars/<?= e($account['profile_image']) ?>"
                            alt="<?= e(currentUserName()) ?>">
                        <?php else: ?>
                        <?= e(strtoupper(substr(currentUserName(), 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                </button>
                <div class="user-dropdown">
                    <a href="/PHP_programs/Library-MS/Account.php">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        My Account
                    </a>
                    <a href="/PHP_programs/Library-MS/PHP/login/auth/logout.php">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>