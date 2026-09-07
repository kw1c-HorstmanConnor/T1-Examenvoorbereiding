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
            <p>Een unieke 20+ campingervaring in het hart van de Canadese natuur. Kom tot rust, beleef avontuur en maak herinneringen voor het leven.</p>
            <div class="social-links" aria-label="Sociale media">
                <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Facebook">f</a>
                <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Instagram"><span class="social-icon social-icon--instagram" aria-hidden="true"></span></a>
                <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="YouTube"><span class="social-icon social-icon--youtube" aria-hidden="true"></span></a>
            </div>
        </div>

        <nav class="footer-column" aria-label="Snel naar">
            <h2>Snel naar</h2>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Accomodatie.php', ENT_QUOTES, 'UTF-8'); ?>">Accommodaties</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home#faciliteiten', ENT_QUOTES, 'UTF-8'); ?>">Faciliteiten</a>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Activiteiten.php', ENT_QUOTES, 'UTF-8'); ?>">Activiteiten</a>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Evenementen.php', ENT_QUOTES, 'UTF-8'); ?>">Events</a>
            <a href="<?= htmlspecialchars($basePath . 'Pages/Omgeving.php', ENT_QUOTES, 'UTF-8'); ?>">Omgeving</a>
        </nav>

        <nav class="footer-column" aria-label="Informatie">
            <h2>Informatie</h2>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Veelgestelde vragen</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Huisregels</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Annuleringsvoorwaarden</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Privacybeleid</a>
            <a href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>">Algemene voorwaarden</a>
        </nav>

        <address class="footer-column footer-contact">
            <h2>Maple Camp</h2>
            <p>Mountain Lake Road 12<br>Banff, Alberta, Canada</p>
            <span class="footer-divider"></span>
            <a href="mailto:info@maplecamp.ca"><span class="contact-icon contact-icon--mail" aria-hidden="true"></span>info@maplecamp.ca</a>
            <a href="tel:+31656743524"><span class="contact-icon contact-icon--phone" aria-hidden="true"></span>+31 6 56743524</a>
        </address>

        <div class="footer-newsletter">
            <h2>Schrijf je in voor onze nieuwsbrief</h2>
            <p>Blijf op de hoogte van nieuws, events en exclusieve aanbiedingen.</p>
            <form class="newsletter-form" action="<?= htmlspecialchars($basePath . 'Index.php', ENT_QUOTES, 'UTF-8'); ?>" method="get">
                <input type="hidden" name="view" value="home">
                <label class="sr-only" for="newsletter-email">Jouw e-mailadres</label>
                <input id="newsletter-email" type="email" name="email" placeholder="Jouw e-mailadres">
                <button type="submit" aria-label="Inschrijven"><span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </div>

    <div class="page-container site-footer__bottom">
        <p>&copy; 2026 Maple Camp. All rights reserved.</p>
        <p>Designed with &#9825; for nature lovers</p>
    </div>
</footer>
