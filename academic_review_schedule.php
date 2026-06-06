<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Academic Master') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$users = get_users();
$teachers = array_filter($users, fn($item) => ($item['role'] ?? '') === 'Teacher');
$reviews = load_json_data('academic_reviews', []);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherUsername = trim($_POST['teacher'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($teacherUsername === '' || $date === '' || $time === '') {
        $message = 'Please choose a teacher, date, and time.';
    } elseif (!isset($teachers[$teacherUsername])) {
        $message = 'Selected teacher does not exist.';
    } else {
        $nextId = 1;
        foreach ($reviews as $review) {
            $nextId = max($nextId, ($review['id'] ?? 0) + 1);
        }

        $reviews[] = [
            'id' => $nextId,
            'teacher' => $teacherUsername,
            'teacher_name' => $teachers[$teacherUsername]['full_name'],
            'scheduled_by' => $user['full_name'],
            'scheduled_at' => date('Y-m-d H:i:s'),
            'review_date' => $date,
            'review_time' => $time,
            'notes' => $notes,
        ];
        save_json_data('academic_reviews', $reviews);
        $message = 'Academic review scheduled successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule Academic Reviews</title>
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
        <h1>Schedule Academic Reviews</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <section class="section-light section">
            <div class="container">
                <form method="post" class="form-grid">
                    <label for="teacher">Teacher</label>
                    <select id="teacher" name="teacher" required>
                        <option value="">Choose teacher</option>
                        <?php foreach ($teachers as $username => $info): ?>
                            <option value="<?= htmlspecialchars($username) ?>"><?= htmlspecialchars($info['full_name'] . ' (' . $username . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="date">Review date</label>
                    <input id="date" name="date" type="date" required />
                    <label for="time">Review time</label>
                    <input id="time" name="time" type="time" required />
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Optional review notes"></textarea>
                    <button class="button" type="submit">Schedule review</button>
                </form>
            </div>
        </section>

        <section class="section-light section">
            <div class="container">
                <h2>Scheduled academic reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p>No reviews have been scheduled yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Teacher</th><th>Date</th><th>Time</th><th>Notes</th><th>Scheduled by</th><th>Created at</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$review['id']) ?></td>
                                    <td><?= htmlspecialchars($review['teacher_name'] ?? $review['teacher']) ?></td>
                                    <td><?= htmlspecialchars($review['review_date']) ?></td>
                                    <td><?= htmlspecialchars($review['review_time']) ?></td>
                                    <td><?= htmlspecialchars($review['notes'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($review['scheduled_by'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($review['scheduled_at'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
