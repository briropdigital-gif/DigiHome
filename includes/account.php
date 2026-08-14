<?php
require_once __DIR__ . '/db.php';

$pageTitle = 'DigiHome | Account Access';
$pageDescription = 'Select login or registration and continue through the correct role account flow.';
$mode = strtolower((string) ($_GET['mode'] ?? 'login'));
$activeTab = $mode === 'register' ? 'register' : 'login';
$rememberedAccounts = remembered_accounts();
$rememberedByRole = remembered_accounts_by_role();
$activeUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'forget_account') {
    $forgetRole = canonical_role((string) ($_POST['role'] ?? ''));
    $forgetId = (int) ($_POST['account_id'] ?? 0);
    if ($forgetId > 0) {
        forget_remembered_account($forgetId, $forgetRole);
        add_flash('success', 'Remembered account removed from this device.');
    }
    header('Location: ' . account_hub_path($activeTab));
    exit;
}

$returnTo = (string) ($_GET['return'] ?? ($_SESSION['account_return_to'] ?? '/DigiHome/seeker/home.php'));
if (isset($_GET['return']) && str_starts_with($returnTo, '/DigiHome/')) {
    $_SESSION['account_return_to'] = $returnTo;
} elseif (!isset($_GET['return'])) {
    $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $parts = parse_url($referrer);
    $refPath = (string) ($parts['path'] ?? '');
    if ($refPath !== '' && str_starts_with($refPath, '/DigiHome/') && !str_contains($refPath, '/includes/account.php')) {
        $refQuery = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $returnTo = $refPath . $refQuery;
        $_SESSION['account_return_to'] = $returnTo;
    }
}

if (!str_starts_with($returnTo, '/DigiHome/')) {
    $returnTo = '/DigiHome/seeker/home.php';
}

$logoutAccountHref = null;
if ($activeUser) {
    $logoutAccountHref = logout_path() . '?scope=current&role=' . urlencode(canonical_role((string) ($activeUser['role'] ?? 'property_seeker')));
}

$loginRoles = get_public_roles();
$registerRoles = get_public_roles(false);
$registerDescriptions = [
    'property_seeker' => 'Browse listings and unlock trusted property details.',
    'property_owner' => 'Publish listings and monitor verification status.',
    'marketer' => 'Manage owners and grow listing visibility.',
];

$flashMap = [
    'success' => 'alert-success',
    'danger' => 'alert-danger',
    'warning' => 'alert-warning',
    'info' => 'alert-info',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/DigiHome/assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-main">
        <div class="auth-shell">
            <a class="account-close" href="<?= htmlspecialchars($returnTo) ?>">
                <span aria-hidden="true">&times;</span>
                <span class="sr-only">Go back</span>
            </a>
            <div class="auth-brand" data-reveal>
                <img src="/DigiHome/assets/img/system/logo.png" alt="DigiHome logo">
                <div>
                    <h1>DigiHome</h1>
                    <p>Secure role-based account access</p>
                </div>
            </div>

            <?php foreach (get_flashes() as $flash): ?>
                <?php $flashClass = $flashMap[$flash['type']] ?? 'alert-info'; ?>
                <div class="alert <?= htmlspecialchars($flashClass) ?>" data-alert>
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endforeach; ?>

            <section class="section-shell auth-card" data-reveal>
                <div class="section-head account-tabs-head">
                    <div>
                        <h2>Account Access</h2>
                        <p>Use Login or Register to continue through your role card.</p>
                    </div>
                    <div class="tabs-switch">
                        <button type="button" class="ghost-button tab-button <?= $activeTab === 'login' ? 'is-active' : '' ?>" data-tab-trigger="login" aria-pressed="<?= $activeTab === 'login' ? 'true' : 'false' ?>">Login</button>
                        <button type="button" class="ghost-button tab-button <?= $activeTab === 'register' ? 'is-active' : '' ?>" data-tab-trigger="register" aria-pressed="<?= $activeTab === 'register' ? 'true' : 'false' ?>">Register</button>
                        <?php if ($logoutAccountHref): ?>
                            <a class="ghost-button" href="<?= htmlspecialchars($logoutAccountHref) ?>" data-confirm-logout="true">Logout</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tabs-content">
                    <div class="tab-pane <?= $activeTab === 'login' ? 'is-active' : '' ?>" data-tab-pane="login">
                        <div class="role-grid">
                            <?php foreach ($loginRoles as $roleKey => $role): ?>
                                <?php $roleAccounts = $rememberedByRole[$roleKey] ?? []; ?>
                                <article class="account-card role-card">
                                    <span class="badge"><?= htmlspecialchars($role['label']) ?></span>
                                    <?php if ($roleAccounts !== []): ?>
                                        <div class="remembered-carousel-shell">
                                            <div class="remembered-carousel" data-account-carousel>
                                                <?php foreach ($roleAccounts as $index => $remembered): ?>
                                                    <div class="remembered-slide <?= $index === 0 ? 'is-active' : '' ?>" data-account-slide>
                                                        <img src="<?= htmlspecialchars($remembered['profile_picture']) ?>" alt="<?= htmlspecialchars($remembered['name']) ?>">
                                                        <h3><?= htmlspecialchars($remembered['name']) ?></h3>
                                                        <p>@<?= htmlspecialchars($remembered['username']) ?></p>
                                                        <p>Remembered on this device.</p>
                                                        <p class="account-counter account-counter-inline" data-account-counter><?= ($index + 1) ?>/<?= count($roleAccounts) ?></p>
                                                        <div class="remembered-actions">
                                                            <a class="primary-button" href="/DigiHome/includes/switch-account.php?role=<?= urlencode($roleKey) ?>&account=<?= (int) $remembered['id'] ?>&return=<?= urlencode($returnTo) ?>">Continue</a>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="forget_account">
                                                                <input type="hidden" name="role" value="<?= htmlspecialchars($roleKey) ?>">
                                                                <input type="hidden" name="account_id" value="<?= (int) $remembered['id'] ?>">
                                                                <button class="ghost-button" type="submit">Forget</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="account-carousel-nav">
                                                <button type="button" class="ghost-button" data-account-prev aria-label="Previous account">&lt;</button>
                                                <button type="button" class="ghost-button" data-account-next aria-label="Next account">&gt;</button>
                                            </div>
                                        </div>
                                        <div class="account-carousel-controls">
                                            <a class="ghost-button" href="<?= htmlspecialchars(role_login_path($roleKey)) ?>">Login another account</a>
                                        </div>
                                    <?php else: ?>
                                        <h3><?= htmlspecialchars($role['label']) ?></h3>
                                        <p><?= htmlspecialchars($role['description']) ?></p>
                                        <a class="primary-button" href="<?= htmlspecialchars(role_login_path($roleKey)) ?>">Login</a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tab-pane <?= $activeTab === 'register' ? 'is-active' : '' ?>" data-tab-pane="register">
                        <div class="role-grid">
                            <?php foreach ($registerRoles as $roleKey => $role): ?>
                                <article class="info-card role-card">
                                    <span class="badge"><?= htmlspecialchars($role['label']) ?></span>
                                    <h3><?= htmlspecialchars($role['label']) ?></h3>
                                    <p><?= htmlspecialchars($registerDescriptions[$roleKey] ?? $role['description']) ?></p>
                                    <a class="primary-button" href="<?= htmlspecialchars(role_register_path($roleKey)) ?>">Register</a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <p class="auth-note"><strong>Admin accounts are login-only.</strong> They are created internally and cannot self-register.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="/DigiHome/assets/js/app.js"></script>
</body>
</html>
