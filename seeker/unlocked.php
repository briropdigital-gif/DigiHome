<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_seeker'])) {
    add_flash('danger', 'Please login as a property seeker to view unlocked properties.');
    header('Location: /DigiHome/seeker/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'remove_unlocked') {
    $propertyId = (int) ($_POST['property_id'] ?? 0);
    if ($propertyId > 0 && remove_unlocked_property_for_seeker((int) $user['id'], $propertyId)) {
        add_flash('success', 'Property removed from unlocked list.');
    } else {
        add_flash('danger', 'Unable to remove property from unlocked list.');
    }
    header('Location: /DigiHome/seeker/unlocked.php');
    exit;
}

$properties = get_seeker_unlocked_properties((int) $user['id']);
$pageTitle = 'DigiHome | Unlocked Properties';
$pageDescription = 'View and manage the properties you have unlocked.';
include dirname(__DIR__) . '/includes/seeker_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Unlocked Properties</h2>
            <p>These are properties you paid to unlock. You can remove any from this list.</p>
        </div>
    </div>
    <?php if ($properties === []): ?>
        <p>You have not unlocked any property yet.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($properties as $property): ?>
                <article class="property-card">
                    <img src="<?= htmlspecialchars($property['cover_image'] ?? ($property['images'][0] ?? '/DigiHome/assets/img/system/kplc 1.png')) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                    <span class="badge badge-success">Unlocked</span>
                    <h3><?= htmlspecialchars($property['title']) ?></h3>
                    <p><?= htmlspecialchars($property['description']) ?></p>
                    <p><strong>Unlocked on:</strong> <?= htmlspecialchars((string) ($property['unlocked_at'] ?? '')) ?></p>
                    <div class="inline-actions">
                        <a class="primary-button" href="/DigiHome/seeker/property.php?id=<?= (int) $property['id'] ?>">View details</a>
                        <form method="post" onsubmit="return confirm('Remove this property from unlocked list?');">
                            <input type="hidden" name="action" value="remove_unlocked">
                            <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                            <button type="submit" class="ghost-button">Remove</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
