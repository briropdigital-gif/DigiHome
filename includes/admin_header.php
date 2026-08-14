<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$activeUser = current_user();
$pageTitle = $pageTitle ?? 'DigiHome | Administration';
$pageDescription = $pageDescription ?? 'Control users, verification, platform modules, and operational reporting from one interface.';
$brandHref = '/DigiHome/admin/dashboard.php';
$siteContent = get_site_content_map();
$brandTitle = $siteContent['system_name'] ?? 'DigiHome';
$brandTagline = $siteContent['header_description'] ?? 'Operate DigiHome with enterprise-grade control, oversight, and governance.';
$bodyClass = 'theme-admin';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$navItems = [
	['label' => 'Home', 'href' => '/DigiHome/admin/dashboard.php', 'icon' => 'fa-house'],
	['label' => 'Listings', 'href' => '/DigiHome/admin/listings.php', 'icon' => 'fa-building'],
	['label' => 'Commissions', 'href' => '/DigiHome/admin/commissions.php', 'icon' => 'fa-coins'],
	['label' => 'Chat', 'href' => '/DigiHome/admin/chat.php', 'icon' => 'fa-comments'],
	['label' => 'Rate Reviews', 'href' => '/DigiHome/admin/rate-us.php', 'icon' => 'fa-star'],
	['label' => 'Users', 'href' => '/DigiHome/admin/users.php', 'icon' => 'fa-users'],
	['label' => 'Verification', 'href' => '/DigiHome/admin/properties.php', 'icon' => 'fa-circle-check'],
	['label' => 'About', 'href' => '/DigiHome/admin/about.php', 'icon' => 'fa-circle-info'],
	['label' => 'Contact', 'href' => '/DigiHome/admin/contact.php', 'icon' => 'fa-envelope'],
];
$flashMap = [
	'success' => 'alert-success',
	'danger' => 'alert-danger',
	'warning' => 'alert-warning',
	'info' => 'alert-info',
];
$currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '/DigiHome/admin/dashboard.php');
$switchAccountHref = account_hub_path('login') . '&return=' . urlencode($currentUri);
$logoutHref = logout_path() . '?scope=current&role=' . urlencode('admin');
$profileHref = $activeUser ? role_profile_path($activeUser['role']) : '#';
$profileTitle = 'Profile';
if ($activeUser) {
	$roleName = str_replace('Property ', '', (string) ($activeUser['role_label'] ?? 'User'));
	$profileTitle = trim($roleName . ': ' . (string) ($activeUser['first_name'] ?? ''));
}
$activeTheme = get_site_content('default_theme', 'light');
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
<body class="<?= htmlspecialchars($bodyClass) ?>" data-theme="<?= htmlspecialchars($activeTheme === 'dark' ? 'dark' : 'light') ?>">
	<div class="page-loader" data-page-loader>
		<div class="loader-mark"></div>
		<p>Loading DigiHome</p>
	</div>
	<header class="site-header">
		<div class="container header-inner">
			<a class="site-brand" href="<?= htmlspecialchars($brandHref) ?>">
				<img src="/DigiHome/assets/img/system/logo.png" alt="DigiHome logo">
				<div>
					<strong><?= htmlspecialchars($brandTitle) ?></strong>
					<span><?= htmlspecialchars($brandTagline) ?></span>
				</div>
			</a>
			<button class="nav-toggle" type="button" aria-expanded="false" aria-label="Toggle navigation" data-nav-toggle>
				<span></span>
				<span></span>
				<span></span>
			</button>
			<button type="button" class="theme-switch" data-theme-toggle aria-label="Toggle theme" title="Toggle theme">
				<span class="theme-switch-track" aria-hidden="true">
					<i class="fa-solid fa-sun"></i>
					<i class="fa-solid fa-moon"></i>
					<span class="theme-switch-knob"></span>
				</span>
			</button>
			<div class="nav-shell" data-nav-shell>
				<button class="nav-close" type="button" aria-label="Close navigation" data-nav-close>&times;</button>
				<nav class="nav-menu" aria-label="Main navigation">
					<?php foreach ($navItems as $item): ?>
						<?php $isActive = $requestPath === $item['href']; ?>
						<a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>">
							<i class="fa-solid <?= htmlspecialchars((string) ($item['icon'] ?? 'fa-circle')) ?>" aria-hidden="true"></i>
							<span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
						</a>
					<?php endforeach; ?>
				</nav>
				<div class="nav-actions">
					<?php if ($activeUser): ?>
						<a class="profile-preview-link" href="<?= htmlspecialchars($profileHref) ?>" aria-label="<?= htmlspecialchars($profileTitle) ?>" title="<?= htmlspecialchars($profileTitle) ?>">
							<?php if (!empty($activeUser['profile_picture']) && $activeUser['profile_picture'] !== default_profile_picture($activeUser['role'])): ?>
								<img class="profile-chip-avatar" src="<?= htmlspecialchars($activeUser['profile_picture']) ?>" alt="<?= htmlspecialchars($activeUser['first_name']) ?>">
							<?php else: ?>
								<span class="profile-chip-initials"><?= htmlspecialchars(user_initials($activeUser)) ?></span>
							<?php endif; ?>
							<span class="profile-preview-name"><?= htmlspecialchars((string) ($activeUser['first_name'] ?? 'Profile')) ?></span>
						</a>
						<a class="ghost-button" href="<?= htmlspecialchars($switchAccountHref) ?>">Switch Account</a>
						<a class="ghost-button logout-menu-only" href="<?= htmlspecialchars($logoutHref) ?>" data-confirm-logout="true">Logout</a>
					<?php else: ?>
						<a class="ghost-button" href="<?= htmlspecialchars(account_hub_path('login')) ?>">Login</a>
						<a class="primary-button" href="<?= htmlspecialchars(account_hub_path('register')) ?>">Register</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</header>
	<main class="site-main">
		<div class="container app-shell">
			<?php foreach (get_flashes() as $flash): ?>
				<?php $flashClass = $flashMap[$flash['type']] ?? 'alert-info'; ?>
				<div class="alert <?= htmlspecialchars($flashClass) ?>" data-alert>
					<?= htmlspecialchars($flash['message']) ?>
				</div>
			<?php endforeach; ?>
