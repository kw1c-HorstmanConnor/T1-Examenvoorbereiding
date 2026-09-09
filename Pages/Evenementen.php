<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'events';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evenementen - Maple Camp</title>
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
                <p class="section-label section-label--light">EVENTS</p>
                <h1 class="home-hero__title" id="page-heading">Aankomende events</h1>
            </div>
        </section>

        <main>
            <section class="home-section">
                <div class="page-container section-header">
                    <div>
                        <p class="section-label">KALENDER</p>
                        <h2 class="section-title">Evenementen op Maple Camp</h2>
                        <p class="section-copy">Seizoensactiviteiten, kampvuuravonden en begeleide tochten brengen gasten samen in de natuur.</p>
                    </div>
                    <a class="outline-button" href="../Index.php?view=home#events">Terug naar events</a>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/../Includes/Footer.php'; ?>
    </div>
</body>
</html>
