<?php
maple_require_admin('Login.php');

$basePath = '../';
$assetBase = '../';
$currentPage = 'admin';
$currentUser = maple_current_user();
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
</head>
<body class="admin-view">
    <div class="admin-page">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="admin-top__content page-container">
                <p class="section-label section-label--light">ADMIN</p>
            </div>
        </section>

        <main class="admin-main page-container">
            <section class="admin-status" aria-label="Admin status">
                <article class="admin-stat">
                </article>
                <article class="admin-stat">
                </article>
                <article class="admin-stat">
                </article>
            </section>

                <div class="admin-workspace__header">
                </div>

                <div class="admin-section-grid">
                </div>
            </section>
        </main>
    </div>
</body>
</html>
