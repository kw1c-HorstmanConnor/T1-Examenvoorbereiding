<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Session.php';

function maple_logout(): void
{
    maple_start_session();
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
}

function maple_safe_local_redirect(string $redirectPath, string $fallbackPath): string
{
    $redirectPath = trim($redirectPath);

    if ($redirectPath === '' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $redirectPath) || substr($redirectPath, 0, 2) === '//') {
        return $fallbackPath;
    }

    return $redirectPath;
}

function maple_handle_logout(string $redirectPath): void
{
    maple_logout();
    header('Location: ' . maple_safe_local_redirect($redirectPath, '../Index.php?view=home'));
    exit;
}
