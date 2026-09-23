<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Auth/Authorization.php';
maple_require_admin('Login.php');

$basePath = '../';
$assetBase = '../';
$currentPage = 'admin';
$tabs = ['events', 'accommodations', 'news'];
$tab = in_array($_GET['tab'] ?? '', $tabs, true) ? $_GET['tab'] : 'events';
$error = '';

function maple_admin_e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function maple_admin_datetime(string $value): ?string {
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value);
    return $date && $date->format('Y-m-d\\TH:i') === $value ? $date->format('Y-m-d H:i:s') : null;
}
function maple_admin_redirect(string $tab, string $notice): void {
    $_SESSION['admin_notice'] = $notice;
    header('Location: AdminPanel.php?tab=' . rawurlencode($tab));
    exit;
}

if (empty($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
$csrf = (string) $_SESSION['admin_csrf'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tab = in_array($_POST['tab'] ?? '', $tabs, true) ? $_POST['tab'] : 'events';
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Your session has expired. Please try again.';
    } else try {
        $db = maple_db();
        if ($tab === 'events') {
            $title = trim((string) ($_POST['title'] ?? '')); $description = trim((string) ($_POST['description'] ?? ''));
            $start = maple_admin_datetime((string) ($_POST['start_time'] ?? '')); $location = trim((string) ($_POST['location'] ?? ''));
            if ($action === 'delete' && $id) {
                $stmt = $db->prepare('DELETE FROM evenementen WHERE evenementen_id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'Event deleted.');
            }
            if ($title === '' || !$start || max(mb_strlen($title), mb_strlen($description), mb_strlen($location)) > 255) $error = 'Enter a title, valid start date and time, and values up to 255 characters.';
            elseif ($action === 'create') {
                $userId = (int) maple_current_user()['user_id']; $stmt = $db->prepare('INSERT INTO evenementen (User_id, Titel, Omschrijving, Start_time, Locatie) VALUES (?, ?, ?, ?, ?)'); $stmt->bind_param('issss', $userId, $title, $description, $start, $location); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'Event created.');
            } elseif ($action === 'update' && $id) {
                $stmt = $db->prepare('UPDATE evenementen SET Titel = ?, Omschrijving = ?, Start_time = ?, Locatie = ? WHERE evenementen_id = ?'); $stmt->bind_param('ssssi', $title, $description, $start, $location, $id); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'Event updated.');
            }
        } elseif ($tab === 'accommodations') {
            $name = trim((string) ($_POST['name'] ?? '')); $location = trim((string) ($_POST['location'] ?? '')); $facilities = trim((string) ($_POST['facilities'] ?? '')); $description = trim((string) ($_POST['description'] ?? ''));
            $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT); $maximum = filter_var($_POST['maximum'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($action === 'delete' && $id) {
                $stmt = $db->prepare('DELETE FROM accomodaties WHERE Huis_id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'Accommodation deleted.');
            }
            if ($name === '' || $location === '' || $price === false || $price < 0 || $maximum === false || max(mb_strlen($name), mb_strlen($location), mb_strlen($facilities), mb_strlen($description)) > 255) $error = 'Enter a name, location, non-negative price, maximum guests, and values up to 255 characters.';
            elseif ($action === 'create') {
                $stmt = $db->prepare('INSERT INTO accomodaties (Huis_naam, Locatie, PPN, Voorzieningen, Max, Omschr) VALUES (?, ?, ?, ?, ?, ?)'); $stmt->bind_param('ssdsis', $name, $location, $price, $facilities, $maximum, $description); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'Accommodation created.');
            } elseif ($action === 'update' && $id) {
                $stmt = $db->prepare('UPDATE accomodaties SET Huis_naam = ?, Locatie = ?, PPN = ?, Voorzieningen = ?, Max = ?, Omschr = ? WHERE Huis_id = ?'); $stmt->bind_param('ssdsisi', $name, $location, $price, $facilities, $maximum, $description, $id); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'Accommodation updated.');
            }
        } else {
            $content = trim((string) ($_POST['content'] ?? '')); $published = maple_admin_datetime((string) ($_POST['published'] ?? ''));
            if ($action === 'delete' && $id) {
                $stmt = $db->prepare('DELETE FROM nieuws WHERE Nieuws_id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'News item deleted.');
            }
            if ($content === '' || mb_strlen($content) > 255) $error = 'Enter news content up to 255 characters.';
            elseif ($action === 'create') {
                $userId = (int) maple_current_user()['user_id']; $stmt = $db->prepare('INSERT INTO nieuws (User_id, Inhoud, Aangemaakt, Gepubliceerd) VALUES (?, ?, NOW(), ?)'); $stmt->bind_param('iss', $userId, $content, $published); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'News item created.');
            } elseif ($action === 'update' && $id) {
                $stmt = $db->prepare('UPDATE nieuws SET Inhoud = ?, Gepubliceerd = ? WHERE Nieuws_id = ?'); $stmt->bind_param('ssi', $content, $published, $id); $stmt->execute(); $stmt->close(); maple_admin_redirect($tab, 'News item updated.');
            }
        }
    } catch (Throwable $exception) { $error = 'The change could not be saved. Please check the database connection.'; }
}

