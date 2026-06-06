<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About School System</title>
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
        <h1>About the School Dashboard</h1>
        <p>This website provides role-based dashboards for school administration and learners.</p>
        <div class="content-grid">
            <section class="content-card">
                <h2>Available roles</h2>
                <ul>
                    <li>Head Master</li>
                    <li>Second Master</li>
                    <li>Academic Master</li>
                    <li>Head of Departments</li>
                    <li>Teacher</li>
                    <li>Student</li>
                </ul>
            </section>
            <section class="content-card">
                <h2>How it works</h2>
                <p>Each user logs in with credentials assigned by the system administrator. The page then opens the dashboard matched to that role.</p>
            </section>
        </div>
        <a class="button" href="index.php">Go to Login</a>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
            <p><a href="index.php">Login</a> | <a href="contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
