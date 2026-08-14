<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$id = (int)($_GET['id'] ?? 0);
$property = get_property_by_id($id);
if (!$property) {
    add_flash('danger', 'Property not found.');
    header('Location: /DigiHome/seeker/listings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock'])) {
    $_SESSION['unlocked_properties'][$id] = true;
    $activeUser = current_user();
    if ($activeUser && canonical_role($activeUser['role']) === 'property_seeker') {
        record_property_unlock($id, (int) $activeUser['id']);
    }
    add_flash('success', 'Hidden details unlocked successfully.');
    header('Location: /DigiHome/seeker/property.php?id=' . $id);
    exit;
}

$activeUser = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_action']) && $activeUser && canonical_role($activeUser['role']) === 'property_seeker') {
    $setFavorite = (string) $_POST['favorite_action'] === 'add';
    if (set_favorite_property((int) $activeUser['id'], $id, $setFavorite)) {
        add_flash('success', $setFavorite ? 'Added to favorites.' : 'Removed from favorites.');
    } else {
        add_flash('danger', 'Unable to update favorites.');
    }
    header('Location: /DigiHome/seeker/property.php?id=' . $id);
    exit;
}

$mapSrc = '';
if (!empty($property['google_maps_link'])) {
    $separator = str_contains($property['google_maps_link'], '?') ? '&' : '?';
    $mapSrc = htmlspecialchars($property['google_maps_link'] . $separator . 'output=embed');
}

$pageTitle = 'DigiHome | Property Details';
$pageDescription = 'Review property details, status, amenities, and interactive map information before unlocking contact data.';
include dirname(__DIR__) . '/includes/seeker_header.php';

$isFavorite = false;
if ($activeUser && canonical_role($activeUser['role']) === 'property_seeker') {
    $isFavorite = is_favorite_property((int) $activeUser['id'], $id);
}

$galleryLabels = ['Cover Image', 'Living Room', 'Kitchen', 'Master Bedroom', 'Bathroom', 'Balcony'];
$unlockFee = get_unlock_fee_amount();
$coverImage = trim((string) ($property['cover_image'] ?? ''));
if ($coverImage === '') {
    $coverImage = (string) ($property['images'][0] ?? '/DigiHome/assets/img/system/logo.png');
}

$canViewHidden = can_view_hidden_details($id);
$hiddenDefaults = [
    'street' => 1,
    'google_map' => 1,
    'building_name' => 1,
    'wing' => 1,
    'block' => 1,
    'unit_number' => 1,
    'postal_address' => 1,
    'directions_landmark' => 1,
    'hidden_images' => 1,
];
$hiddenFlags = array_merge($hiddenDefaults, (array) ($property['hidden_details'] ?? []));
$hiddenImageSet = [];
if (!empty($hiddenFlags['hidden_images']) && !$canViewHidden) {
    foreach ((array) ($property['hidden_images'] ?? []) as $hiddenImagePath) {
        $normalized = trim((string) $hiddenImagePath);
        if ($normalized !== '') {
            $hiddenImageSet[$normalized] = true;
        }
    }
}

$hiddenImagesCount = 0;
if ($hiddenImageSet !== []) {
    foreach ((array) ($property['images'] ?? []) as $imagePath) {
        $normalized = trim((string) $imagePath);
        if ($normalized !== '' && isset($hiddenImageSet[$normalized])) {
            $hiddenImagesCount++;
        }
    }
}

$galleryItems = [];
$imageDescriptions = array_values((array) ($property['image_descriptions'] ?? []));
foreach ((array) ($property['images'] ?? []) as $index => $image) {
    $imagePath = trim((string) $image);
    if ($imagePath === '') {
        continue;
    }
    if (isset($hiddenImageSet[$imagePath])) {
        continue;
    }

    $storedCaption = trim((string) ($imageDescriptions[$index] ?? ''));
    if (preg_match('/^property\s+image\s+\d+$/i', $storedCaption) === 1) {
        $storedCaption = '';
    }
    $galleryItems[] = [
        'src' => $imagePath,
        'caption' => $storedCaption !== ''
            ? $storedCaption
            : (string) ($galleryLabels[$index] ?? ('Property image ' . ((int) $index + 1))),
    ];
}
?>

<section class="property-cover-hero" data-reveal>
    <img src="<?= htmlspecialchars($coverImage) ?>" alt="Main cover image for <?= htmlspecialchars($property['title']) ?>" class="property-cover-hero-image" loading="eager">
    <div class="property-cover-hero-overlay">
        <h1><?= htmlspecialchars($property['title']) ?></h1>
        <p><?= htmlspecialchars($property['description']) ?></p>
    </div>
</section>

