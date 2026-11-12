<?php
// ADMIN/Admin_ManageBooks.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';
require_once '../PHP/login/includes/csrf.php';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

// ── Prepared statement for search — no string interpolation ──
if ($search) {
    $like  = '%' . $search . '%';
    $bk    = $conn->prepare("SELECT * FROM ManageBooksAdmin WHERE is_archived=0 AND (BookName LIKE ? OR Author LIKE ? OR ISBN LIKE ?) ORDER BY added_at ASC LIMIT ? OFFSET ?");
    $bk->bind_param('sssii', $like, $like, $like, $limit, $offset);
    $bk->execute(); $books = $bk->get_result(); $bk->close();
    $ct    = $conn->prepare("SELECT COUNT(*) FROM ManageBooksAdmin WHERE is_archived=0 AND (BookName LIKE ? OR Author LIKE ? OR ISBN LIKE ?)");
    $ct->bind_param('sss', $like, $like, $like); $ct->execute();
    $total = (int)$ct->get_result()->fetch_row()[0]; $ct->close();
} else {
    $books = $conn->query("SELECT * FROM ManageBooksAdmin WHERE is_archived=0 ORDER BY added_at ASC LIMIT $limit OFFSET $offset");
    $total = (int)$conn->query("SELECT COUNT(*) FROM ManageBooksAdmin WHERE is_archived=0")->fetch_row()[0];
}

$categories = ['Computer Science','Mathematics','Database','Software Engineering','Computer Networks','Operating Systems','Artificial Intelligence','Physics','Chemistry','General'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books — Admin</title>
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
        <h1>Manage Books</h1>
        <button class="btn btn-primary" onclick="openModal('addBookModal')">+ Add New Book</button>
      </div>
      <div class="admin-card">
        <!-- Search -->
        <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1rem;">
          <input type="text" name="search" placeholder="Search title, author, ISBN…" value="<?= e($search) ?>" class="form-control" style="max-width:320px;">
          <button type="submit" class="btn btn-secondary btn-sm">Search</button>
          <?php if ($search): ?><a href="?" class="btn btn-sm" style="background:var(--surface-alt);">Clear</a><?php endif; ?>
        </form>

        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Cover</th><th>Title &amp; Author</th><th>ISBN</th><th>Category</th><th>Copies</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($book = $books->fetch_assoc()): ?>
            <tr>
              <td>
                <?php if ($book['CoverImage']): ?>
                <img src="/PHP_programs/Library-MS/Assets/uploads/books/<?= e($book['CoverImage']) ?>" style="height:52px;width:40px;object-fit:cover;border-radius:4px;">
                <?php else: ?>
                <div style="height:52px;width:40px;background:var(--primary);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📖</div>
                <?php endif; ?>
              </td>
              <td><strong><?= e($book['BookName']) ?></strong><br><small style="color:var(--text-muted);"><?= e($book['Author']) ?></small></td>
              <td><?= e($book['ISBN']) ?></td>
              <td><?= e($book['Category']) ?></td>
              <td><?= (int)$book['AvailableCopies'] ?> / <?= (int)$book['TotalCopies'] ?></td>
              <td style="white-space:nowrap;">
                <button class="btn btn-sm btn-secondary" onclick='editBook(<?= htmlspecialchars(json_encode($book), ENT_QUOTES) ?>)'>Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteBook(<?= (int)$book['id'] ?>,'<?= e(addslashes($book['BookName'])) ?>')">Delete</button>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?= renderPagination($page, $total, $limit, '?search='.urlencode($search)) ?>
      </div>
    </div>
  </main>
</div>

<!-- Add Book Modal -->
<div class="modal-overlay" id="addBookModal">
  <div class="modal-box" style="max-width:560px;">
    <div class="modal-header"><h3>Add New Book</h3><button class="modal-close" onclick="closeModal('addBookModal')">×</button></div>
    <div class="modal-body">
      <form id="addBookForm" method="post" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group"><label>Book Title *</label><input type="text" name="BookName" class="form-control" required maxlength="200"></div>
          <div class="form-group"><label>Author *</label><input type="text" name="Author" class="form-control" required maxlength="150"></div>
          <div class="form-group"><label>ISBN *</label><input type="text" name="ISBN" class="form-control" required maxlength="30"></div>
          <div class="form-group"><label>Category</label>
            <select name="Category" class="form-control">
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Total Copies *</label><input type="number" name="TotalCopies" class="form-control" required min="1" value="1"></div>
          <div class="form-group"><label>Cover Image <small>(JPEG/PNG/WebP, max 5MB)</small></label><input type="file" name="CoverImage" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <div class="form-group"><label>description</label><textarea name="description" class="form-control" rows="3" maxlength="1000"></textarea></div>
        <button type="submit" class="btn btn-primary btn-full" id="add-book-btn">Save Book</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Book Modal -->
<div class="modal-overlay" id="editBookModal">
  <div class="modal-box" style="max-width:560px;">
    <div class="modal-header"><h3>Edit Book</h3><button class="modal-close" onclick="closeModal('editBookModal')">×</button></div>
    <div class="modal-body">
      <form id="editBookForm" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group"><label>Book Title *</label><input type="text" name="BookName" id="edit-title" class="form-control" required maxlength="200"></div>
          <div class="form-group"><label>Author *</label><input type="text" name="Author" id="edit-author" class="form-control" required maxlength="150"></div>
          <div class="form-group"><label>ISBN *</label><input type="text" name="ISBN" id="edit-isbn" class="form-control" required maxlength="30"></div>
          <div class="form-group"><label>Category</label>
            <select name="Category" id="edit-category" class="form-control">
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Total Copies *</label><input type="number" name="TotalCopies" id="edit-copies" class="form-control" required min="1"></div>
          <div class="form-group"><label>New Cover <small>(leave blank to keep)</small></label><input type="file" name="CoverImage" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <div class="form-group"><label>description</label><textarea name="description" id="edit-ASC" class="form-control" rows="3" maxlength="1000"></textarea></div>
        <button type="submit" class="btn btn-primary btn-full" id="edit-book-btn">Update Book</button>
      </form>
    </div>
  </div>
</div>

<script src="../Assets/JS/global.js"></script>
<script src="Admin JS/admin.js"></script>
<script>
const CSRF = document.querySelector('input[name="csrf_token"]')?.value || '';

// Add Book
document.getElementById('addBookForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('add-book-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    try {
        const res  = await fetch('Admin PHP/ManageBooks.php', { method:'POST', body: new FormData(e.target) });
        const data = await res.json();
        if (data.success) { toast(data.message, 'success', 7000); setTimeout(()=>location.reload(), 1200); }
        else               toast(data.message, 'error', 7000);
    } catch { toast('Network error.', 'error', 7000); }
    finally  { btn.disabled = false; btn.textContent = 'Save Book'; }
});

