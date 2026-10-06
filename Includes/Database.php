<?php

require_once __DIR__ . '/../Functions/Helpers/DatabaseConfig.php';

$databaseConfig = maple_database_config();
$conn = new mysqli(
    $databaseConfig['host'],
    $databaseConfig['username'],
    $databaseConfig['password'],
    $databaseConfig['database']
);

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

function maple_booking_add_calendar_months(DateTimeImmutable $date, int $months): DateTimeImmutable
{
    $targetMonth = $date->modify('first day of this month')->modify('+' . $months . ' months');
    $targetDay = min((int) $date->format('d'), (int) $targetMonth->format('t'));

    return $targetMonth->setDate(
        (int) $targetMonth->format('Y'),
        (int) $targetMonth->format('m'),
        $targetDay
    );
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

function maple_booking_status_id(mysqli $database, array $labels)
{
    if ($labels === []) {
        return null;
    }

    $normalisedLabels = array_map('strtolower', $labels);
    $result = $database->query('SELECT `Status_id`, `Status` FROM `status`');
    if (!$result) {
        return null;
    }

    while ($status = $result->fetch_assoc()) {
        if (in_array(strtolower(trim((string) $status['Status'])), $normalisedLabels, true)) {
            return (int) $status['Status_id'];
        }
    }

    return null;
}

/**
 * Returns a status only when the supplied labels identify one database row.
 * Ambiguous or missing status configuration must never silently change a booking.
 */
function maple_booking_unique_status_id(mysqli $database, array $labels)
{
    if ($labels === []) {
        return null;
    }

    $normalisedLabels = array_map('strtolower', $labels);
    $result = $database->query('SELECT `Status_id`, `Status` FROM `status`');
    if (!$result) {
        return null;
    }

    $matchingIds = [];
    while ($status = $result->fetch_assoc()) {
        if (in_array(strtolower(trim((string) $status['Status'])), $normalisedLabels, true)) {
            $matchingIds[] = (int) $status['Status_id'];
        }
    }

    return count($matchingIds) === 1 ? $matchingIds[0] : null;
}

function maple_booking_active_reservation_count(mysqli $database, int $userId)
{
    $statement = $database->prepare(
        'SELECT COUNT(*) AS `active_count`
         FROM `reservaties` r
         INNER JOIN `status` s ON s.`Status_id` = r.`Status_id`
         WHERE r.`User_id` = ?
           AND r.`Out_date` > NOW()
           AND (
               LOWER(s.`Status`) IN (\'paid\', \'confirmed\', \'betaald\', \'bevestigd\')
               OR (
                   LOWER(s.`Status`) IN (\'unpaid\', \'reserved\', \'onbetaald\', \'gereserveerd\')
                   AND r.`reservation` IS NOT NULL
                   AND DATE_ADD(r.`reservation`, INTERVAL 1 MONTH) > NOW()
               )
           )'
    );
    if (!$statement) {
        return null;
    }

    $statement->bind_param('i', $userId);
    if (!$statement->execute()) {
        $statement->close();
        return null;
    }
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return is_array($row) ? (int) $row['active_count'] : null;
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

function maple_booking_is_available(mysqli $database, int $huisId, string $startDate, string $endDate, bool $lockRows = false)
{
    $columns = maple_booking_blocked_columns($database);
    if ($columns === null) {
        return null;
    }

    $sql = 'SELECT 1 FROM `blocked` WHERE ' . maple_booking_identifier($columns['huis'])
        . ' = ? AND ' . maple_booking_identifier($columns['start']) . ' < ?'
        . ' AND ' . maple_booking_identifier($columns['end']) . ' > ? LIMIT 1'
        . ($lockRows ? ' FOR UPDATE' : '');
    $statement = $database->prepare($sql);
    if (!$statement) {
        return null;
    }

    $statement->bind_param('iss', $huisId, $endDate, $startDate);
    if (!$statement->execute()) {
        $statement->close();
        return null;
    }
    $result = $statement->get_result();
    if (!$result) {
        $statement->close();
        return null;
    }
    $isBlocked = $result && $result->num_rows > 0;
    $statement->close();
    if ($isBlocked) {
        return false;
    }

    $reservationStatement = $database->prepare(
        'SELECT 1
         FROM `reservaties` r
         INNER JOIN `status` s ON s.`Status_id` = r.`Status_id`
         WHERE r.`Huis_id` = ?
           AND r.`Aan_date` < ?
           AND r.`Out_date` > ?
           AND (
               LOWER(s.`Status`) IN (\'paid\', \'confirmed\', \'betaald\', \'bevestigd\')
               OR (
                   LOWER(s.`Status`) IN (\'unpaid\', \'reserved\', \'onbetaald\', \'gereserveerd\')
                   AND r.`reservation` IS NOT NULL
                   AND DATE_ADD(r.`reservation`, INTERVAL 1 MONTH) > NOW()
               )
           )
         LIMIT 1' . ($lockRows ? ' FOR UPDATE' : '')
    );
    if (!$reservationStatement) {
        return null;
    }

    $reservationStatement->bind_param('iss', $huisId, $endDate, $startDate);
    if (!$reservationStatement->execute()) {
        $reservationStatement->close();
        return null;
    }
    $reservationResult = $reservationStatement->get_result();
    if (!$reservationResult) {
        $reservationStatement->close();
        return null;
    }
    $hasActiveReservation = $reservationResult && $reservationResult->num_rows > 0;
    $reservationStatement->close();

    return !$hasActiveReservation;
}

$conn->set_charset($databaseConfig['charset']);
//Database Include