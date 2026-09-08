<?php
$pageTitle = 'Maple Camp - Cottages';
$basePath = '../';
$assetBase = '../';
$currentPage = 'accommodaties';
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
</head>
<body class="accommodations-page">
    <section class="accommodations-hero" aria-labelledby="accommodations-heading">
        <?php include __DIR__ . '/../Includes/Header.php'; ?>
        <div class="accommodations-hero__content page-container">
            <p class="section-label section-label--light">COTTAGES</p>
            <h1 id="accommodations-heading">Your place in the<br>heart of nature</h1>
            <p>From welcoming bungalows to extra-luxurious stays:<br>find the cottage that feels right for you.</p>
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

                <form class="cottage-search" action="../Index.php?view=home#booking" method="get" aria-label="Search cottages">
                    <label><span>Guests</span><select name="guests"><option>2 guests</option><option>4 guests</option><option>6 guests</option></select></label>
                    <label><span>Arrival</span><input type="date" name="arrival" aria-label="Arrival date"></label>
                    <label><span>Departure</span><input type="date" name="departure" aria-label="Departure date"></label>
                    <button type="submit">Search cottages</button>
                </form>
                <div class="cottage-toolbar" aria-label="Cottage overview controls">
                    <p><strong>3 cottages available</strong><span>Choose the comfort level that suits your stay.</span></p>
                    <div class="cottage-filter-row"><label>Sort by <select aria-label="Sort cottages"><option>Recommended</option><option>Price: low to high</option><option>Most spacious</option></select></label><button type="button">Filters</button><button type="button">Bedrooms</button><button type="button">Facilities</button></div>
                </div>
                <div class="accommodations-list">
                    <article class="accommodation-card accommodation-card--listing">
                        <div class="accommodation-card__image accommodation-card__image--comfort"><span class="popular-badge">Popular</span></div>
                        <div class="accommodation-card__body">
                            <h3>Bungalow Comfort</h3>
                            <div class="accommodation-meta" aria-label="Features"><span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>4 guests</span><span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>2 bedrooms</span><span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>45 m&sup2;</span></div>
                            <p>A welcoming bungalow with everything you need for a relaxing stay in nature.</p>
                            <ul class="accommodation-highlights"><li>Private terrace with outdoor furniture</li><li>Fully equipped kitchen</li><li>Complimentary Wi-Fi</li></ul>
                            <div class="price-block"><span>From</span><strong>&euro; 120 <em>per night</em></strong></div>
                            <div class="listing-actions"><a class="listing-more" href="#overview-heading">View details</a><a class="card-button" href="../Index.php?view=home#booking">Select cottage</a></div>
                        </div>
                    </article>
                    <article class="accommodation-card accommodation-card--listing">
                        <div class="accommodation-card__image accommodation-card__image--luxe"></div>
                        <div class="accommodation-card__body">
                            <h3>Luxury Bungalow</h3>
                            <div class="accommodation-meta" aria-label="Features"><span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>4 guests</span><span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>2 bedrooms</span><span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>60 m&sup2;</span></div>
                            <p>Spacious and luxuriously furnished, with extra comfort and beautiful mountain views.</p>
                            <ul class="accommodation-highlights"><li>Private terrace with mountain views</li><li>Luxury kitchen and living space</li><li>Complimentary Wi-Fi</li></ul>
                            <div class="price-block"><span>From</span><strong>&euro; 145 <em>per night</em></strong></div>
                            <div class="listing-actions"><a class="listing-more" href="#overview-heading">View details</a><a class="card-button" href="../Index.php?view=home#booking">Select cottage</a></div>
                        </div>
                    </article>
                    <article class="accommodation-card accommodation-card--listing">
                        <div class="accommodation-card__image accommodation-card__image--premium"></div>
                        <div class="accommodation-card__body">
                            <h3>Bungalow Premium</h3>
                            <div class="accommodation-meta" aria-label="Features"><span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>6 guests</span><span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>3 bedrooms</span><span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>75 m&sup2;</span></div>
                            <p>Extra spacious, modern and stylish. Perfect for a longer stay or a touch of luxury.</p>
                            <ul class="accommodation-highlights"><li>Large private terrace</li><li>Three comfortable bedrooms</li><li>Extra space for family and friends</li></ul>
                            <div class="price-block"><span>From</span><strong>&euro; 175 <em>per night</em></strong></div>
                            <div class="listing-actions"><a class="listing-more" href="#overview-heading">View details</a><a class="card-button" href="../Index.php?view=home#booking">Select cottage</a></div>
                        </div>
                    </article>
                </div>
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
    <?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
