<?php
declare(strict_types=1);

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

function maple_normalize_phone_number(string $countryCode, string $phone): array
{
    $countryCode = trim($countryCode);
    $phone = trim($phone);
    $countryOptions = maple_phone_country_options();

    if (!isset($countryOptions[$countryCode])) {
        return [false, null];
    }

    if ($phone === '') {
        return [true, null];
    }

    $compactPhone = preg_replace('/[\s\-\(\)\.\[\]\/]+/', '', $phone);

    if (!is_string($compactPhone) || !preg_match('/^\+?\d+$/', $compactPhone)) {
        return [false, null];
    }

    $countryDigits = preg_replace('/\D+/', '', $countryCode);

    if (!is_string($countryDigits) || $countryDigits === '') {
        return [false, null];
    }

    if (str_starts_with($compactPhone, $countryCode)) {
        $compactPhone = substr($compactPhone, strlen($countryCode));
    } elseif (str_starts_with($compactPhone, '00' . $countryDigits)) {
        $compactPhone = substr($compactPhone, strlen('00' . $countryDigits));
    } elseif (str_starts_with($compactPhone, '+') || str_starts_with($compactPhone, '00')) {
        return [false, null];
    }

    $localDigits = preg_replace('/\D+/', '', $compactPhone);

    if (!is_string($localDigits) || $localDigits === '') {
        return [false, null];
    }

    if (str_starts_with($localDigits, '0')) {
        $localDigits = substr($localDigits, 1);
    }

    $internationalDigits = $countryDigits . $localDigits;

    if (strlen($internationalDigits) < 7 || strlen($internationalDigits) > 15) {
        return [false, null];
    }

    return [true, $countryCode . $localDigits];
}
