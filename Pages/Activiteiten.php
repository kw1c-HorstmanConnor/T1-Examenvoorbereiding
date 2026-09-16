<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Activities/Activities.php';
require_once __DIR__ . '/../Functions/Helpers/Session.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'activiteiten';
$languageControlsEnabled = true;
$activities = maple_activities();
$activitiesJson = json_encode(
    $activities,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (!is_string($activitiesJson)) {
    $activitiesJson = '[]';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activities - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/activities.css">
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
    <script type="application/json" id="activities-data"><?= $activitiesJson; ?></script>
    <script src="../Scripts/Activities.js" defer></script>
</head>
<body class="activities-view">
    <div class="home-page activities-page">
        <section class="activities-hero" aria-labelledby="activities-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="activities-hero__content page-container">
                <p class="section-label section-label--light" data-i18n="Activities">Activities</p>
                <h1 class="activities-hero__title" id="activities-heading" data-i18n="Activities">Activities</h1>
                <p class="activities-hero__subtitle" data-i18n="Relax, explore and make memories during your stay.">Relax, explore and make memories during your stay.</p>
            </div>
        </section>

        <main>
            <section class="activities-section" id="activities-list" aria-labelledby="activities-list-heading">
                <div class="page-container">
                    <div class="section-header activities-section__header">
                        <div>
                            <p class="section-label" data-i18n="Outdoor experiences">Outdoor experiences</p>
                            <h2 class="section-title" id="activities-list-heading" data-i18n="Choose your next camp activity">Choose your next camp activity</h2>
                            <p class="section-copy" data-i18n="Pick a quiet moment on the lake, a forest route, or an evening around the fire.">Pick a quiet moment on the lake, a forest route, or an evening around the fire.</p>
                        </div>
                    </div>

                    <div class="activities-grid">
                        <?php foreach ($activities as $activity): ?>
                            <?php
                            $translation = maple_activity_translation($activity, 'en');
                            $imageUrl = maple_activity_image_url($activity, $assetBase);
                            ?>
                            <article
                                class="activities-card activities-card--<?= maple_e($activity['imageClass']); ?>"
                                role="button"
                                tabindex="0"
                                aria-haspopup="dialog"
                                aria-controls="activity-modal"
                                aria-label="<?= maple_e('Open activity details: ' . $translation['title']); ?>"
                                data-activity-card
                                data-activity-id="<?= maple_e($activity['id']); ?>"
                            >
                                <div class="activities-card__image">
                                    <img src="<?= maple_e($imageUrl); ?>" alt="" loading="lazy">
                                </div>
                                <div class="activities-card__body">
                                    <h3 data-activity-title><?= maple_e($translation['title']); ?></h3>
                                    <p data-activity-summary><?= maple_e($translation['summary']); ?></p>
                                    <span class="activities-card__action">
                                        <span data-i18n="View activity">View activity</span>
                                        <span class="button-arrow" aria-hidden="true"></span>
                                    </span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="activities-cta" aria-labelledby="activities-cta-title">
                <div class="page-container activities-cta__inner">
                    <div>
                        <p class="section-label section-label--light" data-i18n="Maple Camp moments">Maple Camp moments</p>
                        <h2 class="activities-cta__title" id="activities-cta-title" data-i18n="Adventure lives here.">Adventure lives here.</h2>
                    </div>
                    <a class="cta-button" href="#activities-list">
                        <span data-i18n="View all activities">View all activities</span>
                        <span class="button-arrow" aria-hidden="true"></span>
                    </a>
                </div>
            </section>
        </main>

        <div class="activity-modal" id="activity-modal" data-activity-modal hidden aria-hidden="true">
            <div class="activity-modal__overlay" data-activity-modal-overlay></div>
            <section
                class="activity-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="activity-modal-title"
                tabindex="-1"
                data-activity-modal-panel
            >
                <button class="activity-modal__close" type="button" data-activity-modal-close aria-label="Close" data-i18n-attr="aria-label:Close">
                    <span aria-hidden="true">&times;</span>
                </button>

                <div class="activity-modal__media">
                    <img src="../Images/background.png" alt="" data-activity-modal-image>
                </div>

                <div class="activity-modal__content">
                    <p class="section-label" data-i18n="Activity details">Activity details</p>
                    <h2 id="activity-modal-title" data-activity-modal-title></h2>
                    <p class="activity-modal__description" data-activity-modal-description></p>

                    <dl class="activity-modal__details">
                        <div class="activity-modal__detail" data-activity-modal-location-row>
                            <dt data-i18n="Location">Location</dt>
                            <dd data-activity-modal-location></dd>
                        </div>
                        <div class="activity-modal__detail" data-activity-modal-duration-row>
                            <dt data-i18n="Duration">Duration</dt>
                            <dd data-activity-modal-duration></dd>
                        </div>
                        <div class="activity-modal__detail" data-activity-modal-price-row>
                            <dt data-i18n="Price">Price</dt>
                            <dd data-activity-modal-price></dd>
                        </div>
                    </dl>

                    <div class="activity-modal__extra" data-activity-modal-extra-wrap>
                        <h3 data-i18n="Extra information">Extra information</h3>
                        <p data-activity-modal-extra></p>
                    </div>
                </div>
            </section>
        </div>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
</body>
</html>
