<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function maple_authenticate_user($email, $password, $voornaam = null)
{
    return false;
}

function maple_register_user(
    $voornaam,
    $achternaam,
    $email,
    $telefoonnummer,
    $password,
    $roleId
) {
    return null;
}

function maple_current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'user_id' => (int) $_SESSION['user_id'],
        'voornaam' => (string) ($_SESSION['voornaam'] ?? ''),
        'role_id' => (int) ($_SESSION['role_id'] ?? 0),
        'role_name' => (string) ($_SESSION['role_name'] ?? ''),
    ];
}

function maple_logout()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
