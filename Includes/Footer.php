<?php
$scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$rootPrefix = basename($scriptDir) === 'Pages' ? '../' : '';
$basePath = $basePath ?? $rootPrefix;
$assetBase = $assetBase ?? $rootPrefix;
?>
<footer class="site-footer">
    <div class="page-container site-footer__grid">
        <div class="site-footer__brand">
            <a class="site-logo site-logo--footer" href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Maple Camp home">
                <span class="site-logo__mark" aria-hidden="true">
                    <span class="site-logo__peak site-logo__peak--one"></span>
                    <span class="site-logo__peak site-logo__peak--two"></span>
                    <span class="site-logo__peak site-logo__peak--three"></span>
                </span>
                <span class="site-logo__name">MAPLE CAMP</span>
                <span class="site-logo__sub">CANADIAN WILDERNESS</span>
                <img class="site-logo__leaf" src="<?= htmlspecialchars($assetBase . 'Images/herfst.webp', ENT_QUOTES, 'UTF-8'); ?>" alt="">
            </a>
            <p>A unique 20+ camping experience in the heart of the Canadian wilderness. Unwind, seek adventure and make memories that last a lifetime.</p>
            <div class="social-links" aria-label="Social media">
                <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Facebook">f</a>
                <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Instagram"><span class="social-icon social-icon--instagram" aria-hidden="true"></span></a>
                <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="YouTube"><span class="social-icon social-icon--youtube" aria-hidden="true"></span></a>
            </div>
        </div>

        <nav class="footer-column" aria-label="Quick links">
            <h2>Quick links</h2>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Accomodatie.php', ENT_QUOTES, 'UTF-8'); ?>">Cottages</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home#faciliteiten', ENT_QUOTES, 'UTF-8'); ?>">Facilities</a>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Activiteiten.php', ENT_QUOTES, 'UTF-8'); ?>">Activities</a>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Evenementen.php', ENT_QUOTES, 'UTF-8'); ?>">Events</a>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Omgeving.php', ENT_QUOTES, 'UTF-8'); ?>">Surroundings</a>
        </nav>

        <nav class="footer-column" aria-label="Information">
            <h2>Information</h2>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Frequently asked questions</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Park rules</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Cancellation policy</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Privacy policy</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Terms and conditions</a>
        </nav>

        <address class="footer-column footer-contact">
            <h2>Maple Camp</h2>
            <p>Mountain Lake Road 12<br>Banff, Alberta, Canada</p>
            <span class="footer-divider"></span>
            <a href="mailto:info@maplecamp.ca"><span class="contact-icon contact-icon--mail" aria-hidden="true"></span>info@maplecamp.ca</a>
            <a href="tel:+31656743524"><span class="contact-icon contact-icon--phone" aria-hidden="true"></span>+31 6 56743524</a>
        </address>

        <div class="footer-newsletter">
            <h2>Sign up for our newsletter</h2>
            <p>Stay up to date with news, events and exclusive offers.</p>
            <form class="newsletter-form" action="<?= htmlspecialchars($basePath . 'Index.php', ENT_QUOTES, 'UTF-8'); ?>" method="get">
                <input type="hidden" name="view" value="home">
                <label class="sr-only" for="newsletter-email">Your email address</label>
                <input id="newsletter-email" type="email" name="email" placeholder="Your email address">
                <button type="submit" aria-label="Subscribe"><span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </div>

    <div class="page-container site-footer__bottom">
        <p>&copy; 2026 Maple Camp. All rights reserved.</p>
        <p>Designed with &#9825; for nature lovers</p>
    </div>
</footer>
