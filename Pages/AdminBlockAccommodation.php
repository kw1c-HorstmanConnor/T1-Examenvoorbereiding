<?php
declare(strict_types=1);

require_once __DIR__ . '/../Functions/Auth/Authorization.php';
require_once __DIR__ . '/../Includes/Database.php';

maple_require_admin('Login.php');

function maple_block_redirect_target(string $returnTo): string
{
    if ($returnTo === 'home') {
        return '../Index.php?view=home#accommodaties';
    }
    if ($returnTo === 'accommodations') {
        return 'Accomodatie.php#overview-heading';
    }

    return 'AdminPanel.php?tab=accommodations#accommodation-blocking';
}

function maple_block_redirect(string $returnTo, string $type, string $message)
{
    $_SESSION['block_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
    header('Location: ' . maple_block_redirect_target($returnTo));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$returnTo = (string) ($_POST['return_to'] ?? 'admin');
if (!in_array($returnTo, ['admin', 'accommodations', 'home'], true)) {
    $returnTo = 'admin';
}

$csrf = (string) ($_POST['csrf'] ?? '');
$sessionCsrf = (string) ($_SESSION['admin_csrf'] ?? '');
$submissionToken = (string) ($_POST['submission_token'] ?? '');
$sessionSubmissionToken = (string) ($_SESSION['block_submission_token'] ?? '');

if ($sessionCsrf === '' || !hash_equals($sessionCsrf, $csrf)) {
    maple_block_redirect($returnTo, 'error', 'Your session has expired. Please try again.');
}

if ($sessionSubmissionToken === '' || !hash_equals($sessionSubmissionToken, $submissionToken)) {
    maple_block_redirect($returnTo, 'error', 'This block request has expired or was already submitted.');
}

unset($_SESSION['block_submission_token']);

$huisId = filter_var(
    $_POST['huis_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$startDate = trim((string) ($_POST['start_date'] ?? ''));
$endDate = trim((string) ($_POST['end_date'] ?? ''));
$reason = trim((string) ($_POST['reason'] ?? ''));
$start = maple_booking_date($startDate);
$end = maple_booking_date($endDate);

if ($huisId === false || $huisId === null) {
    maple_block_redirect($returnTo, 'error', 'Please select a valid accommodation.');
}
if ($start === null) {
    maple_block_redirect($returnTo, 'error', 'Please enter a valid start date.');
}
if ($start < new DateTimeImmutable('today')) {
    maple_block_redirect($returnTo, 'error', 'The start date cannot be in the past.');
}
if ($end === null) {
    maple_block_redirect($returnTo, 'error', 'Please enter a valid end date.');
}
if ($end <= $start) {
    maple_block_redirect($returnTo, 'error', 'The end date must be later than the start date.');
}
if (mb_strlen($reason) > 255) {
    maple_block_redirect($returnTo, 'error', 'The reason must be 255 characters or fewer.');
}

$transactionStarted = false;

try {
    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Transaction could not be started.');
    }
    $transactionStarted = true;

    $lockedHuisId = (int) $huisId;
    $houseStatement = $conn->prepare(
        'SELECT `Huis_id`, `Huis_naam`
         FROM `accomodaties`
         WHERE `Huis_id` = ?
         LIMIT 1
         FOR UPDATE'
    );
    if (!$houseStatement) {
        throw new RuntimeException('Accommodation lookup failed.');
    }
    $houseStatement->bind_param('i', $lockedHuisId);
    if (!$houseStatement->execute()) {
        $houseStatement->close();
        throw new RuntimeException('Accommodation lookup failed.');
    }
    $houseResult = $houseStatement->get_result();
    $house = $houseResult ? $houseResult->fetch_assoc() : null;
    $houseStatement->close();

    if (!is_array($house)) {
        throw new DomainException('The selected accommodation no longer exists.');
    }

    $databaseStart = $start->format('Y-m-d 00:00:00');
    $databaseEnd = $end->format('Y-m-d 00:00:00');
    $hasBlock = maple_booking_has_block_overlap($conn, $lockedHuisId, $databaseStart, $databaseEnd, true);
    if ($hasBlock === null) {
        throw new RuntimeException('Blocked-period lookup failed.');
    }
    if ($hasBlock) {
        throw new DomainException('This accommodation is already blocked during part of the selected period.');
    }

    $hasReservation = maple_booking_has_active_reservation_overlap(
        $conn,
        $lockedHuisId,
        $databaseStart,
        $databaseEnd,
        true
    );
    if ($hasReservation === null) {
        throw new RuntimeException('Reservation lookup failed.');
    }
    if ($hasReservation) {
        throw new DomainException('This accommodation already has a reservation during the selected period.');
    }

    $reasonValue = $reason === '' ? null : $reason;
    $insertStatement = $conn->prepare(
        'INSERT INTO `blocked` (`StartDate`, `EindDate`, `Reden`, `Huis_id`)
         VALUES (?, ?, ?, ?)'
    );
    if (!$insertStatement) {
        throw new RuntimeException('Block insert failed.');
    }
    $insertStatement->bind_param('sssi', $databaseStart, $databaseEnd, $reasonValue, $lockedHuisId);
    if (!$insertStatement->execute() || $insertStatement->affected_rows !== 1) {
        $insertStatement->close();
        throw new RuntimeException('Block insert failed.');
    }
    $insertStatement->close();

    if (!$conn->commit()) {
        throw new RuntimeException('Block commit failed.');
    }
    $transactionStarted = false;

    $houseName = (string) $house['Huis_naam'];
    $message = sprintf(
        '%s has been blocked from %s until %s.',
        $houseName,
        $start->format('j F Y'),
        $end->format('j F Y')
    );
    maple_block_redirect($returnTo, 'success', $message);
} catch (DomainException $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    maple_block_redirect($returnTo, 'error', $exception->getMessage());
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    error_log('Accommodation block failed: ' . $exception->getMessage());
    maple_block_redirect($returnTo, 'error', 'The accommodation could not be blocked. Please try again later.');
}
