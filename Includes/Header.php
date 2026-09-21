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
            'Activiteiten.php' => 'activiteiten',
            'Admin.php' => 'admin',
            'Evenementen.php' => 'events',
            'Omgeving.php' => 'omgeving',
            'Reviews.php' => 'reviews',
    ];
    $currentPage = $pageMap[$scriptName] ?? 'home';
}

$navItems = [
        'home' => ['label' => 'Home', 'href' => $basePath . 'Index.php?view=home', 'dropdown' => false],
        'accommodaties' => ['label' => 'Accommodaties', 'href' => $basePath . 'Pages/Accomodatie.php', 'dropdown' => true],
        'faciliteiten' => ['label' => 'Faciliteiten', 'href' => $basePath . 'Index.php?view=home#faciliteiten', 'dropdown' => false],
        'activiteiten' => ['label' => 'Activiteiten', 'href' => $basePath . 'Pages/Activiteiten.php', 'dropdown' => false],
        'events' => ['label' => 'Events', 'href' => $basePath . 'Pages/Evenementen.php', 'dropdown' => true],
        'reviews' => ['label' => 'Reviews', 'href' => $basePath . 'Pages/Reviews.php', 'dropdown' => false],
        'omgeving' => ['label' => 'Omgeving', 'href' => $basePath . 'Pages/Omgeving.php', 'dropdown' => false],
];
$isAuthPage = in_array($currentPage, ['login', 'register'], true);
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
        <nav class="site-nav" aria-label="Primaire navigatie">
            <?php foreach ($navItems as $key => $item): ?>
                <a class="site-nav__link<?= $currentPage === $key ? ' site-nav__link--active' : ''; ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($item['dropdown']): ?>
                        <span class="chevron" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="site-header__actions">
            <select class="language-select" data-language-select data-no-translate aria-label="Language">
                <option value="en">EN</option>
                <option value="es">ES</option>
                <option value="fr">FR</option>
                <option value="de">DE</option>
            </select>
            <?php if ($loggedInVoornaam !== ''): ?>
                <span class="header-account"><?= htmlspecialchars($loggedInVoornaam, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($loggedInRoleName === 'admin'): ?>
                    <a class="header-login" href="<?= htmlspecialchars($basePath . 'Pages/Admin.php', ENT_QUOTES, 'UTF-8'); ?>">Admin</a>
                <?php endif; ?>
                <a class="header-login" href="<?= htmlspecialchars($basePath . 'Pages/Logout.php', ENT_QUOTES, 'UTF-8'); ?>">Logout</a>
            <?php else: ?>
                <a class="header-login" href="<?= htmlspecialchars($basePath . 'Pages/Login.php', ENT_QUOTES, 'UTF-8'); ?>">Login</a>
            <?php endif; ?>
            <a class="header-booking" href="<?= htmlspecialchars($basePath . 'Index.php?view=home#booking', ENT_QUOTES, 'UTF-8'); ?>">Boek je verblijf</a>
        </div>
    <?php endif; ?>
</header>
