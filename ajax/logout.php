<?php
session_start();
$isAdmin = isset($_SESSION['role']) && (int) $_SESSION['role'] === 2;
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
header('Location: ' . ($isAdmin ? '../admin/index.php' : '../index.php'));
exit;
