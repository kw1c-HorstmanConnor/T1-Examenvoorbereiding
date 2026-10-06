<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Database.php';
require_once __DIR__ . '/../Facilities/Facilities.php';

function maple_accommodations_columns(PDO $pdo): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    $columns = [];
    $statement = $pdo->query('SHOW COLUMNS FROM `accomodaties`');

    foreach ($statement->fetchAll() as $column) {
        $field = (string) ($column['Field'] ?? '');
        if ($field !== '') {
            $columns[strtolower($field)] = $field;
        }
    }

    return $columns;
}

function maple_accommodations_find_column(array $columns, array $names): string
{
    foreach ($names as $name) {
        $key = strtolower((string) $name);
        if (isset($columns[$key])) {
            return $columns[$key];
        }
    }

    return '';
}

function maple_accommodations_select_list(PDO $pdo): string
{
    $columns = maple_accommodations_columns($pdo);
    $descriptionColumn = maple_accommodations_find_column($columns, ['Omschrijving', 'Omschr']);
    $imageColumn = maple_accommodations_find_column($columns, ['Afbeelding']);

    $descriptionSelect = $descriptionColumn !== ''
        ? 'a.`' . str_replace('`', '``', $descriptionColumn) . '` AS `Omschrijving`'
        : "'' AS `Omschrijving`";
    $imageSelect = $imageColumn !== ''
        ? 'a.`' . str_replace('`', '``', $imageColumn) . '` AS `Afbeelding`'
        : "'' AS `Afbeelding`";

    return implode(",\n            ", [
        'a.`Huis_id`',
        'a.`Huis_naam`',
        'a.`Locatie`',
        'a.`PPN`',
        'a.`Voorzieningen`',
        'a.`Max`',
        $descriptionSelect,
        $imageSelect,
    ]);
}

function maple_accommodations_normalize(array $rows): array
{
    foreach ($rows as $index => $row) {
        $rows[$index]['Huis_id'] = (int) ($row['Huis_id'] ?? 0);
        $rows[$index]['Huis_naam'] = (string) ($row['Huis_naam'] ?? '');
        $rows[$index]['Locatie'] = (string) ($row['Locatie'] ?? '');
        $rows[$index]['PPN'] = (float) ($row['PPN'] ?? 0);
        $rows[$index]['Voorzieningen'] = (string) ($row['Voorzieningen'] ?? '');
        $rows[$index]['Max'] = (int) ($row['Max'] ?? 0);
        $rows[$index]['Omschrijving'] = (string) ($row['Omschrijving'] ?? '');
        $rows[$index]['Afbeelding'] = (string) ($row['Afbeelding'] ?? '');
        $rows[$index]['booking_count'] = (int) ($row['booking_count'] ?? 0);
    }

    return $rows;
}

function maple_accommodations_load_all(): array
{
    $pdo = maple_pdo();
    $statement = $pdo->prepare('
        SELECT
            ' . maple_accommodations_select_list($pdo) . '
        FROM `accomodaties` AS a
        ORDER BY a.`Huis_id` ASC
    ');
    $statement->execute();

    return maple_accommodations_normalize($statement->fetchAll());
}

function maple_home_accommodations_by_reservations(int $limit = 3): array
{
    $pdo = maple_pdo();
    $statement = $pdo->prepare('
        SELECT
            ' . maple_accommodations_select_list($pdo) . ',
            COALESCE(bookings.`booking_count`, 0) AS `booking_count`
        FROM `accomodaties` AS a
        LEFT JOIN (
            SELECT `Huis_id`, COUNT(*) AS `booking_count`
            FROM `reservaties`
            GROUP BY `Huis_id`
        ) AS bookings ON bookings.`Huis_id` = a.`Huis_id`
        ORDER BY `booking_count` DESC, a.`Huis_id` DESC
        LIMIT :limit
    ');
    $statement->bindValue('limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    return maple_accommodations_normalize($statement->fetchAll());
}

function maple_home_accommodations_by_newest(int $limit = 3): array
{
    $pdo = maple_pdo();
    $statement = $pdo->prepare('
        SELECT
            ' . maple_accommodations_select_list($pdo) . ',
            0 AS `booking_count`
        FROM `accomodaties` AS a
        ORDER BY a.`Huis_id` DESC
        LIMIT :limit
    ');
    $statement->bindValue('limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    return maple_accommodations_normalize($statement->fetchAll());
}

function maple_home_accommodations(int $limit = 3): array
{
    try {
        return [
            'selection' => 'reservations',
            'items' => maple_home_accommodations_by_reservations($limit),
        ];
    } catch (Throwable $exception) {
        error_log('Homepage accommodations reservation ranking failed: ' . $exception->getMessage());
    }

    try {
        return [
            'selection' => 'newest',
            'items' => maple_home_accommodations_by_newest($limit),
        ];
    } catch (Throwable $exception) {
        error_log('Homepage accommodations newest fallback failed: ' . $exception->getMessage());
    }

    return [
        'selection' => 'unavailable',
        'items' => [],
    ];
}

function maple_accommodation_facility_names(array $accommodation, int $limit = 3): array
{
    $facilityKeys = maple_facility_parse_values((string) ($accommodation['Voorzieningen'] ?? ''));

    return array_slice(maple_facility_names_from_keys($facilityKeys), 0, max(0, $limit));
}

function maple_accommodation_excerpt(string $description, int $limit = 130): string
{
    $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');

    if ($description === '') {
        return '';
    }

    $length = function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description);
    if ($length <= $limit) {
        return $description;
    }

    $sliceLength = max(0, $limit - 3);
    $excerpt = function_exists('mb_substr')
        ? mb_substr($description, 0, $sliceLength, 'UTF-8')
        : substr($description, 0, $sliceLength);

    return rtrim($excerpt) . '...';
}

function maple_accommodation_detail_url(array $accommodation, string $basePath = ''): string
{
    return $basePath . 'Pages/Accomodatie.php#huis-' . (int) ($accommodation['Huis_id'] ?? 0);
}
