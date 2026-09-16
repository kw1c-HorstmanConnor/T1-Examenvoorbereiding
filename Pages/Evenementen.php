<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';
require_once __DIR__ . '/../Includes/Functions.php';

$calendar = maple_events_calendar_data($_GET['month'] ?? null);
$month = $calendar['month'];
$daysInMonth = $calendar['days_in_month'];
$firstWeekday = $calendar['first_weekday'];
$nextMonth = $calendar['next_month'];
$previousMonth = $calendar['previous_month'];
$canGoPrevious = $calendar['can_go_previous'];
$monthLabel = $calendar['month_label'];
$weekdays = $calendar['weekdays'];
$eventsByDate = $calendar['events_by_date'];

$basePath = '../';
$assetBase = '../';
$currentPage = 'events';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evenementen - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/evenementen.css">
</head>
<body class="home-view">
    <div class="home-page">
        <section class="home-hero" aria-labelledby="page-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="home-hero__content page-container">
                <p class="section-label section-label--light">EVENTS</p>
                <h1 class="home-hero__title" id="page-heading">Aankomende events</h1>
            </div>
        </section>

        <main>
            <section class="home-section">
                <div class="page-container section-header">
                    <div>
                        <p class="section-label">KALENDER</p>
                        <h2 class="section-title">Evenementen op Maple Camp</h2>
                        <p class="section-copy">Seizoensactiviteiten, kampvuuravonden en begeleide tochten brengen gasten samen in de natuur.</p>
                    </div>
                    <a class="outline-button" href="../Index.php?view=home#events">Terug naar events</a>
                </div>

                <div class="page-container event-calendar" aria-labelledby="calendar-title">
                    <div class="event-calendar__header">
                        <h2 class="event-calendar__month" id="calendar-title"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="event-calendar__navigation" aria-label="Kalendernavigatie">
                            <?php if ($canGoPrevious): ?>
                                <a class="outline-button outline-button--small" href="?month=<?= htmlspecialchars($previousMonth, ENT_QUOTES, 'UTF-8'); ?>" data-calendar-month>Vorige maand</a>
                            <?php else: ?>
                                <span class="outline-button outline-button--small event-calendar__button--disabled" aria-disabled="true">Vorige maand</span>
                            <?php endif; ?>
                            <a class="outline-button outline-button--small" href="?month=<?= htmlspecialchars($nextMonth, ENT_QUOTES, 'UTF-8'); ?>" data-calendar-month>Volgende maand <span class="button-arrow" aria-hidden="true"></span></a>
                        </div>
                    </div>

                    <div class="event-calendar__weekdays" aria-hidden="true">
                        <?php foreach ($weekdays as $weekday): ?>
                            <span><?= htmlspecialchars(substr($weekday, 0, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="event-calendar__grid">
                        <?php for ($emptyDay = 1; $emptyDay < $firstWeekday; $emptyDay++): ?>
                            <div class="event-calendar__empty" aria-hidden="true"></div>
                        <?php endfor; ?>

                        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                            <?php
                            $date = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), $day);
                            $dateKey = $date->format('Y-m-d');
                            $dayEvents = $eventsByDate[$dateKey] ?? [];
                            ?>
                            <article class="event-calendar__day<?= $dayEvents !== [] ? ' event-calendar__day--has-events' : ''; ?>">
                                <div class="event-calendar__date">
                                    <span class="event-calendar__day-name"><?= htmlspecialchars($weekdays[(int) $date->format('N') - 1], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <time datetime="<?= $dateKey; ?>"><?= $day; ?></time>
                                </div>
                                <?php if ($dayEvents !== []): ?>
                                    <div class="event-calendar__events" aria-label="Events op <?= htmlspecialchars($date->format('d-m-Y'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php foreach ($dayEvents as $event): ?>
                                            <?php
                                            $eventId = (int) $event['evenementen_id'];
                                            $dialogId = 'event-dialog-' . $eventId;
                                            $eventStartTime = substr((string) $event['Start_time'], 11, 5);
                                            ?>
                                            <button class="event-calendar__event-tab" type="button" data-event-dialog="<?= $dialogId; ?>" aria-haspopup="dialog">
                                                <?= htmlspecialchars((string) $event['Titel'], ENT_QUOTES, 'UTF-8'); ?>
                                            </button>
                                            <dialog class="event-dialog" id="<?= $dialogId; ?>" aria-labelledby="<?= $dialogId; ?>-title">
                                                <button class="event-dialog__close" type="button" data-close-dialog aria-label="Sluit eventdetails">&times;</button>
                                                <p class="event-dialog__label">EVENT DETAILS</p>
                                                <h3 id="<?= $dialogId; ?>-title"><?= htmlspecialchars((string) $event['Titel'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <p class="event-dialog__time"><span>Start</span> <?= htmlspecialchars($eventStartTime, ENT_QUOTES, 'UTF-8'); ?> uur</p>
                                                <p class="event-dialog__description"><?= htmlspecialchars((string) ($event['Omschrijving'] ?: 'Geen omschrijving beschikbaar.'), ENT_QUOTES, 'UTF-8'); ?></p>
                                            </dialog>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
    <script>
        const calendarScrollKey = 'maple-events-calendar-scroll-y';
        const savedCalendarScrollY = sessionStorage.getItem(calendarScrollKey);

        if (savedCalendarScrollY !== null) {
            sessionStorage.removeItem(calendarScrollKey);
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            requestAnimationFrame(() => {
                window.scrollTo({
                    top: Number(savedCalendarScrollY),
                    behavior: reduceMotion ? 'auto' : 'smooth',
                });
            });
        }

        document.querySelectorAll('[data-calendar-month]').forEach((link) => {
            link.addEventListener('click', () => {
                sessionStorage.setItem(calendarScrollKey, String(window.scrollY));
            });
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-event-dialog]');

            if (trigger) {
                event.preventDefault();
                document.getElementById(trigger.dataset.eventDialog)?.showModal();
            }

            if (event.target.matches('[data-close-dialog]')) {
                event.target.closest('dialog')?.close();
            }

            if (event.target instanceof HTMLDialogElement) {
                event.target.close();
            }
        });
    </script>
</body>
</html>
