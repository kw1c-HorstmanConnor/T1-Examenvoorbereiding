<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Database.php';

function maple_register_form_values(): array
{
    return [
        'voornaam' => '',
        'achternaam' => '',
        'email' => '',
        'phone_country_code' => '+31',
        'telefoonnummer' => '',
    ];
}

function maple_phone_country_options(): array
{
    return [
        '+31' => 'Netherlands',
        '+32' => 'Belgium',
        '+33' => 'France',
        '+41' => 'Switzerland',
        '+49' => 'Germany',
    ];
}

function maple_register_csrf_token(): string
{
    maple_start_session();

    if (empty($_SESSION['register_csrf'])) {
        $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['register_csrf'];
}

function maple_normalize_phone_number(string $countryCode, string $phone): array
{
    $countryCode = trim($countryCode);
    $phone = trim($phone);
    $countryOptions = maple_phone_country_options();

    if ($phone === '') {
        return [true, null];
    }

    if (!isset($countryOptions[$countryCode])) {
        return [false, null];
    }

    if (!preg_match('/^[0-9\-\s().]{6,16}$/', $phone)) {
        return [false, null];
    }

    $digits = preg_replace('/\D+/', '', $phone);
    $digits = ltrim((string) $digits, '0');

    if ($digits === '' || strlen($digits) < 6) {
        return [false, null];
    }

    // Telefoonnummer is currently an INT column, so keep the stored value in INT range.
    if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, '2147483647') > 0)) {
        return [false, null];
    }

    return [true, (int) $digits];
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

function maple_default_role_id()
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
    $telefoonnummer,
    string $password,
    int $roleId
) {
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

function maple_handle_registration(array $post): array
{
    $values = maple_register_form_values();
    $values['voornaam'] = trim((string) ($post['voornaam'] ?? ''));
    $values['achternaam'] = trim((string) ($post['achternaam'] ?? ''));
    $values['email'] = trim((string) ($post['email'] ?? ''));
    $values['phone_country_code'] = trim((string) ($post['phone_country_code'] ?? '+31'));
    $values['telefoonnummer'] = trim((string) ($post['telefoonnummer'] ?? ''));

    $password = (string) ($post['password'] ?? '');
    $confirmPassword = (string) ($post['confirm_password'] ?? '');
    $csrf = (string) ($post['csrf'] ?? '');
    $errors = [];

    if (!hash_equals(maple_register_csrf_token(), $csrf)) {
        return [
            'success' => false,
            'errors' => ['Your session has expired. Please try again.'],
            'values' => $values,
        ];
    }

    if (
        $values['voornaam'] === ''
        || $values['achternaam'] === ''
        || $values['email'] === ''
        || $password === ''
        || $confirmPassword === ''
    ) {
        $errors[] = 'Please fill in all required fields.';
    }

    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (
        strlen($values['voornaam']) > 255
        || strlen($values['achternaam']) > 255
        || strlen($values['email']) > 255
    ) {
        $errors[] = 'Please keep your name and email details under 255 characters.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    list($phoneIsValid, $phoneNumber) = maple_normalize_phone_number(
        $values['phone_country_code'],
        $values['telefoonnummer']
    );

    if (!$phoneIsValid) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if ($errors !== []) {
        return [
            'success' => false,
            'errors' => $errors,
            'values' => $values,
        ];
    }

    try {
        if (maple_email_exists($values['email'])) {
            return [
                'success' => false,
                'errors' => ['An account with this email address already exists.'],
                'values' => $values,
            ];
        }

        $roleId = maple_default_role_id();

        if ($roleId === null) {
            throw new RuntimeException('No default registration role is available.');
        }

        $userId = maple_register_user(
            $values['voornaam'],
            $values['achternaam'],
            $values['email'],
            $phoneNumber,
            $password,
            $roleId
        );

        if ($userId === null) {
            throw new RuntimeException('User insert did not return an id.');
        }

        $_SESSION['login_notice'] = 'Account created successfully. You can now log in.';
        unset($_SESSION['register_csrf']);

        return [
            'success' => true,
            'errors' => [],
            'values' => maple_register_form_values(),
        ];
    } catch (Throwable $exception) {
        error_log('Registration failed: ' . $exception->getMessage());

        return [
            'success' => false,
            'errors' => ['Something went wrong while creating your account.'],
            'values' => $values,
        ];
    }
}
