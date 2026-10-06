<?php
require_once __DIR__ . '/../Functions/Helpers/Session.php';

$scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$rootPrefix = basename($scriptDir) === 'Pages' ? '../' : '';
$basePath = $basePath ?? $rootPrefix;
$assetBase = $assetBase ?? $rootPrefix;
$loggedInUser = maple_current_user();
$loggedInVoornaam = trim((string) ($loggedInUser['voornaam'] ?? ''));
$loggedInRoleName = strtolower(trim((string) ($loggedInUser['role_name'] ?? '')));
if (!isset($currentPage)) {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? 'Index.php');
    $pageMap = [
            'Index.php' => 'home',
            'Accomodatie.php' => 'accommodaties',
            'Faciliteiten.php' => 'faciliteiten',
            'Activiteiten.php' => 'activiteiten',
            'Admin.php' => 'admin',
            'Evenementen.php' => 'events',
            'Omgeving.php' => 'omgeving',
            'Reviews.php' => 'reviews',
    ];
    $currentPage = $pageMap[$scriptName] ?? 'home';
}

$navItems = [
        'home' => ['label' => 'Home', 'href' => $basePath . 'Index.php?view=home'],
        'accommodaties' => ['label' => 'Accommodaties', 'href' => $basePath . 'Pages/Accomodatie.php'],
        'faciliteiten' => ['label' => 'Faciliteiten', 'href' => $basePath . 'Pages/Faciliteiten.php'],
        'activiteiten' => ['label' => 'Activiteiten', 'href' => $basePath . 'Pages/Activiteiten.php'],
        'events' => ['label' => 'Events', 'href' => $basePath . 'Pages/Evenementen.php'],
        'reviews' => ['label' => 'Reviews', 'href' => $basePath . 'Pages/Reviews.php'],
        'omgeving' => ['label' => 'Omgeving', 'href' => $basePath . 'Pages/Omgeving.php'],
];
$isAuthPage = in_array($currentPage, ['login', 'register'], true);
$loginHref = $basePath . 'Pages/Login.php';

if ($currentPage === 'reviews') {
    $loginHref .= '?redirect=' . rawurlencode('Reviews.php');
}
?>
<?php if (empty($fontAwesomeLoaded)): ?>
    <?php $fontAwesomeLoaded = true; ?>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v7.3.1/css/all.css">
<?php endif; ?>
<header class="site-header<?= $isAuthPage ? ' site-header--auth' : ''; ?> page-container" aria-label="<?= $isAuthPage ? 'Authenticatie' : 'Hoofdnavigatie'; ?>">
    <a class="site-logo" href="<?= htmlspecialchars($basePath . 'Index.php?view=home', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Maple Camp home">
        <img class="site-logo__image" src="<?= htmlspecialchars($assetBase . 'Images/maple_logo.png', ENT_QUOTES, 'UTF-8'); ?>" alt="">
    </a>

    <?php if ($isAuthPage): ?>
        <div class="site-header__actions site-header__actions--auth">
            <label class="language-control language-control--auth">
                <i class="fa-solid fa-globe" aria-hidden="true"></i>
                <select class="language-select language-select--auth" data-language-select data-no-translate aria-label="Language">
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                </select>
            </label>
        </div>
    <?php else: ?>
        <button class="site-menu-toggle" type="button" aria-expanded="false" aria-controls="site-primary-nav site-header-actions">
            <span class="site-menu-toggle__icon" aria-hidden="true"></span>
            <span class="site-menu-toggle__label">Menu</span>
        </button>
        <nav class="site-nav" id="site-primary-nav" aria-label="Primaire navigatie">
            <?php foreach ($navItems as $key => $item): ?>
                <a class="site-nav__link<?= $currentPage === $key ? ' site-nav__link--active' : ''; ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="site-header__actions" id="site-header-actions">
            <select class="language-select" data-language-select aria-label="Language">
                <option value="en">EN</option>
                <option value="es">ES</option>
                <option value="fr">FR</option>
                <option value="de">DE</option>
            </select>
            <?php if ($loggedInVoornaam !== ''): ?>
                <a class="header-account" href="<?= htmlspecialchars($basePath . 'Pages/MyVacation.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="header-account__label">My vacation</span>
                    <span class="header-account__name" data-no-translate><?= htmlspecialchars($loggedInVoornaam, ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <?php if ($loggedInRoleName === 'admin'): ?>
                    <a class="header-login" href="<?= htmlspecialchars($basePath . 'Pages/AdminPanel.php', ENT_QUOTES, 'UTF-8'); ?>">Admin</a>
                <?php endif; ?>
                <a class="header-login" href="<?= htmlspecialchars($basePath . 'Pages/Logout.php', ENT_QUOTES, 'UTF-8'); ?>">Logout</a>
            <?php else: ?>
                <a class="header-login" href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8'); ?>">Login</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</header>
<?php if (!$isAuthPage): ?>
<script>
    (function () {
        'use strict';

        var header = document.currentScript.previousElementSibling;
        var toggle = header ? header.querySelector('.site-menu-toggle') : null;
        var menuLinks = header ? header.querySelectorAll('.site-nav a, .site-header__actions a') : [];

        if (!header || !toggle) {
            return;
        }

        function setMenuOpen(isOpen) {
            header.classList.toggle('site-header--menu-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        header.classList.add('site-header--menu-ready');
        toggle.addEventListener('click', function () {
            setMenuOpen(!header.classList.contains('site-header--menu-open'));
        });

        Array.prototype.forEach.call(menuLinks, function (link) {
            link.addEventListener('click', function () {
                setMenuOpen(false);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && header.classList.contains('site-header--menu-open')) {
                setMenuOpen(false);
                toggle.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 820) {
                setMenuOpen(false);
            }
        });
    }());
</script>
<?php endif; ?>
