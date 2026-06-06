<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_once 'upload_helpers.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Student') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$assignmentId = (int)($_GET['id'] ?? 0);
$assignments = load_json_data('assignments', []);
$assignment = null;
foreach ($assignments as $item) {
    if (($item['id'] ?? 0) === $assignmentId) {
        $assignment = $item;
        break;
    }
}
if (!$assignment) {
    header('Location: dashboard.php');
    exit;
}

$subjectsData = require __DIR__ . '/subjects.php';
$enrollments = $subjectsData['enrollments'] ?? [];
if (!in_array($assignment['subject'], $enrollments[$user['username']] ?? [], true)) {
    header('HTTP/1.1 403 Forbidden');
    echo 'You are not enrolled in this subject.';
    exit;
}

$deadline = new DateTime($assignment['deadline'] . ' 23:59:59');
$now = new DateTime();
$isClosed = $now > $deadline;
$message = '';
$submission = $assignment['submissions'][$user['username']] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-load assignment data to ensure we evaluate the latest deadline
    $freshAssignments = load_json_data('assignments', []);
    $freshAssignment = null;
    foreach ($freshAssignments as $a) {
        if (($a['id'] ?? 0) === $assignmentId) {
            $freshAssignment = $a;
            break;
        }
    }
    if ($freshAssignment === null) {
        $message = 'Assignment no longer exists.';
    } else {
        $freshDeadline = null;
        try {
            $freshDeadline = new DateTime(($freshAssignment['deadline'] ?? '') . ' 23:59:59');
        } catch (Exception $e) {
            $freshDeadline = null;
        }
        $nowCheck = new DateTime();
        if ($freshDeadline !== null && $nowCheck > $freshDeadline) {
            $message = 'The deadline has passed and assignment submissions are closed.';
        } else {
            $content = trim($_POST['content'] ?? '');
            $attachment = null;
            if (!empty($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
                $attachment = save_upload($_FILES['document'], 'assignment');
                if ($attachment === null) {
                    $message = 'Failed to upload the document. Please try again.';
                }
            }

            if ($message === '') {
                if ($content === '' && $attachment === null) {
                    $message = 'Please provide a submission or upload a document.';
                    // if an upload was saved but submission invalid, remove the file
                    if ($attachment !== null && !empty($attachment['saved_name'])) {
                        $path = __DIR__ . '/uploads/' . $attachment['saved_name'];
                        if (is_file($path)) {
                            @unlink($path);
                        }
                    }
                } else {
                    // Final deadline check before saving (prevent race)
                    $latestNow = new DateTime();
                    if ($freshDeadline !== null && $latestNow > $freshDeadline) {
                        $message = 'The deadline has just passed; submission not accepted.';
                        if ($attachment !== null && !empty($attachment['saved_name'])) {
                            $path = __DIR__ . '/uploads/' . $attachment['saved_name'];
                            if (is_file($path)) {
                                @unlink($path);
                            }
                        }
                    } else {
                        // Save submission into the live assignments array and persist
                        foreach ($freshAssignments as &$item) {
                            if (($item['id'] ?? 0) === $assignmentId) {
                                if (!is_array($item['submissions'])) {
                                    $item['submissions'] = [];
                                }
                                $item['submissions'][$user['username']] = [
                                    'content' => $content,
                                    'attachment' => $attachment,
                                    'submitted_at' => date('Y-m-d H:i:s'),
                                ];
                                $submission = $item['submissions'][$user['username']];
                                break;
                            }
                        }
                        unset($item);
                        save_json_data('assignments', $freshAssignments);
                        $message = 'Assignment submitted successfully.';
                        // also update local copy used for display
                        $assignments = $freshAssignments;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Submit Assignment</title>
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
        <h1>Submit Assignment</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <div class="content-card">
            <p><strong>Title:</strong> <?= htmlspecialchars($assignment['title']) ?></p>
            <p><strong>Subject:</strong> <?= htmlspecialchars($assignment['subject']) ?></p>
            <p><strong>Deadline:</strong> <?= htmlspecialchars($assignment['deadline']) ?></p>
            <p><strong>Status:</strong> <?= $isClosed ? 'Closed' : 'Open for submission' ?></p>
            <p><strong>Instructions:</strong> <?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
            <?php if (!empty($assignment['attachment']['saved_name'])): ?>
                <p><strong>Teacher attachment:</strong> <a href="<?= htmlspecialchars(attachment_url($assignment['attachment']['saved_name'])) ?>" target="_blank"><?= htmlspecialchars($assignment['attachment']['original_name']) ?></a></p>
            <?php endif; ?>
        </div>
        <?php if ($isClosed): ?>
            <div class="alert note">This assignment is closed. Submission is no longer accepted.</div>
        <?php else: ?>
            <div class="alert note" id="deadline-timer" data-deadline="<?= htmlspecialchars($assignment['deadline'] . ' 23:59:59') ?>">
                Preparation underway...
            </div>
            <form id="assignment-form" method="post" enctype="multipart/form-data" class="form-grid">
                <label for="content">Your submission</label>
                <textarea id="content" name="content" rows="6"><?= htmlspecialchars($submission['content'] ?? '') ?></textarea>

                <label for="document">Upload document</label>
                <input id="document" name="document" type="file" accept=".pdf,.doc,.docx,.txt,.zip" />

                <button class="button" id="assignment-submit-button" type="submit">Submit Assignment</button>
            </form>
        <?php endif; ?>
        <?php if ($submission): ?>
            <div class="content-card">
                <h2>Last saved submission</h2>
                <p><strong>Submitted at:</strong> <?= htmlspecialchars($submission['submitted_at']) ?></p>
                <?php if (!empty($submission['content'])): ?>
                    <pre><?= htmlspecialchars($submission['content']) ?></pre>
                <?php endif; ?>
                <?php if (!empty($submission['attachment']['saved_name'])): ?>
                    <p><strong>Uploaded document:</strong> <a href="<?= htmlspecialchars(attachment_url($submission['attachment']['saved_name'])) ?>" target="_blank"><?= htmlspecialchars($submission['attachment']['original_name']) ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
        </div>
    </footer>
</body>
</html>
