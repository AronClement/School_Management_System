<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_once 'upload_helpers.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Academic Master') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$exams = array_filter(load_json_data('exams', []), fn($exam) => ($exam['status'] ?? '') === 'Sent to Academic Master');
$users = get_users();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academic Master Review</title>
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
        <h1>Pending Exam Reviews</h1>
        <?php if (empty($exams)): ?>
            <div class="alert note">No exam review requests are available yet.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Subject</th><th>Title</th><th>Teacher</th><th>Status</th><th>Requested at</th><th>Attachment</th><th>Download</th><th>Print</th></tr>
                </thead>
                <tbody>
                <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$exam['id']) ?></td>
                        <td><?= htmlspecialchars($exam['subject']) ?></td>
                        <td><?= htmlspecialchars($exam['title']) ?></td>
                        <td><?= htmlspecialchars($users[$exam['teacher']]['full_name'] ?? $exam['teacher']) ?></td>
                        <td><?= htmlspecialchars($exam['status']) ?></td>
                        <td><?= htmlspecialchars($exam['requested_at']) ?></td>
                        <td>
                            <?php if (!empty($exam['attachment']['saved_name'])): ?>
                                <a href="uploads/<?= rawurlencode($exam['attachment']['saved_name']) ?>" target="_blank">Open exam file</a>
                            <?php else: ?>
                                No upload available
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($exam['attachment']['saved_name'])): ?>
                                <a class="button button-secondary" href="uploads/<?= rawurlencode($exam['attachment']['saved_name']) ?>" download>Download</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($exam['attachment']['saved_name'])): ?>
                                <button class="button button-secondary" type="button" onclick="printExam('uploads/<?= rawurlencode($exam['attachment']['saved_name']) ?>')">Print</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
