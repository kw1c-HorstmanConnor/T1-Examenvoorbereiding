<?php
$scriptDirectory = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$languageAssetBase = $assetBase ?? (basename($scriptDirectory) === 'Pages' ? '../' : '');

$languageFiles = [
    'Language/English.js',
    'Language/German.js',
    'Language/French.js',
    'Language/Spanish.js',
    'Scripts/Language.js',
];
?>
<?php foreach ($languageFiles as $languageFile): ?>
    <?php
    $absolutePath = __DIR__ . '/../' . $languageFile;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';
    ?>
    <script src="<?= htmlspecialchars($languageAssetBase . $languageFile . '?v=' . $version, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endforeach; ?>
