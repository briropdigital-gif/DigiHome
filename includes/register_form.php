<?php
require_once __DIR__ . '/db.php';

$selectedRole = canonical_role($selectedRole ?? 'property_seeker');
if (!is_registerable_role($selectedRole)) {
    add_flash('danger', 'The selected role is not available for self-registration.');
    header('Location: ' . account_hub_path('register'));
    exit;
}

$role = role_config($selectedRole);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedProfile = store_profile_image_upload('profile_picture_file', '', $selectedRole);
    $payload = [
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'address_line' => trim((string) ($_POST['address_line'] ?? '')),
        'password' => (string) ($_POST['password'] ?? ''),
        'confirm_password' => (string) ($_POST['confirm_password'] ?? ''),
        'profile_picture' => $uploadedProfile,
        'county' => trim((string) ($_POST['county'] ?? '')),
        'sub_county' => trim((string) ($_POST['sub_county'] ?? '')),
        'ward' => trim((string) ($_POST['ward'] ?? '')),
        'town' => trim((string) ($_POST['town'] ?? '')),
        'role' => $selectedRole,
    ];

    $errors = validate_registration_data($payload);
    if ($errors !== []) {
        foreach ($errors as $error) {
            add_flash('danger', $error);
        }
    } else {
        $user = create_user($payload);
        if ($user) {
            login_user($user);
            create_notification((int) $user['id'], 'account_created', 'Account created', 'Your DigiHome account is ready.');
            audit_log((int) $user['id'], 'user_created', 'user', (int) $user['id'], 'Self-registration completed');
            add_flash('success', 'Your account has been created successfully.');
            header('Location: ' . role_dashboard_path($user['role']));
            exit;
        }
        add_flash('danger', 'Account creation failed. Please try again.');
    }
}

$flashMap = [
    'success' => 'alert-success',
    'danger' => 'alert-danger',
    'warning' => 'alert-warning',
    'info' => 'alert-info',
];

$pageTitle = $pageTitle ?? 'DigiHome | ' . $role['label'] . ' Registration';
$pageDescription = $pageDescription ?? 'Create a DigiHome account as a ' . $role['label'] . '.';
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
            <a class="account-close" href="<?= htmlspecialchars(account_hub_path('register')) ?>" onclick="if (window.history.length > 1) { window.history.back(); return false; }">
                <span aria-hidden="true">&times;</span>
                <span class="sr-only">Go back</span>
            </a>
            <div class="auth-brand" data-reveal>
                <img src="/DigiHome/assets/img/system/logo.png" alt="DigiHome logo">
                <div>
                    <h1>DigiHome</h1>
                    <p><?= htmlspecialchars($role['label']) ?> registration</p>
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
            <span class="badge badge-success">Registration</span>
            <h2>Create a <?= htmlspecialchars($role['label']) ?> account</h2>
            <p><?= htmlspecialchars($role['description']) ?></p>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat"><strong>Unique</strong><span>Email and phone are validated</span></div>
            <div class="hero-stat"><strong>Complete</strong><span>Profile details are captured upfront</span></div>
        </div>
    </div>
</section>

<section class="form auth-card" data-reveal>
    <form method="post" class="form-grid is-2" enctype="multipart/form-data">
        <label class="field-card full-span profile-upload-card">
            <span>Profile Picture</span>
            <div class="profile-upload-shell">
                <img src="/DigiHome/assets/img/users/avatar-placeholder.svg" alt="Profile preview" class="profile-upload-preview" data-profile-preview>
                <label class="ghost-button" for="profile_picture_file">Choose photo</label>
                <input id="profile_picture_file" type="file" name="profile_picture_file" accept="image/*" data-profile-input>
            </div>
        </label>
        <label class="field-card"><span>First Name</span><input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required></label>
        <label class="field-card"><span>Last Name</span><input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required></label>
        <label class="field-card"><span>Email Address</span><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></label>
        <label class="field-card"><span>Phone Number</span><input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required></label>
        <label class="field-card full-span"><span>Address Line</span><input type="text" name="address_line" value="<?= htmlspecialchars($_POST['address_line'] ?? '') ?>" placeholder="Street, building, or nearest landmark"></label>
        <label class="field-card"><span>Country</span><input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? 'Kenya') ?>" data-location-select="country" required></label>
        <label class="field-card"><span>County</span><input type="text" name="county" value="<?= htmlspecialchars($_POST['county'] ?? '') ?>" data-location-select="county" required></label>
        <label class="field-card"><span>Sub-County</span><input type="text" name="sub_county" value="<?= htmlspecialchars($_POST['sub_county'] ?? '') ?>" data-location-select="sub_county" required></label>
        <label class="field-card"><span>Ward</span><input type="text" name="ward" value="<?= htmlspecialchars($_POST['ward'] ?? '') ?>" data-location-select="ward" required></label>
        <label class="field-card"><span>Town</span><input type="text" name="town" value="<?= htmlspecialchars($_POST['town'] ?? '') ?>" readonly></label>
        <label class="field-card">
            <span>Password</span>
            <div class="password-field">
                <input id="register-password" type="password" name="password" required>
                <button type="button" class="ghost-button password-toggle" data-password-toggle="register-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
        </label>
        <label class="field-card">
            <span>Confirm Password</span>
            <div class="password-field">
                <input id="register-confirm-password" type="password" name="confirm_password" required>
                <button type="button" class="ghost-button password-toggle" data-password-toggle="register-confirm-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
        </label>
        <div class="inline-actions full-span">
            <button type="submit">Create <?= htmlspecialchars($role['label']) ?> Account</button>
            <a class="ghost-button" href="<?= htmlspecialchars(account_hub_path('register')) ?>">Back to account selection</a>
        </div>
    </form>
</section>

        </div>
    </main>

    <script>
        window.DIGIHOME_LOCATION_DATA = <?= json_encode(get_location_hierarchy_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="/DigiHome/assets/js/app.js"></script>
</body>
</html>
