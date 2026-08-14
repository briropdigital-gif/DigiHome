<?php
require_once __DIR__ . '/db.config.php';
$scope = (string) ($_GET['scope'] ?? 'current');
$role = canonical_role((string) ($_GET['role'] ?? (current_user()['role'] ?? '')));

if ($scope === 'all') {
	logout_user();
	session_unset();
	session_destroy();
	session_start();
	add_flash('success', 'You have been logged out from all roles.');
} else {
	logout_user($role);
	add_flash('success', 'You have been logged out from the selected role.');
}

header('Location: /DigiHome/includes/account.php?mode=login');
exit;
