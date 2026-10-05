<?php
declare(strict_types=1);

/**
 * Escapes a value before it is displayed in the admin panel HTML.
 */
function maple_admin_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Converts a datetime-local form value to the database datetime format.
 *
 * Returns null when the submitted value is empty or invalid.
 */
function maple_admin_datetime(string $value)
{
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value);

    return $date && $date->format('Y-m-d\\TH:i') === $value ? $date->format('Y-m-d H:i:s') : null;
}

/**
 * Stores a one-time admin notification and returns the user to the selected tab.
 */
function maple_admin_redirect(string $tab, string $notice)
{
    $_SESSION['admin_notice'] = $notice;
    header('Location: AdminPanel.php?tab=' . rawurlencode($tab));
    exit;
}

/**
 * Trims text and safely limits it to the requested number of characters.
 */
function maple_admin_text(string $value, int $maxLength = 255): string
{
    $value = trim($value);

    return mb_strlen($value) <= $maxLength ? $value : mb_substr($value, 0, $maxLength);
}

/**
 * Provides the permitted compass-direction values for admin location fields.
 *
 * @return string[]
 */
function maple_admin_location_options(): array
{
    return ['North', 'Northeast', 'East', 'Southeast', 'South', 'Southwest', 'West', 'Northwest'];
}

/**
 * Checks whether a submitted location is one of the permitted directions.
 */
function maple_admin_valid_location(string $location): bool
{
    return in_array($location, maple_admin_location_options(), true);
}
