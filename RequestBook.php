<?php
require_once 'PHP/login/config/database.php';
require_once 'PHP/login/includes/session.php';
require_once 'PHP/login/includes/functions.php';
require_once 'PHP/login/includes/csrf.php';

requireUser();
$conn   = getDB();
$email  = currentUserEmail();
$userId = currentUserId();

// Pre-fill title from GET (from BorrowBooks unavailable link)
$title_prefill  = htmlspecialchars($_GET['title'] ?? '', ENT_QUOTES);

// Past requests — correct columns: BookTitle, Author, created_at
$stmt = $conn->prepare(
    "SELECT BookTitle, Author, Status, RejectReason, created_at
     FROM Request_Book WHERE Email = ? ORDER BY created_at ASC"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a Book — LibraryMS</title>
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/RequestBook.css">
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="container page-wrapper" style="max-width:860px;">

        <div style="text-align:center;margin-bottom:1.75rem;">
            <h1 style="margin-bottom:.35rem;">📝 Request a Book</h1>
            <p style="color:var(--text-muted);">Can't find what you're looking for? Let us know!</p>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <form id="requestBookForm">
                <?= csrfField() ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" class="form-control" value="<?= e(currentUserName()) ?>" readonly style="background:var(--surface-alt);">
                    </div>
                    <div class="form-group">
                        <label>Your Email</label>
                        <input type="email" class="form-control" value="<?= e(currentUserEmail()) ?>" readonly style="background:var(--surface-alt);">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Book Title <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="BookTitle" class="form-control" required maxlength="200" value="<?= $title_prefill ?>" placeholder="e.g. Introduction to Algorithms">
                    </div>
                    <div class="form-group">
                        <label>Author Name <span style="color:var(--danger);">*</span></label>
                        <!-- field name is 'Author' to match PHP/RequestBook.php handler -->
                        <input type="text" name="Author" class="form-control" required maxlength="150" placeholder="e.g. Thomas H. Cormen">
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason for Request <span style="color:var(--danger);">*</span></label>
                    <textarea name="Reason" class="form-control" rows="4" required minlength="20" maxlength="2000"
                        placeholder="Briefly explain why this book should be added (minimum 20 characters)…" id="reason-txt"></textarea>
                    <small style="color:var(--text-muted);display:flex;justify-content:space-between;margin-top:.25rem;">
                        <span>Minimum 20 characters</span>
                        <span id="char-count">0 / 2000</span>
                    </small>
                </div>
                <button type="submit" class="btn btn-primary btn-full" id="req-submit-btn">Submit Request</button>
            </form>
        </div>

        <!-- Past Requests -->
        <div class="card">
            <h3 style="margin:0 0 1rem;">My Past Requests</h3>
            <?php $statusMap = ['Approved' => 'badge-approved', 'Rejected' => 'badge-rejected', 'Pending' => 'badge-pending']; ?>
            <?php if ($requests->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Title"><?= e($row['BookTitle']) ?></td>
                                    <td data-label="Author"><?= e($row['Author']) ?></td>
                                    <td data-label="Status"><span class="badge <?= $statusMap[$row['Status']] ?? 'badge-pending' ?>"><?= e($row['Status']) ?></span></td>
                                    <td data-label="Date"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size:2.5rem;">📭</div>
                    <p>No requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/PHP_programs/Library-MS/Assets/JS/global.js"></script>
    <script>
        // Character counter
        const ta = document.getElementById('reason-txt');
        const cnt = document.getElementById('char-count');
        ta.addEventListener('input', () => {
            cnt.textContent = ta.value.length + ' / 2000';
        });

        // Form submit
        document.getElementById('requestBookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('req-submit-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting…';
            const formData = new FormData(this);

            postForm(
                    '/PHP_Programs/Library-MS/PHP/RequestBook.php',
                    formData
                ).then(r => {
                    if (r.success) {
                        toast(r.message, 'success', 7000);
                        this.reset();
                        cnt.textContent = '0 / 2000';
                        setTimeout(() => location.reload(), 2000);
                    } else toast(r.message, 'error', 7000);
                }).catch(() => toast('Network error.', 'error', 7000))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Submit Request';
                });
        });
    </script>
</body>

</html>