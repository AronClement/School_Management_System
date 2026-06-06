<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
$users = get_users();
$role = $user['role'];
$assignments = load_json_data('assignments', []);
$exams = load_json_data('exams', []);
$taskLists = [
    'Head Master' => [
        'Review school policies and notifications',
        'Approve new teacher assignments',
        'Monitor academic performance across departments',
    ],
    'Second Master' => [
        'Support the Head Master in administrative tasks',
        'Coordinate teacher meetings',
        'Review timetable and attendance summaries',
    ],
    'Academic Master' => [
        'Approve lesson plans and curriculum updates',
        'Track student progress reports',
        'Schedule academic reviews with teachers',
        'Review the latest announcements, calendar changes, and department notices',
        'Open performance summaries and exam or attendance reports for your role',
    ],
    'Head of Departments' => [
        'Manage department resources and staff',
        'Review department performance metrics',
        'Approve subject-level assignments',
    ],
    'Teacher' => [
        'View class schedules and lesson plans',
        'Record student assessments',
        'Send announcements to your students',
    ],
    'Student' => [
        'View your timetable and subjects',
        'Check assignments and grades',
        'Send requests to teachers and administration',
    ],
];
$tasks = $taskLists[$role] ?? ['Explore your dashboard tools and system notices.'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($role . ' Dashboard') ?></title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="dashboard.php">School System</a>
            <button class="nav-toggle" aria-label="Toggle navigation">Menu</button>
            <nav class="site-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container section">
        <div class="dashboard-header">
            <h1><?= htmlspecialchars($role) ?> Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($user['full_name']) ?>. Your credentials opened the correct dashboard for your role.</p>
        </div>

        <section class="dashboard-grid">
            <div class="dashboard-card dashboard-summary">
                <h2>Role Summary</h2>
                <p>Role: <strong><?= htmlspecialchars($role) ?></strong></p>
                <p>Username: <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            </div>
            <div class="dashboard-card dashboard-actions">
                <h2>Important Actions</h2>
                <ul>
                    <?php foreach ($tasks as $task): ?>
                        <li><?= htmlspecialchars($task) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <?php if ($role === 'Student'): 
            $data = require __DIR__ . '/subjects.php';
            $subjects = $data['subjects'] ?? [];
            $enrollments = $data['enrollments'] ?? [];
            $myEnroll = $enrollments[$user['username']] ?? [];
            $studentAssignments = [];
            foreach ($assignments as $assignment) {
                if (in_array($assignment['subject'], $myEnroll, true)) {
                    $deadline = new DateTime($assignment['deadline'] . ' 23:59:59');
                    $studentAssignments[] = [
                        'id' => $assignment['id'],
                        'subject' => $assignment['subject'],
                        'title' => $assignment['title'],
                        'deadline' => $assignment['deadline'],
                        'isClosed' => new DateTime() > $deadline,
                        'submitted' => is_array($assignment['submissions'] ?? []) && array_key_exists($user['username'], $assignment['submissions']),
                    ];
                }
            }
            $allUsers = get_users();
        ?>
        <section class="section-light section">
            <div class="container">
                <h2>Your Subjects (<?= count($myEnroll) ?>)</h2>
                <?php if (empty($myEnroll)): ?>
                    <p>You are not enrolled in any subjects for this year.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myEnroll as $code):
                            $meta = $subjects[$code] ?? null;
                            if (!$meta) continue;
                            $teacherUser = $meta['teacher'] ?? '';
                            $teacherName = $allUsers[$teacherUser]['full_name'] ?? $teacherUser;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($code) ?></td>
                                <td><?= htmlspecialchars($meta['name']) ?></td>
                                <td><?= htmlspecialchars($teacherName) ?></td>
                                <td><a href="view_result.php?subject=<?= urlencode($code) ?>">View Results</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        <section class="section-light section">
            <div class="container">
                <h2>Your Assignments</h2>
                <?php if (empty($studentAssignments)): ?>
                    <p>No active assignments have been published for your subjects yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($studentAssignments as $assignment): ?>
                            <tr>
                                <td><?= htmlspecialchars($assignment['title']) ?></td>
                                <td><?= htmlspecialchars($assignment['subject']) ?></td>
                                <td><?= htmlspecialchars($assignment['deadline']) ?></td>
                                <td><?= $assignment['isClosed'] ? 'Closed' : ($assignment['submitted'] ? 'Submitted' : 'Open') ?></td>
                                <td>
                                    <?php if ($assignment['isClosed']): ?>
                                        Closed
                                    <?php else: ?>
                                        <a href="submit_assignment.php?id=<?= htmlspecialchars((string)$assignment['id']) ?>"><?= $assignment['submitted'] ? 'Update' : 'Submit' ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($role === 'Teacher'): 
            $data = require __DIR__ . '/subjects.php';
            $subjects = $data['subjects'] ?? [];
            $teacherDepartment = $users[$user['username']]['department'] ?? '';
            $hodUser = null;
            foreach ($users as $username => $info) {
                if (($info['role'] ?? '') === 'Head of Departments' && (($info['department'] ?? '') === $teacherDepartment)) {
                    $hodUser = $info;
                    break;
                }
            }
            $teacherAssignments = [];
            foreach ($assignments as $assignment) {
                if (($assignment['teacher'] ?? '') === $user['username']) {
                    $teacherAssignments[] = $assignment;
                }
            }
            $pendingExams = 0;
            foreach ($exams as $exam) {
                if (($exam['teacher'] ?? '') === $user['username'] && ($exam['status'] ?? '') === 'Pending review') {
                    $pendingExams++;
                }
            }
        ?>
        <section class="section-light section">
            <div class="container">
                <h2>Teacher tools</h2>
                <div class="card-grid">
                    <article class="card">
                        <h3>Department</h3>
                        <p><?= htmlspecialchars($teacherDepartment ?: 'Not assigned') ?></p>
                        <p><strong>HOD:</strong> <?= htmlspecialchars($hodUser['full_name'] ?? 'Not assigned') ?></p>
                    </article>
                    <article class="card">
                        <h3>Assignments</h3>
                        <p>You have created <?= htmlspecialchars((string)count($teacherAssignments)) ?> assignments.</p>
                        <p><a href="teacher_assignments.php">Create or manage assignments</a></p>
                    </article>
                    <article class="card">
                        <h3>Exam review</h3>
                        <p><?= htmlspecialchars((string)$pendingExams) ?> exam(s) pending review.</p>
                        <p><a href="teacher_exams.php">Send exam to Academic Master</a></p>
                    </article>
                    <article class="card">
                        <h3>Student results</h3>
                        <p>Record or update results for your enrolled students.</p>
                        <p><a href="teacher_results.php">Manage student results</a></p>
                    </article>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($role === 'Head of Departments'): ?>
        <section class="section-light section">
            <div class="container">
                <h2>Department overview</h2>
                <p><a href="hod_dashboard.php">Open HOD dashboard</a></p>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($role === 'Academic Master'): ?>
        <section class="section-light section">
            <div class="container">
                <h2>Academic review</h2>
                <p><a href="academic_master.php">View exam review requests</a></p>
                <p><a href="timetable.php">Upload and manage class timetables</a></p>
                <p><a href="lesson_plans.php">Approve lesson plans and curriculum updates</a></p>
                <p><a href="progress_reports.php">Track student progress reports</a></p>
                <p><a href="academic_review_schedule.php">Schedule academic reviews with teachers</a></p>
                <p><a href="announcements.php">Review latest announcements and notices</a></p>
            </div>
        </section>
        <?php elseif ($role === 'Head of Departments' || $role === 'Teacher'): ?>
        <section class="section-light section">
            <div class="container">
                <h2>Class schedules</h2>
                <p><a href="timetable.php">View your department class timetables</a></p>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($role === 'Student'): ?>
        <section class="section-light section">
            <div class="container">
                <h2>Your timetable</h2>
                <p><a href="timetable.php">View your class timetable</a></p>
            </div>
        </section>
        <?php endif; ?>

        <section class="section-light section">
            <div class="container">
                <h2>Quick links</h2>
                <div class="card-grid">
                    <article class="card">
                        <h3>Profile</h3>
                        <p>Update your personal details and password in the system control panel.</p>
                    </article>
                    <article class="card">
                        <h3>School Notices</h3>
                        <p>Review the latest announcements, calendar changes, and department notices.</p>
                    </article>
                    <article class="card">
                        <h3>Reports</h3>
                        <p>Open performance summaries and exam or attendance reports for your role.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System Dashboard.</p>
            <p><a href="logout.php">Logout</a></p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
