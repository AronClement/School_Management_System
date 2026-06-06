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

$lessonPlans = load_json_data('lesson_plans', []);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateType = trim($_POST['update_type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($updateType === '' || $title === '' || $subject === '') {
        $message = 'Please provide a type, subject, and title for the update.';
    } else {
        $attachment = null;
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $attachment = save_upload($_FILES['attachment'], 'lesson_update');
            if ($attachment === null) {
                $message = 'Failed to upload the file. Please try again.';
            }
        }

        if ($message === '') {
            $nextId = 1;
            foreach ($lessonPlans as $plan) {
                $nextId = max($nextId, ($plan['id'] ?? 0) + 1);
            }

            $lessonPlans[] = [
                'id' => $nextId,
                'type' => $updateType,
                'title' => $title,
                'subject' => $subject,
                'notes' => $notes,
                'teacher' => $user['username'],
                'teacher_name' => $user['full_name'],
                'status' => 'Pending',
                'created_at' => date('Y-m-d H:i:s'),
                'attachment' => $attachment,
            ];
            save_json_data('lesson_plans', $lessonPlans);
            $message = 'Your update has been submitted and is pending Academic Master review.';
        }
    }
}

$myPlans = array_filter($lessonPlans, fn($plan) => ($plan['teacher'] ?? '') === $user['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Submit Lesson Plan or Curriculum Update</title>
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
        <h1>Submit Lesson Plan or Curriculum Update</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <section class="section-light section">
            <div class="container">
                <form method="post" enctype="multipart/form-data" class="form-grid">
                    <label for="update_type">Update type</label>
                    <select id="update_type" name="update_type" required>
                        <option value="">Choose type</option>
                        <option value="Lesson plan">Lesson plan</option>
                        <option value="Curriculum update">Curriculum update</option>
                    </select>
                    <label for="subject">Subject or topic</label>
                    <input id="subject" name="subject" type="text" placeholder="e.g. Biology" required />
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" placeholder="Update title" required />
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Summary or rationale"></textarea>
                    <label for="attachment">Attach supporting file</label>
                    <input id="attachment" name="attachment" type="file" accept=".pdf,.doc,.docx,.xlsx,.xls,.csv" />
                    <button class="button" type="submit">Submit update</button>
                </form>
            </div>
        </section>

        <section class="section-light section">
            <div class="container">
                <h2>Your submitted updates</h2>
                <?php if (empty($myPlans)): ?>
                    <p>You have not submitted any lesson plan or curriculum updates yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Type</th><th>Subject</th><th>Title</th><th>Status</th><th>Submitted</th><th>Attachment</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myPlans as $plan): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$plan['id']) ?></td>
                                <td><?= htmlspecialchars($plan['type']) ?></td>
                                <td><?= htmlspecialchars($plan['subject']) ?></td>
                                <td><?= htmlspecialchars($plan['title']) ?></td>
                                <td><?= htmlspecialchars($plan['status']) ?></td>
                                <td><?= htmlspecialchars($plan['created_at'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($plan['attachment']['saved_name'])): ?>
                                        <a href="uploads/<?= rawurlencode($plan['attachment']['saved_name']) ?>" target="_blank">Open</a>
                                    <?php else: ?>
                                        None
                                    <?php endif; ?>
                                </td>
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
