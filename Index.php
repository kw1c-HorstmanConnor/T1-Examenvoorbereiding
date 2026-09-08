<?php

$view = $_GET['view'] ?? 'splash';
$isHomeView = $view === 'home';
$bodyClass = $isHomeView ? 'home-view' : 'splash-view';
$pageTitle = $isHomeView ? 'Maple Camp - Home' : 'Maple Camp';
$basePath = '';
$assetBase = '';
$currentPage = 'home';

function maple_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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

$requestedArrival = trim((string) ($_GET['aankomst'] ?? ''));
$requestedDeparture = trim((string) ($_GET['vertrek'] ?? ''));
$requestedGuests = max(1, min(12, (int) ($_GET['gasten'] ?? 2)));
$arrivalLabel = maple_home_date_label($requestedArrival);
$departureLabel = maple_home_date_label($requestedDeparture);

$fallbackAccomodations = [
    [
        'title' => 'Bungalow Comfort',
        'guests' => 4,
        'bedrooms' => 2,
        'area' => 45,
        'description' => 'Sfeervolle bungalow met alles wat je nodig hebt voor een ontspannen verblijf in de natuur.',
        'price' => 120,
    ],
    [
        'title' => 'Bungalow Luxe',
        'guests' => 4,
        'bedrooms' => 2,
        'area' => 60,
        'description' => 'Ruim en luxe ingericht met extra comfort en een prachtig uitzicht op de bergen.',
        'price' => 145,
    ],
    [
        'title' => 'Bungalow Premium',
        'guests' => 6,
        'bedrooms' => 3,
        'area' => 75,
        'description' => 'Extra ruim, modern en stijlvol. Perfect voor een langer verblijf of extra luxe.',
        'price' => 175,
    ],
];

$fallbackEvents = [
    [
        'title' => 'Kampvuur avond',
        'datetime' => '2026-05-24 20:00:00',
        'description' => 'Gezellige avond bij het kampvuur met live muziek en marshmallows.',
    ],
    [
        'title' => 'Wandeltocht Rockies',
        'datetime' => '2026-05-26 09:00:00',
        'description' => 'Begeleide wandeltocht door de prachtige Rocky Mountains.',
    ],
    [
        'title' => 'Canoe Experience',
        'datetime' => '2026-05-28 10:00:00',
        'description' => 'Ontdek het meer tijdens een ontspannen canoe tocht.',
    ],
];

