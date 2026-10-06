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
if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['booking_csrf'];

function maple_vacation_is_cancelled_label(string $label): bool
{
    return (bool) preg_match('/cancel|annul|geannul/i', $label);
}

function maple_vacation_is_unpaid_label(string $label): bool
{
    return (bool) preg_match('/unpaid|onbetaald|reserved|gereserveerd/i', $label);
}

function maple_vacation_is_paid_label(string $label): bool
{
    return (bool) preg_match('/^(paid|betaald|confirmed|bevestigd)$/i', trim($label));
}

function maple_vacation_date($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable((string) $value);
    } catch (Throwable $exception) {
        return null;
    }
}

function maple_vacation_format_date($date): string
{
    return $date ? $date->format('d M Y') : 'Unavailable';
}

function maple_vacation_refund_percentage(int $daysUntilArrival): int
{
    if ($daysUntilArrival >= 28) {
        return 100;
    }
    if ($daysUntilArrival >= 21) {
        return 70;
    }
    if ($daysUntilArrival >= 14) {
        return 55;
    }

    return 0;
}

function maple_vacation_refund_amount(float $total, int $percentage): float
{
    return round($total * ($percentage / 100), 2);
}

function maple_vacation_redirect_with_notice(string $type, string $message)
{
    $_SESSION['my_vacation_notice'] = ['type' => $type, 'message' => $message];
    header('Location: MyVacation.php');
    exit;
}

