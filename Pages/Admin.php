<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Auth/Authorization.php';

maple_require_admin('Login.php');

$basePath = '../';
$assetBase = '../';
$currentPage = 'admin';
$currentUser = maple_current_user();
$adminName = trim((string) ($currentUser['voornaam'] ?? 'Admin'));
$adminSections = [
    ['label' => 'Gebruikers', 'title' => 'Users'],
    ['label' => 'Accommodaties', 'title' => 'Accommodations'],
    ['label' => 'Blokkades', 'title' => 'Blocks'],
    ['label' => 'Nieuws', 'title' => 'News'],
    ['label' => 'Events', 'title' => 'Events'],
];
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/admin.css">
</head>
<body class="admin-view">
    <div class="admin-page">
        <section class="admin-top" aria-labelledby="admin-heading">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="admin-top__content page-container">
                <p class="section-label section-label--light">ADMIN</p>
                <h1 id="admin-heading">Maple Camp beheer</h1>
                <p><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </section>

        <main class="admin-main page-container">
            <section class="admin-status" aria-label="Admin status">
                <article class="admin-stat">
                    <span>Rol</span>
                    <strong>Admin</strong>
                </article>
                <article class="admin-stat">
                    <span>Sessie</span>
                    <strong>Actief</strong>
                </article>
                <article class="admin-stat">
                    <span>Project</span>
                    <strong>Maple Camp</strong>
                </article>
            </section>

            <section class="admin-workspace" aria-labelledby="workspace-heading">
                <div class="admin-workspace__header">
                    <h2 id="workspace-heading">Dashboard</h2>
                    <a class="header-login" href="Logout.php">Logout</a>
                </div>

                <div class="admin-section-grid">
                    <?php foreach ($adminSections as $section): ?>
                        <article class="admin-section">
                            <span><?= htmlspecialchars($section['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
