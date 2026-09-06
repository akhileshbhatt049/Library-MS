<?php
// ADMIN/Admin_UserAccount.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';
require_once '../PHP/login/includes/csrf.php';

$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$users = $conn->query("SELECT * FROM Accounts WHERE is_admin=0 LIMIT $limit OFFSET $offset");
$total = $conn->query("SELECT COUNT(*) FROM Accounts WHERE is_admin=0")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Accounts</title>
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
        <h1>User Accounts</h1>
      </div>
      <div class="admin-card">
        <table class="data-table">
          <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while($row = $users->fetch_assoc()): ?>
            <tr>
              <td><?php echo e($row['id']); ?></td>
              <td><?php echo e($row['fullname']); ?></td>
              <td><?php echo e($row['email']); ?></td>
              <td>
                <button class="action-btn" onclick="alert('Viewing user details')">View</button>
              </td>
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
