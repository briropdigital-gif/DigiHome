<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_seeker'])) {
    add_flash('danger', 'Please login as a property seeker to view favorites.');
    header('Location: /DigiHome/seeker/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $propertyId = (int) ($_POST['property_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($propertyId > 0 && in_array($action, ['add_favorite', 'remove_favorite'], true)) {
        $ok = set_favorite_property((int) $user['id'], $propertyId, $action === 'add_favorite');
        add_flash($ok ? 'success' : 'danger', $ok ? 'Favorites updated.' : 'Unable to update favorites.');
    }
    header('Location: /DigiHome/seeker/favorites.php');
    exit;
}

$favorites = get_favorite_properties((int) $user['id']);
$pageTitle = 'DigiHome | Favorites';
$pageDescription = 'Manage your favorite properties.';
include dirname(__DIR__) . '/includes/seeker_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Favorite Properties</h2>
            <p>Properties you marked with the favorite toggle appear here.</p>
        </div>
        <a class="button-link" href="/DigiHome/seeker/listings.php">Back to listings</a>
    </div>

    <?php if ($favorites === []): ?>
        <p>No favorite properties yet.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($favorites as $property): ?>
                <article class="property-card">
                    <a href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>" class="property-card-image-link">
                        <img src="<?= htmlspecialchars($property['cover_image'] ?? ($property['images'][0] ?? '/DigiHome/assets/img/system/kplc 1.png')) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                    </a>
                    <div class="badges-row">
                        <span class="badge"><?= htmlspecialchars(listing_purpose_label($property)) ?></span>
                        <span class="badge badge-secondary"><?= htmlspecialchars(listing_scope_label($property)) ?></span>
                        <span class="badge">Favorite</span>
                    </div>
                    <h3><?= htmlspecialchars($property['title']) ?></h3>
                    <p><?= htmlspecialchars($property['description']) ?></p>
                    <p class="price">KES <?= number_format((float) $property['price']) ?></p>
                    <div class="inline-actions">
                        <a class="primary-button" href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>">View details</a>
                        <form method="post" onsubmit="return confirm('Remove from favorites?');">
                            <input type="hidden" name="action" value="remove_favorite">
                            <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                            <button class="ghost-button" type="submit">Remove</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
