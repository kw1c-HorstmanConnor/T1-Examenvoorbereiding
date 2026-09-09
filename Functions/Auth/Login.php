<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Database.php';

function maple_login_csrf_token(): string
{
    maple_start_session();

    if (empty($_SESSION['login_csrf'])) {
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['login_csrf'];
}

function maple_login_notice(array $query): string
{
    maple_start_session();

    if (!empty($_SESSION['login_notice'])) {
        $notice = (string) $_SESSION['login_notice'];
        unset($_SESSION['login_notice']);

        return $notice;
    }

    if (($query['registered'] ?? '') === '1') {
        return 'Account created successfully. You can now log in.';
    }

    return '';
}

function maple_authenticate_user(string $email, string $password, $voornaam = null): bool
{
    $email = trim($email);
    $voornaam = $voornaam !== null ? trim($voornaam) : null;

    if ($email === '' || $password === '') {
        return false;
    }

    if (!maple_table_has_column('User', 'Password_hash')) {
        return false;
    }

    try {
        $db = maple_db();
        $sql = '
            SELECT
                u.`User_id`,
                u.`Voornaam`,
                u.`Achternaam`,
                u.`Email`,
                u.`Telefoonnummer`,
                u.`Role_id`,
                u.`Password_hash`,
                r.`Role_name`
            FROM `User` u
            INNER JOIN `Roles` r ON r.`Role_id` = u.`Role_id`
            WHERE LOWER(u.`Email`) = LOWER(?)
        ';

        if ($voornaam !== null && $voornaam !== '') {
            $sql .= ' AND u.`Voornaam` = ?';
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);

        if (!$statement) {
            return false;
        }

        if ($voornaam !== null && $voornaam !== '') {
            $statement->bind_param('ss', $email, $voornaam);
        } else {
            $statement->bind_param('s', $email);
        }

        $statement->execute();
        $result = $statement->get_result();
        $user = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
        $statement->close();

        if (!$user || !password_verify($password, (string) $user['Password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['User_id'];
        $_SESSION['voornaam'] = (string) $user['Voornaam'];
        $_SESSION['role_id'] = (int) $user['Role_id'];
        $_SESSION['role_name'] = (string) $user['Role_name'];

        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

function maple_handle_login(array $post, string $adminRedirect, string $userRedirect): array
{
    require_once __DIR__ . '/Authorization.php';

    $username = trim((string) ($post['username'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $password = (string) ($post['password'] ?? '');
    $csrf = (string) ($post['csrf'] ?? '');

    if (!hash_equals(maple_login_csrf_token(), $csrf)) {
        return [
            'success' => false,
            'error' => 'De sessie is verlopen. Probeer opnieuw.',
            'values' => [
                'username' => $username,
                'email' => $email,
            ],
        ];
    }

    if (maple_authenticate_user($email, $password, $username !== '' ? $username : null)) {
        header('Location: ' . (maple_user_is_admin() ? $adminRedirect : $userRedirect));
        exit;
    }

    return [
        'success' => false,
        'error' => 'Controleer je naam, e-mailadres en wachtwoord.',
        'values' => [
            'username' => $username,
            'email' => $email,
        ],
    ];
}
