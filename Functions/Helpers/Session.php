<?php
declare(strict_types=1);

function maple_start_session()
{
    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        session_start();
    }
}

function maple_current_user()
{
    maple_start_session();

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

maple_start_session();
