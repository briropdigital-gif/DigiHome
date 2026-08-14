<?php
require_once dirname(__DIR__) . '/includes/db.php';

$content = get_site_content_map();
$pageTitle = 'DigiHome | Contact';
$pageDescription = 'Get official DigiHome contact details and support channels.';
include dirname(__DIR__) . '/includes/seeker_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2><?= htmlspecialchars($content['contact_title'] ?? 'Contact DigiHome') ?></h2>
            <p><?= htmlspecialchars($content['contact_body'] ?? 'Reach out for account, listing, or platform support.') ?></p>
        </div>
    </div>
    <div class="grid">
        <article class="info-card">
            <h3>Phone</h3>
            <p><?= htmlspecialchars($content['contact_phone'] ?? '') ?></p>
        </article>
        <article class="info-card">
            <h3>Email</h3>
            <p><?= htmlspecialchars($content['contact_email'] ?? '') ?></p>
        </article>
        <article class="info-card">
            <h3>Address</h3>
            <p><?= htmlspecialchars($content['contact_address'] ?? '') ?></p>
        </article>
        <article class="info-card">
            <h3>Operating Hours</h3>
            <p><?= htmlspecialchars($content['contact_hours'] ?? '') ?></p>
        </article>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
