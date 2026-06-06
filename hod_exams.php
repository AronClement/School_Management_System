<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_once 'subjects.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Head of Departments') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$users = get_users();
$subjectsData = require __DIR__ . '/subjects.php';
$exams = load_json_data('exams', []);
$department = $users[$user['username']]['department'] ?? '';
$departmentTeachers = [];
foreach ($users as $username => $info) {
    if (($info['role'] ?? '') === 'Teacher' && (($info['department'] ?? '') === $department)) {
        $departmentTeachers[$username] = $info;
    }
}
$departmentSubjects = [];
foreach ($subjectsData['subjects'] ?? [] as $code => $meta) {
    if (isset($departmentTeachers[$meta['teacher']])) {
        $departmentSubjects[$code] = $meta;
    }
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $examId = (int)($_POST['exam_id'] ?? 0);
    $reason = trim($_POST['reject_reason'] ?? '');
    foreach ($exams as &$exam) {
        if (($exam['id'] ?? 0) !== $examId) {
            continue;
        }
        if (!isset($departmentSubjects[$exam['subject']])) {
            $message = 'This exam is not in your department.';
            break;
        }
        if ($action === 'verify') {
            $exam['status'] = 'Verified by HOD';
            $exam['verified_at'] = date('Y-m-d H:i:s');
            $exam['verified_by'] = $user['full_name'];
            $message = 'Exam verified. You may now send it to the Academic Master.';
        } elseif ($action === 'reject') {
            if ($reason === '') {
                $message = 'Please provide a reason for rejection.';
            } else {
                $exam['status'] = 'Rejected by HOD';
                $exam['rejected_reason'] = $reason;
                $exam['rejected_at'] = date('Y-m-d H:i:s');
                $message = 'Exam rejected and returned to the teacher for resubmission.';
            }
        } elseif ($action === 'send') {
            if (($exam['status'] ?? '') !== 'Verified by HOD') {
                $message = 'Only verified exams can be sent to Academic Master.';
            } else {
                $exam['status'] = 'Sent to Academic Master';
                $exam['sent_at'] = date('Y-m-d H:i:s');
                $message = 'Exam sent to Academic Master.';
            }
        }
        break;
    }
    unset($exam);
    save_json_data('exams', $exams);
}

$departmentExams = array_filter($exams, fn($exam) => isset($departmentSubjects[$exam['subject']]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HOD Exam Verification</title>
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
        <h1>HOD Exam Verification</h1>
        <p>Please review the teacher's uploaded exam file and notes before verifying or rejecting the request.</p>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (empty($departmentExams)): ?>
            <div class="alert note">No exam submissions from your department teachers yet.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Subject</th><th>Title</th><th>Teacher</th><th>Status</th><th>Notes</th><th>Attachment</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($departmentExams as $exam): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$exam['id']) ?></td>
                        <td><?= htmlspecialchars($exam['subject']) ?></td>
                        <td><?= htmlspecialchars($exam['title']) ?></td>
                        <td><?= htmlspecialchars($users[$exam['teacher']]['full_name'] ?? $exam['teacher']) ?></td>
                        <td><?= htmlspecialchars($exam['status']) ?></td>
                        <td><?= htmlspecialchars($exam['notes'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($exam['attachment']['saved_name'])): ?>
                                <a href="uploads/<?= rawurlencode($exam['attachment']['saved_name']) ?>" target="_blank">Open exam file</a>
                            <?php else: ?>
                                No upload available
                            <?php endif; ?>
                        </td>
                        <td>-</td>
                    </tr>
                    <?php if (in_array($exam['status'] ?? '', ['Pending HOD review', 'Verified by HOD', 'Rejected by HOD'], true)): ?>
                        <tr class="exam-action-row">
                            <td colspan="8">
                                <?php if (($exam['status'] ?? '') === 'Pending HOD review'): ?>
                                    <form method="post" class="action-form">
                                        <input type="hidden" name="exam_id" value="<?= htmlspecialchars((string)$exam['id']) ?>" />
                                        <button class="button" type="submit" name="action" value="verify">Verify</button>
                                        <label for="reject_reason_<?= htmlspecialchars((string)$exam['id']) ?>">Reject reason</label>
                                        <input id="reject_reason_<?= htmlspecialchars((string)$exam['id']) ?>" name="reject_reason" type="text" placeholder="Reason" />
                                        <button class="button" type="submit" name="action" value="reject">Reject</button>
                                    </form>
                                <?php elseif (($exam['status'] ?? '') === 'Verified by HOD'): ?>
                                    <form method="post" class="action-form">
                                        <input type="hidden" name="exam_id" value="<?= htmlspecialchars((string)$exam['id']) ?>" />
                                        <button class="button" type="submit" name="action" value="send">Send to Academic Master</button>
                                    </form>
                                <?php elseif (($exam['status'] ?? '') === 'Rejected by HOD'): ?>
                                    <p><strong>Rejected:</strong> <?= htmlspecialchars($exam['rejected_reason'] ?? '') ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
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
</body>
</html>
