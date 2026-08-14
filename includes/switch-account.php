<?php
require_once __DIR__ . '/db.config.php';

$role = canonical_role((string) ($_GET['role'] ?? ''));
$accountId = (int) ($_GET['account'] ?? 0);
$returnTo = (string) ($_GET['return'] ?? role_home_path($role ?: 'property_seeker'));
if (!str_starts_with($returnTo, '/DigiHome/')) {
    $returnTo = role_home_path($role ?: 'property_seeker');
}

if ($accountId <= 0) {
    add_flash('danger', 'Remembered account was not found.');
    header('Location: ' . account_hub_path('login'));
    exit;
}

$user = login_from_remembered_account($accountId, $role);
if (!$user) {
    add_flash('danger', 'Unable to switch to that remembered account.');
    header('Location: ' . account_hub_path('login'));
    exit;
}

add_flash('success', 'Switched to ' . $user['name'] . ' (' . role_label($user['role']) . ').');
header('Location: ' . $returnTo);
exit;
