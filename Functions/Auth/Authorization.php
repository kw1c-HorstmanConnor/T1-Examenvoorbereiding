<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Database.php';

function maple_admin_is_pdo_connection($connection): bool
{
    return class_exists('PDO', false) && $connection instanceof PDO;
}

function maple_admin_is_mysqli_connection($connection): bool
{
    return class_exists('mysqli', false) && $connection instanceof mysqli;
}

function maple_admin_database_connection()
{
    maple_load_database_connection();

    $connectionNames = ['pdo', 'conn', 'con', 'connection', 'db', 'mysqli', 'database', 'dbConnection', 'databaseConnection'];

    foreach ($connectionNames as $connectionName) {
        if (!isset($GLOBALS[$connectionName])) {
            continue;
        }

        $connection = $GLOBALS[$connectionName];

        if (maple_admin_is_pdo_connection($connection) || maple_admin_is_mysqli_connection($connection)) {
            return $connection;
        }
    }

    foreach ($GLOBALS as $connection) {
        if (maple_admin_is_pdo_connection($connection) || maple_admin_is_mysqli_connection($connection)) {
            return $connection;
        }
    }

    return null;
}

function maple_current_user_role_name()
{
    $user = maple_current_user();

    if ($user === null) {
        return null;
    }

    $connection = maple_admin_database_connection();

    if (maple_admin_is_pdo_connection($connection)) {
        try {
            $statement = $connection->prepare(
                'SELECT r.Role_name
                 FROM `User` AS u
                 INNER JOIN `Roles` AS r ON u.Role_id = r.Role_id
                 WHERE u.User_id = :user_id
                 LIMIT 1'
            );
            $statement->execute(['user_id' => $user['user_id']]);
            $roleName = $statement->fetchColumn();
        } catch (Throwable $exception) {
            return null;
        }

        if (is_string($roleName) && trim($roleName) !== '') {
            $_SESSION['role_name'] = $roleName;
            return $roleName;
        }

        return null;
    }

    if (maple_admin_is_mysqli_connection($connection)) {
        try {
            $statement = $connection->prepare(
                'SELECT r.Role_name
                 FROM `User` AS u
                 INNER JOIN `Roles` AS r ON u.Role_id = r.Role_id
                 WHERE u.User_id = ?
                 LIMIT 1'
            );

            if ($statement === false) {
                return null;
            }

            $userId = (int) $user['user_id'];
            $statement->bind_param('i', $userId);
            $statement->execute();

            if (method_exists($statement, 'get_result')) {
                $result = $statement->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $roleName = is_array($row) ? ($row['Role_name'] ?? null) : null;
            } else {
                $roleName = null;
                $statement->bind_result($roleName);
                $statement->fetch();
            }

            $statement->close();
        } catch (Throwable $exception) {
            return null;
        }

        if (is_string($roleName) && trim($roleName) !== '') {
            $_SESSION['role_name'] = $roleName;
            return $roleName;
        }

        return null;
    }

    $sessionRoleName = trim((string) ($user['role_name'] ?? ''));

    return $sessionRoleName !== '' ? $sessionRoleName : null;
}

function maple_user_is_logged_in(): bool
{
    return maple_current_user() !== null;
}

function maple_user_has_role(string $roleName): bool
{
    $currentRoleName = maple_current_user_role_name();

    return $currentRoleName !== null && strtolower(trim($currentRoleName)) === strtolower(trim($roleName));
}

function maple_user_is_admin(): bool
{
    return maple_user_has_role('admin');
}

function maple_admin_login_path(): string
{
    $scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    return basename($scriptDir) === 'Pages' ? 'Login.php' : 'Pages/Login.php';
}

function maple_require_login($loginPath = null)
{
    if (maple_current_user() !== null) {
        return;
    }

    header('Location: ' . ($loginPath ?? maple_admin_login_path()));
    exit;
}

function maple_require_admin($loginPath = null)
{
    maple_require_login($loginPath);

    if (maple_user_is_admin()) {
        return;
    }

    http_response_code(403);
    exit('Access denied');
}
