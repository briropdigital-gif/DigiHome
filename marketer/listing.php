<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to manage owner listings.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

$owners = marketer_registered_owners((int) $user['id']);
$propertyTypes = get_property_types();
$amenities = get_amenities();
$content = get_site_content_map();
$selectedOwnerId = (int) ($_POST['owner_id'] ?? ($_GET['owner_id'] ?? 0));
$draft = get_listing_draft((int) $user['id'], 'marketer', $selectedOwnerId > 0 ? $selectedOwnerId : null);
$formData = array_merge($draft, $_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create_property');
    $ownerId = (int) ($_POST['owner_id'] ?? 0);

    if ($action === 'delete_draft') {
        delete_listing_draft((int) $user['id'], 'marketer', $ownerId > 0 ? $ownerId : null);
        add_flash('success', 'Draft deleted.');
        header('Location: /DigiHome/marketer/listing.php');
        exit;
    }

    $draftPayload = [
        'owner_id' => $ownerId,
        'title' => trim((string) ($_POST['title'] ?? '')),
        'listing_type' => (string) ($_POST['listing_type'] ?? ''),
        'category' => (string) ($_POST['category'] ?? ''),
        'property_type' => trim((string) ($_POST['property_type'] ?? '')),
        'room_type' => trim((string) ($_POST['room_type'] ?? '')),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'deposit' => trim((string) ($_POST['deposit'] ?? '')),
        'total_units' => trim((string) ($_POST['total_units'] ?? ($_POST['available_units'] ?? '1'))),
        'offer_enabled' => !empty($_POST['offer_enabled']) ? '1' : '0',
        'offer_price' => trim((string) ($_POST['offer_price'] ?? '')),
        'offer_reason' => trim((string) ($_POST['offer_reason'] ?? '')),
        'status' => (string) ($_POST['status'] ?? ''),
        'listing_scope' => (string) ($_POST['listing_scope'] ?? ''),
        'purpose' => (string) ($_POST['purpose'] ?? ''),
        'bedrooms' => trim((string) ($_POST['bedrooms'] ?? '')),
        'bathrooms' => trim((string) ($_POST['bathrooms'] ?? '')),
        'parking' => trim((string) ($_POST['parking'] ?? '')),
        'furnished' => !empty($_POST['furnished']) ? '1' : '0',
        'serviced' => !empty($_POST['serviced']) ? '1' : '0',
        'pet_friendly' => !empty($_POST['pet_friendly']) ? '1' : '0',
        'wheelchair_access' => !empty($_POST['wheelchair_access']) ? '1' : '0',
        'property_condition' => trim((string) ($_POST['property_condition'] ?? '')),
        'property_context' => trim((string) ($_POST['property_context'] ?? '')),
        'location' => trim((string) ($_POST['location'] ?? '')),
        'country' => trim((string) ($_POST['country'] ?? '')),
        'county' => trim((string) ($_POST['county'] ?? '')),
        'sub_county' => trim((string) ($_POST['sub_county'] ?? ($_POST['city'] ?? ''))),
        'ward' => trim((string) ($_POST['ward'] ?? '')),
        'estate' => trim((string) ($_POST['estate'] ?? '')),
        'street' => trim((string) ($_POST['street'] ?? '')),
        'block' => trim((string) ($_POST['block'] ?? '')),
        'unit_number' => trim((string) ($_POST['unit_number'] ?? '')),
        'postal_address' => trim((string) ($_POST['postal_address'] ?? ($_POST['postal_code'] ?? ''))),
        'landmark' => trim((string) ($_POST['landmark'] ?? '')),
        'building_name' => trim((string) ($_POST['building_name'] ?? '')),
        'floor' => trim((string) ($_POST['floor'] ?? '')),
        'wing' => trim((string) ($_POST['wing'] ?? '')),
        'room_number' => trim((string) ($_POST['room_number'] ?? '')),
        'google_maps_link' => trim((string) ($_POST['google_maps_link'] ?? '')),
        'contact' => trim((string) ($_POST['contact'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'amenities' => $_POST['amenities'] ?? [],
        'amenity_scope' => $_POST['amenity_scope'] ?? [],
        'hidden_details' => $_POST['hidden_details'] ?? [],
        'hidden_image_flags' => array_map('intval', (array) ($_POST['hidden_image_flags'] ?? [])),
        'new_image_descriptions' => array_map('trim', (array) ($_POST['new_image_descriptions'] ?? [])),
        'cover_image_index' => max(0, (int) ($_POST['cover_image_index'] ?? 0)),
    ];
    save_listing_draft((int) $user['id'], 'marketer', $draftPayload, $ownerId > 0 ? $ownerId : null);

    if ($action === 'save_draft') {
        add_flash('success', 'Draft saved.');
        header('Location: /DigiHome/marketer/listing.php?owner_id=' . $ownerId);
        exit;
    }

    $owner = get_user_by_id($ownerId);
    if (!$owner || (int) ($owner['created_by_marketer_id'] ?? 0) !== (int) $user['id']) {
        add_flash('danger', 'Select a valid property owner managed by your account.');
    } else {
        $propertyData = [
            'title' => trim($_POST['title'] ?? ''),
            'listing_type' => $_POST['listing_type'] ?? 'rent',
            'category' => $_POST['category'] ?? 'residential',
            'property_type' => trim($_POST['property_type'] ?? ''),
            'room_type' => trim($_POST['room_type'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'deposit' => (float) ($_POST['deposit'] ?? 0),
            'total_units' => (int) ($_POST['total_units'] ?? ($_POST['available_units'] ?? 1)),
            'offer_enabled' => !empty($_POST['offer_enabled']) ? 1 : 0,
            'offer_price' => (float) ($_POST['offer_price'] ?? 0),
            'offer_reason' => trim((string) ($_POST['offer_reason'] ?? '')),
            'location' => trim($_POST['location'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status' => $_POST['status'] ?? 'available',
            'owner_name' => $owner['name'],
            'contact' => trim($_POST['contact'] ?? $owner['phone']),
            'listing_scope' => $_POST['listing_scope'] ?? 'entire_property',
            'purpose' => (string) ($_POST['purpose'] ?? 'rent'),
            'bedrooms' => $_POST['bedrooms'] !== '' ? (int) $_POST['bedrooms'] : null,
            'bathrooms' => $_POST['bathrooms'] !== '' ? (int) $_POST['bathrooms'] : null,
            'parking' => $_POST['parking'] !== '' ? (int) $_POST['parking'] : null,
            'furnished' => !empty($_POST['furnished']) ? 1 : 0,
            'serviced' => !empty($_POST['serviced']) ? 1 : 0,
            'pet_friendly' => !empty($_POST['pet_friendly']) ? 1 : 0,
            'wheelchair_access' => !empty($_POST['wheelchair_access']) ? 1 : 0,
            'property_condition' => trim((string) ($_POST['property_condition'] ?? '')),
            'property_context' => trim((string) ($_POST['property_context'] ?? 'standalone')),
            'country' => trim($_POST['country'] ?? ''),
            'county' => trim($_POST['county'] ?? ''),
            'sub_county' => trim($_POST['sub_county'] ?? ($_POST['city'] ?? '')),
            'ward' => trim($_POST['ward'] ?? ''),
            'city' => trim($_POST['sub_county'] ?? ($_POST['city'] ?? '')),
            'estate' => trim($_POST['estate'] ?? ''),
            'street' => trim($_POST['street'] ?? ''),
            'block' => trim((string) ($_POST['block'] ?? '')),
            'unit_number' => trim((string) ($_POST['unit_number'] ?? '')),
            'postal_code' => trim((string) ($_POST['postal_address'] ?? ($_POST['postal_code'] ?? ''))),
            'postal_address' => trim((string) ($_POST['postal_address'] ?? ($_POST['postal_code'] ?? ''))),
            'landmark' => trim((string) ($_POST['landmark'] ?? '')),
            'building_name' => trim((string) ($_POST['building_name'] ?? '')),
            'floor' => trim((string) ($_POST['floor'] ?? '')),
            'wing' => trim((string) ($_POST['wing'] ?? '')),
            'room_number' => trim((string) ($_POST['room_number'] ?? '')),
            'google_maps_link' => trim($_POST['google_maps_link'] ?? ''),
            'created_by_role' => 'marketer',
            'marketer_id' => (int) $user['id'],
            'images' => ['/DigiHome/assets/img/system/kplc 1.png'],
            'cover_image' => '/DigiHome/assets/img/system/kplc 1.png',
            'hidden_images' => [],
            'amenities' => ['_scope' => $_POST['amenity_scope'] ?? []] + (array) ($_POST['amenities'] ?? []),
            'hidden_details' => (array) ($_POST['hidden_details'] ?? []),
        ];

        $created = create_property($propertyData, $ownerId);
        if ($created) {
            $propertyId = (int) ($created['id'] ?? 0);
            $uploaded = store_property_images_upload('property_images', $propertyId, (string) get_site_content('system_name', 'DigiHome'));
            if ($uploaded !== []) {
                $coverIndex = max(0, (int) ($_POST['cover_image_index'] ?? 0));
                $coverImage = $uploaded[$coverIndex] ?? $uploaded[0];
                $descriptionLines = [];
                foreach ($uploaded as $idx => $unusedImage) {
                    $descriptionLines[] = trim((string) ((array) ($_POST['new_image_descriptions'] ?? [])[$idx] ?? ''));
                }
                $hiddenIndexes = array_map('intval', (array) ($_POST['hidden_image_flags'] ?? []));
                $hiddenImages = [];
                foreach ($hiddenIndexes as $rawIndex) {
                    if (isset($uploaded[$rawIndex]) && $uploaded[$rawIndex] !== $coverImage) {
                        $hiddenImages[] = $uploaded[$rawIndex];
                    }
                }
                update_property_media($propertyId, $coverImage, $uploaded, $hiddenImages, $descriptionLines);
            }
            delete_listing_draft((int) $user['id'], 'marketer', $ownerId);
            add_flash('success', 'Listing submitted for admin review.');
            header('Location: /DigiHome/marketer/dashboard.php');
            exit;
        }
        add_flash('danger', 'Failed to create listing.');
    }
}

$pageTitle = 'DigiHome | Add Owner Listing';
$pageDescription = 'Marketers can add listings on behalf of property owners they manage.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="form">
    <h2>Add a property listing for a managed owner</h2>
    <p>Marketer-created properties are submitted for admin review and cannot be self-approved or deleted here.</p>
    <form method="post" class="form-grid" enctype="multipart/form-data">
        <label class="field-card"><span>Property Owner</span><select name="owner_id" required><option value="">Select owner</option><?php foreach ($owners as $owner): ?><option value="<?= (int) $owner['id'] ?>" <?= (int) ($formData['owner_id'] ?? 0) === (int) $owner['id'] ? 'selected' : '' ?>><?= htmlspecialchars($owner['name']) ?></option><?php endforeach; ?></select></label>
        <label class="field-card"><span><?= htmlspecialchars($content['listing_label_title'] ?? 'Property title') ?></span><input type="text" name="title" value="<?= htmlspecialchars((string) ($formData['title'] ?? '')) ?>" placeholder="<?= htmlspecialchars($content['listing_placeholder_title'] ?? 'e.g. Premium 2-bedroom apartment') ?>" required></label>
        <label class="field-card"><span>Listing type</span><select name="listing_type" required><option value="">Select listing type</option><option value="rent" <?= ($formData['listing_type'] ?? '') === 'rent' ? 'selected' : '' ?>>Rent</option><option value="sale" <?= ($formData['listing_type'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale</option><option value="lease" <?= ($formData['listing_type'] ?? '') === 'lease' ? 'selected' : '' ?>>Lease</option><option value="hire_purchase" <?= ($formData['listing_type'] ?? '') === 'hire_purchase' ? 'selected' : '' ?>>Hire Purchase</option><option value="rent_to_own" <?= ($formData['listing_type'] ?? '') === 'rent_to_own' ? 'selected' : '' ?>>Rent-to-Own</option></select></label>
        <label class="field-card"><span>Category</span><select name="category" required><option value="">Select category</option><option value="residential" <?= ($formData['category'] ?? '') === 'residential' ? 'selected' : '' ?>>Residential</option><option value="office" <?= ($formData['category'] ?? '') === 'office' ? 'selected' : '' ?>>Office</option><option value="business" <?= ($formData['category'] ?? '') === 'business' ? 'selected' : '' ?>>Business</option></select></label>
        <label class="field-card"><span>Property type</span><select name="property_type" required><option value="">Select property type</option><?php foreach ($propertyTypes as $type): ?><option value="<?= htmlspecialchars($type['name']) ?>" <?= ($formData['property_type'] ?? '') === $type['name'] ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option><?php endforeach; ?></select></label>
        <label class="field-card"><span>Room type</span><input type="text" name="room_type" value="<?= htmlspecialchars((string) ($formData['room_type'] ?? '')) ?>" required></label>
        <label class="field-card"><span><?= htmlspecialchars($content['listing_label_price'] ?? 'Price') ?></span><input type="number" name="price" value="<?= htmlspecialchars((string) ($formData['price'] ?? '')) ?>" min="1" required></label>
        <label class="field-card"><span>Deposit</span><input type="number" name="deposit" value="<?= htmlspecialchars((string) ($formData['deposit'] ?? '')) ?>" min="0" required></label>
        <label class="field-card"><span>Total units</span><input type="number" name="total_units" value="<?= htmlspecialchars((string) ($formData['total_units'] ?? ($formData['available_units'] ?? '1'))) ?>" min="0"></label>
        <label class="field-card"><span>Status</span><select name="status" required><option value="">Select status</option><option value="available" <?= ($formData['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option><option value="reserved" <?= ($formData['status'] ?? '') === 'reserved' ? 'selected' : '' ?>>Reserved</option><option value="occupied" <?= ($formData['status'] ?? '') === 'occupied' ? 'selected' : '' ?>>Occupied</option></select></label>
        <label class="field-card"><span>Listing scope</span><select name="listing_scope" required><option value="">Select scope</option><option value="entire_property" <?= ($formData['listing_scope'] ?? '') === 'entire_property' ? 'selected' : '' ?>>Whole Building</option><option value="unit" <?= ($formData['listing_scope'] ?? '') === 'unit' ? 'selected' : '' ?>>Single Unit</option></select></label>
        <label class="field-card"><span>Purpose</span><select name="purpose" required><option value="rent" <?= ($formData['purpose'] ?? 'rent') === 'rent' ? 'selected' : '' ?>>Rent</option><option value="sale" <?= ($formData['purpose'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale</option><option value="lease" <?= ($formData['purpose'] ?? '') === 'lease' ? 'selected' : '' ?>>Lease</option><option value="hire_purchase" <?= ($formData['purpose'] ?? '') === 'hire_purchase' ? 'selected' : '' ?>>Hire Purchase</option><option value="airbnb" <?= ($formData['purpose'] ?? '') === 'airbnb' ? 'selected' : '' ?>>Airbnb</option><option value="hotel_booking" <?= ($formData['purpose'] ?? '') === 'hotel_booking' ? 'selected' : '' ?>>Hotel Booking</option><option value="auction" <?= ($formData['purpose'] ?? '') === 'auction' ? 'selected' : '' ?>>Auction</option></select></label>
        <label class="field-card"><span>Bedrooms</span><input type="number" name="bedrooms" min="0" value="<?= htmlspecialchars((string) ($formData['bedrooms'] ?? '')) ?>" placeholder="e.g. 2"></label>
        <label class="field-card"><span>Bathrooms</span><input type="number" name="bathrooms" min="0" value="<?= htmlspecialchars((string) ($formData['bathrooms'] ?? '')) ?>" placeholder="e.g. 2"></label>
        <label class="field-card"><span>Parking spaces</span><input type="number" name="parking" min="0" value="<?= htmlspecialchars((string) ($formData['parking'] ?? '')) ?>" placeholder="e.g. 1"></label>
        <label class="field-card"><span>Property context</span><select name="property_context" required><option value="">Select context</option><option value="standalone" <?= ($formData['property_context'] ?? '') === 'standalone' ? 'selected' : '' ?>>Standalone</option><option value="apartment" <?= ($formData['property_context'] ?? '') === 'apartment' ? 'selected' : '' ?>>Within an apartment</option><option value="estate" <?= ($formData['property_context'] ?? '') === 'estate' ? 'selected' : '' ?>>Within an estate</option><option value="commercial_building" <?= ($formData['property_context'] ?? '') === 'commercial_building' ? 'selected' : '' ?>>Within a commercial building</option></select></label>
        <label class="field-card"><span>Property condition</span><input type="text" name="property_condition" value="<?= htmlspecialchars((string) ($formData['property_condition'] ?? '')) ?>" placeholder="Excellent, Fair, Newly renovated" required></label>
        <label class="field-card"><span><input type="checkbox" name="offer_enabled" value="1" <?= !empty($formData['offer_enabled']) ? 'checked' : '' ?>> Enable offer price</span></label>
        <label class="field-card"><span>Offer price</span><input type="number" name="offer_price" min="0" value="<?= htmlspecialchars((string) ($formData['offer_price'] ?? '')) ?>"></label>
        <label class="field-card full-span"><span>Offer reason</span><input type="text" name="offer_reason" value="<?= htmlspecialchars((string) ($formData['offer_reason'] ?? '')) ?>"></label>
        <label class="field-card"><span><?= htmlspecialchars($content['listing_label_location'] ?? 'Location') ?></span><input type="text" name="location" value="<?= htmlspecialchars((string) ($formData['location'] ?? '')) ?>" placeholder="<?= htmlspecialchars($content['listing_placeholder_location'] ?? 'e.g. Kilimani, Nairobi') ?>" readonly></label>
        <label class="field-card"><span>Country</span><input type="text" name="country" value="<?= htmlspecialchars((string) ($formData['country'] ?? 'Kenya')) ?>" required data-location-select="country" list="location-country-options"></label>
        <datalist id="location-country-options"><option value="Kenya"></option></datalist>
        <label class="field-card"><span>County</span><input type="text" name="county" value="<?= htmlspecialchars((string) ($formData['county'] ?? '')) ?>" required data-location-select="county" list="location-county-options"></label>
        <datalist id="location-county-options"></datalist>
        <label class="field-card"><span>Sub-County</span><input type="text" name="sub_county" value="<?= htmlspecialchars((string) ($formData['sub_county'] ?? ($formData['city'] ?? ''))) ?>" required data-location-select="sub_county" list="location-subcounty-options"></label>
        <datalist id="location-subcounty-options"></datalist>
        <label class="field-card"><span>Ward</span><input type="text" name="ward" value="<?= htmlspecialchars((string) ($formData['ward'] ?? '')) ?>" required data-location-select="ward" list="location-ward-options"></label>
        <datalist id="location-ward-options"></datalist>
        <label class="field-card"><span>Estate / Neighbourhood / Suburb</span><input type="text" name="estate" value="<?= htmlspecialchars((string) ($formData['estate'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Street / Road</span><input type="text" name="street" value="<?= htmlspecialchars((string) ($formData['street'] ?? '')) ?>"></label>
        <label class="field-card"><span>Block</span><input type="text" name="block" value="<?= htmlspecialchars((string) ($formData['block'] ?? '')) ?>"></label>
        <label class="field-card"><span>House / Unit number</span><input type="text" name="unit_number" value="<?= htmlspecialchars((string) ($formData['unit_number'] ?? ($formData['room_number'] ?? ''))) ?>"></label>
        <label class="field-card"><span>Postal Address</span><input type="text" name="postal_address" value="<?= htmlspecialchars((string) ($formData['postal_address'] ?? ($formData['postal_code'] ?? ''))) ?>"></label>
        <label class="field-card"><span>Directions / Landmark</span><input type="text" name="landmark" value="<?= htmlspecialchars((string) ($formData['landmark'] ?? '')) ?>"></label>
        <label class="field-card"><span>Building / Estate name</span><input type="text" name="building_name" value="<?= htmlspecialchars((string) ($formData['building_name'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Wing</span><input type="text" name="wing" value="<?= htmlspecialchars((string) ($formData['wing'] ?? '')) ?>" placeholder="North Wing"></label>
        <label class="field-card"><span>Google Maps link</span><input type="url" name="google_maps_link" value="<?= htmlspecialchars((string) ($formData['google_maps_link'] ?? '')) ?>" required></label>
        <label class="field-card"><span>Owner contact</span><input type="text" name="contact" value="<?= htmlspecialchars((string) ($formData['contact'] ?? '')) ?>"></label>
        <label class="field-card full-span"><span><?= htmlspecialchars($content['listing_label_description'] ?? 'Description') ?></span><textarea name="description" required><?= htmlspecialchars((string) ($formData['description'] ?? '')) ?></textarea></label>
        <label class="field-card full-span"><span>Features</span><div class="checkbox-group"><label class="checkbox-pill"><input type="checkbox" name="furnished" value="1" <?= !empty($formData['furnished']) ? 'checked' : '' ?>> Furnished</label><label class="checkbox-pill"><input type="checkbox" name="serviced" value="1" <?= !empty($formData['serviced']) ? 'checked' : '' ?>> Serviced</label><label class="checkbox-pill"><input type="checkbox" name="pet_friendly" value="1" <?= !empty($formData['pet_friendly']) ? 'checked' : '' ?>> Pet friendly</label><label class="checkbox-pill"><input type="checkbox" name="wheelchair_access" value="1" <?= !empty($formData['wheelchair_access']) ? 'checked' : '' ?>> Wheelchair access</label></div></label>
        <label class="field-card full-span"><span>Amenities</span><div class="amenity-collection"><?php foreach ($amenities as $amenity): $aid = (int) $amenity['id']; $selected = in_array($aid, array_map('intval', (array) ($formData['amenities'] ?? [])), true); ?><div class="amenity-row"><label class="checkbox-pill"><input type="checkbox" name="amenities[]" value="<?= $aid ?>" <?= $selected ? 'checked' : '' ?>> <?= htmlspecialchars($amenity['name']) ?></label><select name="amenity_scope[<?= $aid ?>]"><option value="private" <?= (($formData['amenity_scope'][$aid] ?? 'shared') === 'private') ? 'selected' : '' ?>>In-Unit (Private)</option><option value="shared" <?= (($formData['amenity_scope'][$aid] ?? 'shared') === 'shared') ? 'selected' : '' ?>>Communal (Shared)</option></select></div><?php endforeach; ?></div></label>
        <div class="field-card full-span">
            <span>Upload images with descriptions</span>
            <div class="property-image-builder" data-image-builder data-index-offset="0"></div>
            <small>Choose one image at a time. Each selected image gets its own preview, description, cover toggle, and hidden toggle.</small>
        </div>
        <div class="inline-actions full-span">
            <button type="submit" name="action" value="create_property">Submit listing for review</button>
            <a class="ghost-button" href="/DigiHome/marketer/dashboard.php">Back to dashboard</a>
        </div>
    </form>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