// Populate Edit Modal
function editBook(book) {
    document.getElementById('edit-id').value       = book.id;
    document.getElementById('edit-title').value    = book.BookName;
    document.getElementById('edit-author').value   = book.Author;
    document.getElementById('edit-isbn').value     = book.ISBN;
    document.getElementById('edit-category').value = book.Category;
    document.getElementById('edit-copies').value   = book.TotalCopies;
    document.getElementById('edit-ASC').value     = book.description || '';
    openModal('editBookModal');
}

// Edit Book Submit
document.getElementById('editBookForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('edit-book-btn');
    btn.disabled = true; btn.textContent = 'Updating…';
    try {
        const res  = await fetch('Admin PHP/ManageBooks.php', { method:'POST', body: new FormData(e.target) });
        const data = await res.json();
        if (data.success) { toast(data.message, 'success', 7000); setTimeout(()=>location.reload(), 1200); }
        else               toast(data.message, 'error', 7000);
    } catch { toast('Network error.', 'error', 7000); }
    finally  { btn.disabled = false; btn.textContent = 'Update Book'; }
});

// Delete Book
async function deleteBook(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    const fd = new FormData();
    fd.append('action', 'delete'); fd.append('id', id); fd.append('csrf_token', CSRF);
    try {
        const res  = await fetch('Admin PHP/ManageBooks.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { toast(data.message, 'success', 7000); setTimeout(()=>location.reload(), 1200); }
        else               toast(data.message, 'error', 7000);
    } catch { toast('Network error.', 'error', 7000); }
}
</script>
</body>
</html>
