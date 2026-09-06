<?php
require_once 'PHP/login/config/database.php';
require_once 'PHP/login/includes/session.php';
require_once 'PHP/login/includes/functions.php';

requireUser();
$conn = getDB();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? trim($_GET['course']) : '';
$semester_filter = isset($_GET['semester']) ? trim($_GET['semester']) : '';

// Get distinct courses
$courses = [];
$res = $conn->query("SELECT DISTINCT Course_Name FROM ADMIN_SYLLABUS ORDER BY Course_Name");
while ($row = $res->fetch_assoc()) {
    $courses[] = $row['Course_Name'];
}

// Build query
$query = "SELECT * FROM ADMIN_SYLLABUS WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND Subject_Name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($course_filter) {
    $query .= " AND Course_Name = ?";
    $params[] = $course_filter;
    $types .= "s";
}
if ($semester_filter) {
    $query .= " AND Semester = ?";
    $params[] = $semester_filter;
    $types .= "s";
}

$query .= " ORDER BY Course_Name, Semester, Subject_Name";

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$syllabi = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus - Library Management System</title>
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-Management-System/Assets/CSS/Syllabus.css">
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>

    <div class="container" style="padding-top: 5%; padding-bottom: 2%;">

        <div class="page-header">
            <h1>Academic Syllabus</h1>
        </div>

        <form method="GET" class="filters-bar card">
            <input type="text" name="search" class="form-control" placeholder="Search by subject name..." value="<?php echo e($search); ?>">
            <select name="course" class="form-control course-filter">
                <option value="">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo e($c); ?>" <?php if ($course_filter == $c) echo 'selected'; ?>><?php echo e($c); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="semester" class="form-control course-filter">
                <option value="">All Semesters</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php if ($semester_filter == $i) echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || $course_filter || $semester_filter): ?>
                <a href="Syllabus.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($syllabi->num_rows > 0): ?>
            <div class="syllabus-grid">
                <?php while ($row = $syllabi->fetch_assoc()):
                    $semClass = "sem-" . ($row['Semester'] % 4); // Just to give different colors
                ?>
                    <div class="syllabus-card card">
                        <div class="card-top">
                            <span class="semester-badge <?php echo $semClass; ?>">Semester <?php echo e($row['Semester']); ?></span>
                            <div class="course-name"><?php echo e($row['Course_Name']); ?></div>
                        </div>
                        <h3 class="subject-name"><?php echo e($row['Subject_Name']); ?></h3>
                        <div class="mt-auto">
                            <a href="/PHP_programs/Library-Management-System/ADMIN/uploads/syllabus/<?php echo e($row['Course_PDF']); ?>" target="_blank" class="btn btn-outline w-100">
                                <span class="icon">📄</span> View PDF
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <h3 class="text-muted">No syllabus records found.</h3>
                <p>Try adjusting your filters.</p>
            </div>
        <?php endif; ?>

    </div>

    <script src="/PHP_programs/Library-Management-System/Assets/JS/global.js"></script>
    <script>
        document.getElementById('hamburger').addEventListener('click', function() {
            document.getElementById('navbar-links').classList.toggle('active');
        });
    </script>
</body>

</html>