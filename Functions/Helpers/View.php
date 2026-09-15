<?php
declare(strict_types=1);

function maple_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function maple_home_event_date(string $dateTime): string
{
    try {
        $date = new DateTimeImmutable($dateTime);
    } catch (Throwable $exception) {
        return $dateTime;
    }

    $months = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y') . ' • ' . $date->format('H:i');
}

function maple_home_event_datetime_attr(string $dateTime): string
{
    try {
        return (new DateTimeImmutable($dateTime))->format('Y-m-d\TH:i');
    } catch (Throwable $exception) {
        return '';
    }
}

function maple_home_price($price): string
{
    $number = (float) $price;

    if (abs($number - round($number)) < 0.01) {
        return number_format($number, 0, ',', '.');
    }

    return number_format($number, 2, ',', '.');
}

function maple_feature_value(string $voorzieningen, string $pattern, string $fallback): string
{
    if (preg_match($pattern, $voorzieningen, $matches)) {
        return $matches[1];
    }

    return $fallback;
}
