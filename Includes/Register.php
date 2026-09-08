<?php
declare(strict_types=1);

require_once __DIR__ . '/Login.php';

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

    [$phoneIsValid, $phoneNumber] = maple_normalize_phone_number(
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
