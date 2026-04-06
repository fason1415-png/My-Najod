<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_login(string $login, string $password): bool
{
    $validLogin = 'admin';
    $validPassword = 'admin123';

    if ($login === $validLogin && $password === $validPassword) {
        $_SESSION['auth'] = [
            'login' => $validLogin,
            'name' => 'Bosh Administrator',
            'role' => 'Super Admin',
            'logged_at' => date('Y-m-d H:i:s'),
        ];
        return true;
    }
    return false;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function is_logged_in(): bool
{
    return isset($_SESSION['auth']) && is_array($_SESSION['auth']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user(): array
{
    return $_SESSION['auth'] ?? [];
}