$homeAccomodations = maple_home_accomodation_cards([], $fallbackAccomodations);
$homeEvents = maple_home_event_cards([], $fallbackEvents);
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Styling/index.css">
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($isHomeView): ?>
    <div class="home-page">
        <section class="home-hero" aria-labelledby="home-heading">
            <?php include __DIR__ . '/Includes/Header.php'; ?>

            <div class="home-hero__content page-container">
                <h1 class="home-hero__title" id="home-heading">Enjoy<br>the Canadian Wild</h1>

                <form class="booking-panel" id="booking" action="Index.php#accommodaties" method="get">
                    <input type="hidden" name="view" value="home">
                    <label class="booking-field">
                        <span class="booking-field__label">Aankomst</span>
                        <span class="booking-field__control">
                            <input class="booking-field__input" type="date" name="aankomst" value="<?= maple_e($requestedArrival); ?>" aria-label="Aankomstdatum">
                            <span class="booking-field__value"><?= maple_e($arrivalLabel); ?></span>
                            <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="booking-field">
                        <span class="booking-field__label">Vertrek</span>
                        <span class="booking-field__control">
                            <input class="booking-field__input" type="date" name="vertrek" value="<?= maple_e($requestedDeparture); ?>" aria-label="Vertrekdatum">
                            <span class="booking-field__value"><?= maple_e($departureLabel); ?></span>
                            <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="booking-field">
                        <span class="booking-field__label">Gasten</span>
                        <span class="booking-field__control">
                            <select class="booking-field__input" name="gasten" aria-label="Aantal gasten">
                                <?php foreach ([1, 2, 3, 4, 5, 6] as $guestOption): ?>
                                    <option value="<?= $guestOption; ?>"<?= $requestedGuests === $guestOption ? ' selected' : ''; ?>><?= $guestOption; ?> <?= $guestOption === 1 ? 'gast' : 'gasten'; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="booking-field__value"><?= $requestedGuests; ?> <?= $requestedGuests === 1 ? 'gast' : 'gasten'; ?></span>
                            <span class="booking-field__icon booking-field__icon--guests" aria-hidden="true"></span>
                        </span>
                    </label>

                    <button class="booking-panel__submit" type="submit">Zoek beschikbaarheid</button>
                </form>
            </div>
        </section>

        <main>
            <section class="home-section accommodations" id="accommodaties">
                <div class="page-container">
                    <div class="section-header">
                        <div>
                            <p class="section-label">ACCOMMODATIES</p>
                            <h2 class="section-title">Comfort midden in de natuur</h2>
                            <p class="section-copy">Onze accommodaties zijn sfeervol, comfortabel en van alle gemakken voorzien.<br>Kies de accommodatie die bij jou past en geniet van een onvergetelijk verblijf.</p>
                        </div>
                        <a class="outline-button" href="Pages/Accomodatie.php">Bekijk alle accommodaties <span class="button-arrow" aria-hidden="true"></span></a>
                    </div>

                    <div class="accommodation-grid">
                        <?php foreach ($homeAccomodations as $accomodation): ?>
                            <article class="accommodation-card">
                                <div class="accommodation-card__image accommodation-card__image--<?= maple_e($accomodation['image_class']); ?>">
                                    <?php if ($accomodation['badge'] !== ''): ?>
                                        <span class="popular-badge"><?= maple_e($accomodation['badge']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="accommodation-card__body">
                                    <h3><?= maple_e($accomodation['title']); ?></h3>
                                    <div class="accommodation-meta" aria-label="Kenmerken">
                                        <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i><?= (int) $accomodation['guests']; ?> personen</span>
                                        <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i><?= maple_e($accomodation['bedrooms']); ?> slaapkamers</span>
                                        <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i><?= maple_e($accomodation['area']); ?> m&sup2;</span>
                                    </div>
                                    <p><?= maple_e($accomodation['description']); ?></p>
                                    <div class="price-block">
                                        <span>Vanaf</span>
                                        <strong>&euro; <?= maple_e(maple_home_price($accomodation['price'])); ?> <em>per nacht</em></strong>
                                    </div>
                                    <a class="card-button" href="#booking">Bekijk beschikbaarheid</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="home-section split-section" id="faciliteiten">
                <div class="page-container split-layout">
                    <section class="events-column" id="events" aria-labelledby="events-title">
                        <div class="compact-header">
                            <div>
                                <p class="section-label">EVENTS</p>
                                <h2 class="section-title" id="events-title">Aankomende events</h2>
                            </div>
                            <a class="outline-button outline-button--small" href="Pages/Evenementen.php">Bekijk kalender</a>
                        </div>

                        <div class="event-list">
                            <?php foreach ($homeEvents as $event): ?>
                                <article class="event-row">
                                    <div class="event-row__image event-row__image--<?= maple_e($event['image_class']); ?>" aria-hidden="true"></div>
                                    <div class="event-row__content">
                                        <h3><?= maple_e($event['title']); ?></h3>
                                        <time datetime="<?= maple_e(maple_home_event_datetime_attr((string) $event['datetime'])); ?>"><?= maple_e(maple_home_event_date((string) $event['datetime'])); ?></time>
                                        <p><?= maple_e($event['description']); ?></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <a class="text-link" href="Pages/Evenementen.php">Bekijk alle events <span class="text-link__arrow" aria-hidden="true"></span></a>
                    </section>

                    <section class="activities-column" id="activiteiten" aria-labelledby="activities-title">
                        <div class="compact-header">
                            <div>
                                <p class="section-label">ACTIVITEITEN</p>
                                <h2 class="section-title" id="activities-title">Ontdek, beleef, geniet</h2>
                            </div>
                            <a class="outline-button outline-button--small" href="Pages/Activiteiten.php">Alle activiteiten</a>
                        </div>

                        <div class="activity-grid">
                            <article class="activity-card">
                                <i class="activity-icon fa-solid fa-person-hiking" aria-hidden="true"></i>
                                <h3>Hiking</h3>
                                <p>Ontdek de mooiste<br>wandelroutes</p>
                            </article>
                            <article class="activity-card">
                                <i class="activity-icon fa-solid fa-water" aria-hidden="true"></i>
                                <h3>Canoeing</h3>
                                <p>Peddel over kristalheldere<br>meren</p>
                            </article>
                            <article class="activity-card">
                                <i class="activity-icon fa-solid fa-person-swimming" aria-hidden="true"></i>
                                <h3>Kayaking</h3>
                                <p>Avontuur voor elk<br>niveau</p>
                            </article>
                            <article class="activity-card">
                                <i class="activity-icon fa-solid fa-fish" aria-hidden="true"></i>
                                <h3>Fishing</h3>
                                <p>Vissen in de beste<br>spots</p>
                            </article>
                            <article class="activity-card">
                                <i class="activity-icon fa-solid fa-ship" aria-hidden="true"></i>
                                <h3>Boat Tours</h3>
                                <p>Verken de omgeving<br>vanaf het water</p>
                            </article>
                            <article class="activity-card">
                                <i class="activity-icon fa-solid fa-fire" aria-hidden="true"></i>
                                <h3>Campfires</h3>
                                <p>Avonden vol sfeer<br>en verhalen</p>
                            </article>
                        </div>

                        <a class="text-link" href="Pages/Activiteiten.php">Bekijk alle activiteiten <span class="text-link__arrow" aria-hidden="true"></span></a>
                    </section>
                </div>
            </section>

            <section class="adventure-cta" id="omgeving" aria-labelledby="cta-title">
                <div class="page-container adventure-cta__inner">
                    <div>
                        <p class="section-label section-label--light">JOUW AVONTUUR WACHT</p>
                        <h2 class="adventure-cta__title" id="cta-title">Boek vandaag nog jouw<br>onvergetelijke ervaring</h2>
                        <p>Beperkte beschikbaarheid - boek op tijd!</p>
                    </div>
                    <a class="cta-button" href="#booking">Bekijk beschikbaarheid <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span></a>
                </div>
            </section>

            <section class="home-section reviews" id="reviews">
                <div class="page-container">
                    <div class="section-header section-header--reviews">
                        <div>
                            <p class="section-label">GASTEN OVER ONS</p>
                            <h2 class="section-title">Wat onze gasten zeggen</h2>
                        </div>
                        <a class="text-link text-link--top" href="#reviews">Alle reviews <span class="text-link__arrow" aria-hidden="true"></span></a>
                    </div>

                    <div class="review-grid">
                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--one" aria-hidden="true"></span>
                                <div>
                                    <h3>Lisa &amp; Mark</h3>
                                    <p>Mei 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 van 5 sterren">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>Prachtige locatie, geweldige faciliteiten en een super vriendelijk team. Wij komen zeker terug!</p>
                        </article>

                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--two" aria-hidden="true"></span>
                                <div>
                                    <h3>Tom</h3>
                                    <p>April 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 van 5 sterren">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>De omgeving is adembenemend. Overdag hiken, 's avonds kampvuur. Perfecte vakantie!</p>
                        </article>

                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--three" aria-hidden="true"></span>
                                <div>
                                    <h3>Sanne &amp; Jeroen</h3>
                                    <p>Mei 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 van 5 sterren">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>Luxe bungalow, alles was schoon en compleet. Echt genieten in de natuur.</p>
                        </article>

                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--four" aria-hidden="true"></span>
                                <div>
                                    <h3>Mike</h3>
                                    <p>April 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 van 5 sterren">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>Canoe&euml;n op het meer was het hoogtepunt van onze trip. Aanrader voor iedereen!</p>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/Includes/Footer.php'; ?>
    </div>
<?php else: ?>
    <main class="splash-page" aria-label="Maple Camp introductie">
        <section class="splash-page__content">
            <div class="splash-ornament" aria-hidden="true">
                <span class="splash-ornament__line"></span>
                <img class="splash-ornament__leaf" src="Images/herfst.webp" alt="">
                <span class="splash-ornament__line"></span>
            </div>
            <h1 class="splash-page__title">MAPLE CAMP</h1>
            <div class="splash-page__subtitle">CANADIAN MOUNTAIN CAMPING</div>
            <p class="splash-page__tagline">Mountains. Water. Adventure. Freedom.</p>
            <a class="splash-book" href="Index.php?view=home" aria-label="Open de Maple Camp homepage">
                <span class="splash-book__text">Book Now</span>
                <span class="splash-book__arrow" aria-hidden="true"></span>
            </a>
        </section>
    </main>
<?php endif; ?>
</body>
</html>
