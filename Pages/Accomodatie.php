<?php
$pageTitle = 'Maple Camp - Cottages';
$basePath = '../';
$assetBase = '../';
$currentPage = 'accommodaties';

require_once __DIR__ . '/../Includes/DataBase.php';
require_once __DIR__ . '/../Functions/Helpers/Session.php';

$bookingError = '';
if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['booking_action'] ?? '') === 'continue') {
    $huisId = filter_var($_POST['huis_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $startDate = trim((string) ($_POST['start_date'] ?? ''));
    $endDate = trim((string) ($_POST['end_date'] ?? ''));
    $people = filter_var($_POST['people'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if (!hash_equals((string) $_SESSION['booking_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $bookingError = 'Your session has expired. Please try again.';
    } elseif ($huisId === false || $huisId === null) {
        $bookingError = 'Please select a valid accommodation.';
    } elseif (($start = maple_booking_date($startDate)) === null || ($end = maple_booking_date($endDate)) === null) {
        $bookingError = 'Please enter valid arrival and departure dates.';
    } elseif ($start < new DateTimeImmutable('today')) {
        $bookingError = 'Arrival cannot be in the past.';
    } elseif ($end <= $start) {
        $bookingError = 'Departure must be later than arrival.';
    } elseif ($people === false || $people === null) {
        $bookingError = 'Please enter a valid number of people.';
    } else {
        $selectedAccommodation = maple_booking_accommodation($conn, (int) $huisId);
        if ($selectedAccommodation === null) {
            $bookingError = 'This accommodation is no longer available.';
        } elseif ($people > (int) $selectedAccommodation['Max']) {
            $bookingError = 'The selected number of people exceeds this accommodation\'s maximum occupancy.';
        } else {
            // Recheck immediately before the later final reservation step as well to avoid races.
            $available = maple_booking_is_available($conn, (int) $huisId, $startDate, $endDate);
            if ($available !== true) {
                $bookingError = $available === false
                    ? 'This accommodation is no longer available for the selected dates.'
                    : 'Availability could not be verified. Please try again later.';
            } else {
                $_SESSION['pending_booking'] = [
                    'huis_id' => (int) $huisId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'people' => (int) $people,
                ];

                if (empty($_SESSION['user_id'])) {
                    $_SESSION['booking_login_redirect'] = 'Book-Resi.php';
                    header('Location: Login.php');
                } else {
                    header('Location: Book-Resi.php');
                }
                exit;
            }
        }
    }
}

$filterArrival = trim((string) ($_GET['arrival'] ?? ''));
$filterDeparture = trim((string) ($_GET['departure'] ?? ''));
$filterGuests = filter_var($_GET['guests'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$accommodations = [];
$accommodationStatement = $conn->prepare(
    'SELECT Huis_id, Huis_naam, Locatie, PPN, Voorzieningen, Max, Omschr FROM accomodaties ORDER BY Huis_id'
);

if ($accommodationStatement) {
    $accommodationStatement->execute();
    $accommodationResult = $accommodationStatement->get_result();

    if ($accommodationResult) {
        while ($accommodation = $accommodationResult->fetch_assoc()) {
            $matchesGuests = $filterGuests === false || $filterGuests === null || (int) $accommodation['Max'] >= $filterGuests;
            $matchesAvailability = true;
            if (maple_booking_date($filterArrival) !== null && maple_booking_date($filterDeparture) !== null && $filterDeparture > $filterArrival) {
                $matchesAvailability = maple_booking_is_available($conn, (int) $accommodation['Huis_id'], $filterArrival, $filterDeparture) === true;
            }
            if ($matchesGuests && $matchesAvailability) {
                $accommodations[] = $accommodation;
            }
        }
    }

    $accommodationStatement->close();
}

$accommodationImageClasses = ['comfort', 'luxe', 'premium'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/accomodatie.css">
    <script src="../Javascripts/Accomodaties.js" defer></script>
</head>
<body class="accommodations-page">
    <section class="accommodations-hero" aria-labelledby="accommodations-heading">
        <?php include __DIR__ . '/../Includes/Header.php'; ?>
        <div class="accommodations-hero__content page-container">
            <h1 id="accommodations-heading">Accommodations</h1>
            <p>Unique stays in the Canadian wilderness</p>
        </div>
    </section>

    <main>
        <section class="accommodations-overview" aria-labelledby="overview-heading">
            <div class="page-container">
                <div class="accommodations-overview__header">
                    <div>
                        <p class="section-label">OUR COTTAGES</p>
                        <h2 class="section-title" id="overview-heading">Find your perfect stay</h2>
                        <p>Each bungalow sits among the trees and is fully equipped for a relaxed escape in the Canadian wilderness.</p>
                    </div>
                    <a class="outline-button" href="../Index.php?view=home#booking">Check availability <span class="button-arrow" aria-hidden="true"></span></a>
                </div>

                <form class="cottage-search" id="cottage-search" action="#overview-heading" method="get" aria-label="Search cottages">
                    <label><span>Guests</span><select id="guests" name="guests"><option value="">Any number</option><?php foreach ([1, 2, 3, 4, 5, 6] as $guestOption): ?><option value="<?= $guestOption; ?>"<?= $filterGuests === $guestOption ? ' selected' : ''; ?>><?= $guestOption; ?> guests</option><?php endforeach; ?></select></label>
                    <label><span>Arrival</span><input id="arrival" type="date" name="arrival" value="<?= htmlspecialchars($filterArrival, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Arrival date"></label>
                    <label><span>Departure</span><input id="departure" type="date" name="departure" value="<?= htmlspecialchars($filterDeparture, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Departure date"></label>
                    <button type="submit">Search cottages</button>
                </form>
                <nav class="cottage-tabs" aria-label="Accommodation categories">
                    <a class="cottage-tabs__item cottage-tabs__item--active" data-type="all" href="#overview-heading">All accommodations</a>
                    <a class="cottage-tabs__item" data-type="bungalow" href="#overview-heading">Bungalows</a>
                    <a class="cottage-tabs__item" data-type="electric" href="#overview-heading">Camping with electricity</a>
                    <a class="cottage-tabs__item" data-type="wild" href="#overview-heading">Wild camping</a>
                </nav>
                <div class="cottage-toolbar" aria-label="Cottage overview controls">
                    <p><strong><?= count($accommodations); ?> cottages available</strong><span>Choose the comfort level that suits your stay.</span></p>
                    <div class="cottage-filter-row"><label>Sort by <select aria-label="Sort cottages"><option>Recommended</option><option>Price: low to high</option><option>Most spacious</option></select></label><button type="button">Filters</button><button type="button">Bedrooms</button><button type="button">Facilities</button></div>
                </div>
                <div class="accommodations-list">
                    <?php foreach ($accommodations as $index => $accommodation): ?>
                        <?php $imageClass = $accommodationImageClasses[$index % count($accommodationImageClasses)]; ?>
                        <article class="accommodation-card accommodation-card--listing" data-huis-id="<?= (int) $accommodation['Huis_id']; ?>" data-huis-name="<?= htmlspecialchars($accommodation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?>" data-location="<?= htmlspecialchars($accommodation['Locatie'], ENT_QUOTES, 'UTF-8'); ?>" data-price="<?= htmlspecialchars($accommodation['PPN'], ENT_QUOTES, 'UTF-8'); ?>" data-facilities="<?= htmlspecialchars($accommodation['Voorzieningen'], ENT_QUOTES, 'UTF-8'); ?>" data-max="<?= (int) $accommodation['Max']; ?>" data-description="<?= htmlspecialchars($accommodation['Omschr'], ENT_QUOTES, 'UTF-8'); ?>" data-type="bungalow" data-guests="<?= (int) $accommodation['Max']; ?>" data-available-from="2026-01-01" data-available-to="2026-12-31" tabindex="0" role="button" aria-label="View details for <?= htmlspecialchars($accommodation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="accommodation-card__image accommodation-card__image--<?= $imageClass; ?>"><?php if ($index === 0): ?><span class="popular-badge">Popular</span><?php endif; ?></div>
                            <div class="accommodation-card__body">
                                <h3><?= htmlspecialchars($accommodation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="accommodation-meta" aria-label="Features"><span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i><?= (int) $accommodation['Max']; ?> guests</span></div>
                                <p><?= htmlspecialchars($accommodation['Omschr'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <ul class="accommodation-highlights"><li><?= htmlspecialchars($accommodation['Voorzieningen'], ENT_QUOTES, 'UTF-8'); ?></li></ul>
                                <div class="price-block"><span>From</span><strong>&euro; <?= htmlspecialchars($accommodation['PPN'], ENT_QUOTES, 'UTF-8'); ?> <em>per night</em></strong></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <div class="cottage-options-divider" id="cottage-options-divider" hidden><span>Overige opties</span></div>
                </div>
                <p class="cottage-no-results" id="cottage-no-results" hidden>No exact matches. See the other options below.</p>
                <?php if ($bookingError !== ''): ?>
                    <p class="booking-error" role="alert"><?= htmlspecialchars($bookingError, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </section>
        <section class="accommodations-note">
            <div class="page-container accommodations-note__inner">
                <div><p class="section-label">GOOD TO KNOW</p><h2>Everything for an effortless stay</h2></div>
                <p>Every bungalow has a fully equipped kitchen, comfortable beds, a private terrace and complimentary Wi-Fi. Have a question about your stay? We are happy to help.</p>
                <a class="text-link" href="mailto:info@maplecamp.ca">Get in touch <span class="text-link__arrow" aria-hidden="true"></span></a>
            </div>
        </section>
    </main>
    <div class="accommodation-modal" id="accommodation-modal" hidden aria-hidden="true">
        <div class="accommodation-modal__overlay" data-modal-close="true"></div>
        <section class="accommodation-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="accommodation-modal-title" tabindex="-1">
            <button class="accommodation-modal__close" type="button" aria-label="Close accommodation details" data-modal-close="true">&times;</button>
            <div class="accommodation-modal__image" id="accommodation-modal-image" aria-hidden="true"></div>
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
    <div class="booking-modal" id="booking-modal" hidden aria-hidden="true">
        <div class="booking-modal__overlay" data-booking-modal-close="true"></div>
        <section class="booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="booking-modal-title" tabindex="-1">
            <button class="accommodation-modal__close" type="button" aria-label="Close booking confirmation" data-booking-modal-close="true">&times;</button>
            <p class="section-label">CONFIRM YOUR STAY</p>
            <h2 id="booking-modal-title">Complete your booking</h2>
            <p id="booking-accommodation-name"></p>
            <form method="post" action="Accomodatie.php" id="booking-confirmation-form">
                <input type="hidden" name="booking_action" value="continue">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars((string) $_SESSION['booking_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="huis_id" id="booking-huis-id" value="">
                <label>Arrival<input type="date" name="start_date" id="booking-start-date" required></label>
                <label>Departure<input type="date" name="end_date" id="booking-end-date" required></label>
                <label>Number of people<input type="number" name="people" id="booking-people" min="1" step="1" required></label>
                <button class="booking-button" type="submit">CONTINUE BOOKING</button>
            </form>
        </section>
    </div>
    <?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
