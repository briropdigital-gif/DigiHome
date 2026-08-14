<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to access Contact.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

$content = get_site_content_map();
$pageTitle = 'DigiHome | Marketer Contact';
$pageDescription = 'Contact information for marketer operations and account support.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2><?= htmlspecialchars($content['contact_title'] ?? 'Contact DigiHome') ?></h2>
            <p><?= htmlspecialchars($content['contact_body'] ?? '') ?></p>
        </div>
    </div>
    <div class="grid">
        <article class="info-card"><h3>Phone</h3><p><?= htmlspecialchars($content['contact_phone'] ?? '') ?></p></article>
        <article class="info-card"><h3>Email</h3><p><?= htmlspecialchars($content['contact_email'] ?? '') ?></p></article>
        <article class="info-card"><h3>Address</h3><p><?= htmlspecialchars($content['contact_address'] ?? '') ?></p></article>
        <article class="info-card"><h3>Hours</h3><p><?= htmlspecialchars($content['contact_hours'] ?? '') ?></p></article>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
