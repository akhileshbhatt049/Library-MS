<?php
// ADMIN/AdministratorArea.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';

// Stats queries
$total_books = $conn->query("SELECT COUNT(*) FROM ManageBooksAdmin WHERE is_archived=0")->fetch_row()[0];
$total_users = $conn->query("SELECT COUNT(*) FROM Accounts WHERE is_admin=0")->fetch_row()[0];
$pending_borrow = $conn->query("SELECT COUNT(*) FROM BorrowBook WHERE Status='Pending'")->fetch_row()[0];
$pending_req = $conn->query("SELECT COUNT(*) FROM Request_Book WHERE Status='Pending'")->fetch_row()[0];
$monthly_borrows = $conn->query("SELECT COUNT(*) FROM BorrowBook WHERE MONTH(BorrowDate) = MONTH(CURRENT_DATE())")->fetch_row()[0];
$unread_msg = $conn->query("SELECT COUNT(*) FROM Contacts WHERE is_read=0")->fetch_row()[0];
$overdue_books = $conn->query("SELECT COUNT(*) FROM BorrowBook WHERE ReturnDate < NOW() AND Status='Approved'")->fetch_row()[0];
$avail_books = $conn->query("SELECT SUM(AvailableCopies) FROM ManageBooksAdmin WHERE is_archived=0")->fetch_row()[0] ?? 0;

$recent_logs = $conn->query("SELECT * FROM ActivityLog ORDER BY created_at ASC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../Assets/CSS/global.css">
    <link rel="stylesheet" href="Admin CSS/admin-global.css">
</head>
<body>
<div class="admin-wrapper">
  <?php include 'sidebar.php'; ?>
  <main class="admin-main">
    <header class="admin-topbar">
      <button class="sidebar-toggle" id="sidebar-toggle">&#9776;</button>
      <div class="topbar-info">
        <span class="admin-name"><?php echo e(currentUserName()); ?></span>
        <span class="admin-badge">Administrator</span>
      </div>
    </header>
    <div class="admin-content">
      <div class="admin-page-header">
        <h1>Dashboard</h1>
      </div>
      <div class="stats-row">
        <div class="stat-card" style="border-left-color: #3b82f6;"><div class="stat-val"><?php echo e($total_books); ?></div><div class="stat-label">Total Books</div></div>
        <div class="stat-card" style="border-left-color: #10b981;"><div class="stat-val"><?php echo e($total_users); ?></div><div class="stat-label">Users</div></div>
        <div class="stat-card" style="border-left-color: #f59e0b;"><div class="stat-val"><?php echo e($pending_borrow); ?></div><div class="stat-label">Pending Borrows</div></div>
        <div class="stat-card" style="border-left-color: #8b5cf6;"><div class="stat-val"><?php echo e($pending_req); ?></div><div class="stat-label">Book Requests</div></div>
        <div class="stat-card" style="border-left-color: #ef4444;"><div class="stat-val"><?php echo e($overdue_books); ?></div><div class="stat-label">Overdue Books</div></div>
        <div class="stat-card" style="border-left-color: #14b8a6;"><div class="stat-val"><?php echo e($unread_msg); ?></div><div class="stat-label">Unread Messages</div></div>
        <div class="stat-card" style="border-left-color: #3730a3;"><div class="stat-val"><?php echo e($monthly_borrows); ?></div><div class="stat-label">Monthly Borrows</div></div>
        <div class="stat-card" style="border-left-color: #059669;"><div class="stat-val"><?php echo e($avail_books); ?></div><div class="stat-label">Available Copies</div></div>
      </div>
      <div class="admin-card">
        <h2>Recent Activity</h2>
        <table class="data-table">
          <thead><tr><th>User ID</th><th>Action</th><th>Target</th><th>Time</th></tr></thead>
          <tbody>
            <?php while($row = $recent_logs->fetch_assoc()): ?>
            <tr>
              <td><?php echo e($row['user_id']); ?></td>
              <td><?php echo e($row['action']); ?></td>
              <td><?php echo e($row['target_id'] ?? 'N/A'); ?></td>
              <td><?php echo e(date('M j, Y h:i:s A', strtotime($row['created_at']))); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script src="../Assets/JS/global.js"></script>
<script src="Admin JS/admin.js"></script>
</body>
</html>
