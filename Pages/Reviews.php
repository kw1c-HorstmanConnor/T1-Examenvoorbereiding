<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Reviews/Reviews.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'reviews';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$currentUser = maple_current_user();
$isLoggedIn = $currentUser !== null;
$reviewErrors = [];
$formValues = maple_review_form_values();

if ($requestMethod === 'POST') {
    $reviewResult = maple_handle_review_submission($_POST);
    $reviewErrors = $reviewResult['errors'];
    $formValues = $reviewResult['values'];

    if ($reviewResult['success']) {
        header('Location: Reviews.php?submitted=1#review-form');
        exit;
    }
}

$successMessage = maple_review_success_message();
$csrfToken = $isLoggedIn ? maple_review_csrf_token() : '';
$reviewSummary = maple_reviews_summary();
$reviews = maple_published_reviews();
$totalReviews = (int) $reviewSummary['count'];
$averageRating = $totalReviews > 0 ? number_format((float) $reviewSummary['average'], 1) : '0.0';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reviews - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/reviews.css">
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
    <script src="../Scripts/Reviews.js" defer></script>
</head>
<body class="reviews-page">
    <section class="reviews-hero" aria-labelledby="reviews-heading">
        <?php include __DIR__ . '/../Includes/Header.php'; ?>

        <div class="reviews-hero__content page-container">
            <p class="section-label section-label--light">GUEST REVIEWS</p>
            <h1 id="reviews-heading">Reviews</h1>
            <p>Read guest experiences from Maple Camp and share your own stay after signing in.</p>
        </div>
    </section>

    <main>
        <section class="reviews-dashboard" aria-labelledby="reviews-summary-heading">
            <div class="page-container reviews-dashboard__grid">
                <article class="reviews-summary" aria-labelledby="reviews-summary-heading">
                    <p class="section-label">Average rating</p>
                    <div class="reviews-summary__score">
                        <strong data-no-translate><?= htmlspecialchars($averageRating, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span>/ 5</span>
                    </div>
                    <p id="reviews-summary-heading" class="reviews-summary__based">
                        <span>Based on</span>
                        <span data-no-translate><?= $totalReviews; ?></span>
                        <span><?= $totalReviews === 1 ? 'review' : 'reviews'; ?></span>
                    </p>

                    <div class="rating-bars" aria-label="Rating breakdown">
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <?php
                            $ratingCount = (int) ($reviewSummary['distribution'][$rating] ?? 0);
                            $ratingPercent = $totalReviews > 0 ? ($ratingCount / $totalReviews) * 100 : 0;
                            ?>
                            <div class="rating-bar">
                                <span class="rating-bar__label">
                                    <span data-no-translate><?= $rating; ?></span>
                                    <span><?= $rating === 1 ? 'star' : 'stars'; ?></span>
                                </span>
                                <span class="rating-bar__track" aria-hidden="true">
                                    <span class="rating-bar__fill" style="width: <?= htmlspecialchars(number_format($ratingPercent, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>%"></span>
                                </span>
                                <span class="rating-bar__count" data-no-translate><?= $ratingCount; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </article>

                <section class="review-panel" id="review-form" aria-labelledby="review-form-heading">
                    <p class="section-label">Write a review</p>
                    <h2 id="review-form-heading">Share your Maple Camp experience</h2>

                    <?php if ($successMessage !== ''): ?>
                        <p class="review-message review-message--success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if ($reviewErrors !== []): ?>
                        <div class="review-message review-message--error" role="alert">
                            <?php foreach ($reviewErrors as $error): ?>
                                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <form class="review-form" method="post" action="Reviews.php#review-form" data-review-form>
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                            <fieldset class="review-rating-field">
                                <legend>How would you rate your experience?</legend>
                                <div class="review-rating-stars" data-rating-stars aria-describedby="rating-status">
                                    <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                        <?php $ratingId = 'review-rating-' . $rating; ?>
                                        <input
                                            class="review-rating-input"
                                            type="radio"
                                            id="<?= htmlspecialchars($ratingId, ENT_QUOTES, 'UTF-8'); ?>"
                                            name="rating"
                                            value="<?= $rating; ?>"
                                            required
                                            <?= (string) $formValues['rating'] === (string) $rating ? 'checked' : ''; ?>
                                        >
                                        <label
                                            class="review-rating-star"
                                            for="<?= htmlspecialchars($ratingId, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-rating-star
                                            data-rating-value="<?= $rating; ?>"
                                            aria-label="<?= $rating; ?> van 5 sterren"
                                            title="<?= $rating; ?> van 5 sterren"
                                        >&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-rating-status" id="rating-status" data-rating-output aria-live="polite">No rating selected</p>
                            </fieldset>

                            <label class="review-field">
                                <span>When did you visit?</span>
                                <input type="date" name="ervaring_datum" value="<?= htmlspecialchars($formValues['ervaring_datum'], ENT_QUOTES, 'UTF-8'); ?>">
                            </label>

                            <label class="review-field">
                                <span>Write a review</span>
                                <textarea name="omschrijving" rows="7" minlength="25" maxlength="1500" required data-review-textarea><?= htmlspecialchars($formValues['omschrijving'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </label>
                            <p class="review-counter" aria-live="polite">
                                <span data-character-count data-no-translate>1500</span>
                                <span>characters remaining</span>
                            </p>

                            <label class="review-field">
                                <span>Give your review a title</span>
                                <input type="text" name="titel" maxlength="150" required value="<?= htmlspecialchars($formValues['titel'], ENT_QUOTES, 'UTF-8'); ?>">
                            </label>

                            <button class="review-submit" type="submit">Submit review</button>
                        </form>
                    <?php else: ?>
                        <div class="review-login-prompt">
                            <p>Sign in to leave a review.</p>
                            <a class="review-login-button" href="Login.php">Log in</a>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>

        <?php if ($reviews !== []): ?>
            <section class="reviews-list-section" aria-labelledby="reviews-list-heading">
                <div class="page-container">
                    <div class="reviews-list-section__header">
                        <p class="section-label">Guest experiences</p>
                        <h2 id="reviews-list-heading">Reviews from our guests</h2>
                    </div>

                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $reviewRating = (int) $review['Rating'];
                            $experienceDate = maple_review_format_date($review['Ervaring_datum'] ?? null);
                            $postedDate = maple_review_format_date($review['Aangemaakt'] ?? null);
                            $postedDateTime = str_replace(' ', 'T', trim((string) ($review['Aangemaakt'] ?? '')));
                            ?>
                            <article class="published-review">
                                <div class="published-review__stars" aria-label="<?= $reviewRating; ?> van 5 sterren">
                                    <?= maple_review_stars_html($reviewRating); ?>
                                </div>
                                <p class="published-review__author" data-no-translate><?= htmlspecialchars(maple_review_display_name($review), ENT_QUOTES, 'UTF-8'); ?></p>
                                <h3 data-no-translate><?= htmlspecialchars((string) $review['Titel'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="published-review__description" data-no-translate><?= nl2br(htmlspecialchars((string) $review['Omschrijving'], ENT_QUOTES, 'UTF-8')); ?></p>
                                <div class="published-review__dates">
                                    <?php if ($experienceDate !== ''): ?>
                                        <p><span>Visited</span> <time datetime="<?= htmlspecialchars((string) $review['Ervaring_datum'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($experienceDate, ENT_QUOTES, 'UTF-8'); ?></time></p>
                                    <?php endif; ?>
                                    <?php if ($postedDate !== ''): ?>
                                        <p><span>Posted</span> <time datetime="<?= htmlspecialchars($postedDateTime, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($postedDate, ENT_QUOTES, 'UTF-8'); ?></time></p>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
