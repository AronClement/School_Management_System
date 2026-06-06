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

$users = get_users();
$subjectsData = require __DIR__ . '/subjects.php';
$subjects = $subjectsData['subjects'] ?? [];
$enrollments = $subjectsData['enrollments'] ?? [];
$assignments = load_json_data('assignments', []);
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
    $description = trim($_POST['description'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');

    if ($title === '' || $subject === '' || $description === '' || $deadline === '') {
        $message = 'All fields are required to create an assignment.';
    } elseif (!isset($teacherSubjects[$subject])) {
        $message = 'You can only create assignments for your subjects.';
    } else {
        $nextId = 1;
        foreach ($assignments as $item) {
            $nextId = max($nextId, ($item['id'] ?? 0) + 1);
        }
        $attachment = null;
        if (!empty($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $attachment = save_upload($_FILES['assignment_file'], 'assignment');
            if ($attachment === null) {
                $message = 'Failed to upload the assignment file. Please try again.';
            }
        }

        $assignments[] = [
            'id' => $nextId,
            'subject' => $subject,
            'title' => $title,
            'description' => $description,
            'deadline' => $deadline,
            'teacher' => $user['username'],
            'created_at' => date('Y-m-d H:i:s'),
            'submissions' => [],
            'attachment' => $attachment,
        ];
        save_json_data('assignments', $assignments);
        $message = 'Assignment created successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teacher Assignments</title>
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
        <h1>Create Assignment</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <div class="content-grid">
            <div class="content-card">
                <form method="post" enctype="multipart/form-data" class="form-grid">
                    <label for="title">Assignment Title</label>
                    <input id="title" name="title" type="text" required />

                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Choose subject</option>
                        <?php foreach ($teacherSubjects as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code . ' - ' . $meta['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="description">Instructions</label>
                    <textarea id="description" name="description" rows="4" required></textarea>

                    <label for="deadline">Deadline</label>
                    <input id="deadline" name="deadline" type="date" required />

                    <label for="assignment_file">Attach file (optional)</label>
                    <input id="assignment_file" name="assignment_file" type="file" accept=".pdf,.doc,.docx,.txt,.zip" />

                    <button class="button" type="submit">Publish Assignment</button>
                </form>
            </div>
            <div class="content-card">
                <h2>Your published assignments</h2>
                <?php if (empty($assignments)): ?>
                    <p>No assignments created yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Title</th><th>Subject</th><th>Deadline</th><th>Attachment</th><th>Submissions</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php if ($assignment['teacher'] !== $user['username']) continue; ?>
                            <?php $deadline = new DateTime($assignment['deadline'] . ' 23:59:59'); ?>
                            <?php $closed = new DateTime() > $deadline; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$assignment['id']) ?></td>
                                <td><?= htmlspecialchars($assignment['title']) ?></td>
                                <td><?= htmlspecialchars($assignment['subject']) ?></td>
                                <td><?= htmlspecialchars($assignment['deadline']) ?></td>
                                <td>
                                    <?php if (!empty($assignment['attachment']['saved_name'])): ?>
                                        <a href="uploads/<?= rawurlencode($assignment['attachment']['saved_name']) ?>" target="_blank"><?= htmlspecialchars($assignment['attachment']['original_name']) ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)count((array)$assignment['submissions'])) ?></td>
                                <td><?= $closed ? 'Closed' : 'Open' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($assignments)): ?>
            <section class="section-light section">
                <div class="container">
                    <h2>Assignment submissions</h2>
                    <?php $hasSubmission = false; ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <?php if ($assignment['teacher'] !== $user['username']) continue; ?>
                        <?php if (empty($assignment['submissions'])) continue; ?>
                        <?php $hasSubmission = true; ?>
                        <div class="content-card">
                            <h3><?= htmlspecialchars($assignment['title']) ?> (<?= htmlspecialchars($assignment['subject']) ?>)</h3>
                            <table class="data-table">
                                <thead>
                                    <tr><th>Student</th><th>Submitted at</th><th>Content</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignment['submissions'] as $studentUsername => $submission): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($users[$studentUsername]['full_name'] ?? $studentUsername) ?></td>
                                            <td><?= htmlspecialchars($submission['submitted_at'] ?? '-') ?></td>
                                            <td>
                                                <?php if (!empty($submission['content'])): ?>
                                                    <div><?= nl2br(htmlspecialchars($submission['content'])) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($submission['attachment']['saved_name'])): ?>
                                                    <p><a href="uploads/<?= rawurlencode($submission['attachment']['saved_name']) ?>" target="_blank"><?= htmlspecialchars($submission['attachment']['original_name']) ?></a></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$hasSubmission): ?>
                        <p>No student submissions have arrived yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <p><a href="teacher_exams.php">Send exam for academic review</a></p>
    </main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
        </div>
    </footer>
</body>
</html>
