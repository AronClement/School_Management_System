<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_once 'upload_helpers.php';
require_login();
$user = current_user();
$users = get_users();

$classOptions = [
    'Form 1A' => 'Science',
    'Form 1B' => 'Science',
    'Form 2A' => 'Science',
    'Form 2B' => 'Arts',
];

$timetables = load_json_data('timetables', []);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($user['role'] ?? '') === 'Academic Master') {
    $action = $_POST['action'] ?? 'upload';
    $selectedClass = trim($_POST['class'] ?? '');

    if (!isset($classOptions[$selectedClass])) {
        $message = 'Please select a valid class before performing this action.';
    } else {
        if ($action === 'delete_lesson' || $action === 'delete_exam' || $action === 'delete_class') {
            if (!isset($timetables[$selectedClass])) {
                $message = 'No timetable exists for that class.';
            } else {
                if ($action === 'delete_lesson') {
                    unset($timetables[$selectedClass]['lesson_timetable']);
                    $message = 'Lesson timetable deleted for ' . htmlspecialchars($selectedClass) . '.';
                } elseif ($action === 'delete_exam') {
                    unset($timetables[$selectedClass]['exam_timetable']);
                    $message = 'Exam timetable deleted for ' . htmlspecialchars($selectedClass) . '.';
                } elseif ($action === 'delete_class') {
                    unset($timetables[$selectedClass]);
                    $message = 'All timetable data removed for ' . htmlspecialchars($selectedClass) . '.';
                }
                if ($action !== 'delete_class') {
                    if (empty($timetables[$selectedClass]['lesson_timetable']) && empty($timetables[$selectedClass]['exam_timetable'])) {
                        unset($timetables[$selectedClass]);
                    }
                }
                save_json_data('timetables', $timetables);
            }
        } else {
            $lessonUpload = null;
            $examUpload = null;

            if (!empty($_FILES['lesson_timetable_file']) && $_FILES['lesson_timetable_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $lessonUpload = save_upload($_FILES['lesson_timetable_file'], 'lesson_timetable');
                if ($lessonUpload === null) {
                    $message = 'Failed to upload the lesson timetable file. Please try again.';
                }
            }

            if (!empty($_FILES['exam_timetable_file']) && $_FILES['exam_timetable_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $examUpload = save_upload($_FILES['exam_timetable_file'], 'exam_timetable');
                if ($examUpload === null) {
                    $message = 'Failed to upload the exam timetable file. Please try again.';
                }
            }

            if ($message === '') {
                if ($lessonUpload === null && $examUpload === null) {
                    $message = 'Please upload at least one timetable file.';
                } else {
                    $entry = $timetables[$selectedClass] ?? [];
                    $entry['department'] = $classOptions[$selectedClass];
                    $entry['updated_by'] = $user['full_name'];
                    $entry['updated_at'] = date('Y-m-d H:i:s');
                    if ($lessonUpload !== null) {
                        $entry['lesson_timetable'] = $lessonUpload;
                    }
                    if ($examUpload !== null) {
                        $entry['exam_timetable'] = $examUpload;
                    }
                    $timetables[$selectedClass] = $entry;
                    save_json_data('timetables', $timetables);
                    $message = 'Timetable uploaded successfully for ' . htmlspecialchars($selectedClass) . '.';
                }
            }
        }
    }
}

$visibleTimetables = [];
$role = $user['role'] ?? '';
if ($role === 'Academic Master') {
    $visibleTimetables = $timetables;
} elseif ($role === 'Student') {
    $studentClass = $user['class'] ?? '';
    if ($studentClass !== '' && isset($timetables[$studentClass])) {
        $visibleTimetables[$studentClass] = $timetables[$studentClass];
    }
} elseif (in_array($role, ['Teacher', 'Head of Departments'], true)) {
    $department = $user['department'] ?? '';
    foreach ($timetables as $className => $entry) {
        if (($entry['department'] ?? '') === $department) {
            $visibleTimetables[$className] = $entry;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Class Timetables</title>
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
        <h1>Class Timetables</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($role === 'Academic Master'): ?>
            <section class="section-light section">
                <div class="container">
                    <h2>Upload class timetables</h2>
                    <form method="post" enctype="multipart/form-data" class="form-grid">
                        <label for="class">Class</label>
                        <select id="class" name="class" required>
                            <option value="">Choose class</option>
                            <?php foreach ($classOptions as $className => $department): ?>
                                <option value="<?= htmlspecialchars($className) ?>"><?= htmlspecialchars($className . ' — ' . $department) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="lesson_timetable_file">Lesson timetable file</label>
                        <input id="lesson_timetable_file" name="lesson_timetable_file" type="file" accept=".pdf,.doc,.docx,.xlsx,.xls,.csv" />
                        <label for="exam_timetable_file">Exam timetable file</label>
                        <input id="exam_timetable_file" name="exam_timetable_file" type="file" accept=".pdf,.doc,.docx,.xlsx,.xls,.csv" />
                        <button class="button" type="submit">Upload timetable</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if (empty($visibleTimetables)): ?>
            <div class="alert note">No timetables are available for your role yet.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Class</th><th>Department</th><th>Lesson timetable</th><th>Exam timetable</th><th>Updated by</th><th>Updated at</th><?php if ($role === 'Academic Master'): ?><th>Actions</th><?php endif; ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($visibleTimetables as $className => $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($className) ?></td>
                            <td><?= htmlspecialchars($entry['department'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($entry['lesson_timetable']['saved_name'])): ?>
                                    <a href="uploads/<?= rawurlencode($entry['lesson_timetable']['saved_name']) ?>" target="_blank">Open</a>
                                    <a href="uploads/<?= rawurlencode($entry['lesson_timetable']['saved_name']) ?>" download class="button button-secondary">Download</a>
                                <?php else: ?>
                                    None
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($entry['exam_timetable']['saved_name'])): ?>
                                    <a href="uploads/<?= rawurlencode($entry['exam_timetable']['saved_name']) ?>" target="_blank">Open</a>
                                    <a href="uploads/<?= rawurlencode($entry['exam_timetable']['saved_name']) ?>" download class="button button-secondary">Download</a>
                                <?php else: ?>
                                    None
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($entry['updated_by'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($entry['updated_at'] ?? '-') ?></td>
                            <?php if ($role === 'Academic Master'): ?>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="class" value="<?= htmlspecialchars($className) ?>" />
                                    <?php if (!empty($entry['lesson_timetable']['saved_name'])): ?>
                                        <button class="button button-secondary" type="submit" name="action" value="delete_lesson" onclick="return confirm('Are you sure you want to delete the lesson timetable for <?= htmlspecialchars($className) ?>?');">Delete lesson</button>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['exam_timetable']['saved_name'])): ?>
                                        <button class="button button-secondary" type="submit" name="action" value="delete_exam" onclick="return confirm('Are you sure you want to delete the exam timetable for <?= htmlspecialchars($className) ?>?');">Delete exam</button>
                                    <?php endif; ?>
                                    <button class="button button-secondary" type="submit" name="action" value="delete_class" onclick="return confirm('Are you sure you want to remove all timetable data for <?= htmlspecialchars($className) ?>? This cannot be undone.');">Remove all</button>
                                </form>
                            </td>
                            <?php endif; ?>
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
</body>
</html>
