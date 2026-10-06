<?php
declare(strict_types=1);

require_once __DIR__ . '/Functions/Helpers/Database.php';
require_once __DIR__ . '/Functions/Helpers/Session.php';
require_once __DIR__ . '/Functions/Helpers/View.php';
require_once __DIR__ . '/Functions/Accommodations/Accommodations.php';
require_once __DIR__ . '/Functions/Reviews/Reviews.php';

$view = $_GET['view'] ?? 'splash';
$isHomeView = $view === 'home';
$bodyClass = $isHomeView ? 'home-view' : 'splash-view';
$pageTitle = $isHomeView ? 'Maple Camp - Home' : 'Maple Camp';
$basePath = '';
$assetBase = '';
$currentPage = 'home';

$requestedArrival = trim((string) ($_GET['aankomst'] ?? ''));
$requestedDeparture = trim((string) ($_GET['vertrek'] ?? ''));
$requestedGuests = filter_var($_GET['gasten'] ?? 2, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 6]]) ?: 2;
$arrivalLabel = $requestedArrival !== '' ? $requestedArrival : 'Kies een datum';
$departureLabel = $requestedDeparture !== '' ? $requestedDeparture : 'Kies een datum';
$homepageReviews = $isHomeView ? array_slice(maple_published_reviews(), 0, 4) : [];

$homeAccommodationResult = $isHomeView
    ? maple_home_accommodations(3)
    : ['selection' => 'unavailable', 'items' => []];
$homeAccomodations = $homeAccommodationResult['items'];
$homeAccommodationSelection = $homeAccommodationResult['selection'];

$fallbackEvents = [
    [
        'title' => 'Kampvuur avond',
        'datetime' => '2026-05-24 20:00:00',
        'description' => 'test data as a fallback.',
    ],
    [
        'title' => 'Wandeltocht Rockies',
        'datetime' => '2026-05-26 09:00:00',
        'description' => 'test data as a fallback.',
    ],
    [
        'title' => 'Canoe Experience',
        'datetime' => '2026-05-28 10:00:00',
        'description' => 'test data as a fallback.',
    ],
];

$dbEvents = [];

