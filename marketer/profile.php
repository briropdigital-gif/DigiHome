<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
$townParts = array_values(array_filter(array_map('trim', explode(',', (string) ($user['town'] ?? ''))), static function ($part) {
	return $part !== '';
}));
$formCountry = (string) ($_POST['country'] ?? 'Kenya');
$formCounty = (string) ($_POST['county'] ?? ($user['county'] ?? ''));
$formSubCounty = (string) ($_POST['sub_county'] ?? ($townParts[0] ?? ''));
$formWard = (string) ($_POST['ward'] ?? ($townParts[1] ?? ''));
$formTown = (string) ($_POST['town'] ?? ($user['town'] ?? ''));
if (!$user) {
	add_flash('danger', 'Please login to access your profile.');
	header('Location: ' . account_hub_path('login'));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$password = (string) ($_POST['password'] ?? '');
	$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
	$uploadedProfile = store_profile_image_upload('profile_picture_file', (string) ($user['profile_picture'] ?? ''), (string) ($user['role'] ?? 'marketer'));

	if ($password !== '' && $password !== $confirmPassword) {
		add_flash('danger', 'Password confirmation does not match.');
	} else {
		$updated = update_user_profile((int) $user['id'], [
			'first_name' => trim((string) ($_POST['first_name'] ?? '')),
			'last_name' => trim((string) ($_POST['last_name'] ?? '')),
			'email' => trim((string) ($_POST['email'] ?? '')),
			'phone' => trim((string) ($_POST['phone'] ?? '')),
			'address_line' => trim((string) ($_POST['address_line'] ?? '')),
			'county' => trim((string) ($_POST['county'] ?? '')),
			'town' => trim((string) ($_POST['town'] ?? '')),
			'profile_picture' => $uploadedProfile,
			'password' => $password,
		]);

		if ($updated) {
			$_SESSION['user'] = get_user_by_id((int) $user['id']);
			create_notification((int) $user['id'], 'profile_updated', 'Profile updated', 'Your DigiHome profile information has been updated.');
			add_flash('success', 'Your profile has been updated successfully.');
			header('Location: ' . role_profile_path($user['role']));
			exit;
		}
		add_flash('danger', 'Profile update failed.');
	}
}

$user = current_user();
$pageTitle = 'DigiHome | Marketer Profile';
$pageDescription = 'Manage your DigiHome account details, password, contact information, and location.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="section-shell profile-layout" data-reveal>
	<div class="profile-side card-shell">
		<div class="profile-hero">
			<img class="avatar" src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="<?= htmlspecialchars($user['name']) ?>">
			<div>
				<span class="badge badge-success">Role: <?= htmlspecialchars($user['role_label']) ?></span>
				<h2><?= htmlspecialchars($user['name']) ?></h2>
				<p>Keep your profile current so your role-specific workflows and notifications remain accurate.</p>
			</div>
		</div>
		<div class="inline-actions" style="justify-content:center; margin-top: 12px;">
			<a class="ghost-button" href="<?= htmlspecialchars(account_hub_path('login')) ?>">Switch Account</a>
			<a class="ghost-button" href="<?= htmlspecialchars(logout_path() . '?scope=current&role=' . urlencode($user['role'])) ?>" data-confirm-logout="true">Logout</a>
		</div>
	</div>

	<div class="profile-edit card-shell">
		<form method="post" class="form-grid" enctype="multipart/form-data">
			<label class="field-card"><span>First Name</span><input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required></label>
			<label class="field-card"><span>Last Name</span><input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required></label>
			<label class="field-card"><span>Email Address</span><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></label>
			<label class="field-card"><span>Phone Number</span><input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required></label>
			<label class="field-card full-span"><span>Address Line</span><input type="text" name="address_line" value="<?= htmlspecialchars($user['address_line']) ?>"></label>
			<label class="field-card"><span>Country</span><input type="text" name="country" value="<?= htmlspecialchars($formCountry) ?>" data-location-select="country" required></label>
			<label class="field-card"><span>County</span><input type="text" name="county" value="<?= htmlspecialchars($formCounty) ?>" data-location-select="county" required></label>
			<label class="field-card"><span>Sub-County</span><input type="text" name="sub_county" value="<?= htmlspecialchars($formSubCounty) ?>" data-location-select="sub_county" required></label>
			<label class="field-card"><span>Ward</span><input type="text" name="ward" value="<?= htmlspecialchars($formWard) ?>" data-location-select="ward" required></label>
			<label class="field-card"><span>Town</span><input type="text" name="town" value="<?= htmlspecialchars($formTown) ?>" readonly required></label>
			<label class="field-card">
				<span>Profile Picture</span>
				<div class="profile-upload-shell">
					<img class="profile-upload-preview" src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile preview" data-profile-preview>
					<label class="profile-upload-edit" for="profile_picture_file" aria-label="Edit profile image" title="Edit profile image"><i class="fa-solid fa-pen" aria-hidden="true"></i></label>
					<input id="profile_picture_file" type="file" name="profile_picture_file" accept="image/*" data-profile-input>
				</div>
			</label>
			<label class="field-card"><span>New Password</span><div class="password-field"><input id="marketer-profile-password" type="password" name="password" placeholder="Leave blank to keep existing password"><button type="button" class="ghost-button password-toggle" data-password-toggle="marketer-profile-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></div></label>
			<label class="field-card"><span>Confirm Password</span><div class="password-field"><input id="marketer-profile-confirm-password" type="password" name="confirm_password" placeholder="Repeat new password if changing"><button type="button" class="ghost-button password-toggle" data-password-toggle="marketer-profile-confirm-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></div></label>
			<div class="inline-actions full-span">
				<button type="submit">Save profile changes</button>
			</div>
		</form>
	</div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
