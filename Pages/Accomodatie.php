<?php
$pageTitle = 'Maple Camp - Cottages';
$basePath = '../';
$assetBase = '../';
$currentPage = 'accommodaties';

require_once __DIR__ . '/../Includes/DataBase.php';

$accommodations = [];
$accommodationStatement = $conn->prepare(
    'SELECT Huis_id, Huis_naam, Locatie, PPN, Voorzieningen, Max, Omschr FROM accomodaties ORDER BY Huis_id'
);

if ($accommodationStatement) {
    $accommodationStatement->execute();
    $accommodationResult = $accommodationStatement->get_result();

    if ($accommodationResult) {
        while ($accommodation = $accommodationResult->fetch_assoc()) {
            $accommodations[] = $accommodation;
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
                    <label><span>Guests</span><select id="guests" name="guests"><option value="">Any number</option><option value="1">1 guests</option><option value="2">2 guests</option><option value="3">3 guests</option><option value="4">4 guests</option><option value="5">5 guests</option></select></label>
                    <label><span>Arrival</span><input id="arrival" type="date" name="arrival" aria-label="Arrival date"></label>
                    <label><span>Departure</span><input id="departure" type="date" name="departure" aria-label="Departure date"></label>
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
            </div>
        </section>
    </div>
    <?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
