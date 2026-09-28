<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';
require_once __DIR__ . '/../Includes/DataBase.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'accommodaties';
$bookingError = '';
$bookingNotice = isset($_SESSION['booking_notice']) ? (string) $_SESSION['booking_notice'] : '';
unset($_SESSION['booking_notice']);

$booking = isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null;
$accommodation = null;
$huisId = null;
$people = null;
$startDate = '';
$endDate = '';
$start = null;
$end = null;
$nights = 0;
$total = 0.0;
$canReserveUnpaid = false;
$activeReservationCountForDisplay = null;

if (!is_array($booking)) {
    $bookingError = 'There is no pending booking to display.';
} elseif (empty($_SESSION['user_id'])) {
    $_SESSION['booking_login_redirect'] = 'Book-Resi.php';
    header('Location: Login.php');
    exit;
} else {
    $huisId = filter_var(isset($booking['huis_id']) ? $booking['huis_id'] : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $people = filter_var(isset($booking['people']) ? $booking['people'] : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $startDate = trim((string) (isset($booking['start_date']) ? $booking['start_date'] : ''));
    $endDate = trim((string) (isset($booking['end_date']) ? $booking['end_date'] : ''));
    $start = maple_booking_date($startDate);
    $end = maple_booking_date($endDate);

    if ($huisId === false || $huisId === null || $people === false || $people === null || $start === null || $end === null || $start < new DateTimeImmutable('today') || $end <= $start) {
        $bookingError = 'Your pending booking is invalid. Please select an accommodation again.';
        unset($_SESSION['pending_booking']);
    } else {
        $accommodation = maple_booking_accommodation($conn, (int) $huisId);
        if ($accommodation === null) {
            $bookingError = 'The selected accommodation no longer exists.';
            unset($_SESSION['pending_booking']);
        } elseif ($people > (int) $accommodation['Max']) {
            $bookingError = 'The number of people exceeds this accommodation\'s maximum occupancy.';
            unset($_SESSION['pending_booking']);
        } elseif (maple_booking_is_available($conn, (int) $huisId, $startDate, $endDate) !== true) {
            $bookingError = 'The selected accommodation is no longer available for these dates.';
        } else {
            $nights = (int) $start->diff($end)->days;
            $total = $nights * (float) $accommodation['PPN'];
            $canReserveUnpaid = $start >= maple_booking_add_calendar_months(new DateTimeImmutable('today'), 4);
            unset($_SESSION['booking_login_redirect']);
        }
    }
}

if ($bookingError === '' && !empty($_SESSION['user_id'])) {
    $activeReservationCountForDisplay = maple_booking_active_reservation_count(
        $conn,
        (int) $_SESSION['user_id']
    );
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['booking_action'] ?? '') === 'reserve_unpaid') {
    $submittedCsrf = (string) ($_POST['csrf'] ?? '');
    $submittedToken = (string) ($_POST['submission_token'] ?? '');
    $sessionToken = (string) ($_SESSION['booking_submission_token'] ?? '');

    if (!hash_equals((string) ($_SESSION['booking_csrf'] ?? ''), $submittedCsrf)
        || $sessionToken === ''
        || !hash_equals($sessionToken, $submittedToken)) {
        $_SESSION['booking_notice'] = 'This booking request has expired or was already submitted.';
        header('Location: Book-Resi.php');
        exit;
    }

    unset($_SESSION['booking_submission_token']);

    if ($bookingError !== '' || !$canReserveUnpaid || $huisId === null || $people === null || $start === null || $end === null) {
        $_SESSION['booking_notice'] = !$canReserveUnpaid
            ? 'An unpaid reservation is only available when arrival is at least four calendar months away.'
            : 'The booking information is no longer valid.';
        header('Location: Book-Resi.php');
        exit;
    }

    try {
        $conn->begin_transaction();

        $userId = (int) $_SESSION['user_id'];
        $userStatement = $conn->prepare(
            'SELECT `User_id` FROM `User` WHERE `User_id` = ? LIMIT 1 FOR UPDATE'
        );
        if (!$userStatement) {
            throw new RuntimeException('User lookup failed.');
        }
        $userStatement->bind_param('i', $userId);
        $userStatement->execute();
        $lockedUser = $userStatement->get_result()->fetch_assoc();
        $userStatement->close();

        if (!$lockedUser) {
            throw new DomainException('Your account could not be verified. Please log in again.');
        }

        $activeReservationCount = maple_booking_active_reservation_count($conn, $userId);
        if ($activeReservationCount === null) {
            throw new RuntimeException('Reservation limit check failed.');
        }
        if ($activeReservationCount >= 2) {
            throw new DomainException('You can have a maximum of two active reservations per account.');
        }

        $accommodationStatement = $conn->prepare(
            'SELECT `Huis_id`, `Huis_naam`, `PPN`, `Max` FROM `accomodaties` WHERE `Huis_id` = ? LIMIT 1 FOR UPDATE'
        );
        if (!$accommodationStatement) {
            throw new RuntimeException('Accommodation lookup failed.');
        }
        $lockedHuisId = (int) $huisId;
        $accommodationStatement->bind_param('i', $lockedHuisId);
        $accommodationStatement->execute();
        $lockedAccommodation = $accommodationStatement->get_result()->fetch_assoc();
        $accommodationStatement->close();

        if (!$lockedAccommodation || (int) $people > (int) $lockedAccommodation['Max']) {
            throw new DomainException('The booking information is no longer valid.');
        }

        if (maple_booking_is_available($conn, $lockedHuisId, $startDate, $endDate) !== true) {
            throw new DomainException('The accommodation became unavailable for the selected dates.');
        }

        $unpaidStatusId = maple_booking_status_id($conn, ['unpaid', 'onbetaald']);
        if ($unpaidStatusId === null) {
            throw new RuntimeException('Unpaid status is unavailable.');
        }

        $authoritativeTotal = $nights * (float) $lockedAccommodation['PPN'];
        $insertStatement = $conn->prepare(
            'INSERT INTO `reservaties`
                (`User_id`, `Huis_id`, `Aan_date`, `Out_date`, `Total_prijs`, `Status_id`, `reservation`)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        if (!$insertStatement) {
            throw new RuntimeException('Reservation insert failed.');
        }
        $insertStatement->bind_param('iissdi', $userId, $lockedHuisId, $startDate, $endDate, $authoritativeTotal, $unpaidStatusId);
        $insertStatement->execute();
        if ($insertStatement->affected_rows !== 1) {
            $insertStatement->close();
            throw new RuntimeException('Reservation insert failed.');
        }
        $insertStatement->close();

        if (!$conn->commit()) {
            throw new RuntimeException('Reservation commit failed.');
        }
        unset($_SESSION['pending_booking']);
        $_SESSION['my_vacation_notice'] = [
            'type' => 'success',
            'message' => 'Your unpaid reservation is confirmed and will be held for one calendar month. Complete payment before it expires.',
        ];
        header('Location: MyVacation.php');
        exit;
    } catch (DomainException $exception) {
        $conn->rollback();
        $_SESSION['booking_notice'] = $exception->getMessage();
    } catch (Throwable $exception) {
        $conn->rollback();
        $_SESSION['booking_notice'] = 'Your reservation could not be created. Please try again later.';
    }

    header('Location: Book-Resi.php');
    exit;
}

if ($bookingError === '' && empty($_SESSION['booking_submission_token'])) {
    $_SESSION['booking_submission_token'] = bin2hex(random_bytes(32));
}
$submissionToken = (string) ($_SESSION['booking_submission_token'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking summary - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <link rel="stylesheet" href="../Styling/Booking.css">
</head>
<body class="booking-page">
<?php include __DIR__ . '/../Includes/Header.php'; ?>
<main class="booking-summary page-container">
    <p class="section-label">YOUR BOOKING</p>
    <h1>Booking summary</h1>
    <?php if ($bookingNotice !== ''): ?>
        <p class="booking-summary__error" role="alert"><?= htmlspecialchars($bookingNotice, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($bookingError !== ''): ?>
        <p class="booking-summary__error" role="alert"><?= htmlspecialchars($bookingError, ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="booking-summary__button" href="Accomodatie.php">Back to accommodations</a>
    <?php else: ?>
        <dl class="booking-summary__details">
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars((string) $accommodation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Arrival</dt><dd><?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Departure</dt><dd><?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Nights</dt><dd><?= $nights; ?></dd></div>
            <div><dt>People</dt><dd><?= (int) $people; ?></dd></div>
            <div><dt>Price per night</dt><dd>&euro; <?= number_format((float) $accommodation['PPN'], 2, '.', ''); ?></dd></div>
            <div class="booking-summary__total"><dt>Total price</dt><dd>&euro; <?= number_format($total, 2, '.', ''); ?></dd></div>
        </dl>
        <?php if ($canReserveUnpaid): ?>
            <p>Your reservation will be held for one calendar month. If payment is not completed within this period, the accommodation will become available again.</p>
            <?php if ($activeReservationCountForDisplay !== null && $activeReservationCountForDisplay >= 2): ?>
                <p class="booking-summary__error" role="alert">You already have two active reservations. An account can have a maximum of two active reservations. Cancel or complete one before making another reservation.</p>
            <?php elseif ($activeReservationCountForDisplay === null): ?>
                <p class="booking-summary__error" role="alert">Your reservation limit could not be checked. Please try again later.</p>
            <?php endif; ?>
            <div class="booking-summary__actions">
                <button class="booking-summary__button booking-summary__button--pay" type="button">BETALEN</button>
                <?php if ($activeReservationCountForDisplay !== null && $activeReservationCountForDisplay < 2): ?>
                    <form method="post" action="Book-Resi.php">
                        <input type="hidden" name="booking_action" value="reserve_unpaid">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars((string) $_SESSION['booking_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="submission_token" value="<?= htmlspecialchars($submissionToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="booking-summary__button" type="submit">RESERVEREN</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Arrival is less than four calendar months away. This stay can only be booked through payment.</p>
            <button class="booking-summary__button booking-summary__button--pay" type="button">BETALEN</button>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
