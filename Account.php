<?php
require_once 'PHP/login/config/database.php';
require_once 'PHP/login/includes/session.php';
require_once 'PHP/login/includes/functions.php';
require_once 'PHP/login/includes/csrf.php';

requireUser();
$conn   = getDB();
$userId = currentUserId();
$email  = currentUserEmail();

// ── Fetch account (correct: WHERE user_id = ?, bind 's') ─────
$stmt = $conn->prepare(
    "SELECT user_id, fullname, email, phone, status, profile_image, created_at, last_login
     FROM Accounts WHERE user_id = ? LIMIT 1"
);
$stmt->bind_param('s', $userId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    header('Location: /PHP_programs/Library-Management-System/Sign_up.php');
    exit;
}

// ── Borrow history pagination ─────────────────────────────────
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 8;
$offset = ($page - 1) * $limit;

$cnt = $conn->prepare("SELECT COUNT(*) FROM BorrowBook WHERE Email = ?");
$cnt->bind_param('s', $email);
$cnt->execute();
$total_borrows = (int)$cnt->get_result()->fetch_row()[0];
$cnt->close();
$total_pages   = max(1, (int)ceil($total_borrows / $limit));

// correct column: created_at
$stmt = $conn->prepare(
    "SELECT BookName, Author, ISBN, Status, BorrowDate, ReturnDate, RejectReason, created_at
     FROM BorrowBook WHERE Email = ? ORDER BY created_at ASC LIMIT ? OFFSET ?"
);
$stmt->bind_param('sii', $email, $limit, $offset);
$stmt->execute();
$borrows = $stmt->get_result();
$stmt->close();

