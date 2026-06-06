<?php
require_once 'auth.php';
require_once 'users.php';
require_once 'data_store.php';
require_login();
$user = current_user();
if ($user['role'] !== 'Academic Master') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$lessonPlans = load_json_data('lesson_plans', []);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $planId = (int)($_POST['plan_id'] ?? 0);
    $reason = trim($_POST['reject_reason'] ?? '');

    foreach ($lessonPlans as &$plan) {
        if (($plan['id'] ?? 0) !== $planId) {
            continue;
        }
        if ($plan['status'] !== 'Pending') {
            $message = 'This item has already been reviewed.';
            break;
        }
        if ($action === 'approve') {
            $plan['status'] = 'Approved';
            $plan['reviewed_by'] = $user['full_name'];
            $plan['reviewed_at'] = date('Y-m-d H:i:s');
            $message = 'Lesson plan/curriculum update approved.';
        } elseif ($action === 'reject') {
            if ($reason === '') {
                $message = 'Please provide a reason for rejection.';
            } else {
                $plan['status'] = 'Rejected';
                $plan['rejected_reason'] = $reason;
                $plan['reviewed_by'] = $user['full_name'];
                $plan['reviewed_at'] = date('Y-m-d H:i:s');
                $message = 'Lesson plan/curriculum update rejected.';
            }
        }
        break;
    }
    unset($plan);
    save_json_data('lesson_plans', $lessonPlans);
}

$pendingPlans = array_filter($lessonPlans, fn($plan) => ($plan['status'] ?? '') === 'Pending');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Approve Lesson Plans and Curriculum Updates</title>
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
        <h1>Approve Lesson Plans and Curriculum Updates</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (empty($pendingPlans)): ?>
            <div class="alert note">No pending lesson plan or curriculum updates at the moment.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Type</th><th>Teacher</th><th>Subject</th><th>Title</th><th>Notes</th><th>Attachment</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingPlans as $plan): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$plan['id']) ?></td>
                            <td><?= htmlspecialchars($plan['type']) ?></td>
                            <td><?= htmlspecialchars($plan['teacher_name'] ?? $plan['teacher']) ?></td>
                            <td><?= htmlspecialchars($plan['subject']) ?></td>
                            <td><?= htmlspecialchars($plan['title']) ?></td>
                            <td><?= htmlspecialchars($plan['notes'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($plan['attachment']['saved_name'])): ?>
                                    <a href="uploads/<?= rawurlencode($plan['attachment']['saved_name']) ?>" target="_blank">Open</a>
                                <?php else: ?>
                                    None
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" class="form-grid">
                                    <input type="hidden" name="plan_id" value="<?= htmlspecialchars((string)$plan['id']) ?>" />
                                    <button class="button" type="submit" name="action" value="approve">Approve</button>
                                    <label for="reject_reason_<?= htmlspecialchars((string)$plan['id']) ?>">Reject reason</label>
                                    <input id="reject_reason_<?= htmlspecialchars((string)$plan['id']) ?>" name="reject_reason" type="text" placeholder="Reason" />
                                    <button class="button" type="submit" name="action" value="reject">Reject</button>
                                </form>
                            </td>
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
