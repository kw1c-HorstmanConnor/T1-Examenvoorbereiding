<?php

$conn = new mysqli("localhost", "root", "", "camping_maple");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* Booking helpers deliberately keep database values authoritative. */
function maple_booking_date(string $value)
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date;
}

function maple_booking_accommodation(mysqli $database, int $huisId)
{
    $statement = $database->prepare('SELECT Huis_id, Huis_naam, PPN, `Max` FROM accomodaties WHERE Huis_id = ? LIMIT 1');
    if (!$statement) {
        return null;
    }

    $statement->bind_param('i', $huisId);
    $statement->execute();
    $result = $statement->get_result();
    $accommodation = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return is_array($accommodation) ? $accommodation : null;
}

function maple_booking_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function maple_booking_blocked_columns(mysqli $database)
{
    $columnsResult = $database->query('SHOW COLUMNS FROM `blocked`');
    if (!$columnsResult) {
        return null;
    }

    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[strtolower((string) $column['Field'])] = (string) $column['Field'];
    }

    $findColumn = static function (array $names) use ($columns) {
        foreach ($names as $name) {
            if (isset($columns[strtolower($name)])) {
                return $columns[strtolower($name)];
            }
        }
        return null;
    };

    $huisColumn = $findColumn(['Huis_id']);
    $startColumn = $findColumn(['StartDate', 'Start_date', 'start_date', 'Aankomst', 'Arrival']);
    $endColumn = $findColumn(['EindDate', 'Eind_date', 'end_date', 'Vertrek', 'Departure']);

    return $huisColumn !== null && $startColumn !== null && $endColumn !== null
        ? ['huis' => $huisColumn, 'start' => $startColumn, 'end' => $endColumn]
        : null;
}

function maple_booking_is_available(mysqli $database, int $huisId, string $startDate, string $endDate)
{
    $columns = maple_booking_blocked_columns($database);
    if ($columns === null) {
        return null;
    }

    $sql = 'SELECT 1 FROM `blocked` WHERE ' . maple_booking_identifier($columns['huis'])
        . ' = ? AND ' . maple_booking_identifier($columns['start']) . ' < ?'
        . ' AND ' . maple_booking_identifier($columns['end']) . ' > ? LIMIT 1';
    $statement = $database->prepare($sql);
    if (!$statement) {
        return null;
    }

    $statement->bind_param('iss', $huisId, $endDate, $startDate);
    $statement->execute();
    $result = $statement->get_result();
    $isBlocked = $result && $result->num_rows > 0;
    $statement->close();

    return !$isBlocked;
}