// ── Book requests (correct columns: BookTitle, Author, created_at) ────
$stmt = $conn->prepare(
    "SELECT BookTitle, Author, Status, RejectReason, created_at
     FROM Request_Book WHERE Email = ? ORDER BY created_at ASC"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$book_requests = $stmt->get_result();
$stmt->close();

// ── Notifications (correct: user_id VARCHAR, bind 's') ────────
$stmt = $conn->prepare(
    "SELECT id, title, message, is_read, created_at
     FROM Notifications WHERE user_id = ? ORDER BY created_at ASC LIMIT 15"
);
$stmt->bind_param('s', $userId);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// Unread count
$unc = $conn->prepare("SELECT COUNT(*) FROM Notifications WHERE user_id = ? AND is_read = 0");
$unc->bind_param('s', $userId);
$unc->execute();
$unread_count = (int)$unc->get_result()->fetch_row()[0];
$unc->close();

// Avatar URL helper
$avatarUrl = $account['profile_image']
    ? '/PHP_programs/Library-Management-System/Assets/uploads/avatars/' . $account['profile_image']
    : null;

$statusMap = ['Approved' => 'badge-approved', 'Rejected' => 'badge-rejected', 'Returned' => 'badge-returned', 'Pending' => 'badge-pending'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — LibraryMS</title>
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/Accounts.css">
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="container page-wrapper">

        <!-- Profile Header -->
        <div class="card" style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;padding:1.75rem;">
            <!-- Avatar Upload -->
            <div style="position:relative;flex-shrink:0;">
                <div id="avatar-wrapper" style="width:90px;height:90px;border-radius:50%;overflow:hidden;border:3px solid var(--primary);cursor:pointer;" onclick="document.getElementById('avatar-input').click()" title="Click to change photo">
                    <?php if ($avatarUrl): ?>
                        <img id="avatar-img" src="<?= e($avatarUrl) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <div id="avatar-initials" style="width:100%;height:100%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;">
                            <?= strtoupper(mb_substr($account['fullname'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="position:absolute;bottom:0;right:0;background:var(--accent);border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;" onclick="document.getElementById('avatar-input').click()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                </div>
                <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
            </div>

            <div style="flex:1;">
                <h2 style="margin:0 0 .3rem;"><?= e($account['fullname']) ?></h2>
                <p style="color:var(--text-muted);margin:0 0 .6rem;font-size:.9rem;"><?= e($account['email']) ?></p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <span class="badge" style="background:#e0f2fe;color:#0369a1;">📞 <?= e($account['phone']) ?></span>
                    <span class="badge badge-approved">Member since <?= date('M Y', strtotime($account['created_at'])) ?></span>
                    <?php if ($account['last_login']): ?>
                        <span class="badge" style="background:var(--surface-alt);color:var(--text-muted);">Last login: <?= timeAgo($account['last_login']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:.5rem;">
                <button onclick="openModal('changePwdModal')" class="btn btn-secondary btn-sm">🔒 Change Password</button>
            </div>
        </div>

        <!-- Tabs -->
        <div style="display:flex;gap:.4rem;margin-bottom:1rem;border-bottom:2px solid var(--border);padding-bottom:0;">
            <?php $tabs = [['borrow-history', 'Borrow History'], ['book-requests', 'Book Requests'], ['notifications-tab', 'Notifications ' . ($unread_count > 0 ? "({$unread_count})" : '')]]; ?>
            <?php foreach ($tabs as $i => [$id, $label]): ?>
                <button class="tab-btn<?= $i === 0 ? ' active' : '' ?>" data-tab="<?= $id ?>" style="padding:.6rem 1.1rem;border:none;background:none;cursor:pointer;font-weight:500;color:var(--text-muted);border-bottom:3px solid transparent;margin-bottom:-2px;"><?= $label ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Borrow History Tab -->
        <div class="tab-content card active" id="borrow-history">
            <h3 style="margin:0 0 1rem;">Borrow History</h3>
            <?php if ($borrows->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Borrow Date</th>
                                <th>Return Date</th>
                                <th>Time Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $borrows->fetch_assoc()):
                                $overdue = ($row['Status'] === 'Approved' && strtotime($row['ReturnDate']) < time());
                                $days    = daysUntil($row['ReturnDate']);
                            ?>
                                <tr class="<?= $overdue ? 'row-overdue' : '' ?>">
                                    <td data-label="Book"><?= e($row['BookName']) ?></td>
                                    <td data-label="Author"><?= e($row['Author']) ?></td>
                                    <td data-label="Status"><span class="badge <?= $statusMap[$row['Status']] ?? 'badge-pending' ?>"><?= e($row['Status']) ?></span>
                                        <?php if ($row['RejectReason']): ?><br><small style="color:var(--text-muted);"><?= e($row['RejectReason']) ?></small><?php endif; ?>
                                    </td>
                                    <td data-label="Borrow Date"><?= $row['BorrowDate'] ? date('M j, Y', strtotime($row['BorrowDate'])) : '—' ?></td>
                                    <td data-label="Return Date"><?= $row['ReturnDate'] ? date('M j, Y', strtotime($row['ReturnDate'])) : '—' ?></td>
                                    <td data-label="Time Left">
                                        <?php
                                        if ($row['Status'] === 'Approved') {
                                            if ($overdue) echo '<span style="color:var(--danger);font-weight:600;">Overdue ' . abs($days) . 'd</span>';
                                            else echo $days . ' day(s)';
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination" style="margin-top:1rem;">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-btn<?= $page === $i ? ' active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size:3rem;">📚</div>
                    <h3>No borrows yet</h3>
                    <p>Browse and borrow books from our collection.</p><a href="/PHP_programs/Library-Management-System/BorrowBooks.php" class="btn btn-primary" style="margin-top:.75rem;">Browse Books</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Book Requests Tab -->
        <div class="tab-content card" id="book-requests" style="display:none;">
            <h3 style="margin:0 0 1rem;">Book Requests</h3>
            <?php if ($book_requests->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $book_requests->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Title"><?= e($row['BookTitle']) ?></td>
                                    <td data-label="Author"><?= e($row['Author']) ?></td>
                                    <td data-label="Status"><span class="badge <?= $statusMap[$row['Status']] ?? 'badge-pending' ?>"><?= e($row['Status']) ?></span></td>
                                    <td data-label="Requested"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size:3rem;">📝</div>
                    <h3>No requests yet</h3><a href="/PHP_programs/Library-Management-System/RequestBook.php" class="btn btn-primary" style="margin-top:.75rem;">Request a Book</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Notifications Tab -->
        <div class="tab-content card" id="notifications-tab" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h3 style="margin:0;">Notifications</h3>
                <?php if ($unread_count > 0): ?>
                    <button onclick="markAllRead()" class="btn btn-secondary btn-sm">Mark All Read</button>
                <?php endif; ?>
            </div>
            <?php if ($notifications->num_rows > 0):
                while ($n = $notifications->fetch_assoc()): ?>
                    <div class="notif-item<?= !$n['is_read'] ? ' unread' : '' ?>" id="notif-<?= (int)$n['id'] ?>"
                        style="display:flex;gap:.85rem;padding:.9rem;border-radius:var(--radius-sm);margin-bottom:.5rem;background:<?= !$n['is_read'] ? 'var(--surface-alt)' : 'transparent' ?>;border:1px solid var(--border);">
                        <div style="flex-shrink:0;width:8px;height:8px;border-radius:50%;background:<?= !$n['is_read'] ? 'var(--primary)' : 'transparent' ?>;margin-top:.5rem;"></div>
                        <div style="flex:1;">
                            <h4 style="margin:0 0 .2rem;font-size:.9rem;"><?= e($n['title']) ?></h4>
                            <p style="color:var(--text-muted);font-size:.85rem;margin:0 0 .35rem;"><?= e($n['message']) ?></p>
                            <span style="font-size:.75rem;color:var(--text-muted);"><?= timeAgo($n['created_at']) ?></span>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <button onclick="markRead(<?= (int)$n['id'] ?>)" style="background:none;border:none;color:var(--primary);font-size:.78rem;cursor:pointer;flex-shrink:0;" title="Mark as read">✓</button>
                        <?php endif; ?>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="empty-state">
                    <div style="font-size:3rem;">🔔</div>
                    <h3>No notifications</h3>
                    <p>You're all caught up!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Change Password Modal -->
    <div class="modal-overlay" id="changePwdModal">
        <div class="modal-box" style="max-width:420px;">
            <div class="modal-header">
                <h3>Change Password</h3><button class="modal-close" onclick="closeModal('changePwdModal')">×</button>
            </div>
            <div class="modal-body">
                <form id="changePwdForm">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" class="form-control" id="new_pwd" required placeholder="Min 8 chars, letters + numbers">
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="pwd-bar"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full" id="pwd-submit-btn">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <script src="/PHP_programs/Library-Management-System/Assets/JS/global.js"></script>
    <script>
        // ── Tabs ─────────────────────────────────────────────────────
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active');
                    b.style.color = 'var(--text-muted)';
                    b.style.borderBottomColor = 'transparent';
                });
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                btn.classList.add('active');
                btn.style.color = 'var(--primary)';
                btn.style.borderBottomColor = 'var(--primary)';
                document.getElementById(btn.dataset.tab).style.display = 'block';
            });
        });

        // ── Avatar Upload ─────────────────────────────────────────────
        document.getElementById('avatar-input').addEventListener('change', function() {
            if (!this.files[0]) return;
            const fd = new FormData();
            fd.append('avatar', this.files[0]);
            fd.append('csrf_token', getCSRFToken());
            postForm('/PHP_programs/Library-Management-System/PHP/UploadAvatar.php', fd).then(r => {
                if (r.success) {
                    const w = document.getElementById('avatar-wrapper');
                    let img = document.getElementById('avatar-img');
                    if (!img) {
                        w.innerHTML = '<img id="avatar-img" style="width:100%;height:100%;object-fit:cover;" alt="Avatar">';
                        img = document.getElementById('avatar-img');
                    }
                    img.src = r.avatar_url + '?t=' + Date.now();
                    toast(r.message, 'success', 7000);
                } else toast(r.message, 'error', 7000);
            }).catch(() => toast('Upload failed. Please try again.', 'error', 7000));
        });

        // ── Change Password ───────────────────────────────────────────
        document.getElementById('new_pwd').addEventListener('input', function() {
            const v = this.value,
                bar = document.getElementById('pwd-bar');
            let s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v)) s++;
            if (/[^a-zA-Z0-9]/.test(v)) s++;
            const cls = ['', 'strength-weak', 'strength-medium', 'strength-strong', 'strength-strong'][s] || '';
            bar.parentElement.parentElement.className = 'password-strength ' + cls;
        });

        document.getElementById('changePwdForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('pwd-submit-btn');
            const data = Object.fromEntries(new FormData(this));
            btn.disabled = true;
            btn.textContent = 'Updating…';
            postJSON('/PHP_programs/Library-Management-System/PHP/ChangePassword.php', data).then(r => {
                    if (r.success) {
                        toast(r.message, 'success', 7000);
                        closeModal('changePwdModal');
                        this.reset();
                    } else toast(r.message, 'error', 7000);
                }).catch(() => toast('Network error.', 'error', 7000))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Update Password';
                });
        });

        // ── Notifications ─────────────────────────────────────────────
        function markRead(id) {
            postJSON('/PHP_programs/Library-Management-System/PHP/Notifications.php', {
                    action: 'mark_read',
                    id
                })
                .then(r => {
                    if (r.success) {
                        const el = document.getElementById('notif-' + id);
                        if (el) {
                            el.style.background = 'transparent';
                            el.querySelector('.mark-read-btn')?.remove();
                            el.querySelector('div:first-child').style.background = 'transparent';
                        }
                    }
                });
        }

        function markAllRead() {
            postJSON('/PHP_programs/Library-Management-System/PHP/Notifications.php', {
                    action: 'mark_all'
                })
                .then(r => {
                    if (r.success) {
                        toast('All marked as read', 'success', 7000);
                        setTimeout(() => location.reload(), 1200);
                    }
                });
        }
    </script>
</body>

</html>