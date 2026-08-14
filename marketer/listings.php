<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to view listings.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

$properties = get_marketer_properties((int) $user['id']);
$pageTitle = 'DigiHome | Marketer Listings';
$pageDescription = 'Review listings you submitted on behalf of managed owners.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="panel" data-reveal>
    <div class="hero-inline">
        <div>
            <h2>Your Submitted Listings</h2>
            <p>Listings below were submitted from your marketer account and await or hold admin decisions.</p>
        </div>
        <a class="button-link" href="/DigiHome/marketer/listing.php">Create Listing</a>
    </div>
    <div class="grid">
        <?php if ($properties === []): ?>
            <article class="info-card"><h3>No listings yet</h3><p>Submit your first listing for a managed owner to start visibility.</p></article>
        <?php endif; ?>
        <?php foreach ($properties as $property): ?>
            <article class="property-card">
                <span class="badge <?= !empty($property['verified']) ? 'badge-success' : '' ?>"><?= htmlspecialchars(property_status_label($property)) ?></span>
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p><?= htmlspecialchars($property['owner_name']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($property['location']) ?></p>
                <p class="price">KES <?= number_format((float) $property['price']) ?></p>
                <div class="inline-actions">
                    <a class="button-link" href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>">Open Public View</a>
                    <a class="ghost-button" href="/DigiHome/marketer/edit-property.php?id=<?= (int) $property['id'] ?>">Edit Listing</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
