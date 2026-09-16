<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'omgeving';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surroundings - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/surroundings.css">
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
    <script src="../Scripts/Surroundings.js" defer></script>
</head>
<body class="surroundings-view">
    <div class="home-page surroundings-page">
        <section class="surroundings-hero" aria-labelledby="surroundings-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="page-container surroundings-hero__content reveal-on-scroll">
                <p class="section-label section-label--light">KOOTENAY LAKE, BRITISH COLUMBIA</p>
                <h1 class="surroundings-hero__title" id="surroundings-heading">Explore the Canadian Wilderness</h1>
                <p class="surroundings-hero__copy">Mountains, crystal-clear water and endless wilderness - right outside your cabin.</p>
                <a class="surroundings-button surroundings-button--ghost" href="#discover-area">Discover the area</a>
            </div>
        </section>

        <main id="discover-area">
            <section class="surroundings-intro surroundings-section">
                <div class="page-container surroundings-split">
                    <figure class="surroundings-split__media reveal-on-scroll">
                        <img src="../Images/surroundings-lake.jpg" alt="Mountain lake with pine forest and peaks in British Columbia" data-parallax-image>
                    </figure>
                    <div class="surroundings-split__copy reveal-on-scroll">
                        <p class="section-label">Kootenay Lake</p>
                        <h2>Welcome to Kootenay Lake</h2>
                        <p>Maple Camp is surrounded by the forests, mountains and clear waters of British Columbia. Spend your morning paddling across the lake, explore nearby trails during the afternoon and return to camp as the sun disappears behind the mountains.</p>
                    </div>
                </div>
            </section>

            <section class="surroundings-gallery surroundings-section" aria-labelledby="gallery-title" data-surroundings-gallery>
                <div class="page-container surroundings-gallery__header reveal-on-scroll">
                    <p class="section-label">The Landscape</p>
                    <h2 id="gallery-title">Six ways the wilderness stays with you.</h2>
                </div>

                <div class="page-container surroundings-gallery__stage reveal-on-scroll">
                    <figure class="surroundings-slide is-active" data-slide>
                        <img src="../Images/surroundings-canoe.jpg" alt="Two people canoeing on a calm Canadian mountain lake">
                        <figcaption>Morning on the lake</figcaption>
                    </figure>
                    <figure class="surroundings-slide" data-slide>
                        <img src="../Images/surroundings-hero.jpg" alt="Canoe on clear green water below Canadian mountains">
                        <figcaption>Crystal water below the peaks</figcaption>
                    </figure>
                    <figure class="surroundings-slide" data-slide>
                        <img src="../Images/surroundings-forest.jpg" alt="Dense Canadian pine forest with mountains beyond">
                        <figcaption>Walk into the forest</figcaption>
                    </figure>
                    <figure class="surroundings-slide" data-slide>
                        <img src="../Images/Hiking.jpg" alt="Hikers on a mountain trail surrounded by forest and peaks">
                        <figcaption>Explore the mountains</figcaption>
                    </figure>
                    <figure class="surroundings-slide" data-slide>
                        <img src="../Images/surroundings-mountain-lake.jpg" alt="Wide mountain lake framed by pine forest and rugged peaks">
                        <figcaption>Find your own quiet place</figcaption>
                    </figure>
                    <figure class="surroundings-slide" data-slide>
                        <img src="../Images/surroundings-campfire.jpg" alt="Campfire glowing at dusk with forested mountains in the distance">
                        <figcaption>Golden evenings</figcaption>
                    </figure>

                    <button class="surroundings-gallery__arrow surroundings-gallery__arrow--prev" type="button" aria-label="Previous slide" data-gallery-prev>&lsaquo;</button>
                    <button class="surroundings-gallery__arrow surroundings-gallery__arrow--next" type="button" aria-label="Next slide" data-gallery-next>&rsaquo;</button>

                    <div class="surroundings-gallery__dots" aria-label="Slideshow navigation" data-gallery-dots></div>
                </div>
            </section>

            <section class="lake-playground">
                <div class="lake-playground__image reveal-on-scroll">
                    <img src="../Images/surroundings-canoe.jpg" alt="Red canoe on a calm Canadian lake beneath pine forest" data-parallax-image>
                </div>

                <div class="page-container lake-playground__content">
                    <div class="lake-playground__intro reveal-on-scroll">
                        <p class="section-label section-label--light">Explore the lake</p>
                        <h2>The lake is your playground</h2>
                        <p>Step from the campground straight into the Canadian outdoors. Paddle along the shoreline, explore quiet bays or simply spend the afternoon beside the water.</p>
                    </div>

                    <div class="lake-activities" aria-label="Lake activities">
                        <article class="lake-activity reveal-on-scroll">
                            <img src="../Images/canoeing.png" alt="A guest canoeing across a calm mountain lake">
                            <div>
                                <h3>Canoeing</h3>
                                <p>Explore the shoreline and peaceful bays of Kootenay Lake.</p>
                            </div>
                        </article>
                        <article class="lake-activity reveal-on-scroll">
                            <img src="../Images/Kayaking.png" alt="Two guests kayaking near a forested shoreline">
                            <div>
                                <h3>Kayaking</h3>
                                <p>Head onto the water and experience the mountains from a completely different perspective.</p>
                            </div>
                        </article>
                        <article class="lake-activity reveal-on-scroll">
                            <img src="../Images/surroundings-lake.jpg" alt="Clear mountain lake with pine forest and mountains">
                            <div>
                                <h3>Swimming</h3>
                                <p>Relax along the sandy shoreline and cool down after a day outdoors.</p>
                            </div>
                        </article>
                        <article class="lake-activity reveal-on-scroll">
                            <img src="../Images/fishing.png" alt="Fishing rod at the lake shore">
                            <div>
                                <h3>Fishing</h3>
                                <p>Find a quiet spot beside the lake and enjoy the landscape around you.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="surroundings-map surroundings-section" aria-labelledby="map-title">
                <div class="page-container surroundings-map__grid">
                    <div class="surroundings-map__copy reveal-on-scroll">
                        <p class="section-label">British Columbia</p>
                        <h2 id="map-title">In the heart of British Columbia</h2>
                        <p>Far enough away to disconnect. Close enough to keep exploring.</p>
                        <dl class="map-travel-list">
                            <div>
                                <dt>Kootenay Lake</dt>
                                <dd>Directly beside the campground</dd>
                            </div>
                            <div>
                                <dt>Nelson</dt>
                                <dd>Approximately 20 minutes away</dd>
                            </div>
                            <div>
                                <dt>Mountain and hiking areas</dt>
                                <dd>Surrounding the campground</dd>
                            </div>
                        </dl>
                        <a class="surroundings-button" href="https://www.google.com/maps/search/?api=1&query=Kokanee+Creek+Provincial+Park+British+Columbia+Canada" target="_blank" rel="noopener">View in Google Maps</a>
                    </div>

                    <div class="surroundings-map__frame reveal-on-scroll">
                        <iframe
                            title="Map centered on Kokanee Creek Provincial Park, British Columbia"
                            src="https://www.google.com/maps?q=Kokanee%20Creek%20Provincial%20Park%2C%20British%20Columbia%2C%20Canada&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>
                        <div class="maple-map-marker" aria-hidden="true">
                            <span></span>
                            <strong>Maple Camp</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="hiking-banner" aria-labelledby="hiking-title">
                <div class="page-container hiking-banner__content reveal-on-scroll">
                    <p class="section-label section-label--light">Mountains &amp; Hiking</p>
                    <h2 id="hiking-title">Leave the road behind</h2>
                    <p>Walk through cedar forests, follow mountain trails and discover viewpoints high above the lake.</p>
                    <a class="text-link text-link--light" href="../Pages/Activiteiten.php">Explore activities <span class="text-link__arrow" aria-hidden="true"></span></a>
                </div>
            </section>

            <section class="day-story surroundings-section" aria-labelledby="day-story-title">
                <div class="page-container">
                    <div class="day-story__header reveal-on-scroll">
                        <p class="section-label">A day at Maple Camp</p>
                        <h2 id="day-story-title">From still water to firelight.</h2>
                    </div>

                    <div class="day-story__timeline">
                        <article class="day-moment reveal-on-scroll">
                            <img src="../Images/surroundings-lake.jpg" alt="Still mountain lake in the morning light">
                            <div>
                                <time>07:30</time>
                                <h3>Wake up beside the lake</h3>
                                <p>Coffee, fresh mountain air and still water.</p>
                            </div>
                        </article>
                        <article class="day-moment reveal-on-scroll">
                            <img src="../Images/surroundings-canoe.jpg" alt="Red canoe crossing a peaceful lake">
                            <div>
                                <time>10:00</time>
                                <h3>Take a canoe onto the water</h3>
                                <p>Explore the shoreline before the rest of the world wakes up.</p>
                            </div>
                        </article>
                        <article class="day-moment reveal-on-scroll">
                            <img src="../Images/Hiking.jpg" alt="Hikers following a mountain trail">
                            <div>
                                <time>14:00</time>
                                <h3>Head into the mountains</h3>
                                <p>Spend the afternoon walking through forests and mountain trails.</p>
                            </div>
                        </article>
                        <article class="day-moment reveal-on-scroll">
                            <img src="../Images/surroundings-campfire.jpg" alt="Campfire at sunset in the mountains">
                            <div>
                                <time>20:30</time>
                                <h3>Watch the sunset</h3>
                                <p>Return to camp for a fire beside the lake.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="surroundings-final-cta" aria-labelledby="final-cta-title">
                <div class="page-container surroundings-final-cta__content reveal-on-scroll">
                    <h2 id="final-cta-title">Your Canadian escape starts here.</h2>
                    <p>Trade busy streets for mountain trails, screens for sunsets and alarms for mornings beside the lake.</p>
                    <div class="surroundings-final-cta__actions">
                        <a class="surroundings-button surroundings-button--light" href="../Pages/Accomodatie.php">View accommodations</a>
                        <a class="surroundings-button" href="../Index.php?view=home#booking">Book your stay</a>
                    </div>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
</body>
</html>
