<?php
// ADMIN/Admin_BookRequest.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';
require_once '../PHP/login/includes/csrf.php';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

// ── Whitelist-only status filter ──────────────────────────────
$allowed = ['All', 'Pending', 'Approved', 'Rejected'];
$status  = in_array($_GET['status'] ?? 'All', $allowed) ? ($_GET['status'] ?? 'All') : 'All';

if ($status === 'All') {
    $requests = $conn->query("SELECT * FROM Request_Book ORDER BY created_at ASC LIMIT $limit OFFSET $offset");
    $total    = (int)$conn->query("SELECT COUNT(*) FROM Request_Book")->fetch_row()[0];
} else {
    $st = $conn->prepare("SELECT * FROM Request_Book WHERE Status = ? ORDER BY created_at ASC LIMIT ? OFFSET ?");
    $st->bind_param('sii', $status, $limit, $offset); $st->execute();
    $requests = $st->get_result(); $st->close();
    $ct = $conn->prepare("SELECT COUNT(*) FROM Request_Book WHERE Status = ?");
    $ct->bind_param('s', $status); $ct->execute();
    $total = (int)$ct->get_result()->fetch_row()[0]; $ct->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Requests — Admin</title>
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
        <h1>Book Requests</h1>
        <span style="color:var(--text-muted);font-size:.9rem;">Total: <?= (int)$total ?></span>
      </div>
      <div class="admin-card">
        <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1rem;">
          <select name="status" class="form-control" style="width:auto;">
            <?php foreach ($allowed as $opt): ?>
            <option value="<?= $opt ?>"<?= $status===$opt?' selected':'' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>#</th><th>Book Title</th><th>Author</th><th>Requested By</th><th>Email</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $statusMap = ['Approved'=>'badge-approved','Rejected'=>'badge-rejected','Pending'=>'badge-pending']; ?>
            <?php while ($row = $requests->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><strong><?= e($row['BookTitle']) ?></strong></td>
              <td><?= e($row['Author']) ?></td>
              <td><?= e($row['Name']) ?></td>
              <td><?= e($row['Email']) ?></td>
              <td style="max-width:200px;font-size:.83rem;color:var(--text-muted);"><?= e(mb_strimwidth($row['Reason'], 0, 80, '…')) ?></td>
              <td><span class="badge <?= $statusMap[$row['Status']]??'badge-pending' ?>"><?= e($row['Status']) ?></span></td>
              <td style="white-space:nowrap;">
                <?php if ($row['Status'] === 'Pending'): ?>
                <button class="btn btn-sm" style="background:#10b981;color:#fff;" onclick="updateStatus(<?= (int)$row['id'] ?>,'approve')">✓ Approve</button>
                <button class="btn btn-sm btn-danger" onclick="updateStatus(<?= (int)$row['id'] ?>,'reject')">✗ Reject</button>
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
        reason = prompt('Reason for rejection:');
        if (reason === null) return;
    }
    const fd = new FormData();
    fd.append('action', action); fd.append('id', id); fd.append('csrf_token', CSRF);
    if (reason) fd.append('reason', reason);
    try {
        const res  = await fetch('Admin PHP/BookRequest.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { toast(data.message, 'success', 7000); setTimeout(()=>location.reload(), 1200); }
        else               toast('Error: ' + data.message, 'error', 7000);
    } catch { toast('Network error.', 'error', 7000); }
}
</script>
</body>
</html>
