<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to register a property owner.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedProfile = store_profile_image_upload('profile_picture_file', '');
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
        'role' => 'property_owner',
        'created_by_marketer_id' => (int) $user['id'],
    ];

    $errors = validate_registration_data($payload, ['property_owner']);
    if ($errors !== []) {
        foreach ($errors as $error) {
            add_flash('danger', $error);
        }
    } else {
        $createdOwner = create_user($payload);
        if ($createdOwner) {
            remember_device_user($createdOwner);
            add_flash('success', 'Property owner registered successfully.');
            header('Location: /DigiHome/marketer/dashboard.php');
            exit;
        }
        add_flash('danger', 'Failed to register property owner.');
    }
}

$pageTitle = 'DigiHome | Register Property Owner';
$pageDescription = 'Marketers can create property owner accounts that remain linked to them permanently.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="form">
    <h2>Register a property owner</h2>
    <p>This owner will permanently store your marketer ID as the account creator.</p>
    <form method="post" class="form-grid" enctype="multipart/form-data">
        <label class="field-card"><span>First Name</span><input type="text" name="first_name" value="<?= htmlspecialchars((string) ($_POST['first_name'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Last Name</span><input type="text" name="last_name" value="<?= htmlspecialchars((string) ($_POST['last_name'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Email</span><input type="email" name="email" value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Phone</span><input type="text" name="phone" value="<?= htmlspecialchars((string) ($_POST['phone'] ?? '')) ?>" required></label>
        <label class="field-card full-span"><span>Address line</span><input type="text" name="address_line" value="<?= htmlspecialchars((string) ($_POST['address_line'] ?? '')) ?>" placeholder="Street, building, or nearest landmark"></label>
        <label class="field-card"><span>Country</span><input type="text" name="country" value="<?= htmlspecialchars((string) ($_POST['country'] ?? 'Kenya')) ?>" data-location-select="country" required></label>
        <label class="field-card"><span>County</span><input type="text" name="county" value="<?= htmlspecialchars((string) ($_POST['county'] ?? '')) ?>" data-location-select="county" required></label>
        <label class="field-card"><span>Sub-County</span><input type="text" name="sub_county" value="<?= htmlspecialchars((string) ($_POST['sub_county'] ?? '')) ?>" data-location-select="sub_county" required></label>
        <label class="field-card"><span>Ward</span><input type="text" name="ward" value="<?= htmlspecialchars((string) ($_POST['ward'] ?? '')) ?>" data-location-select="ward" required></label>
        <label class="field-card"><span>Town</span><input type="text" name="town" value="<?= htmlspecialchars((string) ($_POST['town'] ?? '')) ?>" readonly></label>
        <label class="field-card"><span>Profile Picture</span><input type="file" name="profile_picture_file" accept="image/*"></label>
        <label class="field-card"><span>Password</span><div class="password-field"><input id="register-owner-password" type="password" name="password" required><button type="button" class="ghost-button password-toggle" data-password-toggle="register-owner-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></div></label>
        <label class="field-card"><span>Confirm Password</span><div class="password-field"><input id="register-owner-confirm-password" type="password" name="confirm_password" required><button type="button" class="ghost-button password-toggle" data-password-toggle="register-owner-confirm-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></div></label>
        <div class="inline-actions full-span">
            <button type="submit">Register property owner</button>
            <a class="ghost-button" href="/DigiHome/marketer/dashboard.php">Back to dashboard</a>
        </div>
    </form>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
