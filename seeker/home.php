<?php
require_once dirname(__DIR__) . '/includes/db.php';

$properties = get_properties();
$featured = array_slice($properties, 0, 3);
$activeUser = current_user();
$regionalProperties = [];
if ($activeUser && canonical_role($activeUser['role']) === 'property_seeker') {
    $regionalProperties = get_properties_near_user_region((int) $activeUser['id'], 6);
}
$pageTitle = 'DigiHome | Property Seeker Home';
$pageDescription = 'Start your property search journey with trusted listings and clear role-based access.';
include dirname(__DIR__) . '/includes/seeker_header.php';
?>

<section class="hero" data-reveal>
    <div class="hero-grid">
        <div>
            <span class="badge badge-success">Property Seeker Home</span>
            <h1>Find the right property with confidence.</h1>
            <p>Discover verified listings, compare options quickly, and unlock trusted details only when you are ready.</p>
            <div class="inline-actions" style="margin-top: 16px;">
                <a class="primary-button" href="/DigiHome/seeker/listings.php">Browse Listings</a>
                <a class="ghost-button" href="/DigiHome/seeker/about.php">Learn More</a>
            </div>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat"><strong><?= count($properties) ?></strong><span>Listings available</span></div>
            <div class="hero-stat"><strong>Secure</strong><span>Role-based account flows</span></div>
        </div>
    </div>
</section>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Featured Listings</h2>
            <p>Sample of currently available properties across key locations.</p>
        </div>
        <a class="button-link" href="/DigiHome/seeker/listings.php">View all listings</a>
    </div>
    <div class="grid">
        <?php foreach ($featured as $property): ?>
            <article class="property-card">
                <a href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>" class="property-card-image-link">
                    <img src="<?= htmlspecialchars($property['cover_image'] ?? ($property['images'][0] ?? '/DigiHome/assets/img/system/kplc 1.png')) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                </a>
                <div class="badges-row">
                    <span class="badge"><?= htmlspecialchars(listing_purpose_label($property)) ?></span>
                    <span class="badge badge-secondary"><?= htmlspecialchars(listing_scope_label($property)) ?></span>
                    <span class="badge <?= !empty($property['verified']) ? 'badge-success' : '' ?>"><?= htmlspecialchars(property_status_label($property)) ?></span>
                </div>
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p><?= htmlspecialchars($property['description']) ?></p>
                <p class="price">KES <?= number_format((float) $property['price']) ?></p>
                <a class="button-link" href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>">View details</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($activeUser && $regionalProperties !== []): ?>
<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Near Your Region</h2>
            <p>Based on your profile location (<?= htmlspecialchars(trim($activeUser['town'] . ', ' . $activeUser['county'], ', ')) ?>).</p>
        </div>
        <a class="button-link" href="/DigiHome/seeker/listings.php">Explore more</a>
    </div>
    <div class="grid">
        <?php foreach ($regionalProperties as $property): ?>
            <article class="property-card">
                <a href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>" class="property-card-image-link">
                    <img src="<?= htmlspecialchars($property['cover_image'] ?? ($property['images'][0] ?? '/DigiHome/assets/img/system/kplc 1.png')) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                </a>
                <div class="badges-row">
                    <span class="badge"><?= htmlspecialchars(listing_purpose_label($property)) ?></span>
                    <span class="badge badge-secondary"><?= htmlspecialchars(listing_scope_label($property)) ?></span>
                    <span class="badge <?= !empty($property['verified']) ? 'badge-success' : '' ?>"><?= htmlspecialchars(property_status_label($property)) ?></span>
                </div>
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p><?= htmlspecialchars($property['description']) ?></p>
                <p class="price">KES <?= number_format((float) $property['price']) ?></p>
                <a class="button-link" href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>">View details</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
