<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to access home.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

$stats = get_dashboard_stats((int) $user['id'], 'marketer');
$pageTitle = 'DigiHome | Marketer Home';
$pageDescription = 'Marketer home for portfolio growth and listing submission workflows.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="hero" data-reveal>
    <div class="hero-grid">
        <div>
            <span class="badge badge-success">Marketer Home</span>
            <h1>Scale listings through managed owner portfolios.</h1>
            <p>Register property owners, submit quality listings, and keep your managed book organized.</p>
            <div class="inline-actions" style="margin-top: 16px;">
                <a class="primary-button" href="/DigiHome/marketer/register-owner.php">Register Owner</a>
                <a class="ghost-button" href="/DigiHome/marketer/listings.php">View Listings</a>
            </div>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat"><strong><?= (int) ($stats['owners'] ?? 0) ?></strong><span>Owners managed</span></div>
            <div class="hero-stat"><strong><?= (int) ($stats['listings'] ?? 0) ?></strong><span>Listings submitted</span></div>
        </div>
    </div>
</section>

<section class="card-grid" data-reveal>
    <article class="info-card"><h3>Owner Registration</h3><p>Create and link owner accounts directly under your marketer profile.</p></article>
    <article class="info-card"><h3>Listing Submission</h3><p>Add listings with structure and send them to admin verification queue.</p></article>
    <article class="info-card"><h3>Progress Visibility</h3><p>Track approval status and maintain clean contact records for each owner.</p></article>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
