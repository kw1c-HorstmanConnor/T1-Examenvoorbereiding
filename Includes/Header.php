<?php
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

$scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$rootPrefix = basename($scriptDir) === 'Pages' ? '../' : '';
$basePath = $basePath ?? $rootPrefix;
$assetBase = $assetBase ?? $rootPrefix;
$loggedInVoornaam = trim((string) ($_SESSION['voornaam'] ?? ''));
if (!isset($currentPage)) {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? 'Index.php');
    $pageMap = [
        'Index.php' => 'home',
        'Accomodatie.php' => 'accommodaties',
        'Activiteiten.php' => 'activiteiten',
        'Evenementen.php' => 'events',
        'Omgeving.php' => 'omgeving',
    ];
    $currentPage = $pageMap[$scriptName] ?? 'home';
}

$navItems = [
    'home' => ['label' => 'Home', 'href' => $basePath . 'Index.php?view=home', 'dropdown' => false],
    'accommodaties' => ['label' => 'Accommodaties', 'href' => $basePath . 'Pages/Accomodatie.php', 'dropdown' => true],
    'faciliteiten' => ['label' => 'Faciliteiten', 'href' => $basePath . 'Index.php?view=home#faciliteiten', 'dropdown' => false],
    'activiteiten' => ['label' => 'Activiteiten', 'href' => $basePath . 'Pages/Activiteiten.php', 'dropdown' => false],
    'events' => ['label' => 'Events', 'href' => $basePath . 'Pages/Evenementen.php', 'dropdown' => true],
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
        <span class="site-logo__mark" aria-hidden="true">
            <span class="site-logo__peak site-logo__peak--one"></span>
            <span class="site-logo__peak site-logo__peak--two"></span>
            <span class="site-logo__peak site-logo__peak--three"></span>
        </span>
        <span class="site-logo__name">MAPLE CAMP</span>
        <span class="site-logo__sub">CANADIAN WILDERNESS</span>
        <img class="site-logo__leaf" src="<?= htmlspecialchars($assetBase . 'Images/herfst.webp', ENT_QUOTES, 'UTF-8'); ?>" alt="">
    </a>

    <?php if ($isAuthPage): ?>
        <div class="site-header__actions site-header__actions--auth">
            <button class="language-select language-select--auth" type="button">
                <i class="fa-solid fa-globe" aria-hidden="true"></i>
                <span>ENGLISH</span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
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
            <button class="language-select" type="button">NL <span class="chevron" aria-hidden="true"></span></button>
            <?php if ($loggedInVoornaam !== ''): ?>
                <span class="header-account"><?= htmlspecialchars($loggedInVoornaam, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
                <a class="header-login" href="<?= htmlspecialchars($basePath . 'Pages/Login.php', ENT_QUOTES, 'UTF-8'); ?>">Login</a>
            <?php endif; ?>
            <a class="header-booking" href="<?= htmlspecialchars($basePath . 'Index.php?view=home#booking', ENT_QUOTES, 'UTF-8'); ?>">Boek je verblijf</a>
        </div>
    <?php endif; ?>
</header>
