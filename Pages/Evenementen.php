<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';
require_once __DIR__ . '/../Functions/Events/EventFunc.php';

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
$eventsForPopup = [];

foreach ($eventsByDate as $eventDate => $events) {
    $eventsForPopup[$eventDate] = array_map(static function (array $event): array {
        $popupEvent = [
            'id' => (int) $event['id'],
            'title' => (string) $event['title'],
            'datetime' => (string) $event['datetime'],
            'startLabel' => (string) $event['startLabel'],
        ];
        $description = trim((string) ($event['description'] ?? ''));
        $location = trim((string) ($event['location'] ?? ''));

        if ($description !== '') {
            $popupEvent['description'] = $description;
        }

        if ($location !== '') {
            $popupEvent['location'] = $location;
        }

        return $popupEvent;
    }, $events);
}

$eventsForPopupJson = json_encode(
    $eventsForPopup,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($eventsForPopupJson === false) {
    error_log('Event calendar JSON encoding failed: ' . json_last_error_msg());
    $eventsForPopupJson = '{}';
}

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
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
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
                        <h2 class="event-calendar__month" id="calendar-title" data-calendar-month-label="<?= htmlspecialchars($month->format('Y-m'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
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
                        <?php foreach ($weekdays as $weekdayIndex => $weekday): ?>
                            <span data-calendar-weekday="<?= (int) $weekdayIndex + 1; ?>"><?= htmlspecialchars(substr($weekday, 0, 2), ENT_QUOTES, 'UTF-8'); ?></span>
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
                            $dayClasses = ['event-calendar__day'];
                            $visibleEventLimit = 1;
                            $dayEventCount = count($dayEvents);

                            if ($dayEvents !== []) {
                                $dayClasses[] = 'event-calendar__day--has-events';
                            }
                            ?>
                            <article class="<?= htmlspecialchars(implode(' ', $dayClasses), ENT_QUOTES, 'UTF-8'); ?>" data-calendar-date="<?= htmlspecialchars($dateKey, ENT_QUOTES, 'UTF-8'); ?>" role="button" tabindex="0" aria-haspopup="dialog" aria-label="Open events for <?= htmlspecialchars($date->format('d-m-Y'), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="event-calendar__date">
                                    <span class="event-calendar__day-name"><?= htmlspecialchars($weekdays[(int) $date->format('N') - 1], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <time datetime="<?= $dateKey; ?>"><?= $day; ?></time>
                                </div>
                                <?php if ($dayEvents !== []): ?>
                                    <div class="event-calendar__events" aria-label="Events op <?= htmlspecialchars($date->format('d-m-Y'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php foreach ($dayEvents as $eventIndex => $event): ?>
                                            <?php if ($eventIndex < $visibleEventLimit): ?>
                                                <?php $eventStartTime = (string) $event['startLabel']; ?>
                                                <span class="event-calendar__event-tab" data-calendar-event-date="<?= htmlspecialchars($dateKey, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <span class="event-calendar__event-time"><?= htmlspecialchars($eventStartTime, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="event-calendar__event-title"><?= htmlspecialchars((string) $event['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if ($dayEventCount > $visibleEventLimit): ?>
                                            <button class="event-calendar__more-button" type="button" data-calendar-event-date="<?= htmlspecialchars($dateKey, ENT_QUOTES, 'UTF-8'); ?>" data-calendar-show-more aria-label="Show all events for <?= htmlspecialchars($date->format('d-m-Y'), ENT_QUOTES, 'UTF-8'); ?>">
                                                Show more
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endfor; ?>
                    </div>
                </div>

                <dialog class="calendar-day-dialog" id="calendar-day-dialog" role="dialog" aria-modal="true" aria-labelledby="calendar-day-dialog-title">
                    <button class="calendar-day-dialog__close" type="button" data-calendar-dialog-close aria-label="Close day events">&times;</button>
                    <div class="calendar-day-dialog__header">
                        <p class="calendar-day-dialog__label">MAPLE CAMP CALENDAR</p>
                        <div class="calendar-day-dialog__title-row">
                            <h3 id="calendar-day-dialog-title"></h3>
                            <span class="calendar-day-dialog__today" data-calendar-dialog-today hidden>Today</span>
                        </div>
                        <p class="calendar-day-dialog__count" data-calendar-dialog-count></p>
                    </div>
                    <div class="calendar-day-dialog__body" data-calendar-dialog-body></div>
                </dialog>
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

        const localDateKey = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        };

        const todayKey = localDateKey(new Date());
        const calendarEventsByDate = <?= $eventsForPopupJson; ?>;
        const dayDialog = document.getElementById('calendar-day-dialog');
        const dayDialogClose = dayDialog.querySelector('[data-calendar-dialog-close]');
        const dayDialogTitle = document.getElementById('calendar-day-dialog-title');
        const dayDialogToday = dayDialog.querySelector('[data-calendar-dialog-today]');
        const dayDialogCount = dayDialog.querySelector('[data-calendar-dialog-count]');
        const dayDialogBody = dayDialog.querySelector('[data-calendar-dialog-body]');
        const calendarMonthLabel = document.querySelector('[data-calendar-month-label]');
        const calendarWeekdays = document.querySelectorAll('[data-calendar-weekday]');
        const calendarDayNames = document.querySelectorAll('.event-calendar__day[data-calendar-date] .event-calendar__day-name');
        const calendarLocales = {
            en: 'en-GB',
            es: 'es-ES',
            fr: 'fr-FR',
            de: 'de-DE',
        };
        let activeCalendarDay = null;

        const translateCalendarText = (value) => {
            if (!window.MapleLanguage) {
                return value;
            }

            return window.MapleLanguage.translate(value);
        };

        const formatCalendarDate = (date) => {
            const language = window.MapleLanguage ? window.MapleLanguage.getLanguage() : 'en';
            const locale = calendarLocales[language] || calendarLocales.en;

            return new Intl.DateTimeFormat(locale, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            }).format(date);
        };

        const localizeCalendarHeader = () => {
            const language = window.MapleLanguage ? window.MapleLanguage.getLanguage() : 'en';
            const locale = calendarLocales[language] || calendarLocales.en;
            const monthParts = calendarMonthLabel.dataset.calendarMonthLabel.split('-').map(Number);
            const monthDate = new Date(monthParts[0], monthParts[1] - 1, 1);
            calendarMonthLabel.textContent = new Intl.DateTimeFormat(locale, {
                month: 'long',
                year: 'numeric',
            }).format(monthDate);

            calendarWeekdays.forEach((weekday) => {
                const weekdayIndex = Number(weekday.dataset.calendarWeekday);
                const weekdayDate = new Date(2024, 0, weekdayIndex);
                weekday.textContent = new Intl.DateTimeFormat(locale, {weekday: 'short'}).format(weekdayDate);
            });

            calendarDayNames.forEach((dayName) => {
                const calendarDay = dayName.closest('[data-calendar-date]');

                if (!calendarDay) {
                    return;
                }

                const dateParts = calendarDay.dataset.calendarDate.split('-').map(Number);
                const calendarDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
                dayName.textContent = new Intl.DateTimeFormat(locale, {weekday: 'short'}).format(calendarDate);
            });

            dayDialogToday.textContent = translateCalendarText('Today');
        };

        localizeCalendarHeader();

        const dateFromKey = (dateKey) => {
            const [year, month, day] = dateKey.split('-').map(Number);

            return new Date(year, month - 1, day);
        };

        const eventCountLabel = (eventCount) => {
            if (eventCount === 0) {
                return translateCalendarText('No events planned');
            }

            return translateCalendarText(eventCount === 1 ? '1 event planned' : `${eventCount} events planned`);
        };

        const createTextElement = (tagName, className, text) => {
            const element = document.createElement(tagName);
            element.className = className;
            element.textContent = text;

            return element;
        };

        const renderDayDialogEvents = (events, dateKey) => {
            dayDialogBody.replaceChildren();

            if (events.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'calendar-day-dialog__empty';
                emptyState.append(
                    createTextElement('h4', '', translateCalendarText('No events planned for this day.')),
                    createTextElement('p', '', translateCalendarText('A quiet day at Maple Camp.'))
                );
                dayDialogBody.append(emptyState);

                return;
            }

            events.forEach((eventItem) => {
                const eventCard = document.createElement('article');
                eventCard.className = 'calendar-day-dialog__event-card';

                if (dateKey < todayKey) {
                    eventCard.classList.add('calendar-day-dialog__event-card--past');
                }

                eventCard.append(createTextElement('h4', '', eventItem.title));

                const eventMeta = document.createElement('p');
                eventMeta.className = 'calendar-day-dialog__event-meta';

                if (eventItem.startLabel) {
                    eventMeta.append(createTextElement('span', '', `${eventItem.startLabel} ${translateCalendarText('uur')}`));
                }

                if (eventItem.location) {
                    eventMeta.append(createTextElement('span', '', eventItem.location));
                }

                if (eventMeta.children.length > 0) {
                    eventCard.append(eventMeta);
                }

                if (eventItem.description) {
                    eventCard.append(createTextElement('p', 'calendar-day-dialog__event-description', eventItem.description));
                }

                dayDialogBody.append(eventCard);
            });
        };

        const openDayDialog = (calendarDay) => {
            const dateKey = calendarDay.dataset.calendarDate;
            const events = [...(calendarEventsByDate[dateKey] ?? [])].sort((firstEvent, secondEvent) => (
                String(firstEvent.datetime ?? '').localeCompare(String(secondEvent.datetime ?? ''))
            ));

            activeCalendarDay = calendarDay;
            dayDialogTitle.textContent = formatCalendarDate(dateFromKey(dateKey));
            dayDialogToday.hidden = dateKey !== todayKey;
            dayDialogCount.textContent = eventCountLabel(events.length);
            dayDialog.classList.toggle('calendar-day-dialog--past', dateKey < todayKey);
            dayDialog.classList.toggle('calendar-day-dialog--today', dateKey === todayKey);
            renderDayDialogEvents(events, dateKey);

            if (!dayDialog.open) {
                dayDialog.showModal();
            }

            document.body.classList.add('calendar-dialog-open');
            requestAnimationFrame(() => dayDialogClose.focus());
        };

        const closeDayDialog = () => {
            if (dayDialog.open) {
                dayDialog.close();
            }
        };

        document.querySelectorAll('.event-calendar__day[data-calendar-date]').forEach((calendarDay) => {
            const dateKey = calendarDay.dataset.calendarDate;
            const eventTabs = calendarDay.querySelectorAll('.event-calendar__event-tab[data-calendar-event-date], .event-calendar__more-button[data-calendar-event-date]');

            if (dateKey < todayKey) {
                calendarDay.classList.add('calendar-day--past');
                eventTabs.forEach((eventTab) => eventTab.classList.add('calendar-event--past'));
            }

            if (dateKey === todayKey) {
                calendarDay.classList.add('calendar-day--today');
                eventTabs.forEach((eventTab) => eventTab.classList.add('calendar-event--today'));
            }

            calendarDay.addEventListener('click', () => {
                openDayDialog(calendarDay);
            });

            calendarDay.addEventListener('keydown', (event) => {
                if (event.target.closest('[data-calendar-show-more]')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
                    event.preventDefault();
                    openDayDialog(calendarDay);
                }
            });

            calendarDay.querySelectorAll('[data-calendar-show-more]').forEach((showMoreButton) => {
                showMoreButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    openDayDialog(calendarDay);
                });
            });
        });

        document.querySelectorAll('[data-calendar-month]').forEach((link) => {
            link.addEventListener('click', () => {
                sessionStorage.setItem(calendarScrollKey, String(window.scrollY));
            });
        });

        dayDialog.addEventListener('click', (event) => {
            if (event.target === dayDialog || event.target.closest('[data-calendar-dialog-close]')) {
                closeDayDialog();
            }
        });

        dayDialog.addEventListener('close', () => {
            document.body.classList.remove('calendar-dialog-open');

            if (activeCalendarDay) {
                activeCalendarDay.focus({ preventScroll: true });
                activeCalendarDay = null;
            }
        });

        window.addEventListener('maple:languagechange', () => {
            localizeCalendarHeader();

            if (!activeCalendarDay || !dayDialog.open) {
                return;
            }

            const dateKey = activeCalendarDay.dataset.calendarDate;
            const events = [...(calendarEventsByDate[dateKey] ?? [])].sort((firstEvent, secondEvent) => (
                String(firstEvent.datetime ?? '').localeCompare(String(secondEvent.datetime ?? ''))
            ));

            dayDialogTitle.textContent = formatCalendarDate(dateFromKey(dateKey));
            dayDialogCount.textContent = eventCountLabel(events.length);
            renderDayDialogEvents(events, dateKey);
        });
    </script>
</body>
</html>
