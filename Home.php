<?php
    require_once 'PHP/login/config/database.php';
    require_once 'PHP/login/includes/session.php';
    require_once 'PHP/login/includes/functions.php';
    require_once 'PHP/login/includes/csrf.php';

    requireUser();
    $conn   = getDB();
    $email  = currentUserEmail();
    $userId = currentUserId();

    //  Stats counts
    $stmt = $conn->prepare("SELECT COUNT(*) FROM BorrowBook WHERE Email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $total_requests = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM BorrowBook WHERE Email = ? AND Status = 'Pending'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $pending_requests = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM BorrowBook WHERE Email = ? AND Status = 'Approved'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $active_borrows = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM BorrowBook WHERE Email = ? AND Status = 'Approved' AND ReturnDate < CURDATE()");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $overdue_books = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    //  Recent books
    $recent_books = $conn->query(
        "SELECT id, BookName, Author, Category, ISBN, CoverImage, AvailableCopies
        FROM ManageBooksAdmin WHERE is_archived = 0 ORDER BY added_at ASC LIMIT 6"
    );

    //  Recent requests (correct column: created_at)
    $stmt = $conn->prepare(
        "SELECT BookName, Status, created_at FROM BorrowBook
        WHERE Email = ? ORDER BY created_at ASC LIMIT 3"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $recent_requests = $stmt->get_result();
    $stmt->close();

    //  User avatar
    $av = $conn->prepare("SELECT profile_image FROM Accounts WHERE user_id = ? LIMIT 1");
    $av->bind_param('s', $userId);
    $av->execute();
    $avatarRow = $av->get_result()->fetch_assoc();
    $av->close();
    $avatarFile = $avatarRow['profile_image'] ?? null;
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/Home.css">
    <title>Home — LibraryMS</title>
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>
    
    <div id="toast-container"></div>

    <div class="container page-wrapper">

        <!-- Welcome Section -->
        <div class="hero-section card" style="background:linear-gradient(135deg,var(--primary), var(--primary-light)); color:#fff; margin-bottom:1.5rem;">
            <h1 style="color:#ffff; margin-bottom:.35rem;">
                Welcome back, <?= e(currentUserName()) ?> <span>!</span>
            </h1>
            <p style="color:rgba(255,255,255,.8); margin:0;"><?= date('l, F j, Y') ?></p>
        </div>

        <!-- Stats Count-->
        <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.75rem;">
            <?php
            $stats = [
                ['Total Requests', $total_requests,  '#3b82f6'],
                ['Pending',        $pending_requests, '#f59e0b'],
                ['Active Borrows', $active_borrows,   '#10b981'],
                ['Overdue',        $overdue_books,    '#ef4444'],
            ];
            foreach ($stats as [$label, $val, $color]):
            ?>
                <div class="card" style="border-left:4px solid <?= $color ?>;padding:1.25rem;">
                    <div style="font-size:2.8rem;font-weight:700;color:<?= $color ?>;">
                        <?= (int)$val ?>
                    </div>
                    
                    <div style="color:var(--text-muted);font-size:.95rem;">
                        <?= $label ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Recent Books -->
        <div class="home-layout" style="display:grid; grid-template-columns:1fr 0px; gap:2.5rem;">
            <div>
                <div class="section-header">
                    <h2 class="section-title">Recently Added Books</h2>
                    
                    <a href="/PHP_programs/Library-MS/BorrowBooks.php" class="btn btn-secondary btn-sm">View All</a>
                </div>
                
                <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:1rem; position:relative; left:20px;">
                    
                    <?php while ($book = $recent_books->fetch_assoc()): ?>
                        <div class="card card-hover" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
                            
                            <?php if ($book['CoverImage']): ?>
                                <img src="/PHP_programs/Library-MS/Assets/uploads/books/<?= e($book['CoverImage']) ?>" 
                                    alt="Cover" style="width:100%; height:180px; object-fit:cover;"
                                />
                                
                                <?php else: ?>
                                    <div style="width:100%; height:180px; background:linear-gradient(135deg,var(--primary),var(--primary-light)); display:flex; align-items:center; justify-content:center; font-size:2.5rem;">
                                        📖
                                    </div>
                                <?php endif; ?>

                                <div style="padding:.9rem; flex:1; display:flex; flex-direction:column;">
                                    <h4 style="margin:0 0 .3rem; font-size:.9rem; line-height:1.3;" title="<?= e($book['BookName']) ?>">
                                        <?= e(mb_strimwidth($book['BookName'], 0, 40, '…')) ?>
                                    </h4>

                                    <p style="color:var(--text-muted);font-size:.8rem;margin:0 0 .5rem;">
                                        <?= e($book['Author']) ?>
                                    </p>
                                        
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:.72rem; margin-bottom:.6rem;">
                                        <?= e($book['Category']) ?>
                                    </span>

                                    <?php if ($book['AvailableCopies'] > 0): ?>
                                        <span class="badge badge-approved" style="margin-bottom:.5rem;">
                                            Available (<?= (int)$book['AvailableCopies'] ?>)
                                        </span>
                                            
                                        <a href="/PHP_programs/Library-MS/BorrowBooks.php?isbn=<?= urlencode($book['ISBN']) ?>" class="btn btn-primary btn-sm" style="text-align:center;margin-top:auto;">
                                            Borrow
                                        </a>
                                    <?php else: ?>
                    
                                    <span class="badge badge-rejected" style="margin-bottom:.5rem;">Unavailable</span>
                                        <a href="/PHP_programs/Library-MS/RequestBook.php?title=<?= urlencode($book['BookName']) ?>" class="btn btn-secondary btn-sm" style="text-align:center;margin-top:auto;">
                                            Request
                                        </a>
                            <?php endif; ?>
                                </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/PHP_programs/Library-MS/Assets/JS/global.js"></script>
</body>

</html>