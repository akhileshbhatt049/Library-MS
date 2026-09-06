<?php
// ADMIN/Admin_Contact.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';
require_once '../PHP/login/includes/csrf.php';

$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$contacts = $conn->query("SELECT * FROM Contacts ORDER BY is_read ASC, created_at ASC LIMIT $limit OFFSET $offset");
$total = $conn->query("SELECT COUNT(*) FROM Contacts")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Messages</title>
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
        <h1>Contact Messages</h1>
      </div>
      <div class="admin-card">
        <table class="data-table">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Message</th><th>Time</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php while($row = $contacts->fetch_assoc()): ?>
            <tr>
              <td><?php echo e($row['name']); ?></td>
              <td><?php echo e($row['email']); ?></td>
              <td><?php echo e(substr($row['message'], 0, 50)); ?>...</td>
              <td><?php echo e(date('M j, Y h:i:s A', strtotime($row['created_at']))); ?></td>
              <td><?php echo $row['is_read'] ? 'Read' : 'Unread'; ?></td>
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
