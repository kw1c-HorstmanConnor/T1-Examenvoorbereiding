<?php
$scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$rootPrefix = basename($scriptDir) === 'Pages' ? '../' : '';
$basePath = $basePath ?? $rootPrefix;
$assetBase = $assetBase ?? $rootPrefix;
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
    'accommodaties' => ['label' => 'Cottages', 'href' => $basePath . 'Pages/Accomodatie.php', 'dropdown' => true],
    'faciliteiten' => ['label' => 'Facilities', 'href' => $basePath . 'Index.php?view=home#faciliteiten', 'dropdown' => false],
    'activiteiten' => ['label' => 'Activities', 'href' => $basePath . 'Pages/Activiteiten.php', 'dropdown' => false],
    'events' => ['label' => 'Events', 'href' => $basePath . 'Pages/Evenementen.php', 'dropdown' => true],
    'omgeving' => ['label' => 'Surroundings', 'href' => $basePath . 'Pages/Omgeving.php', 'dropdown' => false],
];
?>
<header class="site-header page-container" aria-label="Main navigation">
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

    <nav class="site-nav" aria-label="Primary navigation">
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
        <button class="language-select" type="button">EN <span class="chevron" aria-hidden="true"></span></button>
        <a class="header-booking" href="<?= htmlspecialchars($basePath . 'Index.php?view=home#booking', ENT_QUOTES, 'UTF-8'); ?>">Book your stay</a>
    </div>
</header>