$cancelledStatusId = maple_booking_unique_status_id($conn, ['cancelled', 'canceled', 'geannuleerd']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['vacation_action'] ?? '') === 'cancel') {
    $reservationId = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $submittedToken = (string) ($_POST['cancel_submission_token'] ?? '');
    $sessionToken = (string) ($_SESSION['cancel_submission_token'] ?? '');

    if (!hash_equals($csrfToken, (string) ($_POST['csrf'] ?? '')) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        maple_vacation_redirect_with_notice('error', 'This cancellation request has expired or was already submitted.');
    }
    unset($_SESSION['cancel_submission_token']);

    if ($reservationId === false || $reservationId === null) {
        maple_vacation_redirect_with_notice('error', 'Please select a valid reservation.');
    }
    if ($cancelledStatusId === null) {
        maple_vacation_redirect_with_notice('error', 'Cancellation is currently unavailable because no unique Cancelled status is configured.');
    }

    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Transaction could not be started.');
        }

        $userStatement = $conn->prepare('SELECT `User_id` FROM `User` WHERE `User_id` = ? LIMIT 1 FOR UPDATE');
        if (!$userStatement) {
            throw new RuntimeException('User lookup failed.');
        }
        $userStatement->bind_param('i', $userId);
        if (!$userStatement->execute()) {
            $userStatement->close();
            throw new RuntimeException('User lookup failed.');
        }
        $userResult = $userStatement->get_result();
        $lockedUser = $userResult ? $userResult->fetch_assoc() : null;
        $userStatement->close();
        if (!$lockedUser) {
            throw new DomainException('Your account could not be verified. Please log in again.');
        }

        $reservationStatement = $conn->prepare(
            'SELECT r.`Reservatie_id`, r.`Aan_date`, r.`Out_date`, r.`Total_prijs`, r.`Status_id`,
                    DATE_ADD(r.`reservation`, INTERVAL 1 MONTH) AS `expires_at`, s.`Status` AS `Status_label`
             FROM `reservaties` r
             INNER JOIN `status` s ON s.`Status_id` = r.`Status_id`
             WHERE r.`Reservatie_id` = ? AND r.`User_id` = ?
             LIMIT 1 FOR UPDATE'
        );
        if (!$reservationStatement) {
            throw new RuntimeException('Reservation lookup failed.');
        }
        $reservationStatement->bind_param('ii', $reservationId, $userId);
        if (!$reservationStatement->execute()) {
            $reservationStatement->close();
            throw new RuntimeException('Reservation lookup failed.');
        }
        $reservationResult = $reservationStatement->get_result();
        $reservation = $reservationResult ? $reservationResult->fetch_assoc() : null;
        $reservationStatement->close();
        if (!$reservation) {
            throw new DomainException('This reservation could not be found or cannot be cancelled.');
        }

        $statusLabel = trim((string) $reservation['Status_label']);
        if ((int) $reservation['Status_id'] === $cancelledStatusId || maple_vacation_is_cancelled_label($statusLabel)) {
            throw new DomainException('This vacation has already been cancelled.');
        }
        $isPaid = maple_vacation_is_paid_label($statusLabel);
        $isUnpaid = maple_vacation_is_unpaid_label($statusLabel);
        if (!$isPaid && !$isUnpaid) {
            throw new DomainException('This reservation status cannot be cancelled.');
        }
        if ($isUnpaid) {
            $expiresAt = maple_vacation_date($reservation['expires_at']);
            if ($expiresAt === null || $expiresAt <= new DateTimeImmutable()) {
                throw new DomainException('This unpaid reservation has already expired.');
            }
        }

        $today = new DateTimeImmutable('today');
        $arrival = maple_vacation_date($reservation['Aan_date']);
        if ($arrival === null || $arrival <= $today) {
            throw new DomainException('Only upcoming vacations can be cancelled.');
        }
        $daysUntilArrival = (int) $today->diff($arrival)->days;
        $refundPercentage = $isPaid ? maple_vacation_refund_percentage($daysUntilArrival) : 0;
        $refundAmount = maple_vacation_refund_amount((float) $reservation['Total_prijs'], $refundPercentage);

        $updateStatement = $conn->prepare(
            'UPDATE `reservaties` SET `Status_id` = ?
             WHERE `Reservatie_id` = ? AND `User_id` = ? AND `Status_id` = ? AND `Aan_date` > CURDATE()'
        );
        if (!$updateStatement) {
            throw new RuntimeException('Cancellation update failed.');
        }
        $previousStatusId = (int) $reservation['Status_id'];
        $updateStatement->bind_param('iiii', $cancelledStatusId, $reservationId, $userId, $previousStatusId);
        if (!$updateStatement->execute() || $updateStatement->affected_rows !== 1) {
            $updateStatement->close();
            throw new DomainException('Cancellation is no longer allowed for this reservation.');
        }
        $updateStatement->close();

        if (!$conn->commit()) {
            throw new RuntimeException('Cancellation commit failed.');
        }

        $refundText = number_format($refundAmount, 2, ',', '.');
        maple_vacation_redirect_with_notice(
            'success',
            $isPaid
                ? 'De reservering is geannuleerd. Volgens de annuleringsvoorwaarden bedraagt de terugbetaling €' . $refundText . '. De daadwerkelijke betaalverwerking wordt later gekoppeld.'
                : 'The unpaid reservation has been cancelled. No payment or refund was processed.'
        );
    } catch (DomainException $exception) {
        $conn->rollback();
        maple_vacation_redirect_with_notice('error', $exception->getMessage());
    } catch (Throwable $exception) {
        $conn->rollback();
        maple_vacation_redirect_with_notice('error', 'Your reservation could not be cancelled. Please try again later.');
    }
}