<section class="section-shell">
    <div class="badges-row">
        <span class="badge"><?= htmlspecialchars(listing_purpose_label($property)) ?></span>
        <span class="badge badge-secondary"><?= htmlspecialchars(listing_scope_label($property)) ?></span>
        <span class="badge <?= !empty($property['verified']) ? 'badge-success' : '' ?>"><?= htmlspecialchars(property_status_label($property)) ?></span>
    </div>
    <?php $favoriteTotal = get_favorite_count((int) $id); ?>
    <?php if ($activeUser && canonical_role($activeUser['role']) === 'property_seeker'): ?>
        <form method="post" class="inline-actions property-favorite-wrap">
            <input type="hidden" name="favorite_action" value="<?= $isFavorite ? 'remove' : 'add' ?>">
            <button type="submit" class="favorite-toggle <?= $isFavorite ? 'is-active' : '' ?>" aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>" title="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                <i class="<?= $isFavorite ? 'fa-solid' : 'fa-regular' ?> fa-heart" aria-hidden="true"></i>
                <span class="favorite-count"><?= $favoriteTotal ?></span>
            </button>
            <a class="ghost-button" href="/DigiHome/seeker/favorites.php">View favorites</a>
        </form>
    <?php else: ?>
        <div class="inline-actions property-favorite-wrap">
            <span class="favorite-toggle" aria-label="Favorite count" title="Number of users who favorited this property">
                <i class="fa-regular fa-heart" aria-hidden="true"></i>
                <span class="favorite-count"><?= $favoriteTotal ?></span>
            </span>
        </div>
    <?php endif; ?>
    <div class="card card-soft property-overview-card">
        <h3>Property Overview</h3>
        <div class="property-overview-grid">
            <div class="property-overview-item"><strong>Property type</strong><span><?= htmlspecialchars($property['property_type_name'] ?: $property['property_type']) ?></span></div>
            <div class="property-overview-item"><strong>Category</strong><span><?= htmlspecialchars($property['category']) ?></span></div>
            <div class="property-overview-item"><strong>Purpose</strong><span><?= htmlspecialchars(listing_purpose_label($property)) ?></span></div>
            <div class="property-overview-item"><strong>Listing scope</strong><span><?= htmlspecialchars(listing_scope_label($property)) ?></span></div>
            <div class="property-overview-item"><strong>Room type</strong><span><?= htmlspecialchars($property['room_type']) ?></span></div>
            <div class="property-overview-item"><strong>Total units</strong><span><?= (int) ($property['total_units'] ?? ($property['available_units'] ?? 0)) ?></span></div>
            <div class="property-overview-item"><strong>Status</strong><span><?= htmlspecialchars($property['status']) ?></span></div>
            <div class="property-overview-item"><strong>Condition</strong><span><?= htmlspecialchars($property['property_condition'] ?: 'Standard') ?></span></div>
            <div class="property-overview-item property-overview-item-price"><strong>Price</strong><span>
                <?php if (!empty($property['offer_enabled']) && !empty($property['offer_price'])): ?>
                    <s>KES <?= number_format($property['price']) ?></s>
                    <b>KES <?= number_format((float) $property['offer_price']) ?></b>
                    <?php if (!empty($property['offer_reason'])): ?><small>(<?= htmlspecialchars($property['offer_reason']) ?>)</small><?php endif; ?>
                <?php else: ?>
                    KES <?= number_format($property['price']) ?>
                <?php endif; ?>
            </span></div>
            <div class="property-overview-item"><strong>Deposit</strong><span>KES <?= number_format($property['deposit']) ?></span></div>
        </div>
    </div>

    <div class="grid property-gallery" style="margin-top: 16px;">
        <?php foreach ($galleryItems as $item): ?>
            <figure class="gallery-card" data-gallery-item>
                <img class="gallery-image" src="<?= htmlspecialchars((string) $item['src']) ?>" alt="<?= htmlspecialchars((string) $item['caption']) ?>" loading="lazy" data-gallery-image>
                <figcaption><?= htmlspecialchars((string) $item['caption']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
        <?php if ($hiddenImagesCount > 0): ?>
            <figure class="gallery-card gallery-hidden-count-card" aria-label="<?= (int) $hiddenImagesCount ?> more images hidden" data-scroll-target="#unlock-phone-input">
                <div class="gallery-hidden-count-box">
                    <strong><?= (int) $hiddenImagesCount ?> More Images Hidden</strong>
                </div>
            </figure>
        <?php endif; ?>
    </div>

    <div class="gallery-lightbox" data-gallery-lightbox hidden>
        <button type="button" class="ghost-button gallery-close" data-gallery-close>&times;</button>
        <button type="button" class="ghost-button gallery-nav gallery-prev" data-gallery-prev>&lt;</button>
        <figure>
            <img src="" alt="Expanded property image" data-gallery-lightbox-image>
            <figcaption data-gallery-lightbox-caption></figcaption>
        </figure>
        <button type="button" class="ghost-button gallery-nav gallery-next" data-gallery-next>&gt;</button>
        <div class="gallery-strip" data-gallery-strip></div>
    </div>

    <div class="row" style="margin-top: 18px;">
        <div class="card card-soft property-detail-list">
            <h3>Core details</h3>
            <p><strong>Bedrooms:</strong> <?= htmlspecialchars((string) ($property['bedrooms'] ?? 'N/A')) ?></p>
            <p><strong>Bathrooms:</strong> <?= htmlspecialchars((string) ($property['bathrooms'] ?? 'N/A')) ?></p>
            <p><strong>Parking:</strong> <?= htmlspecialchars((string) ($property['parking'] ?? 'N/A')) ?></p>
            <p><strong>Furnished:</strong> <?= !empty($property['furnished']) ? 'Yes' : 'No' ?></p>
            <p><strong>Serviced:</strong> <?= !empty($property['serviced']) ? 'Yes' : 'No' ?></p>
            <p><strong>Pet friendly:</strong> <?= !empty($property['pet_friendly']) ? 'Yes' : 'No' ?></p>
        </div>
        <div class="card card-soft property-detail-list">
            <h3>Location</h3>
            <p><strong>Country:</strong> <?= htmlspecialchars($property['country'] ?: 'Kenya') ?></p>
            <p><strong>County:</strong> <?= htmlspecialchars($property['county']) ?></p>
            <p><strong>Sub-County:</strong> <?= htmlspecialchars($property['city']) ?></p>
            <p><strong>Ward:</strong> <?= htmlspecialchars((string) ($property['ward'] ?? '')) ?></p>
            <p><strong>Estate / Neighbourhood / Suburb:</strong> <?= htmlspecialchars($property['estate']) ?></p>
        </div>
    </div>

    <?php if (!empty($property['amenities'])): ?>
        <div class="card card-soft" style="margin-top: 16px;">
            <h3>Amenities</h3>
            <div class="badges-row">
                <?php foreach ($property['amenities'] as $amenity): ?>
                    <span class="badge badge-secondary"><?= htmlspecialchars($amenity['name']) ?> (<?= (($amenity['usage_scope'] ?? 'shared') === 'private') ? 'Private' : 'Shared' ?>)</span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canViewHidden): ?>
        <div class="card card-soft hidden-box hidden-reveal-card" style="margin-top: 16px;">
            <h3>Unlocked hidden details</h3>
            <?php if (!empty($hiddenFlags['owner_contact'])): ?><p><strong>Owner contact:</strong> <?= htmlspecialchars($property['contact']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['owner_name'])): ?><p><strong>Owner name:</strong> <?= htmlspecialchars($property['owner_name']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['street'])): ?><p><strong>Street / Road:</strong> <?= htmlspecialchars($property['street']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['google_map']) && !empty($property['google_maps_link'])): ?>
                <p><strong>Google map:</strong> <a href="<?= htmlspecialchars($property['google_maps_link']) ?>" class="copy-link" data-copy-link="<?= htmlspecialchars($property['google_maps_link']) ?>" title="Click to copy link" aria-label="Click to copy link"><?= htmlspecialchars($property['google_maps_link']) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($hiddenFlags['building_name'])): ?><p><strong>Building / Estate name:</strong> <?= htmlspecialchars($property['building_name']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['wing'])): ?><p><strong>Wing:</strong> <?= htmlspecialchars($property['wing']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['block'])): ?><p><strong>Block:</strong> <?= htmlspecialchars($property['block']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['unit_number']) || !empty($hiddenFlags['room'])): ?><p><strong>House / Unit number:</strong> <?= htmlspecialchars($property['unit_number'] ?: $property['room_number']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['postal_address'])): ?><p><strong>Postal address:</strong> <?= htmlspecialchars($property['postal_code']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['directions_landmark'])): ?><p><strong>Directions / Landmark:</strong> <?= htmlspecialchars($property['landmark']) ?></p><?php endif; ?>
            <?php if (!empty($hiddenFlags['google_map']) && !empty($mapSrc)): ?>
                <div class="card card-soft" style="margin-top: 16px;">
                    <h3>Embedded map</h3>
                    <iframe src="<?= $mapSrc ?>" width="100%" height="320" style="border:0; border-radius: 12px;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            <?php endif; ?>
            <?php if (!empty($hiddenFlags['hidden_images']) && !empty($property['hidden_images'])): ?><p><strong>Hidden images:</strong> Added to the main gallery view above.</p><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="form" style="margin-top: 16px;">
            <h3>Unlock hidden details</h3>
            <p>Pay via M-Pesa STK push to access hidden owner contact, owner name, building name, floors, wing, room, street, map link, and hidden images when enabled by listing controls.</p>
            <p>Unlock amount is configured by admin: <strong>KES <?= number_format($unlockFee, 2) ?></strong>.</p>
            <form method="post" class="unlock-form">
                <input type="text" id="unlock-phone-input" name="phone" placeholder="Enter M-Pesa number" required>
                <button type="submit" name="unlock" data-pay-button>Pay KES <?= number_format($unlockFee, 2) ?> to unlock</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
