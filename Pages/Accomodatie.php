<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'accommodaties';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accommodaties - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
</head>
<body class="home-view">
    <div class="home-page">
        <section class="home-hero" aria-labelledby="page-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="home-hero__content page-container">
                <p class="section-label section-label--light">ACCOMMODATIES</p>
                <h1 class="home-hero__title" id="page-heading">Comfort in de natuur</h1>
            </div>
        </section>

        <main>
            <section class="home-section">
                <div class="page-container section-header">
                    <div>
                        <p class="section-label">VERBLIJF</p>
                        <h2 class="section-title">Bungalows voor rust en avontuur</h2>
                        <p class="section-copy">Kies een verblijf dat past bij je reisgezelschap en plan je aankomst via de homepage.</p>
                    </div>
                    <a class="outline-button" href="../Index.php?view=home#booking">Bekijk beschikbaarheid</a>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
</body>
</html>