$events = $accommodations = $news = [];
try {
    $db = maple_db();
    $result = $db->query('SELECT evenementen_id, Titel, Omschrijving, Start_time, Locatie FROM evenementen ORDER BY Start_time DESC'); $events = $result ? $result->fetch_all(MYSQLI_ASSOC) : []; $result?->free();
    $result = $db->query('SELECT Huis_id, Huis_naam, Locatie, PPN, Voorzieningen, Max, Omschr FROM accomodaties ORDER BY Huis_id DESC'); $accommodations = $result ? $result->fetch_all(MYSQLI_ASSOC) : []; $result?->free();
    $result = $db->query('SELECT Nieuws_id, Inhoud, Aangemaakt, Gepubliceerd FROM nieuws ORDER BY Aangemaakt DESC'); $news = $result ? $result->fetch_all(MYSQLI_ASSOC) : []; $result?->free();
} catch (Throwable $exception) { $error = $error ?: 'The admin data could not be loaded. Please check the database connection.'; }

$idColumn = $tab === 'events' ? 'evenementen_id' : ($tab === 'accommodations' ? 'Huis_id' : 'Nieuws_id');
$items = $tab === 'events' ? $events : ($tab === 'accommodations' ? $accommodations : $news);
$editing = null; $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
foreach ($items as $item) if ($editId && (int) $item[$idColumn] === $editId) { $editing = $item; break; }
$notice = (string) ($_SESSION['admin_notice'] ?? ''); unset($_SESSION['admin_notice']);
?>
<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin panel - Maple Camp</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="../Styling/index.css"><link rel="stylesheet" href="../Styling/admin.css"><?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?></head>
<body class="admin-view"><div class="admin-page"><section class="admin-top" aria-labelledby="admin-heading"><?php include __DIR__ . '/../Includes/Header.php'; ?><div class="admin-top__content page-container"><p class="section-label section-label--light">ADMIN</p><h1 id="admin-heading">Content management</h1><p>Create, edit, and remove events, accommodations, and news.</p></div></section>
<main class="admin-main page-container"><nav class="admin-tabs" aria-label="Admin sections"><a class="<?= $tab === 'events' ? 'is-active' : ''; ?>" href="AdminPanel.php?tab=events">Events</a><a class="<?= $tab === 'accommodations' ? 'is-active' : ''; ?>" href="AdminPanel.php?tab=accommodations">Accommodations</a><a class="<?= $tab === 'news' ? 'is-active' : ''; ?>" href="AdminPanel.php?tab=news">News</a></nav><?php if ($notice): ?><p class="admin-feedback success" role="status"><?= maple_admin_e($notice); ?></p><?php endif; ?><?php if ($error): ?><p class="admin-feedback error" role="alert"><?= maple_admin_e($error); ?></p><?php endif; ?>
<?php if ($tab === 'events'): ?>
<section class="admin-crud"><div class="admin-crud__form"><p class="section-label">EVENTS</p><h2><?= $editing ? 'Edit event' : 'New event'; ?></h2><form class="admin-form" method="post"><input type="hidden" name="csrf" value="<?= maple_admin_e($csrf); ?>"><input type="hidden" name="tab" value="events"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create'; ?>"><?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['evenementen_id']; ?>"><?php endif; ?><label>Title<input required maxlength="255" name="title" value="<?= maple_admin_e($editing['Titel'] ?? ''); ?>"></label><label>Start date and time<input required type="datetime-local" name="start_time" value="<?= maple_admin_e($editing ? date('Y-m-d\\TH:i', strtotime($editing['Start_time'])) : ''); ?>"></label><label>Location<input maxlength="255" name="location" value="<?= maple_admin_e($editing['Locatie'] ?? ''); ?>"></label><label>Description<textarea maxlength="255" name="description" rows="4"><?= maple_admin_e($editing['Omschrijving'] ?? ''); ?></textarea></label><div class="admin-form__actions"><button><?= $editing ? 'Save event' : 'Create event'; ?></button><?php if ($editing): ?><a href="AdminPanel.php?tab=events">Cancel</a><?php endif; ?></div></form></div><div class="admin-crud__list"><h2>All events</h2><?php foreach ($events as $event): ?><article class="admin-record"><div><h3><?= maple_admin_e($event['Titel']); ?></h3><p><?= maple_admin_e($event['Start_time']); ?> · <?= maple_admin_e($event['Locatie']); ?></p></div><div class="admin-record__actions"><a href="AdminPanel.php?tab=events&edit=<?= (int) $event['evenementen_id']; ?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?= maple_admin_e($csrf); ?>"><input type="hidden" name="tab" value="events"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $event['evenementen_id']; ?>"><button class="admin-delete" onclick="return confirm('Delete this event?');">Delete</button></form></div></article><?php endforeach; ?></div></section>
<?php elseif ($tab === 'accommodations'): ?>
<section class="admin-crud"><div class="admin-crud__form"><p class="section-label">ACCOMMODATIONS</p><h2><?= $editing ? 'Edit accommodation' : 'New accommodation'; ?></h2><form class="admin-form" method="post"><input type="hidden" name="csrf" value="<?= maple_admin_e($csrf); ?>"><input type="hidden" name="tab" value="accommodations"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create'; ?>"><?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['Huis_id']; ?>"><?php endif; ?><label>Name<input required maxlength="255" name="name" value="<?= maple_admin_e($editing['Huis_naam'] ?? ''); ?>"></label><label>Location<input required maxlength="255" name="location" value="<?= maple_admin_e($editing['Locatie'] ?? ''); ?>"></label><label>Price per night<input required type="number" min="0" step="0.01" name="price" value="<?= maple_admin_e($editing['PPN'] ?? ''); ?>"></label><label>Maximum guests<input required type="number" min="1" name="maximum" value="<?= maple_admin_e($editing['Max'] ?? ''); ?>"></label><label>Facilities<input maxlength="255" name="facilities" value="<?= maple_admin_e($editing['Voorzieningen'] ?? ''); ?>"></label><label>Description<textarea maxlength="255" name="description" rows="4"><?= maple_admin_e($editing['Omschr'] ?? ''); ?></textarea></label><div class="admin-form__actions"><button><?= $editing ? 'Save accommodation' : 'Create accommodation'; ?></button><?php if ($editing): ?><a href="AdminPanel.php?tab=accommodations">Cancel</a><?php endif; ?></div></form></div><div class="admin-crud__list"><h2>All accommodations</h2><?php foreach ($accommodations as $a): ?><article class="admin-record"><div><h3><?= maple_admin_e($a['Huis_naam']); ?></h3><p><?= maple_admin_e($a['Locatie']); ?> · €<?= maple_admin_e($a['PPN']); ?> / night · <?= (int) $a['Max']; ?> guests</p></div><div class="admin-record__actions"><a href="AdminPanel.php?tab=accommodations&edit=<?= (int) $a['Huis_id']; ?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?= maple_admin_e($csrf); ?>"><input type="hidden" name="tab" value="accommodations"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $a['Huis_id']; ?>"><button class="admin-delete" onclick="return confirm('Delete this accommodation?');">Delete</button></form></div></article><?php endforeach; ?></div></section>
<?php else: ?>
<section class="admin-crud"><div class="admin-crud__form"><p class="section-label">NEWS</p><h2><?= $editing ? 'Edit news item' : 'New news item'; ?></h2><form class="admin-form" method="post"><input type="hidden" name="csrf" value="<?= maple_admin_e($csrf); ?>"><input type="hidden" name="tab" value="news"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create'; ?>"><?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['Nieuws_id']; ?>"><?php endif; ?><label>Content<textarea required maxlength="255" name="content" rows="5"><?= maple_admin_e($editing['Inhoud'] ?? ''); ?></textarea></label><label>Publish at <small>(leave blank to keep unpublished)</small><input type="datetime-local" name="published" value="<?= maple_admin_e(!empty($editing['Gepubliceerd']) ? date('Y-m-d\\TH:i', strtotime($editing['Gepubliceerd'])) : ''); ?>"></label><div class="admin-form__actions"><button><?= $editing ? 'Save news item' : 'Create news item'; ?></button><?php if ($editing): ?><a href="AdminPanel.php?tab=news">Cancel</a><?php endif; ?></div></form></div><div class="admin-crud__list"><h2>All news</h2><?php foreach ($news as $item): ?><article class="admin-record"><div><h3><?= maple_admin_e($item['Inhoud']); ?></h3><p>Created <?= maple_admin_e($item['Aangemaakt']); ?><?= $item['Gepubliceerd'] ? ' · Published ' . maple_admin_e($item['Gepubliceerd']) : ' · Unpublished'; ?></p></div><div class="admin-record__actions"><a href="AdminPanel.php?tab=news&edit=<?= (int) $item['Nieuws_id']; ?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?= maple_admin_e($csrf); ?>"><input type="hidden" name="tab" value="news"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['Nieuws_id']; ?>"><button class="admin-delete" onclick="return confirm('Delete this news item?');">Delete</button></form></div></article><?php endforeach; ?></div></section>
<?php endif; ?></main></div><?php include __DIR__ . '/../Includes/Footer.php'; ?></body></html>
