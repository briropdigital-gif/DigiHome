<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to view listings.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

$listings = get_owner_properties((int) $user['id']);
$pageTitle = 'DigiHome | Owner Listings';
$pageDescription = 'View and monitor your property listings and verification states.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="panel" data-reveal>
    <div class="hero-inline">
        <div>
            <h2>Your Listings</h2>
            <p>Track status, price, and verification for each listing in one place.</p>
        </div>
        <a class="button-link" href="/DigiHome/owner/listing.php">Create New Listing</a>
    </div>
    <div class="grid">
        <?php if ($listings === []): ?>
            <article class="info-card"><h3>No listings yet</h3><p>Create your first listing to begin visibility in the marketplace.</p></article>
        <?php endif; ?>
        <?php foreach ($listings as $listing): ?>
            <article class="property-card">
                <?php $assignedMarketer = !empty($listing['marketer_id']) ? get_user_by_id((int) $listing['marketer_id']) : null; ?>
                <span class="badge <?= !empty($listing['verified']) ? 'badge-success' : '' ?>"><?= htmlspecialchars(property_status_label($listing)) ?></span>
                <h3><?= htmlspecialchars($listing['title']) ?></h3>
                <p><?= htmlspecialchars($listing['description']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($listing['location']) ?></p>
                <?php if ($assignedMarketer): ?>
                    <p><strong>Marketer:</strong> <?= htmlspecialchars($assignedMarketer['name']) ?> (<?= htmlspecialchars($assignedMarketer['phone']) ?>)</p>
                <?php endif; ?>
                <p class="price">KES <?= number_format((float) $listing['price']) ?></p>
                <div class="inline-actions">
                    <a class="button-link" href="/DigiHome/seeker/property.php?id=<?= (int) $listing['id'] ?>">Open Public View</a>
                    <a class="ghost-button" href="/DigiHome/owner/edit-property.php?id=<?= (int) $listing['id'] ?>">Edit Listing</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
