<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to access home.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

$stats = get_dashboard_stats((int) $user['id'], 'property_owner');
$pageTitle = 'DigiHome | Owner Home';
$pageDescription = 'Owner home for listing performance and workflow visibility.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="hero" data-reveal>
    <div class="hero-grid">
        <div>
            <span class="badge badge-success">Property Owner Home</span>
            <h1>Operate your listings like a pro.</h1>
            <p>Track approvals, pricing, and visibility from one organized owner workspace.</p>
            <div class="inline-actions" style="margin-top: 16px;">
                <a class="primary-button" href="/DigiHome/owner/listing.php">Create Listing</a>
                <a class="ghost-button" href="/DigiHome/owner/listings.php">View Listings</a>
            </div>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat"><strong><?= (int) $stats['listings'] ?></strong><span>Total listings</span></div>
            <div class="hero-stat"><strong><?= (int) $stats['verified'] ?></strong><span>Verified listings</span></div>
        </div>
    </div>
</section>

<section class="card-grid" data-reveal>
    <article class="info-card"><h3>Verification Workflow</h3><p>All submissions are reviewed by admins before trusted distribution.</p></article>
    <article class="info-card"><h3>Audience Reach</h3><p>Property seekers can discover your listings through structured filters.</p></article>
    <article class="info-card"><h3>Profile Management</h3><p>Keep contact, location, and identity details up to date for smooth transactions.</p></article>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
