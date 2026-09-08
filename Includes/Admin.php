<?php
declare(strict_types=1);

require_once __DIR__ . '/Login.php';

function maple_user_is_admin(): bool
{
    $user = maple_current_user();

    if ($user === null) {
        return false;
    }

    $roleName = strtolower(trim($user['role_name']));

    return in_array($roleName, ['admin', 'administrator', 'beheerder'], true);
}

function maple_require_admin()
{
    if (maple_user_is_admin()) {
        return;
    }

    http_response_code(403);
    exit('Geen toegang.');
}
