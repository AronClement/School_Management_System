<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_users(): array
{
    return require __DIR__ . '/users.php';
}

function find_user(string $username, string $password): ?array
{
    $users = get_users();
    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        return $users[$username];
    }
    return null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['username'] ?? '') && !empty($_SESSION['role'] ?? '');
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function ensure_logged_out(): void
{
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): array
{
    return [
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}
