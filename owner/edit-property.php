<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to edit listings.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

$propertyId = (int) ($_GET['id'] ?? $_POST['property_id'] ?? 0);
$property = get_property_by_id($propertyId);
if (!$property || !can_user_edit_property($property, $user)) {
    add_flash('danger', 'You are not allowed to edit this property.');
    header('Location: /DigiHome/owner/listings.php');
    exit;
}

$amenities = get_amenities();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_existing_image'])) {
        $result = delete_property_image($propertyId, (int) ($_POST['delete_existing_image'] ?? -1), $user);
        add_flash($result['ok'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Unable to delete image.'));
        header('Location: /DigiHome/owner/edit-property.php?id=' . $propertyId);
        exit;
    }

    $payload = $_POST;
    if (update_property_details($propertyId, $payload, $user)) {
        add_flash('success', 'Property details updated successfully.');
        header('Location: /DigiHome/owner/listings.php');
        exit;
    }
    add_flash('danger', 'Failed to update property. Ensure required fields are filled.');
    $property = array_merge($property, $payload);
}

$pageTitle = 'DigiHome | Edit Property';
$pageDescription = 'Update your property details and availability.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Edit Property</h2>
            <p>Update listing details and save changes.</p>
        </div>
    </div>
    <form method="post" class="form-grid is-3" enctype="multipart/form-data">
        <input type="hidden" name="property_id" value="<?= (int) $propertyId ?>">
        <label class="field-card"><span>Title</span><input type="text" name="title" value="<?= htmlspecialchars((string) ($property['title'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Listing type</span><select name="listing_type" required><option value="rent" <?= (($property['listing_type'] ?? '') === 'rent') ? 'selected' : '' ?>>Rent</option><option value="sale" <?= (($property['listing_type'] ?? '') === 'sale') ? 'selected' : '' ?>>Sale</option><option value="lease" <?= (($property['listing_type'] ?? '') === 'lease') ? 'selected' : '' ?>>Lease</option><option value="airbnb" <?= (($property['listing_type'] ?? '') === 'airbnb') ? 'selected' : '' ?>>Airbnb</option></select></label>
        <label class="field-card"><span>Category</span><select name="category" required><option value="residential" <?= (($property['category'] ?? '') === 'residential') ? 'selected' : '' ?>>Residential</option><option value="office" <?= (($property['category'] ?? '') === 'office') ? 'selected' : '' ?>>Office</option><option value="business" <?= (($property['category'] ?? '') === 'business') ? 'selected' : '' ?>>Business</option></select></label>
        <label class="field-card"><span>Property Type</span><input type="text" name="property_type" value="<?= htmlspecialchars((string) ($property['property_type'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Room Type</span><input type="text" name="room_type" value="<?= htmlspecialchars((string) ($property['room_type'] ?? '')) ?>"></label>
        <label class="field-card"><span>Purpose</span><select name="purpose"><option value="rent" <?= (($property['purpose'] ?? '') === 'rent') ? 'selected' : '' ?>>Rent</option><option value="sale" <?= (($property['purpose'] ?? '') === 'sale') ? 'selected' : '' ?>>Sale</option><option value="lease" <?= (($property['purpose'] ?? '') === 'lease') ? 'selected' : '' ?>>Lease</option><option value="airbnb" <?= (($property['purpose'] ?? '') === 'airbnb') ? 'selected' : '' ?>>Airbnb</option><option value="hire_purchase" <?= (($property['purpose'] ?? '') === 'hire_purchase') ? 'selected' : '' ?>>Hire Purchase</option></select></label>
        <label class="field-card"><span>Price</span><input type="number" min="1" step="0.01" name="price" value="<?= htmlspecialchars((string) ($property['price'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Deposit</span><input type="number" min="0" step="0.01" name="deposit" value="<?= htmlspecialchars((string) ($property['deposit'] ?? '0')) ?>"></label>
        <label class="field-card"><span>Total Units</span><input type="number" min="0" name="total_units" value="<?= htmlspecialchars((string) ($property['total_units'] ?? ($property['available_units'] ?? '1'))) ?>"></label>
        <label class="field-card"><span>Bedrooms</span><input type="number" min="0" name="bedrooms" value="<?= htmlspecialchars((string) ($property['bedrooms'] ?? '0')) ?>"></label>
        <label class="field-card"><span>Bathrooms</span><input type="number" min="0" name="bathrooms" value="<?= htmlspecialchars((string) ($property['bathrooms'] ?? '0')) ?>"></label>
        <label class="field-card"><span>Parking</span><input type="number" min="0" name="parking" value="<?= htmlspecialchars((string) ($property['parking'] ?? '0')) ?>"></label>
        <label class="field-card"><span>Listing Scope</span><select name="listing_scope" required><option value="entire_property" <?= (($property['listing_scope'] ?? 'entire_property') === 'entire_property') ? 'selected' : '' ?>>Entire Property</option><option value="unit" <?= (($property['listing_scope'] ?? '') === 'unit') ? 'selected' : '' ?>>Individual Unit</option></select></label>
        <label class="field-card"><span>Status</span><input type="text" name="status" value="<?= htmlspecialchars((string) ($property['status'] ?? 'available')) ?>"></label>
        <label class="field-card"><span>Property Condition</span><input type="text" name="property_condition" value="<?= htmlspecialchars((string) ($property['property_condition'] ?? '')) ?>"></label>
        <label class="field-card"><span>Property Context</span><input type="text" name="property_context" value="<?= htmlspecialchars((string) ($property['property_context'] ?? 'standalone')) ?>"></label>
        <label class="field-card full-span"><span>Location</span><input type="text" name="location" value="<?= htmlspecialchars((string) ($property['location'] ?? '')) ?>" readonly></label>
        <label class="field-card"><span>Country</span><input type="text" name="country" value="<?= htmlspecialchars((string) ($property['country'] ?? '')) ?>" required data-location-select="country" list="location-country-options"></label>
        <datalist id="location-country-options"><option value="Kenya"></option></datalist>
        <label class="field-card"><span>County</span><input type="text" name="county" value="<?= htmlspecialchars((string) ($property['county'] ?? '')) ?>" required data-location-select="county" list="location-county-options"></label>
        <datalist id="location-county-options"></datalist>
        <label class="field-card"><span>Sub-County</span><input type="text" name="sub_county" value="<?= htmlspecialchars((string) (($property['city'] ?? ''))) ?>" required data-location-select="sub_county" list="location-subcounty-options"></label>
        <datalist id="location-subcounty-options"></datalist>
        <label class="field-card"><span>Ward</span><input type="text" name="ward" value="<?= htmlspecialchars((string) ($property['ward'] ?? '')) ?>" required data-location-select="ward" list="location-ward-options"></label>
        <datalist id="location-ward-options"></datalist>
        <label class="field-card"><span>Estate / Neighbourhood / Suburb</span><input type="text" name="estate" value="<?= htmlspecialchars((string) ($property['estate'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Street / Road</span><input type="text" name="street" value="<?= htmlspecialchars((string) ($property['street'] ?? '')) ?>"></label>
        <label class="field-card"><span>Block</span><input type="text" name="block" value="<?= htmlspecialchars((string) ($property['block'] ?? '')) ?>"></label>
        <label class="field-card"><span>House / Unit Number</span><input type="text" name="unit_number" value="<?= htmlspecialchars((string) ($property['unit_number'] ?? ($property['room_number'] ?? ''))) ?>"></label>
        <label class="field-card"><span>Postal Address</span><input type="text" name="postal_address" value="<?= htmlspecialchars((string) ($property['postal_code'] ?? '')) ?>"></label>
        <label class="field-card"><span>Directions / Landmark</span><input type="text" name="landmark" value="<?= htmlspecialchars((string) ($property['landmark'] ?? '')) ?>"></label>
        <label class="field-card"><span>Google Maps Link</span><input type="url" name="google_maps_link" value="<?= htmlspecialchars((string) ($property['google_maps_link'] ?? '')) ?>"></label>
        <label class="field-card"><span>Owner Name</span><input type="text" name="owner_name" value="<?= htmlspecialchars((string) ($property['owner_name'] ?? '')) ?>"></label>
        <label class="field-card"><span>Contact</span><input type="text" name="contact" value="<?= htmlspecialchars((string) ($property['contact'] ?? '')) ?>"></label>
        <label class="field-card"><span>Building / Estate Name</span><input type="text" name="building_name" value="<?= htmlspecialchars((string) ($property['building_name'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Wing</span><input type="text" name="wing" value="<?= htmlspecialchars((string) ($property['wing'] ?? '')) ?>" placeholder="North Wing"></label>
        <label class="field-card"><span>Hidden Image Indexes (comma separated)</span><input type="text" name="hidden_image_indexes" placeholder="e.g. 2,4"></label>
        <label class="field-card full-span"><span>Features</span><div class="checkbox-group"><label class="checkbox-pill"><input type="checkbox" name="furnished" value="1" <?= !empty($property['furnished']) ? 'checked' : '' ?>> Furnished</label><label class="checkbox-pill"><input type="checkbox" name="serviced" value="1" <?= !empty($property['serviced']) ? 'checked' : '' ?>> Serviced</label><label class="checkbox-pill"><input type="checkbox" name="pet_friendly" value="1" <?= !empty($property['pet_friendly']) ? 'checked' : '' ?>> Pet Friendly</label><label class="checkbox-pill"><input type="checkbox" name="wheelchair_access" value="1" <?= !empty($property['wheelchair_access']) ? 'checked' : '' ?>> Wheelchair Access</label></div></label>
        <label class="field-card"><span><input type="checkbox" name="offer_enabled" value="1" <?= !empty($property['offer_enabled']) ? 'checked' : '' ?>> Enable offer</span></label>
        <label class="field-card"><span>Offer Price</span><input type="number" min="0" step="0.01" name="offer_price" value="<?= htmlspecialchars((string) ($property['offer_price'] ?? '')) ?>"></label>
        <label class="field-card"><span>Offer Reason</span><input type="text" name="offer_reason" value="<?= htmlspecialchars((string) ($property['offer_reason'] ?? '')) ?>"></label>
        <label class="field-card full-span"><span>Description</span><textarea name="description" required><?= htmlspecialchars((string) ($property['description'] ?? '')) ?></textarea></label>

        <?php
        $amenitySelection = [];
        $amenityScope = [];
        foreach ((array) ($property['amenities'] ?? []) as $amenityRow) {
            $rowId = (int) ($amenityRow['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            $amenitySelection[$rowId] = true;
            $scopeValue = strtolower(trim((string) ($amenityRow['usage_scope'] ?? 'shared')));
            $amenityScope[$rowId] = in_array($scopeValue, ['private', 'shared'], true) ? $scopeValue : 'shared';
        }
        ?>
        <label class="field-card full-span"><span>Amenities</span><div class="amenity-collection"><?php foreach ($amenities as $amenity): $aid = (int) $amenity['id']; $selected = !empty($amenitySelection[$aid]); $scopeValue = $amenityScope[$aid] ?? 'shared'; ?><div class="amenity-row"><label class="checkbox-pill"><input type="checkbox" name="amenities[]" value="<?= $aid ?>" <?= $selected ? 'checked' : '' ?>> <?= htmlspecialchars($amenity['name']) ?></label><select name="amenity_scope[<?= $aid ?>]"><option value="private" <?= $scopeValue === 'private' ? 'selected' : '' ?>>Private</option><option value="shared" <?= $scopeValue === 'shared' ? 'selected' : '' ?>>Shared</option></select></div><?php endforeach; ?></div></label>

        <section class="field-card full-span property-media-editor">
            <span>Property Images (Existing + New)</span>
            <?php
            $imageDescriptions = $property['image_descriptions'] ?? [];
            $galleryLabels = ['Cover Image', 'Living Room', 'Kitchen', 'Master Bedroom', 'Bathroom', 'Balcony'];
            if (!is_array($imageDescriptions)) {
                $rawDescriptions = (string) $imageDescriptions;
                $imageDescriptions = decode_json_array($rawDescriptions);
                if ($imageDescriptions === [] && trim($rawDescriptions) !== '') {
                    $imageDescriptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawDescriptions)), static function ($line) {
                        return $line !== '';
                    }));
                }
            }
            ?>
            <div class="property-media-grid">
                <?php foreach ((array) ($property['images'] ?? []) as $imageIndex => $imageUrl): ?>
                    <?php
                    $descriptionValue = $imageDescriptions[$imageIndex] ?? '';
                    if (is_array($descriptionValue)) {
                        $descriptionValue = implode(' ', array_map('strval', $descriptionValue));
                    }
                    $descriptionValue = trim((string) $descriptionValue);
                    if ($descriptionValue === '' || preg_match('/^property\s+image\s+\d+$/i', $descriptionValue) === 1) {
                        $descriptionValue = (string) ($galleryLabels[$imageIndex] ?? ('Property image ' . ((int) $imageIndex + 1)));
                    }
                    ?>
                    <article class="property-media-item">
                        <img src="<?= htmlspecialchars((string) $imageUrl) ?>" alt="Image <?= (int) $imageIndex + 1 ?>">
                        <label><input type="checkbox" name="cover_image_index" value="<?= (int) $imageIndex ?>" <?= ((string) ($property['cover_image'] ?? '') === (string) $imageUrl || ((int) $imageIndex === 0 && empty($property['cover_image']))) ? 'checked' : '' ?>> Cover image</label>
                        <label><input type="checkbox" name="hidden_image_flags[]" value="<?= (int) $imageIndex ?>" <?= in_array((string) $imageUrl, (array) ($property['hidden_images'] ?? []), true) ? 'checked' : '' ?>> Hidden image</label>
                        <input type="text" name="image_descriptions_by_index[<?= (int) $imageIndex ?>]" value="<?= htmlspecialchars((string) $descriptionValue) ?>" placeholder="Description">
                        <button type="submit" class="ghost-button" name="delete_existing_image" value="<?= (int) $imageIndex ?>" formaction="/DigiHome/owner/edit-property.php?id=<?= (int) $propertyId ?>" formmethod="post" onclick="return confirm('Delete this image now? This updates the database immediately.');">Delete image</button>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="field-card full-span">
                <span>Add New Images</span>
                <div class="property-image-builder" data-image-builder data-index-offset="<?= count((array) ($property['images'] ?? [])) ?>"></div>
                <small>Choose one image at a time. Each selected image adds its own preview, description, and cover/hidden controls.</small>
            </div>
        </section>

        <div class="inline-actions full-span">
            <button type="submit">Save Changes</button>
            <a class="ghost-button" href="/DigiHome/owner/listings.php">Cancel</a>
        </div>
    </form>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
