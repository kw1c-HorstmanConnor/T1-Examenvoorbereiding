<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Facilities/Facilities.php';
require_once __DIR__ . '/../Functions/Helpers/Session.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'faciliteiten';
$pageTitle = 'Facilities - Maple Camp';

$facilitiesData = maple_facilities_data();
$facilities = $facilitiesData['facilities'];
$accommodations = $facilitiesData['accommodations'];
$facilitiesByCategory = maple_facilities_by_category($facilities);
$popularFacilities = array_values(array_filter($facilities, static function (array $facility): bool {
    return (bool) ($facility['popular'] ?? false);
}));
$filterFacilities = array_values(array_filter($facilities, static function (array $facility): bool {
    return (bool) ($facility['filter'] ?? false);
}));

$facilityBySlug = [];
foreach ($facilities as $facility) {
    $facilityBySlug[(string) $facility['slug']] = $facility;
}

$comparisonSlugs = ['wifi', 'fireplace', 'lake-view', 'bbq', 'dishwasher', 'pet-friendly'];
$comparisonFacilities = [];
foreach ($comparisonSlugs as $slug) {
    if (isset($facilityBySlug[$slug])) {
        $comparisonFacilities[] = $facilityBySlug[$slug];
    }
}
$comparisonAccommodations = array_slice($accommodations, 0, 4);

