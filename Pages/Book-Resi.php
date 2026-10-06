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

if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

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
        } elseif ((int) $people > (int) $accommodation['Max']) {
            $bookingError = 'The number of people exceeds this accommodation\'s maximum occupancy.';
            unset($_SESSION['pending_booking']);
        } else {
            $available = maple_booking_is_available($conn, (int) $huisId, $startDate, $endDate);
            if ($available !== true) {
                $bookingError = $available === false ? 'The selected accommodation is no longer available for these dates.' : 'Availability could not be verified. Please try again later.';
            } else {
                $nights = (int) $start->diff($end)->days;
                $total = round($nights * (float) $accommodation['PPN'], 2);
                unset($_SESSION['booking_login_redirect']);
            }
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['booking_action'] ?? '') === 'pay') {
    $submittedCsrf = (string) ($_POST['csrf'] ?? '');
    $submittedToken = (string) ($_POST['submission_token'] ?? '');
    $sessionToken = (string) ($_SESSION['booking_submission_token'] ?? '');

    if (!hash_equals((string) $_SESSION['booking_csrf'], $submittedCsrf) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        $_SESSION['booking_notice'] = 'This payment confirmation has expired or was already submitted.';
        header('Location: Book-Resi.php');
        exit;
    }

    unset($_SESSION['booking_submission_token']);
    if ($bookingError !== '' || $huisId === null || $people === null || $start === null || $end === null || $nights < 1) {
        $_SESSION['booking_notice'] = 'The booking information is no longer valid.';
        header('Location: Book-Resi.php');
        exit;
    }

    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Transaction could not be started.');
        }

        $userId = (int) $_SESSION['user_id'];
        $userStatement = $conn->prepare('SELECT `User_id` FROM `User` WHERE `User_id` = ? LIMIT 1');
        if (!$userStatement) {
            throw new RuntimeException('User lookup failed.');
        }
        $userStatement->bind_param('i', $userId);
        if (!$userStatement->execute()) {
            $userStatement->close();
            throw new RuntimeException('User lookup failed.');
        }
        $userResult = $userStatement->get_result();
        $verifiedUser = $userResult ? $userResult->fetch_assoc() : null;
        $userStatement->close();
        if (!$verifiedUser) {
            throw new DomainException('Your account could not be verified. Please log in again.');
        }

        $lockedHuisId = (int) $huisId;
        $accommodationStatement = $conn->prepare('SELECT `Huis_id`, `Huis_naam`, `PPN`, `Max` FROM `accomodaties` WHERE `Huis_id` = ? LIMIT 1 FOR UPDATE');
        if (!$accommodationStatement) {
            throw new RuntimeException('Accommodation lookup failed.');
        }
        $accommodationStatement->bind_param('i', $lockedHuisId);
        if (!$accommodationStatement->execute()) {
            $accommodationStatement->close();
            throw new RuntimeException('Accommodation lookup failed.');
        }
        $accommodationResult = $accommodationStatement->get_result();
        $lockedAccommodation = $accommodationResult ? $accommodationResult->fetch_assoc() : null;
        $accommodationStatement->close();
        if (!$lockedAccommodation) {
            throw new DomainException('The selected accommodation no longer exists.');
        }
        if ((int) $people < 1 || (int) $people > (int) $lockedAccommodation['Max']) {
            throw new DomainException('The number of people is no longer valid for this accommodation.');
        }

        $lockedStart = maple_booking_date($startDate);
        $lockedEnd = maple_booking_date($endDate);
        if ($lockedStart === null || $lockedEnd === null || $lockedStart < new DateTimeImmutable('today') || $lockedEnd <= $lockedStart) {
            throw new DomainException('The selected dates are no longer valid.');
        }
        $lockedNights = (int) $lockedStart->diff($lockedEnd)->days;
        $available = maple_booking_is_available($conn, $lockedHuisId, $startDate, $endDate, true);
        if ($available !== true) {
            throw new DomainException($available === false ? 'The accommodation became unavailable for the selected dates.' : 'Availability could not be verified. Please try again later.');
        }

        $paidStatusId = maple_booking_unique_status_id($conn, ['paid', 'betaald']);
        if ($paidStatusId === null) {
            throw new RuntimeException('A unique Paid status is unavailable.');
        }

        $authoritativeTotal = round($lockedNights * (float) $lockedAccommodation['PPN'], 2);
        $insertStatement = $conn->prepare(
            'INSERT INTO `reservaties` (`User_id`, `Huis_id`, `Aan_date`, `Out_date`, `Total_prijs`, `Status_id`, `reservation`)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        if (!$insertStatement) {
            throw new RuntimeException('Reservation insert failed.');
        }
        $insertStatement->bind_param('iissdi', $userId, $lockedHuisId, $startDate, $endDate, $authoritativeTotal, $paidStatusId);
        if (!$insertStatement->execute() || $insertStatement->affected_rows !== 1) {
            $insertStatement->close();
            throw new RuntimeException('Reservation insert failed.');
        }
        $insertStatement->close();

        if (!$conn->commit()) {
            throw new RuntimeException('Reservation commit failed.');
        }
        unset($_SESSION['pending_booking']);
        $_SESSION['my_vacation_notice'] = ['type' => 'success', 'message' => 'Your payment confirmation was accepted and your reservation is now paid.'];
        header('Location: MyVacation.php');
        exit;
    } catch (DomainException $exception) {
        $conn->rollback();
        $_SESSION['booking_notice'] = $exception->getMessage();
    } catch (Throwable $exception) {
        $conn->rollback();
        $_SESSION['booking_notice'] = 'Your paid reservation could not be created. Please try again later.';
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
    <?php include __DIR__ . '/../Includes/LanguagesScripts.php'; ?>
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
        <p>Payment confirms this reservation immediately. Availability, capacity and the total price are checked again when you continue.</p>
        <form method="post" action="Book-Resi.php">
            <input type="hidden" name="booking_action" value="pay">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars((string) $_SESSION['booking_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="submission_token" value="<?= htmlspecialchars($submissionToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="booking-summary__button booking-summary__button--pay" type="submit">BETALEN</button>
        </form>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../Includes/Footer.php'; ?>
</body>
</html>
