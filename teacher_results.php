<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
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
$teacherSubjects = [];
foreach ($subjects as $code => $meta) {
    if (($meta['teacher'] ?? '') === $user['username']) {
        $teacherSubjects[$code] = $meta;
    }
}
$students = array_filter($users, fn($info) => $info['role'] === 'Student');
$results = load_json_data('results', []);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $student = $_POST['student'] ?? '';
    $score = trim($_POST['score'] ?? '');
    $grade = trim($_POST['grade'] ?? '');

    if (!isset($teacherSubjects[$subject])) {
        $message = 'You can only update results for your subjects.';
    } elseif (!isset($students[$student])) {
        $message = 'Invalid student selected.';
    } elseif (!in_array($subject, $enrollments[$student] ?? [], true)) {
        $message = 'This student is not enrolled in that subject.';
    } elseif ($score === '' || $grade === '') {
        $message = 'Please provide both score and grade.';
    } else {
        $results[$student][$subject] = [
            'score' => (int)$score,
            'grade' => $grade,
            'updated_by' => $user['full_name'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        save_json_data('results', $results);
        $message = 'Result updated successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Results</title>
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
        <h1>Manage Student Results</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <div class="content-grid">
            <div class="content-card">
                <h2>Record or edit a result</h2>
                <form method="post" class="form-grid">
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Choose subject</option>
                        <?php foreach ($teacherSubjects as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code . ' - ' . $meta['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="student">Student</label>
                    <select id="student" name="student" required>
                        <option value="">Choose student</option>
                        <?php foreach ($students as $username => $studentInfo): ?>
                            <option value="<?= htmlspecialchars($username) ?>"><?= htmlspecialchars($studentInfo['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="score">Score</label>
                    <input id="score" name="score" type="number" min="0" max="100" required />

                    <label for="grade">Grade</label>
                    <input id="grade" name="grade" type="text" required />

                    <button class="button" type="submit">Save Result</button>
                </form>
            </div>
            <div class="content-card">
                <h2>Your subject scores</h2>
                <?php if (empty($teacherSubjects)): ?>
                    <p>You are not assigned to any subject in the system.</p>
                <?php else: ?>
                    <?php foreach ($teacherSubjects as $code => $meta): ?>
                        <h3><?= htmlspecialchars($code . ' - ' . $meta['name']) ?></h3>
                        <table class="data-table">
                            <thead>
                                <tr><th>Student</th><th>Score</th><th>Grade</th><th>Updated</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($enrollments as $studentUsername => $codes): ?>
                                <?php if (!in_array($code, $codes, true)) continue; ?>
                                <?php $row = $results[$studentUsername][$code] ?? null; ?>
                                <tr>
                                    <td><?= htmlspecialchars($users[$studentUsername]['full_name'] ?? $studentUsername) ?></td>
                                    <td><?= htmlspecialchars($row['score'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['grade'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['updated_at'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
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
