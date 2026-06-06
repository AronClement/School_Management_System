<?php
require_once 'auth.php';
ensure_logged_out();
$errors = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '') {
        $errors[] = 'Please enter your username.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $user = find_user($username, $password);
        if ($user === null) {
            $errors[] = 'Credentials not recognized. Please use a valid username and password.';
        } else {
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School System Login</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="index.php">School System</a>
            <button class="nav-toggle" aria-label="Toggle navigation">Menu</button>
            <nav class="site-nav">
                <a href="index.php">Login</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </nav>
        </div>
    </header>

    <main class="container section">
        <section class="hero login-hero">
            <div class="container hero-content">
                <h1>School Dashboard Login</h1>
                <p>Enter your credentials to open the correct dashboard for your role.</p>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form class="form-grid login-form" method="post" action="index.php">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?= htmlspecialchars($username) ?>" required />

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required />

                    <button class="button" type="submit">Login</button>
                </form>
                <div class="alert note">
                    <strong>Sample credentials:</strong>
                    <ul>
                        <li>Head Master: <code>headmaster</code> / <code>head123</code></li>
                        <li>Second Master: <code>secondmaster</code> / <code>second123</code></li>
                        <li>Academic Master: <code>academicmaster</code> / <code>academic123</code></li>
                        <li>Head of Departments: <code>hod</code> / <code>hod123</code></li>
                        <li>Teacher: <code>teacher1</code> / <code>teach123</code></li>
                        <li>Student: <code>student1</code> / <code>study123</code></li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
            <p><a href="about.php">About</a> | <a href="contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