$facilitiesJson = json_encode(
    [
        'facilities' => $facilities,
        'accommodations' => $accommodations,
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (!is_string($facilitiesJson)) {
    $facilitiesJson = '{"facilities":[],"accommodations":[]}';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= maple_e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php if (empty($fontAwesomeLoaded)): ?>
        <?php $fontAwesomeLoaded = true; ?>
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v7.3.1/css/all.css">
    <?php endif; ?>
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/facilities.css">
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
    <script type="application/json" id="facilities-data"><?= $facilitiesJson; ?></script>
    <script src="../Scripts/Facilities.js" defer></script>
</head>
<body class="facilities-view">
    <div class="home-page facilities-page">
        <section class="facilities-hero" aria-labelledby="facilities-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="facilities-hero__content page-container">
                <p class="section-label section-label--light">MAPLE CAMP COTTAGES</p>
                <h1 class="facilities-hero__title" id="facilities-heading">Everything you need for a comfortable stay</h1>
                <p class="facilities-hero__subtitle">Discover the facilities available in our cottages and find the stay that suits you.</p>
                <a class="facilities-hero__button" href="#facilities-list">Explore facilities <span class="button-arrow" aria-hidden="true"></span></a>
            </div>
        </section>

        <main>
            <?php if ($popularFacilities !== []): ?>
                <section class="facilities-popular" aria-labelledby="popular-facilities-title">
                    <div class="page-container facilities-popular__inner">
                        <div class="facilities-popular__copy">
                            <p class="section-label">Popular with our guests</p>
                            <h2 class="section-title" id="popular-facilities-title">Comforts guests ask for most</h2>
                        </div>
                        <div class="facilities-popular__grid">
                            <?php foreach (array_slice($popularFacilities, 0, 6) as $facility): ?>
                                <?php
                                $iconClass = maple_facility_icon_class((string) $facility['icon']);
                                ?>
                                <button
                                    class="popular-facility"
                                    type="button"
                                    data-facility-card
                                    data-facility-value="<?= maple_e($facility['value']); ?>"
                                    aria-haspopup="dialog"
                                    aria-controls="facility-modal"
                                    aria-label="<?= maple_e('Open facility details: ' . $facility['name']); ?>"
                                >
                                    <span class="facility-icon" aria-hidden="true"><i class="<?= maple_e($iconClass); ?>"></i></span>
                                    <span data-facility-name data-no-translate><?= maple_e($facility['name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="facilities-section" id="facilities-list" aria-labelledby="all-facilities-title">
                <div class="page-container">
                    <div class="facilities-section__header">
                        <div>
                            <p class="section-label">Facilities</p>
                            <h2 class="section-title" id="all-facilities-title">Browse cottage facilities by category</h2>
                            <p class="section-copy">Select any facility for details and to see which cottages include it.</p>
                        </div>
                        <nav class="facility-category-nav" aria-label="Facility categories">
                            <?php foreach (array_keys($facilitiesByCategory) as $category): ?>
                                <a href="#category-<?= maple_e(maple_facility_slug($category)); ?>"><?= maple_e($category); ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </div>

                    <div class="facility-category-list">
                        <?php foreach ($facilitiesByCategory as $category => $categoryFacilities): ?>
                            <section class="facility-category" id="category-<?= maple_e(maple_facility_slug($category)); ?>" aria-labelledby="category-title-<?= maple_e(maple_facility_slug($category)); ?>">
                                <div class="facility-category__heading">
                                    <span class="facility-category__rule" aria-hidden="true"></span>
                                    <h3 id="category-title-<?= maple_e(maple_facility_slug($category)); ?>"><?= maple_e($category); ?></h3>
                                </div>
                                <div class="facility-card-grid">
                                    <?php foreach ($categoryFacilities as $facility): ?>
                                        <?php
                                        $iconClass = maple_facility_icon_class((string) $facility['icon']);
                                        ?>
                                        <button
                                            class="facility-card"
                                            type="button"
                                            data-facility-card
                                            data-facility-value="<?= maple_e($facility['value']); ?>"
                                            aria-haspopup="dialog"
                                            aria-controls="facility-modal"
                                            aria-label="<?= maple_e('Open facility details: ' . $facility['name']); ?>"
                                        >
                                            <span class="facility-icon" aria-hidden="true"><i class="<?= maple_e($iconClass); ?>"></i></span>
                                            <span class="facility-card__content">
                                                <strong data-facility-name data-no-translate><?= maple_e($facility['name']); ?></strong>
                                                <span data-facility-short data-no-translate><?= maple_e($facility['short']); ?></span>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="facility-filter-section" aria-labelledby="facility-filter-title">
                <div class="page-container facility-filter-layout">
                    <aside class="facility-filter-panel" aria-label="Facility filters">
                        <p class="section-label">Find your perfect cottage</p>
                        <h2 id="facility-filter-title">Select desired facilities</h2>
                        <p>Choose one or more facilities to show cottages that include all selected options.</p>

                        <details class="facility-filter-disclosure" data-facility-filter-disclosure open>
                            <summary>
                                <span>Selected facilities</span>
                                <span class="facility-filter-summary" data-filter-selection-count data-no-translate>No facilities selected</span>
                            </summary>
                            <div class="facility-filter-options">
                                <div class="facility-checkboxes">
                                    <?php foreach ($filterFacilities as $facility): ?>
                                        <label class="facility-checkbox">
                                            <input type="checkbox" value="<?= maple_e($facility['value']); ?>" data-facility-filter>
                                            <span class="facility-checkbox__box" aria-hidden="true"></span>
                                            <span data-filter-label data-facility-value="<?= maple_e($facility['value']); ?>" data-no-translate><?= maple_e($facility['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <button class="facility-clear" type="button" data-clear-facility-filters>Clear filters</button>
                            </div>
                        </details>
                    </aside>

                    <div class="facility-results" aria-live="polite">
                        <div class="facility-results__header">
                            <div>
                                <p class="section-label">Matching cottages</p>
                                <h3>Available stays with your selected facilities</h3>
                            </div>
                            <p class="facility-results__count" data-results-count data-no-translate></p>
                        </div>

                        <div class="facility-cottage-grid">
                            <?php foreach ($accommodations as $accommodation): ?>
                                <?php
                                $facilityValues = $accommodation['facilityValues'] ?? [];
                                $imageClass = maple_facility_slug((string) ($accommodation['imageClass'] ?? 'comfort'));
                                $imageUrl = maple_accommodation_image_url($accommodation['image'] ?? '', $assetBase);
                                $fallbackImageUrl = maple_accommodation_image_fallback_url($assetBase);
                                ?>
                                <article
                                    class="facility-cottage"
                                    data-cottage-card
                                    data-cottage-id="<?= (int) $accommodation['id']; ?>"
                                    data-facilities="<?= maple_e(implode(',', $facilityValues)); ?>"
                                >
                                    <div class="facility-cottage__image facility-cottage__image--<?= maple_e($imageClass); ?>" aria-hidden="true">
                                        <img src="<?= maple_e($imageUrl); ?>" alt="" data-fallback-src="<?= maple_e($fallbackImageUrl); ?>">
                                    </div>
                                    <div class="facility-cottage__body">
                                        <h4 data-no-translate><?= maple_e($accommodation['name']); ?></h4>
                                        <?php if ((string) $accommodation['description'] !== ''): ?>
                                            <p data-no-translate><?= maple_e($accommodation['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="facility-cottage__meta">
                                            <span><strong><?= (int) $accommodation['maxGuests']; ?></strong> guests</span>
                                            <span><strong>&euro; <?= maple_e(maple_facility_price($accommodation['price'])); ?></strong> per night</span>
                                        </div>
                                        <div class="facility-cottage__matches" data-cottage-matches data-no-translate></div>
                                        <a class="facility-cottage__button" href="Accomodatie.php#huis-<?= (int) $accommodation['id']; ?>">View cottage</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <p class="facility-no-results" data-no-results hidden>No cottages currently match all selected facilities.</p>
                    </div>
                </div>
            </section>

            <?php if ($comparisonFacilities !== [] && $comparisonAccommodations !== []): ?>
                <section class="facility-comparison" aria-labelledby="facility-comparison-title">
                    <div class="page-container">
                        <div class="facility-comparison__header">
                            <p class="section-label">Cottage comparison</p>
                            <h2 class="section-title" id="facility-comparison-title">Compare popular facilities</h2>
                            <p class="section-copy">A quick overview of the most requested cottage amenities.</p>
                        </div>

                        <div class="comparison-table-wrap">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Facility</th>
                                        <?php foreach ($comparisonAccommodations as $accommodation): ?>
                                            <th scope="col" data-no-translate><?= maple_e($accommodation['name']); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comparisonFacilities as $facility): ?>
                                        <tr>
                                            <th scope="row" data-comparison-facility-name data-facility-value="<?= maple_e($facility['value']); ?>" data-no-translate><?= maple_e($facility['name']); ?></th>
                                            <?php foreach ($comparisonAccommodations as $accommodation): ?>
                                                <?php $hasFacility = in_array((string) $facility['value'], $accommodation['facilityValues'] ?? [], true); ?>
                                                <td>
                                                    <?php if ($hasFacility): ?>
                                                        <span class="comparison-check" aria-label="Included">&check;</span>
                                                    <?php else: ?>
                                                        <span class="comparison-empty" aria-label="Not included">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="facilities-cta" aria-labelledby="facilities-cta-title">
                <div class="page-container facilities-cta__inner">
                    <div>
                        <p class="section-label section-label--light">READY TO CHOOSE?</p>
                        <h2 id="facilities-cta-title">Find the cottage that fits your stay.</h2>
                    </div>
                    <a class="cta-button" href="Accomodatie.php">View accommodations <span class="button-arrow" aria-hidden="true"></span></a>
                </div>
            </section>
        </main>

        <div class="facility-modal" id="facility-modal" data-facility-modal hidden aria-hidden="true">
            <div class="facility-modal__overlay" data-facility-modal-close></div>
            <section
                class="facility-modal__dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="facility-modal-title"
                tabindex="-1"
                data-facility-modal-dialog
            >
                <button class="facility-modal__close" type="button" aria-label="Close facility details" data-facility-modal-close>
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="facility-modal__icon facility-icon" data-modal-icon aria-hidden="true"><i class="fa-solid fa-circle-info"></i></div>
                <p class="section-label" data-modal-category data-no-translate></p>
                <h2 id="facility-modal-title" data-modal-title data-no-translate></h2>
                <p class="facility-modal__description" data-modal-description data-no-translate></p>
                <div class="facility-modal__cottages">
                    <h3>Available in</h3>
                    <ul data-modal-cottages data-no-translate></ul>
                </div>
            </section>
        </div>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
</body>
</html>
