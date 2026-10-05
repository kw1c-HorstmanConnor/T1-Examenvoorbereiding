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

function maple_login_safe_redirect_path(string $redirectPath, string $fallbackPath = ''): string
{
    $redirectPath = trim($redirectPath);

    if (
        $redirectPath === ''
        || preg_match('/[\x00-\x1f\x7f]/i', $redirectPath)
        || preg_match('/^[a-z][a-z0-9+.-]*:/i', $redirectPath)
        || substr($redirectPath, 0, 2) === '//'
        || substr($redirectPath, 0, 1) === '\\'
    ) {
        return $fallbackPath;
    }

    return $redirectPath;
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

function maple_email_exists(string $email): bool
{
    $email = trim($email);

    if ($email === '') {
        return false;
    }

    $db = maple_db();
    $statement = $db->prepare('SELECT `User_id` FROM `User` WHERE LOWER(`Email`) = LOWER(?) LIMIT 1');

    if (!$statement) {
        throw new RuntimeException('Could not prepare email lookup.');
    }

    $statement->bind_param('s', $email);
    $statement->execute();
    $result = $statement->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $statement->close();

    return $exists;
}

function maple_default_role_id(): ?int
{
    $db = maple_db();
    $statement = $db->prepare('SELECT `Role_id`, `Role_name` FROM `Roles` ORDER BY `Role_id` ASC');

    if (!$statement) {
        throw new RuntimeException('Could not prepare role lookup.');
    }

    $statement->execute();
    $result = $statement->get_result();
    $roles = $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    if ($roles === []) {
        return null;
    }

    $preferredRoleNames = ['user', 'customer', 'klant', 'gebruiker', 'guest', 'gast', 'member', 'bezoeker'];
    $adminRoleNames = ['admin', 'administrator', 'beheerder'];

    foreach ($preferredRoleNames as $preferredRoleName) {
        foreach ($roles as $role) {
            if (strtolower(trim((string) $role['Role_name'])) === $preferredRoleName) {
                return (int) $role['Role_id'];
            }
        }
    }

    foreach ($roles as $role) {
        $roleName = strtolower(trim((string) $role['Role_name']));

        if (!in_array($roleName, $adminRoleNames, true)) {
            return (int) $role['Role_id'];
        }
    }

    return null;
}

function maple_role_exists(int $roleId): bool
{
    $db = maple_db();
    $statement = $db->prepare('SELECT `Role_id` FROM `Roles` WHERE `Role_id` = ? LIMIT 1');

    if (!$statement) {
        throw new RuntimeException('Could not prepare role validation.');
    }

    $statement->bind_param('i', $roleId);
    $statement->execute();
    $result = $statement->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $statement->close();

    return $exists;
}

function maple_register_user(
    string $voornaam,
    string $achternaam,
    string $email,
    ?string $telefoonnummer,
    string $password,
    int $roleId
): ?int {
    if (!maple_table_has_column('User', 'Password_hash')) {
        throw new RuntimeException('Password hash column is missing.');
    }

    if (!maple_role_exists($roleId)) {
        throw new RuntimeException('Registration role does not exist.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $db = maple_pdo();
    $statement = $db->prepare('
        INSERT INTO `User`
            (`Voornaam`, `Role_id`, `Achternaam`, `Email`, `Telefoonnummer`, `Password_hash`)
        VALUES
            (:voornaam, :role_id, :achternaam, :email, :telefoonnummer, :password_hash)
    ');

    $statement->bindValue(':voornaam', $voornaam, PDO::PARAM_STR);
    $statement->bindValue(':role_id', $roleId, PDO::PARAM_INT);
    $statement->bindValue(':achternaam', $achternaam, PDO::PARAM_STR);
    $statement->bindValue(':email', $email, PDO::PARAM_STR);
    $statement->bindValue(
        ':telefoonnummer',
        $telefoonnummer,
        $telefoonnummer === null ? PDO::PARAM_NULL : PDO::PARAM_STR
    );
    $statement->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);

    if (!$statement->execute()) {
        throw new RuntimeException('Could not create user.');
    }

    $newUserId = (int) $db->lastInsertId();

    return $newUserId > 0 ? $newUserId : null;
}

function maple_handle_login(array $post, string $adminRedirect, string $userRedirect): array
{
    require_once __DIR__ . '/Authorization.php';

    $adminRedirect = maple_login_safe_redirect_path($adminRedirect, 'Admin.php');
    $userRedirect = maple_login_safe_redirect_path($userRedirect, '../Index.php?view=home');
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
