<?php
// ADMIN/Admin_BorrowRequest.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';
require_once '../PHP/login/includes/csrf.php';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

// ── Whitelist-only status filter — no string concatenation ────
$allowed = ['All', 'Pending', 'Approved', 'Rejected', 'Returned'];
$status  = in_array($_GET['status'] ?? 'All', $allowed) ? ($_GET['status'] ?? 'All') : 'All';

if ($status === 'All') {
    $borrows = $conn->query("SELECT * FROM BorrowBook ORDER BY created_at ASC LIMIT $limit OFFSET $offset");
    $total   = (int)$conn->query("SELECT COUNT(*) FROM BorrowBook")->fetch_row()[0];
} else {
    $st = $conn->prepare("SELECT * FROM BorrowBook WHERE Status = ? ORDER BY created_at ASC LIMIT ? OFFSET ?");
    $st->bind_param('sii', $status, $limit, $offset);
    $st->execute();
    $borrows = $st->get_result();
    $ct = $conn->prepare("SELECT COUNT(*) FROM BorrowBook WHERE Status = ?");
    $ct->bind_param('s', $status); $ct->execute();
    $total = (int)$ct->get_result()->fetch_row()[0]; $ct->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrow Requests — Admin</title>
    <link rel="stylesheet" href="../Assets/CSS/global.css">
    <link rel="stylesheet" href="Admin CSS/admin-global.css">
</head>
<body>
<div id="toast-container"></div>
<div class="admin-wrapper">
  <?php include 'sidebar.php'; ?>
  <main class="admin-main">
    <header class="admin-topbar">
      <button class="sidebar-toggle" id="sidebar-toggle">&#9776;</button>
      <div class="topbar-info">
        <span class="admin-name"><?= e(currentUserName()) ?></span>
        <span class="admin-badge">Administrator</span>
      </div>
    </header>
    <div class="admin-content">
      <div class="admin-page-header">
        <h1>Borrow Requests</h1>
        <span style="color:var(--text-muted);font-size:.9rem;">Total: <?= (int)$total ?></span>
      </div>
      <div class="admin-card">
        <!-- Filter -->
        <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
          <select name="status" class="form-control" style="width:auto;">
            <?php foreach ($allowed as $opt): ?>
            <option value="<?= $opt ?>"<?= $status===$opt?' selected':'' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>

        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>#</th><th>Book</th><th>Student</th><th>Email</th><th>Phone</th><th>Return Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $statusMap = ['Approved'=>'badge-approved','Rejected'=>'badge-rejected','Returned'=>'badge-returned','Pending'=>'badge-pending']; ?>
            <?php while ($row = $borrows->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><strong><?= e($row['BookName']) ?></strong><br><small style="color:var(--text-muted);"><?= e($row['ISBN']) ?></small></td>
              <td><?= e($row['Name']) ?><br><small style="color:var(--text-muted);">ID: <?= e($row['StudentID']) ?></small></td>
              <td><?= e($row['Email']) ?></td>
              <td><?= e($row['phone']) ?></td>
              <td><?= $row['ReturnDate'] ? date('M j, Y', strtotime($row['ReturnDate'])) : '—' ?></td>
              <td>
                <span class="badge <?= $statusMap[$row['Status']]??'badge-pending' ?>"><?= e($row['Status']) ?></span>
                <?php if ($row['RejectReason']): ?>
                <br><small style="color:var(--text-muted);"><?= e($row['RejectReason']) ?></small>
                <?php endif; ?>
              </td>
              <td style="white-space:nowrap;">
                <?php if ($row['Status'] === 'Pending'): ?>
                <button class="btn btn-sm" style="background:#10b981;color:#fff;" onclick="updateStatus(<?= (int)$row['id'] ?>,'approve')">✓ Approve</button>
                <button class="btn btn-sm btn-danger" onclick="updateStatus(<?= (int)$row['id'] ?>,'reject')">✗ Reject</button>
                <?php elseif ($row['Status'] === 'Approved'): ?>
                <button class="btn btn-sm" style="background:#6366f1;color:#fff;" onclick="updateStatus(<?= (int)$row['id'] ?>,'return')">↩ Returned</button>
                <?php else: ?>
                <span style="color:var(--text-muted);font-size:.82rem;">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?= renderPagination($page, $total, $limit, '?status='.urlencode($status)) ?>
      </div>
    </div>
  </main>
</div>

<script src="../Assets/JS/global.js"></script>
<script src="Admin JS/admin.js"></script>
<script>
const CSRF = '<?= generateCSRFToken() ?>';
async function updateStatus(id, action) {
    let reason = '';
    if (action === 'reject') {
        reason = prompt('Reason for rejection (shown to student):');
        if (reason === null) return;
    }
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id', id);
    if (reason) fd.append('reason', reason);
    fd.append('csrf_token', CSRF);
    try {
        const res  = await fetch('Admin PHP/BorrowRequest.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { toast(data.message, 'success', 7000); setTimeout(()=>location.reload(), 1200); }
        else               toast('Error: ' + data.message, 'error', 7000);
    } catch { toast('Network error. Please try again.', 'error', 7000); }
}
</script>
</body>
</html>
