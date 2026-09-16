<?php
$scriptDirectory = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$languageAssetBase = $assetBase ?? (basename($scriptDirectory) === 'Pages' ? '../' : '');

$languageFiles = [
        'Language/English.js',
        'Language/Spanish.js',
        'Language/French.js',
        'Language/German.js',
        'Scripts/Language.js',
];

$languageCss = 'Styling/Language.css';
$cssPath = __DIR__ . '/../' . $languageCss;
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<?php if (is_file($cssPath)): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($languageAssetBase . $languageCss . '?v=' . $cssVersion, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
<?php foreach ($languageFiles as $languageFile): ?>
    <?php
    $absolutePath = __DIR__ . '/../' . $languageFile;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';
    ?>
    <script src="<?= htmlspecialchars($languageAssetBase . $languageFile . '?v=' . $version, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endforeach; ?>
