<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact School System</title>
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
        <h1>Contact Support</h1>
        <p>If you need credentials or help accessing your dashboard, contact the system administrator below.</p>
        <div class="content-grid">
            <section class="content-card">
                <h2>School Support</h2>
                <p>Email: <a href="mailto:support@schoolsystem.local">support@schoolsystem.local</a></p>
                <p>Phone: +254 700 000 000</p>
            </section>
            <section class="content-card">
                <h2>Login Help</h2>
                <p>Use the login page to enter your credentials. The system routes you to the right dashboard automatically.</p>
            </section>
        </div>
        <a class="button" href="index.php">Go to Login</a>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; <?= date('Y') ?> School System.</p>
            <p><a href="index.php">Login</a> | <a href="about.php">About</a></p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
