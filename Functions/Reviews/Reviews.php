<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Database.php';

function maple_review_form_values(): array
{
    return [
        'rating' => '',
        'ervaring_datum' => '',
        'titel' => '',
        'omschrijving' => '',
    ];
}

function maple_review_csrf_token(): string
{
    maple_start_session();

    if (empty($_SESSION['review_csrf'])) {
        $_SESSION['review_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['review_csrf'];
}

function maple_review_success_message(): string
{
    maple_start_session();

    if (empty($_SESSION['review_success'])) {
        return '';
    }

    $message = (string) $_SESSION['review_success'];
    unset($_SESSION['review_success']);

    return $message;
}

function maple_review_text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

function maple_review_valid_date(string $value): bool
{
    if ($value === '') {
        return true;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    return $date !== false
        && !($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        && $date->format('Y-m-d') === $value;
}

function maple_review_user_exists(PDO $pdo, int $userId): bool
{
    $statement = $pdo->prepare('SELECT `User_id` FROM `User` WHERE `User_id` = :user_id LIMIT 1');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchColumn() !== false;
}

function maple_handle_review_submission(array $post): array
{
    $values = maple_review_form_values();
    $values['rating'] = trim((string) ($post['rating'] ?? ''));
    $values['ervaring_datum'] = trim((string) ($post['ervaring_datum'] ?? ''));
    $values['titel'] = trim((string) ($post['titel'] ?? ''));
    $values['omschrijving'] = trim((string) ($post['omschrijving'] ?? ''));

    $errors = [];
    $currentUser = maple_current_user();
    $csrf = (string) ($post['csrf'] ?? '');

    if ($currentUser === null || (int) $currentUser['user_id'] <= 0) {
        $errors[] = 'Sign in to leave a review.';
    }

    if (!hash_equals(maple_review_csrf_token(), $csrf)) {
        $errors[] = 'Your session has expired. Please try again.';
    }

    $rating = filter_var(
        $values['rating'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 5]]
    );

    if ($rating === false) {
        $errors[] = 'Please choose a rating from 1 to 5 stars.';
    }

    if ($values['titel'] === '') {
        $errors[] = 'Please give your review a title.';
    } elseif (maple_review_text_length($values['titel']) > 150) {
        $errors[] = 'Review title may not be longer than 150 characters.';
    }

    $descriptionLength = maple_review_text_length($values['omschrijving']);
    if ($values['omschrijving'] === '') {
        $errors[] = 'Please write a review.';
    } elseif ($descriptionLength < 25) {
        $errors[] = 'Review text must contain at least 25 characters.';
    } elseif ($descriptionLength > 1500) {
        $errors[] = 'Review text may not be longer than 1500 characters.';
    }

    if (!maple_review_valid_date($values['ervaring_datum'])) {
        $errors[] = 'Please enter a valid visit date.';
    }

    if ($errors !== []) {
        return [
            'success' => false,
            'errors' => $errors,
            'values' => $values,
        ];
    }

    try {
        $pdo = maple_pdo();
        $userId = (int) $currentUser['user_id'];

        if (!maple_review_user_exists($pdo, $userId)) {
            return [
                'success' => false,
                'errors' => ['Your account could not be verified. Please sign in again.'],
                'values' => $values,
            ];
        }

        $statement = $pdo->prepare(
            'INSERT INTO `Reviews`
                (`User_id`, `Rating`, `Titel`, `Omschrijving`, `Ervaring_datum`)
             VALUES
                (:user_id, :rating, :titel, :omschrijving, :ervaring_datum)'
        );

        $statement->execute([
            'user_id' => $userId,
            'rating' => (int) $rating,
            'titel' => $values['titel'],
            'omschrijving' => $values['omschrijving'],
            'ervaring_datum' => $values['ervaring_datum'] !== '' ? $values['ervaring_datum'] : null,
        ]);

        unset($_SESSION['review_csrf']);
        $_SESSION['review_success'] = 'Thank you! Your review has been submitted.';

        return [
            'success' => true,
            'errors' => [],
            'values' => maple_review_form_values(),
        ];
    } catch (Throwable $exception) {
        error_log('Review submission failed: ' . $exception->getMessage());

        return [
            'success' => false,
            'errors' => ['Something went wrong while submitting your review. Please try again later.'],
            'values' => $values,
        ];
    }
}

function maple_reviews_summary(): array
{
    $summary = [
        'average' => 0.0,
        'count' => 0,
        'distribution' => [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ],
    ];

    try {
        $pdo = maple_pdo();
        $summaryStatement = $pdo->prepare(
            'SELECT AVG(`Rating`) AS average_rating, COUNT(*) AS review_count
             FROM `Reviews`
             WHERE `Gepubliceerd` = 1'
        );
        $summaryStatement->execute();
        $summaryRow = $summaryStatement->fetch();

        if (is_array($summaryRow)) {
            $summary['average'] = $summaryRow['average_rating'] !== null ? (float) $summaryRow['average_rating'] : 0.0;
            $summary['count'] = (int) $summaryRow['review_count'];
        }

        $distributionStatement = $pdo->prepare(
            'SELECT `Rating`, COUNT(*) AS rating_count
             FROM `Reviews`
             WHERE `Gepubliceerd` = 1
             GROUP BY `Rating`'
        );
        $distributionStatement->execute();

        foreach ($distributionStatement->fetchAll() as $row) {
            $rating = (int) $row['Rating'];

            if ($rating >= 1 && $rating <= 5) {
                $summary['distribution'][$rating] = (int) $row['rating_count'];
            }
        }
    } catch (Throwable $exception) {
        error_log('Review summary failed: ' . $exception->getMessage());
    }

    return $summary;
}

function maple_published_reviews(): array
{
    try {
        $pdo = maple_pdo();
        $statement = $pdo->prepare(
            'SELECT
                r.`Review_id`,
                r.`Rating`,
                r.`Titel`,
                r.`Omschrijving`,
                r.`Ervaring_datum`,
                r.`Aangemaakt`,
                u.`Voornaam`,
                u.`Achternaam`
             FROM `Reviews` AS r
             INNER JOIN `User` AS u ON r.`User_id` = u.`User_id`
             WHERE r.`Gepubliceerd` = 1
             ORDER BY r.`Aangemaakt` DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    } catch (Throwable $exception) {
        error_log('Published reviews lookup failed: ' . $exception->getMessage());

        return [];
    }
}

function maple_review_display_name(array $review): string
{
    $firstName = trim((string) ($review['Voornaam'] ?? ''));
    $lastName = trim((string) ($review['Achternaam'] ?? ''));

    if ($firstName === '' && $lastName === '') {
        return 'Guest';
    }

    if ($lastName === '') {
        return $firstName;
    }

    $initial = function_exists('mb_substr')
        ? mb_substr($lastName, 0, 1, 'UTF-8')
        : substr($lastName, 0, 1);

    return trim($firstName . ' ' . strtoupper($initial) . '.');
}

function maple_review_stars_html(int $rating): string
{
    $rating = max(1, min(5, $rating));
    $stars = '';

    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '&#9733;' : '&#9734;';
    }

    return $stars;
}

function maple_review_format_date($value): string
{
    if ($value === null || trim((string) $value) === '') {
        return '';
    }

    try {
        $date = new DateTimeImmutable((string) $value);
    } catch (Throwable $exception) {
        return '';
    }

    $months = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}
