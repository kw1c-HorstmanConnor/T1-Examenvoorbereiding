<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/View.php';

function maple_home_date_label(string $date): string
{
    if ($date === '') {
        return 'Selecteer datum';
    }

    $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
        return 'Selecteer datum';
    }

    return $dateObject->format('d-m-Y');
}

function maple_home_guest_count($guestValue): int
{
    return max(1, min(12, (int) $guestValue));
}

function maple_home_accomodation_cards(array $dbRows, array $fallbackCards): array
{
    $cards = [];
    $imageClasses = ['comfort', 'luxe', 'premium'];

    for ($index = 0; $index < 3; $index++) {
        $fallback = $fallbackCards[$index];
        $row = $dbRows[$index] ?? [];
        $voorzieningen = (string) ($row['Voorzieningen'] ?? '');

        $cards[] = [
            'huis_id' => $row['Huis_id'] ?? null,
            'title' => $row['Huis_naam'] ?? $fallback['title'],
            'guests' => isset($row['Max']) ? (int) $row['Max'] : $fallback['guests'],
            'bedrooms' => maple_feature_value($voorzieningen, '/(\d+)\s*slaapkamers?/i', (string) $fallback['bedrooms']),
            'area' => maple_feature_value($voorzieningen, '/(\d+)\s*(?:m2|m\^2|m²|vierkante meter)/i', (string) $fallback['area']),
            'description' => $row['Omschr'] ?? $fallback['description'],
            'price' => $row['PPN'] ?? $fallback['price'],
            'image_class' => $imageClasses[$index],
            'badge' => $index === 0 ? 'Populair' : '',
        ];
    }

    return $cards;
}

function maple_home_event_cards(array $dbRows, array $fallbackEvents): array
{
    $events = [];
    $imageClasses = ['campfire', 'rockies', 'canoe'];

    for ($index = 0; $index < 3; $index++) {
        $fallback = $fallbackEvents[$index];
        $row = $dbRows[$index] ?? [];

        $events[] = [
            'title' => $row['Titel'] ?? $fallback['title'],
            'datetime' => $row['Start_time'] ?? $fallback['datetime'],
            'description' => $row['Omschrijving'] ?? $fallback['description'],
            'location' => $row['Locatie'] ?? '',
            'image_class' => $imageClasses[$index],
        ];
    }

    return $events;
}
