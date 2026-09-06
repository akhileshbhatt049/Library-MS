<?php
require_once 'PHP/login/config/database.php';
require_once 'PHP/login/includes/session.php';
require_once 'PHP/login/includes/functions.php';
require_once 'PHP/login/includes/csrf.php';

requireUser();
$conn   = getDB();
$email  = currentUserEmail();
$userId = currentUserId();

// ── Already-requested ISBNs ───────────────────────────────────
$requested_isbns = [];
$stmt = $conn->prepare(
    "SELECT ISBN FROM BorrowBook WHERE Email = ? AND Status IN ('Pending','Approved')"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $requested_isbns[] = $row['ISBN'];
}
$stmt->close();

// ── All books (correct columns) ───────────────────────────────
$books = $conn->query(
    "SELECT id, BookName, Author, ISBN, Category, CoverImage, AvailableCopies
     FROM ManageBooksAdmin WHERE is_archived = 0 ORDER BY BookName ASC"
);

// ── Categories ────────────────────────────────────────────────
$categories = [];
$cat_res = $conn->query(
    "SELECT DISTINCT Category FROM ManageBooksAdmin WHERE is_archived = 0 AND Category != '' ORDER BY Category"
);
while ($row = $cat_res->fetch_assoc()) {
    $categories[] = $row['Category'];
}

