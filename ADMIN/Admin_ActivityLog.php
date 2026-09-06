<?php
// ADMIN/Admin_ActivityLog.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';

$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$logs = $conn->query("SELECT * FROM ActivityLog ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$total = $conn->query("SELECT COUNT(*) FROM ActivityLog")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log</title>
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
      </div>
    </header>
    <div class="admin-content">
      <div class="admin-page-header">
        <h1>Activity Log</h1>
      </div>
      <div class="admin-card">
        <table class="data-table">
          <thead>
            <tr><th>Log ID</th><th>User ID</th><th>Action</th><th>Time</th></tr>
          </thead>
          <tbody>
            <?php while($row = $logs->fetch_assoc()): ?>
            <tr>
              <td><?php echo e($row['id']); ?></td>
              <td><?php echo e($row['user_id']); ?></td>
              <td><?php echo e($row['action']); ?></td>
              <td><?php echo e(date('M j, Y h:i:s A', strtotime($row['created_at']))); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php echo renderPagination($page, $total, $limit, ''); ?>
      </div>
    </div>
  </main>
</div>
<script src="../Assets/JS/global.js"></script>
<script src="Admin JS/admin.js"></script>
</body>
</html>
