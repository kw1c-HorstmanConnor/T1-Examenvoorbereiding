<?php
require_once __DIR__ . '/../Includes/Admin.php';
maple_require_admin('Login.php');

$basePath = '../';
$assetBase = '../';
$currentPage = 'admin';
$currentUser = maple_current_user();
$adminName = trim((string) ($currentUser['voornaam'] ?? ''));
$adminRole = trim((string) ($_SESSION['role_name'] ?? 'Admin'));

function maple_admin_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
</head>
<body class="admin-view">
    <div class="admin-page">
        <section class="admin-top" aria-labelledby="admin-title">
            <?php include __DIR__ . '/../Includes/Header.php'; ?>

            <div class="admin-top__content page-container">
                <p class="section-label section-label--light">ADMIN</p>
                <h1 id="admin-title">Admin Panel</h1>
                <p>Ingelogd als <?= maple_admin_e($adminName !== '' ? $adminName : 'Admin'); ?></p>
            </div>
        </section>

        <main class="admin-main page-container">
            <section class="admin-status" aria-label="Admin status">
                <article class="admin-stat">
                    <span>Gebruiker ID</span>
                    <strong><?= (int) ($currentUser['user_id'] ?? 0); ?></strong>
                </article>
                <article class="admin-stat">
                    <span>Rol</span>
                    <strong><?= maple_admin_e($adminRole !== '' ? $adminRole : 'Admin'); ?></strong>
                </article>
                <article class="admin-stat">
                    <span>Toegang</span>
                    <strong>Actief</strong>
                </article>
            </section>

            <section class="admin-workspace" aria-labelledby="admin-workspace-title">
                <div class="admin-workspace__header">
                    <div>
                        <p class="section-label">BEHEER</p>
                        <h2 id="admin-workspace-title">Maple Camp beheer</h2>
                    </div>
                    <a class="outline-button" href="../Index.php?view=home">Terug naar website <span class="button-arrow" aria-hidden="true"></span></a>
                </div>

                <div class="admin-section-grid">
                    <a class="admin-section" href="Accomodatie.php">
                        <span>Accommodaties</span>
                        <strong>Open overzicht</strong>
                    </a>
                    <a class="admin-section" href="Evenementen.php">
                        <span>Events</span>
                        <strong>Open kalender</strong>
                    </a>
                    <a class="admin-section" href="Activiteiten.php">
                        <span>Activiteiten</span>
                        <strong>Open lijst</strong>
                    </a>
                    <a class="admin-section" href="Omgeving.php">
                        <span>Omgeving</span>
                        <strong>Open pagina</strong>
                    </a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
