<?php
$view = $_GET['view'] ?? 'splash';
$isHomeView = $view === 'home';
$bodyClass = $isHomeView ? 'home-view' : 'splash-view';
$pageTitle = $isHomeView ? 'Maple Camp - Home' : 'Maple Camp';
$basePath = '';
$assetBase = '';
$currentPage = 'home';
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
                        <span class="booking-field__label">Arrival</span>
                        <span class="booking-field__control">
                            <input class="booking-field__input" type="date" name="arrival" aria-label="Arrival date">
                            <span class="booking-field__value">Select date</span>
                            <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="booking-field">
                        <span class="booking-field__label">Departure</span>
                        <span class="booking-field__control">
                            <input class="booking-field__input" type="date" name="departure" aria-label="Departure date">
                            <span class="booking-field__value">Select date</span>
                            <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="booking-field">
                        <span class="booking-field__label">Guests</span>
                        <span class="booking-field__control">
                            <select class="booking-field__input" name="guests" aria-label="Number of guests">
                                <option value="2">2 guests</option>
                                <option value="1">1 guest</option>
                                <option value="3">3 guests</option>
                                <option value="4">4 guests</option>
                                <option value="5">5 guests</option>
                                <option value="6">6 guests</option>
                            </select>
                            <span class="booking-field__value">2 guests</span>
                            <span class="booking-field__icon booking-field__icon--guests" aria-hidden="true"></span>
                        </span>
                    </label>

                    <button class="booking-panel__submit" type="submit">Check availability</button>
                </form>
            </div>
        </section>

        <main>
            <section class="home-section accommodations" id="accommodaties">
                <div class="page-container">
                    <div class="section-header">
                        <div>
                            <p class="section-label">COTTAGES</p>
                            <h2 class="section-title">Comfort in the heart of nature</h2>
                            <p class="section-copy">Our cottages are inviting, comfortable and thoughtfully equipped.<br>Choose the stay that suits you and enjoy an unforgettable escape.</p>
                        </div>
                        <a class="outline-button" href="Pages/Accomodatie.php">View all cottages <span class="button-arrow" aria-hidden="true"></span></a>
                    </div>

                    <div class="accommodation-grid">
                        <article class="accommodation-card">
                            <div class="accommodation-card__image accommodation-card__image--comfort">
                                <span class="popular-badge">Popular</span>
                            </div>
                            <div class="accommodation-card__body">
                                <h3>Bungalow Comfort</h3>
                                <div class="accommodation-meta" aria-label="Features">
                                    <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>4 guests</span>
                                    <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>2 bedrooms</span>
                                    <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>45 m&sup2;</span>
                                </div>
                                <p>A welcoming bungalow with everything you need for a relaxing stay in nature.</p>
                                <div class="price-block">
                                    <span>From</span>
                                    <strong>&euro; 120 <em>per night</em></strong>
                                </div>
                                <a class="card-button" href="#booking">Check availability</a>
                            </div>
                        </article>

                        <article class="accommodation-card">
                            <div class="accommodation-card__image accommodation-card__image--luxe"></div>
                            <div class="accommodation-card__body">
                                <h3>Luxury Bungalow</h3>
                                <div class="accommodation-meta" aria-label="Features">
                                    <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>4 guests</span>
                                    <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>2 bedrooms</span>
                                    <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>60 m&sup2;</span>
                                </div>
                                <p>Spacious and luxuriously furnished, with extra comfort and beautiful mountain views.</p>
                                <div class="price-block">
                                    <span>From</span>
                                    <strong>&euro; 145 <em>per night</em></strong>
                                </div>
                                <a class="card-button" href="#booking">Check availability</a>
                            </div>
                        </article>

                        <article class="accommodation-card">
                            <div class="accommodation-card__image accommodation-card__image--premium"></div>
                            <div class="accommodation-card__body">
                                <h3>Bungalow Premium</h3>
                                <div class="accommodation-meta" aria-label="Features">
                                    <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>6 guests</span>
                                    <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>3 bedrooms</span>
                                    <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>75 m&sup2;</span>
                                </div>
                                <p>Extra spacious, modern and stylish. Perfect for a longer stay or a touch of luxury.</p>
                                <div class="price-block">
                                    <span>From</span>
                                    <strong>&euro; 175 <em>per night</em></strong>
                                </div>
                                <a class="card-button" href="#booking">Check availability</a>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="home-section split-section" id="faciliteiten">
                <div class="page-container split-layout">
                    <section class="events-column" id="events" aria-labelledby="events-title">
                        <div class="compact-header">
                            <div>
                                <p class="section-label">EVENTS</p>
                                <h2 class="section-title" id="events-title">Upcoming events</h2>
                            </div>
                            <a class="outline-button outline-button--small" href="Pages/Evenementen.php">View calendar</a>
                        </div>

                        <div class="event-list">
                            <article class="event-row">
                                <div class="event-row__image event-row__image--campfire" aria-hidden="true"></div>
                                <div class="event-row__content">
                                    <h3>Campfire evening</h3>
                                    <time datetime="2026-05-24T20:00">24 May 2026 &bull; 20:00</time>
                                    <p>A cosy evening by the campfire with live music and marshmallows.</p>
                                </div>
                            </article>

                            <article class="event-row">
                                <div class="event-row__image event-row__image--rockies" aria-hidden="true"></div>
                                <div class="event-row__content">
                                    <h3>Rockies hike</h3>
                                    <time datetime="2026-05-26T09:00">26 May 2026 &bull; 09:00</time>
                                    <p>A guided hike through the beautiful Rocky Mountains.</p>
                                </div>
                            </article>

                            <article class="event-row">
                                <div class="event-row__image event-row__image--canoe" aria-hidden="true"></div>
                                <div class="event-row__content">
                                    <h3>Canoe Experience</h3>
                                    <time datetime="2026-05-28T10:00">28 May 2026 &bull; 10:00</time>
                                    <p>Discover the lake on a relaxed canoe trip.</p>
                                </div>
                            </article>
                        </div>

                        <a class="text-link" href="Pages/Evenementen.php">View all events <span class="text-link__arrow" aria-hidden="true"></span></a>
                    </section>

                    <section class="activities-column" id="activiteiten" aria-labelledby="activities-title">
                        <div class="compact-header">
                            <div>
                                <p class="section-label">ACTIVITIES</p>
                                <h2 class="section-title" id="activities-title">Discover, experience, enjoy</h2>
                            </div>
                            <a class="outline-button outline-button--small" href="Pages/Activiteiten.php">All activities</a>
                        </div>

                        <div class="activity-grid">
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--hiking" aria-hidden="true"></span>
                                <h3>Hiking</h3>
                                <p>Discover beautiful<br>walking trails</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--canoeing" aria-hidden="true"></span>
                                <h3>Canoeing</h3>
                                <p>Paddle across crystal-clear<br>lakes</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--kayaking" aria-hidden="true"></span>
                                <h3>Kayaking</h3>
                                <p>Adventure for every<br>level</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--fishing" aria-hidden="true"></span>
                                <h3>Fishing</h3>
                                <p>Fish in the best<br>spots</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--boat" aria-hidden="true"></span>
                                <h3>Boat Tours</h3>
                                <p>Explore the surroundings<br>from the water</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--campfire" aria-hidden="true"></span>
                                <h3>Campfires</h3>
                                <p>Evenings full of stories<br>and atmosphere</p>
                            </article>
                        </div>

                        <a class="text-link" href="Pages/Activiteiten.php">View all activities <span class="text-link__arrow" aria-hidden="true"></span></a>
                    </section>
                </div>
            </section>

            <section class="adventure-cta" id="omgeving" aria-labelledby="cta-title">
                <div class="page-container adventure-cta__inner">
                    <div>
                        <p class="section-label section-label--light">YOUR ADVENTURE AWAITS</p>
                        <h2 class="adventure-cta__title" id="cta-title">Book your unforgettable<br>experience today</h2>
                        <p>Limited availability — book early!</p>
                    </div>
                    <a class="cta-button" href="#booking">Check availability <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span></a>
                </div>
            </section>

            <section class="home-section reviews" id="reviews">
                <div class="page-container">
                    <div class="section-header section-header--reviews">
                        <div>
                            <p class="section-label">GUEST REVIEWS</p>
                            <h2 class="section-title">What our guests say</h2>
                        </div>
                        <a class="text-link text-link--top" href="#reviews">All reviews <span class="text-link__arrow" aria-hidden="true"></span></a>
                    </div>

                    <div class="review-grid">
                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--one" aria-hidden="true"></span>
                                <div>
                                    <h3>Lisa &amp; Mark</h3>
                                    <p>May 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>Beautiful location, great facilities and a wonderfully friendly team. We will definitely be back!</p>
                        </article>

                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--two" aria-hidden="true"></span>
                                <div>
                                    <h3>Tom</h3>
                                    <p>April 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>The surroundings are breathtaking. Hiking by day, campfires by night. A perfect holiday!</p>
                        </article>

                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--three" aria-hidden="true"></span>
                                <div>
                                    <h3>Sanne &amp; Jeroen</h3>
                                    <p>May 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>A luxurious bungalow, clean and complete. A truly relaxing escape in nature.</p>
                        </article>

                        <article class="review-card">
                            <div class="review-card__header">
                                <span class="review-avatar review-avatar--four" aria-hidden="true"></span>
                                <div>
                                    <h3>Mike</h3>
                                    <p>April 2026</p>
                                </div>
                            </div>
                            <div class="stars" aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                            <p>Canoeing on the lake was the highlight of our trip. Highly recommended!</p>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/Includes/Footer.php'; ?>
    </div>
<?php else: ?>
    <main class="splash-page" aria-label="Maple Camp introduction">
        <section class="splash-page__content">
            <div class="splash-ornament" aria-hidden="true">
                <span class="splash-ornament__line"></span>
                <img class="splash-ornament__leaf" src="Images/herfst.webp" alt="">
                <span class="splash-ornament__line"></span>
            </div>
            <h1 class="splash-page__title">MAPLE CAMP</h1>
            <div class="splash-page__subtitle">CANADIAN MOUNTAIN CAMPING</div>
            <p class="splash-page__tagline">Mountains. Water. Adventure. Freedom.</p>
            <a class="splash-book" href="Index.php?view=home" aria-label="Open the Maple Camp homepage">
                <span class="splash-book__text">Book Now</span>
                <span class="splash-book__arrow" aria-hidden="true"></span>
            </a>
        </section>
    </main>
<?php endif; ?>
</body>
</html>
