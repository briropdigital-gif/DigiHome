<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to access this dashboard.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

$stats = get_dashboard_stats((int) $user['id'], 'property_owner');
$listings = get_owner_properties((int) $user['id']);
$assignedMarketer = get_property_owner_marketer((int) $user['id']);
$unlockHistory = get_unlock_history_for_user((int) $user['id'], 'property_owner');
$notifications = get_notifications((int) $user['id']);
$pageTitle = 'DigiHome | Property Owner Dashboard';
$pageDescription = 'Monitor listing verification, buyer unlock activity, assigned marketer support, and portfolio performance.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="hero">
    <div class="hero-inline">
        <div>
            <span class="badge badge-success">Property Owner Dashboard</span>
            <h2>Manage listings, verification, and buyer engagement.</h2>
            <p>Track your portfolio performance, see which listings were unlocked, and confirm whether a marketer is assigned to your account.</p>
        </div>
        <div class="stats-row">
            <div class="mini-stat"><strong><?= (int) $stats['listings'] ?></strong><span>Listings</span></div>
            <div class="mini-stat"><strong><?= (int) $stats['verified'] ?></strong><span>Verified</span></div>
            <div class="mini-stat"><strong><?= (int) $stats['pending'] ?></strong><span>Pending</span></div>
        </div>
    </div>
</section>

<section class="card-grid">
    <article class="info-card" data-reveal>
        <h3>Assigned marketer</h3>
        <p><?= $assignedMarketer ? htmlspecialchars($assignedMarketer['name'] . ' · ' . $assignedMarketer['phone']) : 'No marketer is assigned to this property owner account.' ?></p>
    </article>
    <article class="info-card" data-reveal>
        <h3>Unlock history</h3>
        <p><?= count($unlockHistory) ?> seeker unlock events recorded for your listings.</p>
    </article>
    <article class="info-card" data-reveal>
        <h3>Notifications</h3>
        <p><?= count($notifications) ?> recent notifications related to profile, listings, or verification activity.</p>
    </article>
</section>

<section class="panel">
    <div class="hero-inline">
        <h3>Your recent listings</h3>
        <a class="button-link" href="/DigiHome/owner/listing.php">Create new listing</a>
    </div>
    <div class="grid">
        <?php foreach ($listings as $listing): ?>
            <div class="property-card" data-reveal>
                <h3><?= htmlspecialchars($listing['title']) ?></h3>
                <p><?= htmlspecialchars($listing['description']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($listing['location']) ?></p>
                <p><strong>Price:</strong> KES <?= number_format($listing['price']) ?></p>
                <p><strong>Verification:</strong> <?= htmlspecialchars(property_status_label($listing)) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <h3>Unlocked listings</h3>
            <p>Property seekers who unlock your listings appear here for performance visibility.</p>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Property ID</th>
                <th>Property Seeker ID</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($unlockHistory === []): ?>
                <tr><td colspan="3">No unlock activity has been recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($unlockHistory as $entry): ?>
                    <tr>
                        <td>#<?= (int) $entry['property_id'] ?></td>
                        <td>#<?= (int) $entry['property_seeker_id'] ?></td>
                        <td><?= htmlspecialchars($entry['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
