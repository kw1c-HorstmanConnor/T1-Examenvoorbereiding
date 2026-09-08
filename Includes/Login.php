<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/Database.php';

function maple_db(): mysqli
{
    global $conn;

    if (!$conn instanceof mysqli) {
        throw new RuntimeException('Database connection unavailable.');
    }

    return $conn;
}

function maple_table_has_column(string $table, string $column): bool
{
    $allowedTables = [
        'User' => 'User',
        'Roles' => 'Roles',
    ];

    if (!isset($allowedTables[$table])) {
        return false;
    }

    try {
        $db = maple_db();
        $tableName = $allowedTables[$table];
        $statement = $db->prepare('
            SELECT COUNT(*) AS column_count
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND LOWER(TABLE_NAME) = LOWER(?)
                AND COLUMN_NAME = ?
        ');

        if (!$statement) {
            return false;
        }

        $statement->bind_param('ss', $tableName, $column);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
        $hasColumn = $row !== null && (int) $row['column_count'] > 0;
        $statement->close();

        return $hasColumn;
    } catch (Throwable $exception) {
        return false;
    }
}

function maple_authenticate_user(string $email, string $password, ?string $voornaam = null): bool
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
    ?int $telefoonnummer,
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
    $db = maple_db();
    $statement = $db->prepare('
        INSERT INTO `User`
            (`Voornaam`, `Role_id`, `Achternaam`, `Email`, `Telefoonnummer`, `Password_hash`)
        VALUES
            (?, ?, ?, ?, ?, ?)
    ');

    if (!$statement) {
        throw new RuntimeException('Could not prepare user insert.');
    }

    $statement->bind_param(
        'sissis',
        $voornaam,
        $roleId,
        $achternaam,
        $email,
        $telefoonnummer,
        $passwordHash
    );

    if (!$statement->execute()) {
        $statement->close();
        throw new RuntimeException('Could not create user.');
    }

    $newUserId = (int) $db->insert_id;
    $statement->close();

    return $newUserId > 0 ? $newUserId : null;
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