try {
    $database = maple_db();
    $result = $database->query('
        SELECT `Titel`, `Start_time`, `Omschrijving`, `Locatie`
        FROM `evenementen`
        WHERE `Start_time` >= NOW()
        ORDER BY `Start_time` ASC
        LIMIT 3
    ');

    if ($result instanceof mysqli_result) {
        $dbEvents = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
} catch (Throwable $exception) {
    // Keep showing the fallback events when the database is unavailable.
}

$eventImageClasses = ['campfire', 'rockies', 'canoe'];
$homeEvents = [];
foreach ($dbEvents !== [] ? $dbEvents : $fallbackEvents as $index => $event) {
    $homeEvents[] = [
        'title' => (string) ($event['Titel'] ?? $event['title'] ?? ''),
        'datetime' => (string) ($event['Start_time'] ?? $event['datetime'] ?? ''),
        'description' => (string) ($event['Omschrijving'] ?? $event['description'] ?? ''),
        'image_class' => $eventImageClasses[$index % count($eventImageClasses)],
    ];
}
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
    <?php if ($isHomeView): ?>
        <link rel="stylesheet" href="Styling/accomodatie.css">
        <script src="Javascripts/Accomodaties.js" defer></script>
    <?php endif; ?>
    <?php include __DIR__ . '/Includes/LanguagesScripts.php'; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($isHomeView): ?>
    <div class="home-page">
        <section class="home-hero" aria-labelledby="home-heading">
            <?php include __DIR__ . '/Includes/Header.php'; ?>

            <div class="home-hero__content page-container">
                <h1 class="home-hero__title" id="home-heading">Enjoy<br>the Canadian Wild</h1>
            </div>
        </section>

        <main>
            <section class="home-section accommodations" id="accommodaties">
                <div class="page-container">
                    <div class="section-header">
                        <div>
                            <p class="section-label">ACCOMMODATIES</p>
                            <h2 class="section-title">Populaire verblijven</h2>
                            <p class="section-copy">Onze accommodaties zijn sfeervol, comfortabel en van alle gemakken voorzien.<br>Kies de accommodatie die bij jou past en geniet van een onvergetelijk verblijf.</p>
                        </div>
                        <a class="outline-button" href="Pages/Accomodatie.php">Bekijk alle accommodaties <span class="button-arrow" aria-hidden="true"></span></a>
                    </div>

                    <?php if ($homeAccomodations !== []): ?>
                        <div class="accommodation-grid">
                            <?php foreach ($homeAccomodations as $index => $accomodation): ?>
                                <?php
                                $imageUrl = maple_accommodation_image_url($accomodation['Afbeelding'] ?? '', $assetBase);
                                $fallbackImageUrl = maple_accommodation_image_fallback_url($assetBase);
                                $allFacilityNames = maple_accommodation_facility_names($accomodation, 99);
                                $facilityNames = array_slice($allFacilityNames, 0, 3);
                                $facilityDisplay = $allFacilityNames !== [] ? implode(', ', $allFacilityNames) : 'No facilities listed';
                                $description = maple_accommodation_excerpt((string) ($accomodation['Omschrijving'] ?? ''));
                                $detailUrl = maple_accommodation_detail_url($accomodation, $basePath);
                                ?>
                                <article class="accommodation-card" data-accommodation-card data-huis-id="<?= (int) $accomodation['Huis_id']; ?>" data-huis-name="<?= maple_e($accomodation['Huis_naam']); ?>" data-location="<?= maple_e($accomodation['Locatie']); ?>" data-price="<?= maple_e(maple_home_price($accomodation['PPN'])); ?>" data-facilities="<?= maple_e($facilityDisplay); ?>" data-max="<?= (int) $accomodation['Max']; ?>" data-description="<?= maple_e($accomodation['Omschrijving']); ?>" data-image-src="<?= maple_e($imageUrl); ?>" data-detail-url="<?= maple_e($detailUrl); ?>">
                                    <a class="accommodation-card__image" href="<?= maple_e($detailUrl); ?>" aria-label="Bekijk accommodatie" data-accommodation-open>
                                        <img class="accommodation-card__photo" src="<?= maple_e($imageUrl); ?>" alt="<?= maple_e($accomodation['Huis_naam']); ?>" data-fallback-src="<?= maple_e($fallbackImageUrl); ?>" onerror="if (this.dataset.fallbackApplied !== '1' && this.dataset.fallbackSrc) { this.dataset.fallbackApplied = '1'; this.src = this.dataset.fallbackSrc; }">
                                        <?php if ($index === 0 && $homeAccommodationSelection === 'reservations' && (int) ($accomodation['booking_count'] ?? 0) > 0): ?>
                                            <span class="popular-badge">Populair</span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="accommodation-card__body">
                                        <h3 data-no-translate><?= maple_e($accomodation['Huis_naam']); ?></h3>
                                        <?php if (trim((string) ($accomodation['Locatie'] ?? '')) !== ''): ?>
                                            <p class="accommodation-location" data-no-translate><?= maple_e($accomodation['Locatie']); ?></p>
                                        <?php endif; ?>
                                        <div class="accommodation-meta" aria-label="Kenmerken">
                                            <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i><span data-no-translate><?= (int) $accomodation['Max']; ?></span> <span>guests</span></span>
                                        </div>
                                        <?php if ($description !== ''): ?>
                                            <p data-no-translate><?= maple_e($description); ?></p>
                                        <?php endif; ?>
                                        <?php if ($facilityNames !== []): ?>
                                            <ul class="accommodation-facilities" aria-label="Faciliteiten">
                                                <?php foreach ($facilityNames as $facilityName): ?>
                                                    <li><?= maple_e($facilityName); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <div class="price-block">
                                            <span>Vanaf</span>
                                            <strong>&euro; <?= maple_e(maple_home_price($accomodation['PPN'])); ?> <em>per nacht</em></strong>
                                        </div>
                                        <a class="card-button" href="<?= maple_e($detailUrl); ?>" data-accommodation-open>Bekijk accommodatie</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="accommodation-section-footer">
                            <a class="outline-button" href="Pages/Accomodatie.php">Bekijk alle accommodaties <span class="button-arrow" aria-hidden="true"></span></a>
                        </div>
                    <?php else: ?>
                        <p class="accommodation-empty">Er zijn momenteel geen accommodaties beschikbaar.</p>
                    <?php endif; ?>
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

            <?php if ($homepageReviews !== []): ?>
                <section class="home-section reviews" id="reviews">
                    <div class="page-container">
                        <div class="section-header section-header--reviews">
                            <div>
                                <p class="section-label">GASTEN OVER ONS</p>
                                <h2 class="section-title">Wat onze gasten zeggen</h2>
                            </div>
                            <a class="text-link text-link--top" href="Pages/Reviews.php">Alle reviews <span class="text-link__arrow" aria-hidden="true"></span></a>
                        </div>

                        <div class="review-grid">
                            <?php foreach ($homepageReviews as $index => $review): ?>
                                <?php
                                $reviewRating = (int) $review['Rating'];
                                $postedDate = maple_review_format_date($review['Aangemaakt'] ?? null);
                                $avatarClass = 'review-avatar--' . ['one', 'two', 'three', 'four'][$index % 4];
                                ?>
                                <article class="review-card">
                                    <div class="review-card__header">
                                        <span class="review-avatar <?= htmlspecialchars($avatarClass, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
                                        <div>
                                            <h3 data-no-translate><?= htmlspecialchars(maple_review_display_name($review), ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <?php if ($postedDate !== ''): ?>
                                                <p><?= htmlspecialchars($postedDate, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="stars" aria-label="<?= $reviewRating; ?> van 5 sterren"><?= maple_review_stars_html($reviewRating); ?></div>
                                    <p data-no-translate><?= htmlspecialchars((string) $review['Omschrijving'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>

        <div class="accommodation-modal" id="accommodation-modal" hidden aria-hidden="true">
            <div class="accommodation-modal__overlay" data-modal-close="true"></div>
            <section class="accommodation-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="accommodation-modal-title" tabindex="-1">
                <button class="accommodation-modal__close" type="button" aria-label="Close accommodation details" data-modal-close="true">&times;</button>
                <div class="accommodation-modal__image" id="accommodation-modal-image" aria-hidden="true">
                    <img id="accommodation-modal-photo" src="<?= maple_e(maple_accommodation_image_fallback_url($assetBase)); ?>" alt="" data-fallback-src="<?= maple_e(maple_accommodation_image_fallback_url($assetBase)); ?>">
                </div>
                <div class="accommodation-modal__content">
                    <p class="section-label">ACCOMMODATION DETAILS</p>
                    <h2 id="accommodation-modal-title"></h2>
                    <dl class="accommodation-modal__details">
                        <div><dt>Location</dt><dd id="accommodation-modal-location"></dd></div>
                        <div><dt>Price per night</dt><dd id="accommodation-modal-price"></dd></div>
                        <div><dt>Maximum guests</dt><dd id="accommodation-modal-max"></dd></div>
                        <div><dt>Facilities</dt><dd id="accommodation-modal-facilities"></dd></div>
                        <div><dt>Description</dt><dd id="accommodation-modal-description"></dd></div>
                    </dl>
                    <button class="booking-button" id="book-now-button" type="button">BOOK NOW</button>
                </div>
            </section>
        </div>

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
