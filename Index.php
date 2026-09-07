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
                            <input class="booking-field__input" type="date" name="aankomst" aria-label="Aankomstdatum">
                            <span class="booking-field__value">Selecteer datum</span>
                            <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="booking-field">
                        <span class="booking-field__label">Vertrek</span>
                        <span class="booking-field__control">
                            <input class="booking-field__input" type="date" name="vertrek" aria-label="Vertrekdatum">
                            <span class="booking-field__value">Selecteer datum</span>
                            <span class="booking-field__icon booking-field__icon--calendar" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="booking-field">
                        <span class="booking-field__label">Gasten</span>
                        <span class="booking-field__control">
                            <select class="booking-field__input" name="gasten" aria-label="Aantal gasten">
                                <option value="2">2 gasten</option>
                                <option value="1">1 gast</option>
                                <option value="3">3 gasten</option>
                                <option value="4">4 gasten</option>
                                <option value="5">5 gasten</option>
                                <option value="6">6 gasten</option>
                            </select>
                            <span class="booking-field__value">2 gasten</span>
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
                        <article class="accommodation-card">
                            <div class="accommodation-card__image accommodation-card__image--comfort">
                                <span class="popular-badge">Populair</span>
                            </div>
                            <div class="accommodation-card__body">
                                <h3>Bungalow Comfort</h3>
                                <div class="accommodation-meta" aria-label="Kenmerken">
                                    <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>4 personen</span>
                                    <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>2 slaapkamers</span>
                                    <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>45 m&sup2;</span>
                                </div>
                                <p>Sfeervolle bungalow met alles wat je nodig hebt voor een ontspannen verblijf in de natuur.</p>
                                <div class="price-block">
                                    <span>Vanaf</span>
                                    <strong>&euro; 120 <em>per nacht</em></strong>
                                </div>
                                <a class="card-button" href="#booking">Bekijk beschikbaarheid</a>
                            </div>
                        </article>

                        <article class="accommodation-card">
                            <div class="accommodation-card__image accommodation-card__image--luxe"></div>
                            <div class="accommodation-card__body">
                                <h3>Bungalow Luxe</h3>
                                <div class="accommodation-meta" aria-label="Kenmerken">
                                    <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>4 personen</span>
                                    <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>2 slaapkamers</span>
                                    <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>60 m&sup2;</span>
                                </div>
                                <p>Ruim en luxe ingericht met extra comfort en een prachtig uitzicht op de bergen.</p>
                                <div class="price-block">
                                    <span>Vanaf</span>
                                    <strong>&euro; 145 <em>per nacht</em></strong>
                                </div>
                                <a class="card-button" href="#booking">Bekijk beschikbaarheid</a>
                            </div>
                        </article>

                        <article class="accommodation-card">
                            <div class="accommodation-card__image accommodation-card__image--premium"></div>
                            <div class="accommodation-card__body">
                                <h3>Bungalow Premium</h3>
                                <div class="accommodation-meta" aria-label="Kenmerken">
                                    <span><i class="meta-icon meta-icon--guest" aria-hidden="true"></i>6 personen</span>
                                    <span><i class="meta-icon meta-icon--bed" aria-hidden="true"></i>3 slaapkamers</span>
                                    <span><i class="meta-icon meta-icon--area" aria-hidden="true"></i>75 m&sup2;</span>
                                </div>
                                <p>Extra ruim, modern en stijlvol. Perfect voor een langer verblijf of extra luxe.</p>
                                <div class="price-block">
                                    <span>Vanaf</span>
                                    <strong>&euro; 175 <em>per nacht</em></strong>
                                </div>
                                <a class="card-button" href="#booking">Bekijk beschikbaarheid</a>
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
                                <h2 class="section-title" id="events-title">Aankomende events</h2>
                            </div>
                            <a class="outline-button outline-button--small" href="Pages/Evenementen.php">Bekijk kalender</a>
                        </div>

                        <div class="event-list">
                            <article class="event-row">
                                <div class="event-row__image event-row__image--campfire" aria-hidden="true"></div>
                                <div class="event-row__content">
                                    <h3>Kampvuur avond</h3>
                                    <time datetime="2025-05-24T20:00">24 mei 2026 &bull; 20:00</time>
                                    <p>Gezellige avond bij het kampvuur met live muziek en marshmallows.</p>
                                </div>
                            </article>

                            <article class="event-row">
                                <div class="event-row__image event-row__image--rockies" aria-hidden="true"></div>
                                <div class="event-row__content">
                                    <h3>Wandeltocht Rockies</h3>
                                    <time datetime="2025-05-26T09:00">26 mei 2026 &bull; 09:00</time>
                                    <p>Begeleide wandeltocht door de prachtige Rocky Mountains.</p>
                                </div>
                            </article>

                            <article class="event-row">
                                <div class="event-row__image event-row__image--canoe" aria-hidden="true"></div>
                                <div class="event-row__content">
                                    <h3>Canoe Experience</h3>
                                    <time datetime="2025-05-28T10:00">28 mei 2026 &bull; 10:00</time>
                                    <p>Ontdek het meer tijdens een ontspannen canoe tocht.</p>
                                </div>
                            </article>
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
                                <span class="activity-icon activity-icon--hiking" aria-hidden="true"></span>
                                <h3>Hiking</h3>
                                <p>Ontdek de mooiste<br>wandelroutes</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--canoeing" aria-hidden="true"></span>
                                <h3>Canoeing</h3>
                                <p>Peddel over kristalheldere<br>meren</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--kayaking" aria-hidden="true"></span>
                                <h3>Kayaking</h3>
                                <p>Avontuur voor elk<br>niveau</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--fishing" aria-hidden="true"></span>
                                <h3>Fishing</h3>
                                <p>Vissen in de beste<br>spots</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--boat" aria-hidden="true"></span>
                                <h3>Boat Tours</h3>
                                <p>Verken de omgeving<br>vanaf het water</p>
                            </article>
                            <article class="activity-card">
                                <span class="activity-icon activity-icon--campfire" aria-hidden="true"></span>
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
