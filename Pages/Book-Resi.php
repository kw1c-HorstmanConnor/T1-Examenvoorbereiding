<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Helpers/Session.php';
require_once __DIR__ . '/../Includes/DataBase.php';

$basePath = '../'; $assetBase = '../'; $currentPage = 'accommodaties';
$bookingError = ''; $booking = $_SESSION['pending_booking'] ?? null; $accommodation = null; $nights = 0; $total = 0.0;
if (!is_array($booking)) {
    $bookingError = 'There is no pending booking to display.';
} elseif (empty($_SESSION['user_id'])) {
    $_SESSION['booking_login_redirect'] = 'Book-Resi.php'; header('Location: Login.php'); exit;
} else {
    $huisId = filter_var($booking['huis_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $people = filter_var($booking['people'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $startDate = trim((string) ($booking['start_date'] ?? '')); $endDate = trim((string) ($booking['end_date'] ?? ''));
    $start = maple_booking_date($startDate); $end = maple_booking_date($endDate);
    if ($huisId === false || $huisId === null || $people === false || $people === null || $start === null || $end === null || $start < new DateTimeImmutable('today') || $end <= $start) {
        $bookingError = 'Your pending booking is invalid. Please select an accommodation again.'; unset($_SESSION['pending_booking']);
    } else {
        $accommodation = maple_booking_accommodation($conn, (int) $huisId);
        if ($accommodation === null) {
            $bookingError = 'The selected accommodation no longer exists.'; unset($_SESSION['pending_booking']);
        } elseif ($people > (int) $accommodation['Max']) {
            $bookingError = 'The number of people exceeds this accommodation\'s maximum occupancy.'; unset($_SESSION['pending_booking']);
        } elseif (maple_booking_is_available($conn, (int) $huisId, $startDate, $endDate) !== true) {
            $bookingError = 'The selected accommodation is no longer available for these dates.'; unset($_SESSION['pending_booking']);
        } else {
            $nights = (int) $start->diff($end)->days; $total = $nights * (float) $accommodation['PPN']; unset($_SESSION['booking_login_redirect']);
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Booking summary - Maple Camp</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="../Styling/index.css"><link rel="stylesheet" href="../Styling/Booking.css"></head>
<body class="booking-page"><?php include __DIR__ . '/../Includes/Header.php'; ?><main class="booking-summary page-container"><p class="section-label">YOUR BOOKING</p><h1>Booking summary</h1>
<?php if ($bookingError !== ''): ?><p class="booking-summary__error" role="alert"><?= htmlspecialchars($bookingError, ENT_QUOTES, 'UTF-8'); ?></p><a class="booking-summary__button" href="Accomodatie.php">Back to accommodations</a>
<?php else: ?><dl class="booking-summary__details"><div><dt>Accommodation</dt><dd><?= htmlspecialchars((string) $accommodation['Huis_naam'], ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Arrival</dt><dd><?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Departure</dt><dd><?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Nights</dt><dd><?= $nights; ?></dd></div><div><dt>People</dt><dd><?= $people; ?></dd></div><div><dt>Price per night</dt><dd>&euro; <?= number_format((float) $accommodation['PPN'], 2, '.', ''); ?></dd></div><div class="booking-summary__total"><dt>Total price</dt><dd>&euro; <?= number_format($total, 2, '.', ''); ?></dd></div></dl><!-- Payment functionality will be implemented in a later booking step. --><button class="booking-summary__button" type="button">PAY</button><?php endif; ?></main><?php include __DIR__ . '/../Includes/Footer.php'; ?></body></html>
