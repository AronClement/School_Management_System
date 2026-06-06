<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
$users = get_users();
$announcements = load_json_data('announcements', []);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($user['role'] ?? '') === 'Academic Master') {
    $title = trim($_POST['title'] ?? '');
    $noticeType = trim($_POST['notice_type'] ?? 'Announcement');
    $target = trim($_POST['target'] ?? 'all');
    $targetValue = trim($_POST['target_value'] ?? '');
    $messageText = trim($_POST['message'] ?? '');

    if ($title === '' || $messageText === '') {
        $message = 'Please enter a title and message.';
    } else {
        $nextId = 1;
        foreach ($announcements as $item) {
            $nextId = max($nextId, ($item['id'] ?? 0) + 1);
        }
        $announcements[] = [
            'id' => $nextId,
            'title' => $title,
            'notice_type' => $noticeType,
            'target' => $target,
            'target_value' => $targetValue,
            'message' => $messageText,
            'created_by' => $user['full_name'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        save_json_data('announcements', $announcements);
        $message = 'Announcement posted successfully.';
    }
}

$visibleAnnouncements = [];
foreach ($announcements as $item) {
    if (($item['target'] ?? 'all') === 'all') {
        $visibleAnnouncements[] = $item;
        continue;
    }
    if (($item['target'] ?? '') === 'department') {
        if (($user['department'] ?? '') === trim($item['target_value'] ?? '')) {
            $visibleAnnouncements[] = $item;
        }
        continue;
    }
    if (($item['target'] ?? '') === 'class') {
        if (($user['class'] ?? '') === trim($item['target_value'] ?? '')) {
            $visibleAnnouncements[] = $item;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School Notices</title>
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
        <h1>School Notices</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($user['role'] === 'Academic Master'): ?>
            <section class="section-light section">
                <div class="container">
                    <h2>Post a notice</h2>
                    <form method="post" class="form-grid">
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" required />
                        <label for="notice_type">Type</label>
                        <select id="notice_type" name="notice_type">
                            <option value="Announcement">Announcement</option>
                            <option value="Calendar change">Calendar change</option>
                            <option value="Department notice">Department notice</option>
                        </select>
                        <label for="target">Target</label>
                        <select id="target" name="target">
                            <option value="all">All users</option>
                            <option value="department">Department</option>
                            <option value="class">Class</option>
                        </select>
                        <label for="target_value">Department / Class</label>
                        <input id="target_value" name="target_value" type="text" placeholder="e.g. Science or Form 1A" />
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                        <button class="button" type="submit">Post notice</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="section-light section">
            <div class="container">
                <h2>Latest notices</h2>
                <?php if (empty($visibleAnnouncements)): ?>
                    <div class="alert note">No notices available for your role at this time.</div>
                <?php else: ?>
                    <div class="content-grid">
                        <?php foreach ($visibleAnnouncements as $item): ?>
                            <article class="card">
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p><strong>Type:</strong> <?= htmlspecialchars($item['notice_type'] ?? '-') ?></p>
                                <p><strong>Target:</strong> <?= htmlspecialchars($item['target'] === 'all' ? 'All users' : ($item['target'] . ' - ' . ($item['target_value'] ?? '-'))) ?></p>
                                <p><?= nl2br(htmlspecialchars($item['message'])) ?></p>
                                <p><em>Posted by <?= htmlspecialchars($item['created_by'] ?? '-') ?> on <?= htmlspecialchars($item['created_at'] ?? '-') ?></em></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
        </div>
    </footer>
</body>
</html>
