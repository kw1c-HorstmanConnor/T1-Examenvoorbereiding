<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'activiteiten';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activiteiten - Maple Camp</title>
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
                <p class="section-label section-label--light">ACTIVITEITEN</p>
                <h1 class="home-hero__title" id="page-heading">Buiten beleven</h1>
            </div>
        </section>

        <main>
            <section class="home-section">
                <div class="page-container section-header">
                    <div>
                        <p class="section-label">ONTDEKKEN</p>
                        <h2 class="section-title">Hiking, canoeing en kampvuuravonden</h2>
                        <p class="section-copy">Van bergpaden tot rustige meren: kies activiteiten die passen bij jouw tempo en seizoen.</p>
                    </div>
                    <a class="outline-button" href="../Index.php?view=home#activiteiten">Terug naar activiteiten</a>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
</body>
</html>