// ── Borrow history (correct column: created_at) ───────────────
$stmt = $conn->prepare(
    "SELECT BookName, ISBN, Status, ReturnDate, created_at
     FROM BorrowBook WHERE Email = ? ORDER BY created_at ASC"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$history = $stmt->get_result();
$stmt->close();

// ── Phone pre-fill ────────────────────────────────────────────
$phoneStmt = $conn->prepare("SELECT phone FROM Accounts WHERE user_id = ? LIMIT 1");
$phoneStmt->bind_param('s', $userId);
$phoneStmt->execute();
$phone = $phoneStmt->get_result()->fetch_assoc()['phone'] ?? '';
$phoneStmt->close();

$minDate = date('Y-m-d', strtotime('+1 day'));
$maxDate = date('Y-m-d', strtotime('+30 days'));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Books — LibraryMS</title>
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/Borrow Books.css">
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="container page-wrapper">
        <div class="section-header" style="margin-bottom:1.25rem;">
            <h1>Borrow Books <span class="badge badge-pending" style="font-size:.7rem;vertical-align:middle;"><?= (int)$books->num_rows ?> Books</span></h1>
        </div>

        <!-- Filters -->
        <div class="filters-bar card" style="padding:.9rem 1.25rem;gap:.75rem;margin-bottom:1.25rem;">
            <div class="search-bar" style="flex:1;">
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" id="search-books" class="form-control" placeholder="Search by title, author or ISBN…" style="padding-left:2.4rem;border-radius:999px;">
            </div>
            <select id="category-filter" class="form-control" style="max-width:200px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Books Grid -->
        <div id="book-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
            <?php while ($book = $books->fetch_assoc()):
                $already = in_array($book['ISBN'], $requested_isbns);
                $bookData = htmlspecialchars(json_encode([
                    'title'  => $book['BookName'],
                    'author' => $book['Author'],
                    'isbn'   => $book['ISBN'],
                ]), ENT_QUOTES);
            ?>
                <div class="card book-card card-hover"
                    data-title="<?= strtolower(e($book['BookName'])) ?>"
                    data-author="<?= strtolower(e($book['Author'])) ?>"
                    data-isbn="<?= strtolower(e($book['ISBN'])) ?>"
                    data-category="<?= e($book['Category']) ?>"
                    style="padding:0;overflow:hidden;display:flex;flex-direction:column;">
                    <?php if ($book['CoverImage']): ?>
                        <img src="/PHP_programs/Library-Management-System/Assets/uploads/books/<?= e($book['CoverImage']) ?>"
                            alt="Cover" style="width:100%;height:180px;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%;height:180px;background:linear-gradient(135deg,#1e3a5f,#2d5a8e);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">📖</div>
                    <?php endif; ?>
                    <div style="padding:.9rem;flex:1;display:flex;flex-direction:column;gap:.4rem;">
                        <h4 style="margin:0;font-size:.88rem;line-height:1.3;" title="<?= e($book['BookName']) ?>"><?= e(mb_strimwidth($book['BookName'], 0, 40, '…')) ?></h4>
                        <p style="color:var(--text-muted);font-size:.78rem;margin:0;"><?= e($book['Author']) ?></p>
                        <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:.7rem;"><?= e($book['Category']) ?></span>
                        <p style="color:var(--text-muted);font-size:.72rem;margin:0;">ISBN: <?= e($book['ISBN']) ?></p>
                        <div style="margin-top:auto;">
                            <?php if ($already): ?>
                                <span class="badge badge-pending" style="display:block;text-align:center;padding:.5rem;">Already Requested</span>
                            <?php elseif ($book['AvailableCopies'] > 0): ?>
                                <span class="badge badge-approved" style="margin-bottom:.4rem;">Available (<?= (int)$book['AvailableCopies'] ?>)</span>
                                <button type="button" class="btn btn-primary btn-sm btn-full" onclick='openBorrowModal(<?= $bookData ?>)'>Request Borrow</button>
                            <?php else: ?>
                                <span class="badge badge-rejected" style="margin-bottom:.4rem;">Unavailable</span>
                                <a href="/PHP_programs/Library-Management-System/RequestBook.php?title=<?= urlencode($book['BookName']) ?>" class="btn btn-secondary btn-sm btn-full" style="text-align:center;">Request Book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div id="no-results" class="empty-state" style="display:none;">
            <div style="font-size:3rem;">🔍</div>
            <h3>No books match your search</h3>
            <p>Try different keywords or clear the filter.</p>
        </div>

        <!-- Borrow History -->
        <div class="card">
            <h3 style="margin:0 0 1rem;cursor:pointer;" id="toggle-history">My Borrow History <span id="hist-arrow">▼</span></h3>
            <div id="history-content">
                <?php if ($history->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>ISBN</th>
                                    <th>Status</th>
                                    <th>Return Date</th>
                                    <th>Time Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $history->fetch_assoc()):
                                    $overdue = ($row['Status'] === 'Approved' && strtotime($row['ReturnDate']) < time());
                                    $days    = daysUntil($row['ReturnDate']);
                                ?>
                                    <tr class="<?= $overdue ? 'row-overdue' : '' ?>">
                                        <td data-label="Book"><?= e($row['BookName']) ?></td>
                                        <td data-label="ISBN"><?= e($row['ISBN']) ?></td>
                                        <td data-label="Status">
                                            <?php $sc = ['Approved' => 'badge-approved', 'Rejected' => 'badge-rejected', 'Returned' => 'badge-returned']; ?>
                                            <span class="badge <?= $sc[$row['Status']] ?? 'badge-pending' ?>"><?= e($row['Status']) ?></span>
                                        </td>
                                        <td data-label="Return Date"><?= $row['ReturnDate'] ? date('M j, Y', strtotime($row['ReturnDate'])) : '—' ?></td>
                                        <td data-label="Time Left">
                                            <?php if ($row['Status'] === 'Approved'):
                                                if ($overdue) echo '<span style="color:var(--danger);font-weight:600;">Overdue by ' . abs($days) . ' day(s)</span>';
                                                else echo $days . ' day(s) left';
                                            else: echo '—';
                                            endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);">No borrow history yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Borrow Modal -->
    <div class="modal-overlay" id="borrowModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Request to Borrow</h3>
                <button class="modal-close" onclick="closeModal('borrowModal')">×</button>
            </div>
            <div class="modal-body">
                <form id="borrowForm">
                    <?= csrfField() ?>
                    <div id="modal-book-info" style="background:var(--surface-alt);border-radius:var(--radius-sm);padding:.9rem;margin-bottom:1rem;">
                        <h4 id="modal-book-title" style="margin:0 0 .2rem;"></h4>
                        <p id="modal-book-author" style="color:var(--text-muted);margin:0 0 .2rem;font-size:.88rem;"></p>
                        <span style="font-size:.8rem;color:var(--text-muted);">ISBN: <span id="modal-book-isbn"></span></span>
                    </div>
                    <input type="hidden" name="BookName" id="input-book-title">
                    <input type="hidden" name="Author" id="input-book-author">
                    <input type="hidden" name="ISBN" id="input-book-isbn">
                    <div class="form-group">
                        <label>Student / User ID</label>
                        <input type="text" name="StudentID" class="form-control" required placeholder="Your ID number">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($phone) ?>" required placeholder="07XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Expected Return Date</label>
                        <input type="date" name="ReturnDate" class="form-control" required
                            min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= $minDate ?>">
                        <small style="color:var(--text-muted);">Maximum 30 days from today.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full" id="borrow-submit-btn">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <script src="/PHP_programs/Library-Management-System/Assets/JS/global.js"></script>
    <script src="/PHP_programs/Library-Management-System/Assets/JS/borrow_books.js"></script>
    <script>
        // Search + filter
        const grid = document.getElementById('book-grid');
        const noRes = document.getElementById('no-results');

        function filterBooks() {
            const q = document.getElementById('search-books').value.toLowerCase().trim();
            const cat = document.getElementById('category-filter').value.toLowerCase();
            let vis = 0;
            grid.querySelectorAll('.book-card').forEach(c => {
                const matchQ = !q || c.dataset.title.includes(q) || c.dataset.author.includes(q) || c.dataset.isbn.includes(q);
                const matchCat = !cat || c.dataset.category.toLowerCase() === cat;
                c.style.display = (matchQ && matchCat) ? '' : 'none';
                if (matchQ && matchCat) vis++;
            });
            noRes.style.display = vis === 0 ? 'block' : 'none';
        }
        document.getElementById('search-books').addEventListener('input', filterBooks);
        document.getElementById('category-filter').addEventListener('change', filterBooks);

        // Collapsible history
        document.getElementById('toggle-history').addEventListener('click', () => {
            const c = document.getElementById('history-content');
            const a = document.getElementById('hist-arrow');
            c.style.display = c.style.display === 'none' ? '' : 'none';
            a.textContent = c.style.display === 'none' ? '▶' : '▼';
        });

        // Modal populate
        function openBorrowModal(book) {
            document.getElementById('modal-book-title').textContent = book.title;
            document.getElementById('modal-book-author').textContent = book.author;
            document.getElementById('modal-book-isbn').textContent = book.isbn;
            document.getElementById('input-book-title').value = book.title;
            document.getElementById('input-book-author').value = book.author;
            document.getElementById('input-book-isbn').value = book.isbn;
            openModal('borrowModal');
        }

        // Borrow form submit
        document.getElementById('borrowForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('borrow-submit-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting…';
            postForm('/PHP_programs/Library-Management-System/PHP/BorrowRequest.php', new FormData(this))
                .then(r => {
                    if (r.success) {
                        toast(r.message, 'success', 7000);
                        closeModal('borrowModal');
                        this.reset();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toast(r.message, 'error', 7000);
                    }
                })
                .catch(() => toast('Network error. Please try again.', 'error', 7000))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Submit Request';
                });
        });
    </script>
</body>

</html>