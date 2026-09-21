<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Auth/Authorization.php';

maple_require_admin('Login.php');

$basePath = '../';
$assetBase = '../';
$currentPage = 'admin';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin panel - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/admin.css">
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
</head>
<body class="admin-view">
    <div class="admin-page">
        <section class="admin-top" aria-labelledby="admin-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="admin-top__content page-container">
                <p class="section-label section-label--light">ADMIN</p>
                <h1 id="admin-heading">Admin panel</h1>
            </div>
        </section>

        <main class="admin-main page-container">
            <p>Welcome to the admin panel.</p>
        </main>
    </div>

    <?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
