<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Head of Departments') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$users = get_users();
$subjectsData = require __DIR__ . '/subjects.php';
$assignments = load_json_data('assignments', []);
$exams = load_json_data('exams', []);
$results = load_json_data('results', []);
$department = $users[$user['username']]['department'] ?? '';
$departmentTeachers = [];
foreach ($users as $username => $info) {
    if (($info['role'] ?? '') === 'Teacher' && (($info['department'] ?? '') === $department)) {
        $departmentTeachers[$username] = $info;
    }
}
$teacherSubjects = [];
foreach ($subjectsData['subjects'] ?? [] as $code => $meta) {
    if (isset($departmentTeachers[$meta['teacher']])) {
        $teacherSubjects[$code] = $meta;
    }
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_subject') {
    $subject = $_POST['subject'] ?? '';
    $teacher = $_POST['teacher'] ?? '';
    if ($subject === '' || $teacher === '') {
        $message = 'Choose both a subject and a teacher.';
    } elseif (!isset($teacherSubjects[$subject])) {
        $message = 'You can only reassign subjects in your department.';
    } elseif (!isset($departmentTeachers[$teacher])) {
        $message = 'You can only assign a teacher from your department.';
    } else {
        $subjectsData['subjects'][$subject]['teacher'] = $teacher;
        save_json_data('subjects', $subjectsData);
        $teacherSubjects[$subject]['teacher'] = $teacher;
        $message = 'Subject assignment updated successfully.';
    }
}
$departmentResults = [];
foreach ($results as $studentUsername => $scoreData) {
    foreach ($scoreData as $subjectCode => $scoreRow) {
        if (isset($teacherSubjects[$subjectCode])) {
            $departmentResults[] = [
                'student' => $studentsFullName = $users[$studentUsername]['full_name'] ?? $studentUsername,
                'subject' => $subjectCode,
                'score' => $scoreRow['score'] ?? '-',
                'grade' => $scoreRow['grade'] ?? '-',
                'updated_at' => $scoreRow['updated_at'] ?? '-',
            ];
        }
    }
}
$activities = [];
foreach ($assignments as $assignment) {
    if (!isset($teacherSubjects[$assignment['subject']])) {
        continue;
    }
    $activities[] = [
        'time' => $assignment['created_at'] ?? '0000-00-00 00:00:00',
        'label' => 'Assignment published',
        'detail' => sprintf('%s published assignment "%s" for %s.', $users[$assignment['teacher']]['full_name'] ?? $assignment['teacher'], $assignment['title'], $assignment['subject']),
    ];
}
foreach ($exams as $exam) {
    if (!isset($teacherSubjects[$exam['subject']])) {
        continue;
    }
    $activities[] = [
        'time' => $exam['requested_at'] ?? '0000-00-00 00:00:00',
        'label' => 'Exam submitted',
        'detail' => sprintf('%s submitted exam "%s" for %s (%s).', $users[$exam['teacher']]['full_name'] ?? $exam['teacher'], $exam['title'], $exam['subject'], $exam['status']),
    ];
}
foreach ($results as $studentUsername => $scoreData) {
    $studentFullName = $users[$studentUsername]['full_name'] ?? $studentUsername;
    foreach ($scoreData as $subjectCode => $scoreRow) {
        if (!isset($teacherSubjects[$subjectCode])) {
            continue;
        }
        if (!empty($scoreRow['updated_at'])) {
            $activities[] = [
                'time' => $scoreRow['updated_at'],
                'label' => 'Result updated',
                'detail' => sprintf('%s score updated for %s by %s.', $subjectCode, $studentFullName, $scoreRow['updated_by'] ?? 'teacher'),
            ];
        }
    }
}
usort($activities, fn($a, $b) => strcmp($b['time'], $a['time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Head of Department</title>
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
        <h1>Head of Department</h1>
        <div class="content-grid">
            <article class="card">
                <h2>Department</h2>
                <p><?= htmlspecialchars($department ?: 'Not assigned') ?></p>
            </article>
            <article class="card">
                <h2>Teachers</h2>
                <ul>
                    <?php foreach ($departmentTeachers as $info): ?>
                        <li><?= htmlspecialchars($info['full_name']) ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($departmentTeachers)): ?>
                        <li>No teachers assigned yet.</li>
                    <?php endif; ?>
                </ul>
            </article>
            <article class="card">
                <h2>Subject count</h2>
                <p><?= htmlspecialchars((string)count($teacherSubjects)) ?> subjects managed by your department</p>
            </article>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <section class="section-light section">
            <div class="container">
                <h2>Assign subject to teacher</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="assign_subject" />
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Choose subject</option>
                        <?php foreach ($teacherSubjects as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code . ' - ' . $meta['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="teacher">Teacher</label>
                    <select id="teacher" name="teacher" required>
                        <option value="">Choose teacher</option>
                        <?php foreach ($departmentTeachers as $username => $info): ?>
                            <option value="<?= htmlspecialchars($username) ?>"><?= htmlspecialchars($info['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button class="button" type="submit">Save Assignment</button>
                </form>
            </div>
        </section>
        <section class="section-light section">
            <div class="container">
                <h2>Recent assignments</h2>
                <?php if (empty($assignments)): ?>
                    <p>No assignments have been published yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Title</th><th>Subject</th><th>Teacher</th><th>Deadline</th><th>Submissions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <?php if (!isset($teacherSubjects[$assignment['subject']])) continue; ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$assignment['id']) ?></td>
                                    <td><?= htmlspecialchars($assignment['title']) ?></td>
                                    <td><?= htmlspecialchars($assignment['subject']) ?></td>
                                    <td><?= htmlspecialchars($users[$assignment['teacher']]['full_name'] ?? $assignment['teacher']) ?></td>
                                    <td><?= htmlspecialchars($assignment['deadline']) ?></td>
                                    <td><?= htmlspecialchars((string)count((array)$assignment['submissions'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        <section class="section-light section">
            <div class="container">
                <h2>Exam review requests</h2>
                <?php $departmentExams = array_filter($exams, fn($exam) => isset($teacherSubjects[$exam['subject']])); ?>
                <?php if (empty($departmentExams)): ?>
                    <p>No exam review requests in your department yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Subject</th><th>Title</th><th>Teacher</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departmentExams as $exam): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$exam['id']) ?></td>
                                    <td><?= htmlspecialchars($exam['subject']) ?></td>
                                    <td><?= htmlspecialchars($exam['title']) ?></td>
                                    <td><?= htmlspecialchars($users[$exam['teacher']]['full_name'] ?? $exam['teacher']) ?></td>
                                    <td><?= htmlspecialchars($exam['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <p><a href="hod_exams.php">Review and verify exams</a></p>
            </div>
        </section>
        <section class="section-light section">
            <div class="container">
                <h2>Department student results</h2>
                <?php if (empty($departmentResults)): ?>
                    <p>No department results have been recorded yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Student</th><th>Subject</th><th>Score</th><th>Grade</th><th>Updated at</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departmentResults as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student']) ?></td>
                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['score']) ?></td>
                                    <td><?= htmlspecialchars($row['grade']) ?></td>
                                    <td><?= htmlspecialchars($row['updated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        <section class="section-light section">
            <div class="container">
                <h2>Recent department activity</h2>
                <?php if (empty($activities)): ?>
                    <p>No activity yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($activities as $activity): ?>
                            <li><strong><?= htmlspecialchars($activity['time']) ?>:</strong> <?= htmlspecialchars($activity['detail']) ?></li>
                        <?php endforeach; ?>
                    </ul>
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
