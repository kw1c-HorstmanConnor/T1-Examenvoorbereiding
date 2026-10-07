<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Database.php';

// -------------------------------------------------------------------------
// Calendar month
// -------------------------------------------------------------------------

// Returns the requested month, defaulting to the current month for invalid input.
function maple_events_calendar_month(?string $requestedMonth): DateTimeImmutable
{
    $requestedMonth = trim((string) $requestedMonth);
    $month = DateTimeImmutable::createFromFormat('!Y-m', $requestedMonth);

    if (!$month || $month->format('Y-m') !== $requestedMonth) {
        return new DateTimeImmutable('first day of this month midnight');
    }

    return $month;
}

// -------------------------------------------------------------------------
// Calendar events
// -------------------------------------------------------------------------

function maple_events_normalize_row(array $event): ?array
{
    $datetime = trim((string) ($event['Start_time'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $datetime)) {
        return null;
    }

    return [
        'id' => (int) ($event['evenementen_id'] ?? 0),
        'title' => (string) ($event['Titel'] ?? ''),
        'description' => trim((string) ($event['Omschrijving'] ?? '')),
        'datetime' => $datetime,
        'dateKey' => substr($datetime, 0, 10),
        'startLabel' => strlen($datetime) >= 16 ? substr($datetime, 11, 5) : '',
        'location' => trim((string) ($event['Locatie'] ?? '')),
    ];
}

// Loads the events scheduled within the selected month and groups them by date.
function maple_events_by_date(DateTimeImmutable $month): array
{
    $eventsByDate = [];
    $statement = null;

    try {
        $database = maple_db();
        $monthStart = $month->format('Y-m-d 00:00:00');
        $nextMonthStart = $month->modify('+1 month')->format('Y-m-d 00:00:00');
        $statement = $database->prepare('
            SELECT `evenementen_id`, `Titel`, `Start_time`, `Omschrijving`, `Locatie`
            FROM `evenementen`
            WHERE `Start_time` >= ? AND `Start_time` < ?
            ORDER BY `Start_time` ASC
        ');

        if (!$statement) {
            throw new RuntimeException('Event query prepare failed: ' . $database->error);
        }

        $statement->bind_param('ss', $monthStart, $nextMonthStart);
        if (!$statement->execute()) {
            throw new RuntimeException('Event query execute failed: ' . $statement->error);
        }

        $result = $statement->get_result();

        if (!$result instanceof mysqli_result) {
            throw new RuntimeException('Event query returned no result set.');
        }

        foreach ($result->fetch_all(MYSQLI_ASSOC) as $event) {
            $normalizedEvent = maple_events_normalize_row($event);

            if ($normalizedEvent !== null) {
                $eventsByDate[$normalizedEvent['dateKey']][] = $normalizedEvent;
            }
        }

        $result->free();
    } catch (Throwable $exception) {
        error_log('Event calendar events could not be loaded: ' . $exception->getMessage());
    } finally {
        if ($statement instanceof mysqli_stmt) {
            $statement->close();
        }
    }

    return $eventsByDate;
}

// -------------------------------------------------------------------------
// Calendar view data
// -------------------------------------------------------------------------

// Builds the dates, labels, navigation values, and events used by the calendar.
function maple_events_calendar_data(?string $requestedMonth): array
{
    $month = maple_events_calendar_month($requestedMonth);
    $months = [
        1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april',
        5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'augustus',
        9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
    ];

    return [
        'month' => $month,
        'days_in_month' => (int) $month->format('t'),
        'first_weekday' => (int) $month->format('N'),
        'next_month' => $month->modify('+1 month')->format('Y-m'),
        'previous_month' => $month->modify('-1 month')->format('Y-m'),
        'can_go_previous' => true,
        'month_label' => ucfirst($months[(int) $month->format('n')]) . ' ' . $month->format('Y'),
        'weekdays' => ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'],
        'events_by_date' => maple_events_by_date($month),
    ];
}
