<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
$users = get_users();
$subjectsData = require __DIR__ . '/subjects.php';
$results = load_json_data('results', []);

$role = $user['role'] ?? '';
$studentClasses = [];
foreach ($users as $username => $info) {
    if (($info['role'] ?? '') === 'Student') {
        $studentClasses[$username] = $info['class'] ?? 'Unknown';
    }
}

$reportRows = [];
foreach ($results as $studentUsername => $scores) {
    $total = 0;
    $count = 0;
    foreach ($scores as $subject => $row) {
        $score = isset($row['score']) ? (float)$row['score'] : 0;
        $total += $score;
        $count++;
    }
    if ($count === 0) {
        continue;
    }
    $average = round($total / $count, 1);
    $status = $average >= 75 ? 'Excellent' : ($average >= 60 ? 'Good' : ($average >= 50 ? 'Satisfactory' : 'Needs support'));
    $reportRows[] = [
        'student' => $studentUsername,
        'student_name' => $users[$studentUsername]['full_name'] ?? $studentUsername,
        'class' => $studentClasses[$studentUsername] ?? 'Unknown',
        'average' => $average,
        'status' => $status,
        'subjects' => $scores,
    ];
}

$filteredReports = [];
if ($role === 'Student') {
    $filteredReports = array_filter($reportRows, fn($row) => $row['student'] === $user['username']);
} elseif ($role === 'Teacher') {
    $teacherSubjects = [];
    foreach ($subjectsData['subjects'] ?? [] as $code => $meta) {
        if (($meta['teacher'] ?? '') === $user['username']) {
            $teacherSubjects[] = $code;
        }
    }
    foreach ($reportRows as $row) {
        foreach ($row['subjects'] as $subjectCode => $_) {
            if (in_array($subjectCode, $teacherSubjects, true)) {
                $filteredReports[] = $row;
                break;
            }
        }
    }
} elseif ($role === 'Head of Departments') {
    $department = $users[$user['username']]['department'] ?? '';
    $departmentSubjects = [];
    foreach ($subjectsData['subjects'] ?? [] as $code => $meta) {
        if (($users[$meta['teacher']]['department'] ?? '') === $department) {
            $departmentSubjects[] = $code;
        }
    }
    foreach ($reportRows as $row) {
        foreach ($row['subjects'] as $subjectCode => $_) {
            if (in_array($subjectCode, $departmentSubjects, true)) {
                $filteredReports[] = $row;
                break;
            }
        }
    }
} else {
    $filteredReports = $reportRows;
}

$attendance = load_json_data('attendance', [
    'Form 1A' => 92,
    'Form 1B' => 88,
    'Form 2A' => 85,
    'Form 2B' => 90,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Progress Reports</title>
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
        <h1>Student Progress Reports</h1>
        <?php if (empty($filteredReports)): ?>
            <div class="alert note">No progress reports are available for your role yet.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Class</th><th>Average Score</th><th>Progress</th><th>Details</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredReports as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars((string)$row['average']) ?>%</td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php foreach ($row['subjects'] as $subjectCode => $subjectRow): ?>
                                    <strong><?= htmlspecialchars($subjectCode) ?>:</strong> <?= htmlspecialchars((string)($subjectRow['score'] ?? '-')) ?>;<br />
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <section class="section-light section">
            <div class="container">
                <h2>Attendance summary</h2>
                <table class="data-table">
                    <thead>
                        <tr><th>Class</th><th>Attendance</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $className => $rate): ?>
                            <tr>
                                <td><?= htmlspecialchars($className) ?></td>
                                <td><?= htmlspecialchars((string)$rate) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
