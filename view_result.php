<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Student') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$subject = $_GET['subject'] ?? '';
$data = require __DIR__ . '/subjects.php';
$subjects = $data['subjects'] ?? [];
$enrollments = $data['enrollments'] ?? [];
$myEnroll = $enrollments[$user['username']] ?? [];
if ($subject === '' || !in_array($subject, $myEnroll, true)) {
    header('Location: dashboard.php');
    exit;
}

$results = load_json_data('results', []);
$studentResults = $results[$user['username']] ?? [];
$row = $studentResults[$subject] ?? null;

$teacherUser = $subjects[$subject]['teacher'] ?? '';
$allUsers = get_users();
$teacherName = $allUsers[$teacherUser]['full_name'] ?? $teacherUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Result - <?= htmlspecialchars($subjects[$subject]['name'] ?? $subject) ?></title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="dashboard.php">School System</a>
            <nav class="site-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container section">
        <h1>Result for <?= htmlspecialchars($subjects[$subject]['name'] ?? $subject) ?></h1>
        <p><strong>Student:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
        <p><strong>Teacher:</strong> <?= htmlspecialchars($teacherName) ?></p>

        <?php if ($row === null): ?>
            <div class="alert note">No result recorded yet for this subject.</div>
        <?php else: ?>
            <table class="data-table">
                <tr><th>Score</th><td><?= htmlspecialchars((string)$row['score']) ?></td></tr>
                <tr><th>Grade</th><td><?= htmlspecialchars($row['grade']) ?></td></tr>
            </table>
        <?php endif; ?>

        <p><a href="dashboard.php">Back to dashboard</a></p>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
