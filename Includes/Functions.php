<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Database.php';

// -------------------------------------------------------------------------
// Calendar month
// -------------------------------------------------------------------------

// Returns the requested month, with the current month as the earliest option.
function maple_events_calendar_month(?string $requestedMonth): DateTimeImmutable
{
    $currentMonth = new DateTimeImmutable('first day of this month midnight');
    $requestedMonth = trim((string) $requestedMonth);
    $month = DateTimeImmutable::createFromFormat('!Y-m', $requestedMonth);

    if (!$month || $month->format('Y-m') !== $requestedMonth || $month < $currentMonth) {
        return $currentMonth;
    }

    return $month;
}

// -------------------------------------------------------------------------
// Calendar events
// -------------------------------------------------------------------------

// Loads the events scheduled within the selected month and groups them by date.
function maple_events_by_date(DateTimeImmutable $month): array
{
    $eventsByDate = [];

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
            return $eventsByDate;
        }

        $statement->bind_param('ss', $monthStart, $nextMonthStart);
        $statement->execute();
        $result = $statement->get_result();

        if ($result instanceof mysqli_result) {
            foreach ($result->fetch_all(MYSQLI_ASSOC) as $event) {
                $eventDate = substr((string) $event['Start_time'], 0, 10);
                $eventsByDate[$eventDate][] = $event;
            }

            $result->free();
        }

        $statement->close();
    } catch (Throwable $exception) {
        // Keep the calendar visible when the database is unavailable.
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
    $currentMonth = new DateTimeImmutable('first day of this month midnight');
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
        'can_go_previous' => $month > $currentMonth,
        'month_label' => ucfirst($months[(int) $month->format('n')]) . ' ' . $month->format('Y'),
        'weekdays' => ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'],
        'events_by_date' => maple_events_by_date($month),
    ];
}
