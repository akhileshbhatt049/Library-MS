<?php
// ADMIN/Admin_Syllabus.php
require_once '../PHP/login/includes/session.php';
requireAdmin();
require_once '../PHP/login/config/database.php';
$conn = getDB();
require_once '../PHP/login/includes/functions.php';
require_once '../PHP/login/includes/csrf.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$syllabi = $conn->query("SELECT * FROM ADMIN_SYLLABUS ORDER BY id ASC LIMIT $limit OFFSET $offset");
$total = $conn->query("SELECT COUNT(*) FROM ADMIN_SYLLABUS")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Syllabus</title>
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
        <h1>Manage Syllabus</h1>
        <button class="btn" data-modal="uploadSyllabusModal">Upload Syllabus</button>
      </div>
      <div class="admin-card">
        <table class="data-table">
          <thead>
            <tr><th>ID</th><th>Course</th><th>Semester</th><th>Subject</th><th>PDF</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while($row = $syllabi->fetch_assoc()): ?>
            <tr>
              <td><?php echo e($row['id']); ?></td>
              <td><?php echo e($row['Course_Name']); ?></td>
              <td><?php echo e($row['Semester']); ?></td>
              <td><?php echo e($row['Subject_Name']); ?></td>
              <td><a href="/PHP_programs/Library-MS/ADMIN/uploads/syllabus/<?php echo e($row['Upload_Syllabus']); ?>" target="_blank">View PDF</a></td>
              <td>
                <button class="action-btn delete" onclick="deleteSyllabus(<?php echo $row['id']; ?>)">Delete</button>
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

<!-- Upload Modal -->
<div class="modal" id="uploadSyllabusModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Upload Syllabus</h2>
      <button class="close-modal">&times;</button>
    </div>
    <form id="uploadForm">
      <?php echo csrfField(); ?>
      <input type="hidden" name="action" value="upload">
      <div class="form-grid">
        <div class="form-group"><label>Course Name</label><input type="text" name="Course_Name" required></div>
        <div class="form-group"><label>Semester</label><input type="text" name="Semester" required></div>
        <div class="form-group"><label>Subject Name</label><input type="text" name="Subject_Name" required></div>
        <div class="form-group"><label>PDF File</label><input type="file" name="pdf" accept="application/pdf" required></div>
      </div>
      <button type="submit" class="btn">Upload</button>
    </form>
  </div>
</div>

<script src="../Assets/JS/global.js"></script>
<script src="Admin JS/admin.js"></script>
<script>
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch('Admin PHP/AdminSyllabus.php', { method: 'POST', body: formData });
    const data = await res.json();
    if(data.success) location.reload();
    else alert('Error: ' + data.message);
});
async function deleteSyllabus(id) {
    if(!confirm('Delete this syllabus?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    const res = await fetch('Admin PHP/AdminSyllabus.php', { method: 'POST', body: formData });
    const data = await res.json();
    if(data.success) location.reload();
    else alert('Error: ' + data.message);
}
</script>
</body>
</html>
