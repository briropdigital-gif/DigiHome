<?php
require_once dirname(__DIR__) . '/includes/db.php';

$selectedRole = 'marketer';
$role = role_config($selectedRole);
$rememberedAccounts = remembered_accounts();
$rememberedId = (int) ($_GET['remembered'] ?? 0);
$rememberedAccount = null;
foreach ($rememberedAccounts as $account) {
    if ((int) ($account['id'] ?? 0) === $rememberedId && canonical_role($account['role']) === $selectedRole) {
        $rememberedAccount = $account;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        add_flash('danger', 'Please enter a valid email address.');
        header('Location: ' . role_login_path($selectedRole));
        exit;
    }

    $user = authenticate_user($email, $password);

    if (!$user) {
        add_flash('danger', 'Invalid credentials or the account is not active.');
    } elseif (canonical_role($user['role']) !== $selectedRole) {
        add_flash('danger', 'This account does not belong to the selected role.');
    } else {
        $user = login_user($user);
        add_flash('success', 'Welcome back, ' . $user['name'] . '.');
        header('Location: ' . role_dashboard_path($user['role']));
        exit;
    }
}

$flashMap = [
    'success' => 'alert-success',
    'danger' => 'alert-danger',
    'warning' => 'alert-warning',
    'info' => 'alert-info',
];

$pageTitle = 'DigiHome | Marketer Sign In';
$pageDescription = 'Sign in to DigiHome as a Marketer.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/DigiHome/assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-main">
        <div class="auth-shell">
            <a class="account-close" href="<?= htmlspecialchars(account_hub_path('login')) ?>" onclick="if (window.history.length > 1) { window.history.back(); return false; }">
                <span aria-hidden="true">&times;</span>
                <span class="sr-only">Go back</span>
            </a>
            <div class="auth-brand" data-reveal>
                <img src="/DigiHome/assets/img/system/logo.png" alt="DigiHome logo">
                <div>
                    <h1>DigiHome</h1>
                    <p><?= htmlspecialchars($role['label']) ?> login</p>
                </div>
            </div>

            <?php foreach (get_flashes() as $flash): ?>
                <?php $flashClass = $flashMap[$flash['type']] ?? 'alert-info'; ?>
                <div class="alert <?= htmlspecialchars($flashClass) ?>" data-alert>
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endforeach; ?>

            <section class="hero auth-hero" data-reveal>
                <div class="hero-grid">
                    <div>
                        <span class="badge badge-success">Role login</span>
                        <h2><?= htmlspecialchars($role['label']) ?> Sign In</h2>
                        <p><?= htmlspecialchars($role['description']) ?></p>
                    </div>
                    <div class="hero-stat-grid">
                        <div class="hero-stat"><strong>Secure</strong><span>Role-specific access</span></div>
                        <div class="hero-stat"><strong>Remembered</strong><span>Device account support</span></div>
                    </div>
                </div>
            </section>

            <section class="form auth-card" data-reveal>
                <form method="post" class="form-grid">
                    <label class="field-card full-span">
                        <span>Email address</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($rememberedAccount['email'] ?? '') ?>" placeholder="Enter email address" required>
                    </label>
                    <label class="field-card full-span">
                        <span>Password</span>
                        <div class="password-field">
                            <input id="marketer-login-password" type="password" name="password" placeholder="Enter password" required>
                            <button type="button" class="ghost-button password-toggle" data-password-toggle="marketer-login-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </label>
                    <div class="inline-actions full-span">
                        <button type="submit">Login to <?= htmlspecialchars($role['label']) ?></button>
                        <a class="ghost-button" href="<?= htmlspecialchars(account_hub_path('login')) ?>">Back to account selection</a>
                        <a class="ghost-button" href="<?= htmlspecialchars($role['register']) ?>">Create account</a>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script src="/DigiHome/assets/js/app.js"></script>
</body>
</html>
