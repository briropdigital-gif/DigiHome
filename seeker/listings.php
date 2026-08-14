<?php
require_once dirname(__DIR__) . '/includes/db.php';

$pageTitle = 'DigiHome | Listings';
$pageDescription = 'Explore verified listings, filter by listing intent, and discover properties across DigiHome.';

$activeUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activeUser && canonical_role($activeUser['role']) === 'property_seeker') {
    $propertyId = (int) ($_POST['property_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($propertyId > 0 && in_array($action, ['add_favorite', 'remove_favorite'], true)) {
        $ok = set_favorite_property((int) $activeUser['id'], $propertyId, $action === 'add_favorite');
        add_flash($ok ? 'success' : 'danger', $ok ? 'Favorites updated.' : 'Unable to update favorites.');
    }
    header('Location: /DigiHome/seeker/listings.php?' . http_build_query($_GET));
    exit;
}

$filters = [
    'location' => $_GET['location'] ?? '',
    'listing_type' => $_GET['listing_type'] ?? '',
    'category' => $_GET['category'] ?? '',
    'purpose' => $_GET['purpose'] ?? '',
    'listing_scope' => $_GET['listing_scope'] ?? '',
    'bedrooms' => $_GET['bedrooms'] ?? '',
    'bathrooms' => $_GET['bathrooms'] ?? '',
    'price_range' => $_GET['price_range'] ?? '',
    'furnished' => !empty($_GET['furnished']) ? 1 : 0,
    'serviced' => !empty($_GET['serviced']) ? 1 : 0,
    'pet_friendly' => !empty($_GET['pet_friendly']) ? 1 : 0,
];
$properties = get_properties($filters);
include dirname(__DIR__) . '/includes/seeker_header.php';
?>

<section class="hero">
    <div class="hero-grid">
        <div>
            <span class="badge badge-success">Property Seeker Experience</span>
            <h1>Search trusted listings with enterprise-grade clarity.</h1>
            <p>Filter listings by purpose, category, and lifestyle details while keeping verification status visible at every step.</p>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat"><strong><?= count($properties) ?></strong><span>Listings loaded</span></div>
            <div class="hero-stat"><strong>Live</strong><span>Role-based unlock flows</span></div>
        </div>
    </div>
    <button type="button" class="ghost-button search-toggle" data-search-toggle aria-expanded="false">Show search filters</button>
    <form method="get" class="search-grid is-collapsed" data-search-panel>
        <input type="text" name="location" value="<?= htmlspecialchars($filters['location']) ?>" placeholder="City, estate, or suburb">
        <select name="purpose">
            <option value="">Any purpose</option>
            <option value="rent" <?= $filters['purpose'] === 'rent' ? 'selected' : '' ?>>Rent</option>
            <option value="sale" <?= $filters['purpose'] === 'sale' ? 'selected' : '' ?>>Sale</option>
            <option value="lease" <?= $filters['purpose'] === 'lease' ? 'selected' : '' ?>>Lease</option>
            <option value="hire_purchase" <?= $filters['purpose'] === 'hire_purchase' ? 'selected' : '' ?>>Hire Purchase</option>
            <option value="rent_to_own" <?= $filters['purpose'] === 'rent_to_own' ? 'selected' : '' ?>>Rent-to-Own</option>
        </select>
        <select name="listing_type">
            <option value="">Any listing type</option>
            <option value="rent" <?= $filters['listing_type'] === 'rent' ? 'selected' : '' ?>>Rent</option>
            <option value="sale" <?= $filters['listing_type'] === 'sale' ? 'selected' : '' ?>>Sale</option>
            <option value="lease" <?= $filters['listing_type'] === 'lease' ? 'selected' : '' ?>>Lease</option>
        </select>
        <select name="listing_scope">
            <option value="">Any scope</option>
            <option value="entire_property" <?= $filters['listing_scope'] === 'entire_property' ? 'selected' : '' ?>>Entire property</option>
            <option value="unit" <?= $filters['listing_scope'] === 'unit' ? 'selected' : '' ?>>Unit</option>
        </select>
        <select name="category">
            <option value="">Any category</option>
            <option value="residential" <?= $filters['category'] === 'residential' ? 'selected' : '' ?>>Residential</option>
            <option value="office" <?= $filters['category'] === 'office' ? 'selected' : '' ?>>Office</option>
            <option value="business" <?= $filters['category'] === 'business' ? 'selected' : '' ?>>Business</option>
        </select>
        <select name="price_range">
            <option value="">Any price range</option>
            <option value="0-5000" <?= $filters['price_range'] === '0-5000' ? 'selected' : '' ?>>0 - 5k</option>
            <option value="5000-10000" <?= $filters['price_range'] === '5000-10000' ? 'selected' : '' ?>>5k - 10k</option>
            <option value="10000-15000" <?= $filters['price_range'] === '10000-15000' ? 'selected' : '' ?>>10k - 15k</option>
            <option value="15000-20000" <?= $filters['price_range'] === '15000-20000' ? 'selected' : '' ?>>15k - 20k</option>
            <option value="20000-30000" <?= $filters['price_range'] === '20000-30000' ? 'selected' : '' ?>>20k - 30k</option>
            <option value="30000-40000" <?= $filters['price_range'] === '30000-40000' ? 'selected' : '' ?>>30k - 40k</option>
            <option value="40000-60000" <?= $filters['price_range'] === '40000-60000' ? 'selected' : '' ?>>40k - 60k</option>
            <option value="60000-80000" <?= $filters['price_range'] === '60000-80000' ? 'selected' : '' ?>>60k - 80k</option>
            <option value="80000-100000" <?= $filters['price_range'] === '80000-100000' ? 'selected' : '' ?>>80k - 100k</option>
            <option value="100000+" <?= $filters['price_range'] === '100000+' ? 'selected' : '' ?>>Over 100k</option>
        </select>
        <input type="number" name="bedrooms" min="0" value="<?= htmlspecialchars((string) $filters['bedrooms']) ?>" placeholder="Bedrooms">
        <input type="number" name="bathrooms" min="0" value="<?= htmlspecialchars((string) $filters['bathrooms']) ?>" placeholder="Bathrooms">
        <label class="checkbox-pill"><input type="checkbox" name="furnished" value="1" <?= !empty($filters['furnished']) ? 'checked' : '' ?>> Furnished</label>
        <label class="checkbox-pill"><input type="checkbox" name="serviced" value="1" <?= !empty($filters['serviced']) ? 'checked' : '' ?>> Serviced</label>
        <label class="checkbox-pill"><input type="checkbox" name="pet_friendly" value="1" <?= !empty($filters['pet_friendly']) ? 'checked' : '' ?>> Pet friendly</label>
        <button type="submit">Search</button>
    </form>
</section>

<section class="card-grid">
    <div class="info-card" data-reveal>
        <h3>Verified listings</h3>
        <p>Every listing is reviewed before going live and is easy to trust.</p>
    </div>
    <div class="info-card" data-reveal>
        <h3>Flexible offers</h3>
        <p>Rent, sale, lease, hire purchase, and rent-to-own pathways can be surfaced through the same listing experience.</p>
    </div>
    <div class="info-card" data-reveal>
        <h3>Built for growth</h3>
        <p>Property seekers browse cleanly while owners, marketers, and admins manage the marketplace in parallel.</p>
    </div>
</section>

<section class="grid">
    <?php foreach ($properties as $property): ?>
        <article class="property-card" data-reveal>
            <a href="/DigiHome/seeker/property.php?id=<?= (int) ($property['id'] ?? 0) ?>" class="property-card-image-link">
                <img src="<?= htmlspecialchars($property['cover_image'] ?? ($property['images'][0] ?? '/DigiHome/assets/img/system/kplc 1.png')) ?>" alt="<?= htmlspecialchars($property['title'] ?? 'Property image') ?>">
            </a>
            <div class="badges-row">
                <span class="badge"><?= htmlspecialchars(listing_purpose_label($property)) ?></span>
                <span class="badge badge-secondary"><?= htmlspecialchars(listing_scope_label($property)) ?></span>
                <span class="badge <?= !empty($property['verified']) ? 'badge-success' : '' ?>"><?= htmlspecialchars(property_status_label($property)) ?></span>
            </div>
            <h3><?= htmlspecialchars($property['title'] ?? '') ?></h3>
            <p><?= htmlspecialchars($property['description'] ?? '') ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars(trim(implode(' • ', array_filter([$property['estate'] ?? '', $property['city'] ?? '', $property['county'] ?? '', $property['location'] ?? ''])))) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($property['status'] ?? 'available') ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars(($property['property_type_name'] ?? '') !== '' ? $property['property_type_name'] : ($property['property_type'] ?? '')) ?></p>
            <p><strong>Total units:</strong> <?= (int) ($property['total_units'] ?? ($property['available_units'] ?? 0)) ?></p>
            <p class="price">
                <?php if (!empty($property['offer_enabled']) && !empty($property['offer_price'])): ?>
                    <span style="text-decoration: line-through; color:#6b7280;">KES <?= number_format((float) ($property['price'] ?? 0)) ?></span>
                    <span style="color:#15803d;">KES <?= number_format((float) ($property['offer_price'] ?? 0)) ?></span>
                <?php else: ?>
                    KES <?= number_format((float) ($property['price'] ?? 0)) ?>
                <?php endif; ?>
            </p>
            <div class="inline-actions">
                <a class="button-link" href="/DigiHome/seeker/property.php?id=<?= (int) ($property['id'] ?? 0) ?>">View details</a>
                <?php if ($activeUser && canonical_role($activeUser['role']) === 'property_seeker'): ?>
                    <?php $favorite = is_favorite_property((int) $activeUser['id'], (int) $property['id']); ?>
                    <form method="post">
                        <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                        <input type="hidden" name="action" value="<?= $favorite ? 'remove_favorite' : 'add_favorite' ?>">
                        <button type="submit" class="favorite-toggle <?= $favorite ? 'is-active' : '' ?>" aria-label="<?= $favorite ? 'Remove from favorites' : 'Add to favorites' ?>" title="<?= $favorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                            <i class="<?= $favorite ? 'fa-solid' : 'fa-regular' ?> fa-heart" aria-hidden="true"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
