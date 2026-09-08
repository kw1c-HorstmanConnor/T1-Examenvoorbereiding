<?php
require_once __DIR__ . '/../Includes/Login.php';

$basePath = '../';
$assetBase = '../';
$currentPage = 'login';
$loginError = '';
$loginNotice = '';
$username = '';
$email = '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

if (!empty($_SESSION['login_notice'])) {
    $loginNotice = (string) $_SESSION['login_notice'];
    unset($_SESSION['login_notice']);
} elseif (($_GET['registered'] ?? '') === '1') {
    $loginNotice = 'Account created successfully. You can now log in.';
}

if ($requestMethod === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrf = (string) ($_POST['csrf'] ?? '');

    if (!hash_equals($_SESSION['login_csrf'], $csrf)) {
        $loginError = 'De sessie is verlopen. Probeer opnieuw.';
    } elseif (maple_authenticate_user($email, $password, $username !== '' ? $username : null)) {
        require_once __DIR__ . '/../Includes/Admin.php';

        if (maple_user_is_admin()) {
            header('Location: Admin.php');
        } else {
            header('Location: ../Index.php?view=home');
        }
        exit;
    } else {
        $loginError = 'Controleer je naam, e-mailadres en wachtwoord.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Maple Camp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styling/index.css">
    <script src="../Functions/auth.js" defer></script>
</head>
<body class="login-view">
    <section class="login-hero">
        <?php include __DIR__ . '/../Includes/Header.php'; ?>

        <main class="login-main page-container">
            <form class="login-card" method="post" action="Login.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

                <p class="section-label section-label--light">LOGIN</p>
                <h1>Welcome back</h1>
                <p class="login-card__copy">Log in with your email address and password. First name is checked only when you enter it.</p>

                <?php if ($loginError !== ''): ?>
                    <p class="login-error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if ($loginNotice !== ''): ?>
                    <p class="login-notice" role="status"><?= htmlspecialchars($loginNotice, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label class="login-field">
                    <span>First name</span>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input type="text" name="username" autocomplete="given-name" maxlength="255" placeholder="Enter your first name" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </label>

                <label class="login-field">
                    <span>Email</span>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <input type="email" name="email" autocomplete="email" maxlength="255" placeholder="Enter your email address" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </label>

                <label class="login-field">
                    <span>Password</span>
                    <div class="auth-input-wrap auth-input-wrap--password">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <button class="auth-password-toggle" type="button" aria-label="Show password" aria-controls="login-password" data-password-toggle>
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </label>

                <button class="login-submit" type="submit">
                    <span>LOG IN</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>

                <div class="login-or-divider" aria-hidden="true">
                    <span></span>
                    <strong>OR</strong>
                    <span></span>
                </div>

                <p class="login-create-account">
                    <span>Don't have an account?</span>
                    <a href="Register.php">Create one</a>
                </p>

                <div class="login-lower-divider" aria-hidden="true"></div>
            </form>
        </main>
    </section>
</body>
</html>
