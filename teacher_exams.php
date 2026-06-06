<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_once 'upload_helpers.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Teacher') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$subjectsData = require __DIR__ . '/subjects.php';
$subjects = $subjectsData['subjects'] ?? [];
$exams = load_json_data('exams', []);
$message = '';
$teacherSubjects = [];
foreach ($subjects as $code => $meta) {
    if (($meta['teacher'] ?? '') === $user['username']) {
        $teacherSubjects[$code] = $meta;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subject = $_POST['subject'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $attachment = null;

    if ($title === '' || $subject === '' || $notes === '') {
        $message = 'All fields are required to submit an exam for review.';
    } elseif (!isset($teacherSubjects[$subject])) {
        $message = 'You can only submit exams for your subjects.';
    } else {
        if (!empty($_FILES['exam_file']) && $_FILES['exam_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $attachment = save_upload($_FILES['exam_file'], 'exam');
            if ($attachment === null) {
                $message = 'Failed to upload the exam document. Please try again.';
            }
        }
        $nextId = 1;
        foreach ($exams as $item) {
            $nextId = max($nextId, ($item['id'] ?? 0) + 1);
        }
        $exams[] = [
            'id' => $nextId,
            'subject' => $subject,
            'title' => $title,
            'notes' => $notes,
            'teacher' => $user['username'],
            'status' => 'Pending HOD review',
            'requested_at' => date('Y-m-d H:i:s'),
            'attachment' => $attachment,
        ];
        save_json_data('exams', $exams);
        $message = 'Exam submitted for review to the Head of Department.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Submit Exam for Review</title>
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
        <h1>Submit Exam for Academic Review</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <div class="content-grid">
            <div class="content-card">
                <form method="post" enctype="multipart/form-data" class="form-grid">
                    <label for="title">Exam Title</label>
                    <input id="title" name="title" type="text" required />

                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Choose subject</option>
                        <?php foreach ($teacherSubjects as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code . ' - ' . $meta['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="notes">Review notes</label>
                    <textarea id="notes" name="notes" rows="4" required></textarea>

                    <label for="exam_file">Upload exam document</label>
                    <input id="exam_file" name="exam_file" type="file" accept=".pdf,.doc,.docx,.txt,.zip" />

                    <button class="button" type="submit">Send for Review</button>
                </form>
            </div>
            <div class="content-card">
                <h2>Previous submissions</h2>
                <?php if (empty($exams)): ?>
                    <p>No exam reviews requested yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Subject</th><th>Title</th><th>Status</th><th>Requested</th><th>Attachment</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <?php if ($exam['teacher'] !== $user['username']) continue; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$exam['id']) ?></td>
                                <td><?= htmlspecialchars($exam['subject']) ?></td>
                                <td><?= htmlspecialchars($exam['title']) ?></td>
                                <td><?= htmlspecialchars($exam['status']) ?></td>
                                <td><?= htmlspecialchars($exam['requested_at']) ?></td>
                                <td>
                                    <?php if (!empty($exam['attachment']['saved_name'])): ?>
                                        <a href="uploads/<?= rawurlencode($exam['attachment']['saved_name']) ?>" target="_blank"><?= htmlspecialchars($exam['attachment']['original_name']) ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
        </div>
    </footer>
</body>
</html>
