<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';
require_once __DIR__ . '/../Includes/DataBase.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'my-vacation';

if (empty($_SESSION['user_id'])) {
    header('Location: Login.php?redirect=' . rawurlencode('MyVacation.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* The existing booking CSRF token is reused for this account action. */
if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['booking_csrf'];

function maple_vacation_status_label(array $row): string
{
    foreach ($row as $column => $value) {
        $columnName = strtolower((string) $column);
        if ($columnName === 'status_id' || !is_string($value) || trim($value) === '') {
            continue;
        }

        if (strpos($columnName, 'status') !== false || strpos($columnName, 'naam') !== false || strpos($columnName, 'name') !== false) {
            return trim($value);
        }
    }

    return '';
}

function maple_vacation_is_cancelled_label(string $label): bool
{
    return (bool) preg_match('/cancel|annul|geannul/i', $label);
}

function maple_vacation_is_unpaid_label(string $label): bool
{
    return (bool) preg_match('/unpaid|onbetaald|reserved|gereserveerd/i', $label);
}

function maple_vacation_date($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $exception) {
        return null;
    }
}

function maple_vacation_format_date($date): string
{
    return $date ? $date->format('d M Y') : 'Unavailable';
}

function maple_vacation_redirect_with_notice(string $type, string $message)
{
    $_SESSION['my_vacation_notice'] = ['type' => $type, 'message' => $message];
    header('Location: MyVacation.php');
    exit;
}

$statusLabels = [];
$cancelledStatusId = null;
$cancelledStatusCount = 0;
try {
    $statusStatement = $conn->prepare('SELECT * FROM `status`');
    if ($statusStatement) {
        $statusStatement->execute();
        $statusResult = $statusStatement->get_result();
        while ($statusResult && ($status = $statusResult->fetch_assoc())) {
            if (!isset($status['Status_id'])) {
                continue;
            }
            $statusId = (int) $status['Status_id'];
            $label = maple_vacation_status_label($status);
            $statusLabels[$statusId] = $label;
            if (maple_vacation_is_cancelled_label($label)) {
                $cancelledStatusCount++;
                $cancelledStatusId = $statusId;
            }
        }
        $statusStatement->close();
    }
} catch (Throwable $exception) {
    // The overview remains available; cancellation is safely unavailable.
}
if ($cancelledStatusCount !== 1) {
    $cancelledStatusId = null;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['vacation_action'] ?? '') === 'cancel') {
    $reservationId = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if (!hash_equals($csrfToken, (string) ($_POST['csrf'] ?? ''))) {
        maple_vacation_redirect_with_notice('error', 'Your session has expired. Please try again.');
    }
    if ($reservationId === false || $reservationId === null) {
        maple_vacation_redirect_with_notice('error', 'Please select a valid reservation.');
    }
    if ($cancelledStatusId === null) {
        maple_vacation_redirect_with_notice('error', 'Cancellation is currently unavailable for this reservation.');
    }

    try {
        $reservationStatement = $conn->prepare(
            'SELECT r.`Reservatie_id`, r.`Aan_date`, r.`Out_date`, r.`Status_id`,
                    DATE_ADD(r.`reservation`, INTERVAL 1 MONTH) AS `expires_at`, a.`Huis_naam`
             FROM `reservaties` r
             INNER JOIN `accomodaties` a ON a.`Huis_id` = r.`Huis_id`
             WHERE r.`Reservatie_id` = ? AND r.`User_id` = ?
             LIMIT 1'
        );
        if (!$reservationStatement) {
            maple_vacation_redirect_with_notice('error', 'Your reservation could not be cancelled. Please try again later.');
        }
        $reservationStatement->bind_param('ii', $reservationId, $userId);
        $reservationStatement->execute();
        $reservation = $reservationStatement->get_result()->fetch_assoc();
        $reservationStatement->close();

        if (!$reservation) {
            // A reservation ID alone never authorizes a cancellation.
            maple_vacation_redirect_with_notice('error', 'This reservation could not be found or cannot be cancelled.');
        }
        if ((int) $reservation['Status_id'] === $cancelledStatusId || maple_vacation_is_cancelled_label($statusLabels[(int) $reservation['Status_id']] ?? '')) {
            maple_vacation_redirect_with_notice('error', 'This vacation has already been cancelled.');
        }
        $statusLabel = (string) ($statusLabels[(int) $reservation['Status_id']] ?? '');
        $expiresAt = maple_vacation_date($reservation['expires_at']);
        if (maple_vacation_is_unpaid_label($statusLabel)
            && ($expiresAt === null || $expiresAt <= new DateTimeImmutable())) {
            maple_vacation_redirect_with_notice('error', 'This unpaid reservation has already expired.');
        }

        $arrival = maple_vacation_date((string) $reservation['Aan_date']);
        if ($arrival === null || $arrival <= new DateTimeImmutable('today')) {
            maple_vacation_redirect_with_notice('error', 'Only upcoming vacations can be cancelled.');
        }

        $updateStatement = $conn->prepare(
            'UPDATE `reservaties`
             SET `Status_id` = ?
             WHERE `Reservatie_id` = ? AND `User_id` = ?
               AND `Status_id` <> ? AND `Aan_date` > CURDATE()'
        );
        if (!$updateStatement) {
            maple_vacation_redirect_with_notice('error', 'Your reservation could not be cancelled. Please try again later.');
        }
        $updateStatement->bind_param('iiii', $cancelledStatusId, $reservationId, $userId, $cancelledStatusId);
        $updateStatement->execute();
        $wasCancelled = $updateStatement->affected_rows === 1;
        $updateStatement->close();

        maple_vacation_redirect_with_notice(
            $wasCancelled ? 'success' : 'error',
            $wasCancelled ? 'Your vacation has been cancelled successfully.' : 'Cancellation is no longer allowed for this reservation.'
        );
    } catch (Throwable $exception) {
        maple_vacation_redirect_with_notice('error', 'Your reservation could not be cancelled. Please try again later.');
    }
}

$notice = $_SESSION['my_vacation_notice'] ?? null;
unset($_SESSION['my_vacation_notice']);
$activeReservationCount = maple_booking_active_reservation_count($conn, $userId);
$reservations = [];
try {
    $overviewStatement = $conn->prepare(
        'SELECT r.`Reservatie_id`, r.`Aan_date`, r.`Out_date`, r.`Total_prijs`, r.`Status_id`,
                DATE_ADD(r.`reservation`, INTERVAL 1 MONTH) AS `expires_at`,
                a.`Huis_naam`, a.`Locatie`, s.`Status` AS `Status_label`
         FROM `reservaties` r
         INNER JOIN `accomodaties` a ON a.`Huis_id` = r.`Huis_id`
         LEFT JOIN `status` s ON s.`Status_id` = r.`Status_id`
         WHERE r.`User_id` = ?
         ORDER BY r.`Aan_date` ASC, r.`Reservatie_id` DESC'
    );
    if ($overviewStatement) {
        $overviewStatement->bind_param('i', $userId);
        $overviewStatement->execute();
        $result = $overviewStatement->get_result();
        while ($result && ($reservation = $result->fetch_assoc())) {
            $reservation['status_label'] = trim((string) ($reservation['Status_label'] ?? ''));
            if ($reservation['status_label'] === '') {
                $reservation['status_label'] = $statusLabels[(int) $reservation['Status_id']] ?? 'Unknown';
            }
            $arrival = maple_vacation_date((string) $reservation['Aan_date']);
            $departure = maple_vacation_date((string) $reservation['Out_date']);
            $today = new DateTimeImmutable('today');
            $isCancelled = maple_vacation_is_cancelled_label((string) $reservation['status_label']);
            $expiresAt = maple_vacation_is_unpaid_label((string) $reservation['status_label'])
                ? maple_vacation_date($reservation['expires_at'])
                : null;
            $isExpired = $expiresAt === null && maple_vacation_is_unpaid_label((string) $reservation['status_label'])
                ? true
                : ($expiresAt !== null && $expiresAt <= new DateTimeImmutable());
            if ($isExpired) {
                $reservation['status_label'] = 'Expired';
            }
            $reservation['category'] = $isCancelled ? 'Cancelled vacations' : ($isExpired ? 'Expired reservations' : (($departure !== null && $departure <= $today) ? 'Past vacations' : (($arrival !== null && $arrival <= $today) ? 'Current vacation' : 'Upcoming vacations')));
            $reservation['arrival'] = $arrival;
            $reservation['departure'] = $departure;
            $reservation['expires_at'] = $expiresAt;
            $reservation['is_unpaid'] = !$isExpired && maple_vacation_is_unpaid_label((string) ($reservation['Status_label'] ?? ''));
            $reservation['nights'] = ($arrival !== null && $departure !== null && $departure > $arrival) ? (int) $arrival->diff($departure)->days : 0;
            $reservation['can_cancel'] = $cancelledStatusId !== null && !$isCancelled && !$isExpired && $arrival !== null && $arrival > $today;
            $reservations[] = $reservation;
        }
        $overviewStatement->close();
    }
} catch (Throwable $exception) {
    $notice = ['type' => 'error', 'message' => 'Your reservations could not be loaded. Please try again later.'];
}

$categories = ['Upcoming vacations', 'Current vacation', 'Past vacations', 'Expired reservations', 'Cancelled vacations'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Vacation - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/myvacantion.css">
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
</head>
<body class="my-vacation-page">
<?php include __DIR__ . '/../Includes/Header.php'; ?>
<main class="my-vacation page-container">
    <p class="section-label">MY ACCOUNT</p>
    <h1>My vacation</h1>
    <p class="my-vacation__intro">View and manage your Maple Camp reservations.</p>
    <?php if (is_array($notice)): ?>
        <p class="my-vacation__notice my-vacation__notice--<?= ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>" role="<?= ($notice['type'] ?? '') === 'success' ? 'status' : 'alert'; ?>"><?= htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($activeReservationCount !== null && $activeReservationCount >= 2): ?>
        <p class="my-vacation__notice my-vacation__notice--info" role="status">You have reached the maximum of two active reservations for this account. Cancel or complete one before making another reservation.</p>
    <?php endif; ?>
    <?php if ($reservations === []): ?>
        <section class="my-vacation__empty"><h2>No vacations booked yet</h2><p>You don't have any vacations booked yet.</p><a class="outline-button" href="Accomodatie.php">View accommodations <span class="button-arrow" aria-hidden="true"></span></a></section>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <?php $categoryReservations = array_values(array_filter($reservations, static function (array $reservation) use ($category): bool { return $reservation['category'] === $category; })); ?>
            <?php if ($categoryReservations !== []): ?>
                <section class="my-vacation__section" aria-labelledby="<?= htmlspecialchars(strtolower(str_replace(' ', '-', $category)), ENT_QUOTES, 'UTF-8'); ?>"><h2 id="<?= htmlspecialchars(strtolower(str_replace(' ', '-', $category)), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h2><div class="my-vacation__grid">
                <?php foreach ($categoryReservations as $reservation): ?>
                    <article class="vacation-card"><p class="vacation-card__number">Reservation #<?= (int) $reservation['Reservatie_id']; ?></p><h3><?= htmlspecialchars((string) $reservation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?></h3><p class="vacation-card__location"><?= htmlspecialchars((string) $reservation['Locatie'], ENT_QUOTES, 'UTF-8'); ?></p><dl><div><dt>Arrival</dt><dd><?= htmlspecialchars(maple_vacation_format_date($reservation['arrival']), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Departure</dt><dd><?= htmlspecialchars(maple_vacation_format_date($reservation['departure']), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Nights</dt><dd><?= (int) $reservation['nights']; ?></dd></div><div><dt>Total</dt><dd>&euro; <?= number_format((float) $reservation['Total_prijs'], 2, '.', ''); ?></dd></div><?php if ($reservation['is_unpaid'] && $reservation['expires_at'] !== null): ?><div><dt>Held until</dt><dd><?= htmlspecialchars(maple_vacation_format_date($reservation['expires_at']), ENT_QUOTES, 'UTF-8'); ?></dd></div><?php endif; ?></dl><p class="vacation-card__status"><span>Status</span><?= htmlspecialchars((string) $reservation['status_label'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($reservation['is_unpaid']): ?><p>This unpaid reservation is held temporarily. Complete payment before the date shown above.</p><?php endif; ?>
                    <?php if ($reservation['is_unpaid']): ?><button class="vacation-card__pay" type="button">BETALEN</button><?php endif; ?>
                    <?php if ($reservation['can_cancel']): ?><button class="vacation-card__cancel" type="button" data-cancel-button data-reservation-id="<?= (int) $reservation['Reservatie_id']; ?>" data-name="<?= htmlspecialchars((string) $reservation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?>" data-dates="<?= htmlspecialchars(maple_vacation_format_date($reservation['arrival']) . ' – ' . maple_vacation_format_date($reservation['departure']), ENT_QUOTES, 'UTF-8'); ?>">Cancel vacation</button><?php endif; ?>
                    </article>
                <?php endforeach; ?>
                </div></section>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<div class="vacation-modal" id="vacation-cancel-modal" hidden aria-hidden="true"><div class="vacation-modal__overlay" data-cancel-close></div><section class="vacation-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="vacation-cancel-title"><button class="vacation-modal__close" type="button" aria-label="Keep vacation" data-cancel-close>&times;</button><p class="section-label">CANCELLATION</p><h2 id="vacation-cancel-title">Cancel vacation?</h2><p id="cancel-reservation-name"></p><p id="cancel-reservation-dates"></p><p>Are you sure you want to cancel this vacation?</p><form method="post" action="MyVacation.php"><input type="hidden" name="vacation_action" value="cancel"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="reservation_id" id="cancel-reservation-id" value=""><div class="vacation-modal__actions"><button type="button" class="outline-button" data-cancel-close>Keep vacation</button><button type="submit" class="vacation-card__cancel">Cancel vacation</button></div></form></section></div>
<?php include __DIR__ . '/../Includes/Footer.php'; ?>
<script>
document.querySelectorAll('[data-cancel-button]').forEach((button) => { button.addEventListener('click', () => { const modal = document.getElementById('vacation-cancel-modal'); document.getElementById('cancel-reservation-id').value = button.dataset.reservationId; document.getElementById('cancel-reservation-name').textContent = button.dataset.name; document.getElementById('cancel-reservation-dates').textContent = button.dataset.dates; modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); }); });
document.querySelectorAll('[data-cancel-close]').forEach((button) => { button.addEventListener('click', () => { const modal = document.getElementById('vacation-cancel-modal'); modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); }); });
</script>
</body>
</html>