$notice = $_SESSION['my_vacation_notice'] ?? null;
unset($_SESSION['my_vacation_notice']);
if (empty($_SESSION['cancel_submission_token'])) {
    $_SESSION['cancel_submission_token'] = bin2hex(random_bytes(32));
}
$cancelSubmissionToken = (string) $_SESSION['cancel_submission_token'];
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
    if (!$overviewStatement) {
        throw new RuntimeException('Reservation overview could not be prepared.');
    }
    $overviewStatement->bind_param('i', $userId);
    if (!$overviewStatement->execute()) {
        $overviewStatement->close();
        throw new RuntimeException('Reservation overview could not be loaded.');
    }
    $result = $overviewStatement->get_result();
    while ($result && ($reservation = $result->fetch_assoc())) {
        $statusLabel = trim((string) ($reservation['Status_label'] ?? ''));
        $arrival = maple_vacation_date($reservation['Aan_date']);
        $departure = maple_vacation_date($reservation['Out_date']);
        $today = new DateTimeImmutable('today');
        $isCancelled = maple_vacation_is_cancelled_label($statusLabel);
        $isUnpaid = maple_vacation_is_unpaid_label($statusLabel);
        $isPaid = maple_vacation_is_paid_label($statusLabel);
        $expiresAt = $isUnpaid ? maple_vacation_date($reservation['expires_at']) : null;
        $isExpired = $isUnpaid && ($expiresAt === null || $expiresAt <= new DateTimeImmutable());
        $displayStatus = $isExpired ? 'Expired' : ($statusLabel !== '' ? $statusLabel : 'Unknown');

        $reservation['status_label'] = $displayStatus;
        $reservation['category'] = $isCancelled ? 'Cancelled vacations' : ($isExpired ? 'Expired reservations' : (($departure !== null && $departure <= $today) ? 'Past vacations' : (($arrival !== null && $arrival <= $today) ? 'Current vacation' : 'Upcoming vacations')));
        $reservation['arrival'] = $arrival;
        $reservation['departure'] = $departure;
        $reservation['expires_at'] = $expiresAt;
        $reservation['is_unpaid'] = $isUnpaid && !$isExpired;
        $reservation['is_paid'] = $isPaid;
        $reservation['nights'] = ($arrival !== null && $departure !== null && $departure > $arrival) ? (int) $arrival->diff($departure)->days : 0;
        $reservation['days_until_arrival'] = $arrival !== null && $arrival > $today ? (int) $today->diff($arrival)->days : 0;
        $reservation['refund_percentage'] = $isPaid ? maple_vacation_refund_percentage((int) $reservation['days_until_arrival']) : 0;
        $reservation['refund_amount'] = maple_vacation_refund_amount((float) $reservation['Total_prijs'], (int) $reservation['refund_percentage']);
        $reservation['can_cancel'] = $cancelledStatusId !== null && !$isCancelled && !$isExpired && ($isPaid || $isUnpaid) && $arrival !== null && $arrival > $today;
        $reservations[] = $reservation;
    }
    $overviewStatement->close();
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
    <?php if ($reservations === []): ?>
        <section class="my-vacation__empty"><h2>No vacations booked yet</h2><p>You don't have any vacations booked yet.</p><a class="outline-button" href="Accomodatie.php">View accommodations <span class="button-arrow" aria-hidden="true"></span></a></section>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <?php $categoryReservations = array_values(array_filter($reservations, static function (array $reservation) use ($category): bool { return $reservation['category'] === $category; })); ?>
            <?php if ($categoryReservations !== []): ?>
                <section class="my-vacation__section" aria-labelledby="<?= htmlspecialchars(strtolower(str_replace(' ', '-', $category)), ENT_QUOTES, 'UTF-8'); ?>">
                    <h2 id="<?= htmlspecialchars(strtolower(str_replace(' ', '-', $category)), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="my-vacation__grid">
                    <?php foreach ($categoryReservations as $reservation): ?>
                        <article class="vacation-card">
                            <p class="vacation-card__number">Reservation #<?= (int) $reservation['Reservatie_id']; ?></p>
                            <h3><?= htmlspecialchars((string) $reservation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="vacation-card__location"><?= htmlspecialchars((string) $reservation['Locatie'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <dl>
                                <div><dt>Arrival</dt><dd><?= htmlspecialchars(maple_vacation_format_date($reservation['arrival']), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                                <div><dt>Departure</dt><dd><?= htmlspecialchars(maple_vacation_format_date($reservation['departure']), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                                <div><dt>Nights</dt><dd><?= (int) $reservation['nights']; ?></dd></div>
                                <div><dt>Total</dt><dd>&euro; <?= number_format((float) $reservation['Total_prijs'], 2, ',', '.'); ?></dd></div>
                                <?php if ($reservation['is_unpaid'] && $reservation['expires_at'] !== null): ?><div><dt>Held until</dt><dd><?= htmlspecialchars(maple_vacation_format_date($reservation['expires_at']), ENT_QUOTES, 'UTF-8'); ?></dd></div><?php endif; ?>
                            </dl>
                            <p class="vacation-card__status"><span>Status</span><?= htmlspecialchars((string) $reservation['status_label'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($reservation['is_unpaid']): ?><p>This historical unpaid reservation is held temporarily until the date shown above.</p><?php endif; ?>
                            <?php if ($reservation['can_cancel']): ?>
                                <button class="vacation-card__cancel" type="button" data-cancel-button
                                    data-reservation-id="<?= (int) $reservation['Reservatie_id']; ?>"
                                    data-name="<?= htmlspecialchars((string) $reservation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-arrival="<?= htmlspecialchars(maple_vacation_format_date($reservation['arrival']), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-total="<?= htmlspecialchars(number_format((float) $reservation['Total_prijs'], 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-paid="<?= $reservation['is_paid'] ? '1' : '0'; ?>"
                                    data-days="<?= (int) $reservation['days_until_arrival']; ?>"
                                    data-percentage="<?= (int) $reservation['refund_percentage']; ?>"
                                    data-refund="<?= htmlspecialchars(number_format((float) $reservation['refund_amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>">ANNULEREN</button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<div class="vacation-modal" id="vacation-cancel-modal" hidden aria-hidden="true">
    <div class="vacation-modal__overlay" data-cancel-close></div>
    <section class="vacation-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="vacation-cancel-title" tabindex="-1">
        <button class="vacation-modal__close" type="button" aria-label="Keep reservation" data-cancel-close>&times;</button>
        <p class="section-label">CANCELLATION</p>
        <h2 id="vacation-cancel-title">Reservering annuleren?</h2>
        <dl class="vacation-modal__details">
            <div><dt>Accommodatie</dt><dd id="cancel-reservation-name"></dd></div>
            <div><dt>Aankomst</dt><dd id="cancel-reservation-arrival"></dd></div>
            <div><dt id="cancel-reservation-total-label">Totaal betaald</dt><dd id="cancel-reservation-total"></dd></div>
        </dl>
        <p id="cancel-refund-summary"></p>
        <p class="vacation-modal__warning">Annuleren is definitief. Er wordt nog geen echte terugbetaling uitgevoerd.</p>
        <form method="post" action="MyVacation.php">
            <input type="hidden" name="vacation_action" value="cancel">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="cancel_submission_token" value="<?= htmlspecialchars($cancelSubmissionToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="reservation_id" id="cancel-reservation-id" value="">
            <div class="vacation-modal__actions">
                <button type="button" class="outline-button" data-cancel-close>BEHOUD RESERVERING</button>
                <button type="submit" class="vacation-card__cancel">DEFINITIEF ANNULEREN</button>
            </div>
        </form>
    </section>
</div>
<?php include __DIR__ . '/../Includes/Footer.php'; ?>
<script>
(function () {
    'use strict';
    var modal = document.getElementById('vacation-cancel-modal');
    var cancelButtons = document.querySelectorAll('[data-cancel-button]');
    var closeButtons = document.querySelectorAll('[data-cancel-close]');

    Array.prototype.forEach.call(cancelButtons, function (button) {
        button.addEventListener('click', function () {
            document.getElementById('cancel-reservation-id').value = button.getAttribute('data-reservation-id');
            document.getElementById('cancel-reservation-name').textContent = button.getAttribute('data-name');
            document.getElementById('cancel-reservation-arrival').textContent = button.getAttribute('data-arrival');
            var isPaid = button.getAttribute('data-paid') === '1';
            document.getElementById('cancel-reservation-total-label').textContent = isPaid ? 'Totaal betaald' : 'Reserveringsbedrag';
            document.getElementById('cancel-reservation-total').textContent = '€' + button.getAttribute('data-total');
            document.getElementById('cancel-refund-summary').textContent = isPaid
                ? 'Je annuleert ' + button.getAttribute('data-days') + ' dagen voor aankomst. Volgens de annuleringsvoorwaarden ontvang je ' + button.getAttribute('data-percentage') + '% terug: €' + button.getAttribute('data-refund') + ' van €' + button.getAttribute('data-total') + '.'
                : 'Je annuleert een historische onbetaalde reservering. Er is niets betaald en er wordt geen terugbetaling uitgevoerd.';
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            modal.querySelector('.vacation-modal__dialog').focus();
        });
    });

    Array.prototype.forEach.call(closeButtons, function (button) {
        button.addEventListener('click', function () {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
        });
    });
}());
</script>
</body>
</html>
