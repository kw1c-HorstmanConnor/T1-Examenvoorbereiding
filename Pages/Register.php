<?php
require_once __DIR__ . '/../Includes/Register.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'register';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$registerErrors = [];
$formValues = maple_register_form_values();
$phoneCountryOptions = maple_phone_country_options();

if ($requestMethod === 'POST') {
    $registerResult = maple_handle_registration($_POST);
    $registerErrors = $registerResult['errors'];
    $formValues = $registerResult['values'];

    if ($registerResult['success']) {
        header('Location: Login.php');
        exit;
    }
}

$csrfToken = maple_register_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <script src="../Functions/auth.js" defer></script>
</head>
<body class="login-view register-view">
    <section class="login-hero">
        <?php include __DIR__ . '/../Includes/Header.php'; ?>

        <main class="login-main register-main page-container">
            <form class="login-card register-card" method="post" action="Register.php" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <p class="section-label section-label--light">REGISTER</p>
                <h1>Create your account</h1>
                <p class="login-card__copy">Join Maple Camp and start your next adventure.</p>

                <?php if ($registerErrors !== []): ?>
                    <div class="login-error" role="alert">
                        <?php foreach ($registerErrors as $error): ?>
                            <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <label class="login-field">
                    <span>First name</span>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input type="text" name="voornaam" autocomplete="given-name" maxlength="255" placeholder="Enter your first name" required value="<?= htmlspecialchars($formValues['voornaam'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </label>

                <label class="login-field">
                    <span>Last name</span>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input type="text" name="achternaam" autocomplete="family-name" maxlength="255" placeholder="Enter your last name" required value="<?= htmlspecialchars($formValues['achternaam'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </label>

                <label class="login-field">
                    <span>Email</span>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <input type="email" name="email" autocomplete="email" maxlength="255" placeholder="Enter your email address" required value="<?= htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </label>

                <label class="login-field">
                    <span>Phone number</span>
                    <div class="auth-input-wrap auth-phone-wrap">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                        <select name="phone_country_code" aria-label="Country code">
                            <?php foreach ($phoneCountryOptions as $countryCode => $countryName): ?>
                                <option value="<?= htmlspecialchars($countryCode, ENT_QUOTES, 'UTF-8'); ?>"<?= $formValues['phone_country_code'] === $countryCode ? ' selected' : ''; ?>>
                                    <?= htmlspecialchars($countryCode . ' ' . $countryName, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" name="telefoonnummer" autocomplete="tel" maxlength="24" placeholder="Enter your phone number" value="<?= htmlspecialchars($formValues['telefoonnummer'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </label>

                <label class="login-field">
                    <span>Password</span>
                    <div class="auth-input-wrap auth-input-wrap--password">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input id="register-password" type="password" name="password" autocomplete="new-password" placeholder="Create a password" required>
                        <button class="auth-password-toggle" type="button" aria-label="Show password" aria-controls="register-password" data-password-toggle>
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </label>

                <label class="login-field">
                    <span>Confirm password</span>
                    <div class="auth-input-wrap auth-input-wrap--password">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input id="register-confirm-password" type="password" name="confirm_password" autocomplete="new-password" placeholder="Repeat your password" required>
                        <button class="auth-password-toggle" type="button" aria-label="Show password" aria-controls="register-confirm-password" data-password-toggle>
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </label>

                <button class="login-submit register-submit" type="submit">
                    <span>CREATE ACCOUNT</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>

                <p class="login-create-account register-login-link">
                    <span>Already have an account?</span>
                    <a href="Login.php">Log in</a>
                </p>
            </form>
        </main>
    </section>
</body>
</html>
