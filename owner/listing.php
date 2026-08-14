<?php
require_once dirname(__DIR__) . '/includes/db.config.php';
$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to access the listing workspace.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

$propertyTypes = get_property_types();
$amenities = get_amenities();
$content = get_site_content_map();
$step = $_GET['step'] ?? 'general';
$tabs = ['general', 'details', 'location', 'images', 'submit'];
$activeTab = in_array($step, $tabs, true) ? $step : 'general';

$draft = get_listing_draft((int) $user['id'], 'property_owner');
$formData = array_merge($draft, $_POST);

$nextTab = static function ($tab) use ($tabs) {
    $index = array_search($tab, $tabs, true);
    if ($index === false || $index >= count($tabs) - 1) {
        return $tab;
    }
    return $tabs[$index + 1];
};
$prevTab = static function ($tab) use ($tabs) {
    $index = array_search($tab, $tabs, true);
    if ($index === false || $index <= 0) {
        return $tab;
    }
    return $tabs[$index - 1];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save_draft');
    $targetTab = (string) ($_POST['target_tab'] ?? $activeTab);

    if ($action === 'delete_draft') {
        delete_listing_draft((int) $user['id'], 'property_owner');
        add_flash('success', 'Draft deleted.');
        header('Location: /DigiHome/owner/listing.php?step=general');
        exit;
    }

    $draftPayload = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'listing_type' => (string) ($_POST['listing_type'] ?? ''),
        'category' => (string) ($_POST['category'] ?? ''),
        'property_type' => trim((string) ($_POST['property_type'] ?? '')),
        'room_type' => trim((string) ($_POST['room_type'] ?? '')),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'deposit' => trim((string) ($_POST['deposit'] ?? '')),
        'location' => trim((string) ($_POST['location'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'status' => (string) ($_POST['status'] ?? ''),
        'contact' => trim((string) ($_POST['contact'] ?? '')),
        'building_name' => trim((string) ($_POST['building_name'] ?? '')),
        'floor' => trim((string) ($_POST['floor'] ?? '')),
        'wing' => trim((string) ($_POST['wing'] ?? '')),
        'room_number' => trim((string) ($_POST['room_number'] ?? '')),
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
        'google_maps_link' => trim((string) ($_POST['google_maps_link'] ?? '')),
        'total_units' => trim((string) ($_POST['total_units'] ?? ($_POST['available_units'] ?? '1'))),
        'offer_enabled' => !empty($_POST['offer_enabled']) ? '1' : '0',
        'offer_price' => trim((string) ($_POST['offer_price'] ?? '')),
        'offer_reason' => trim((string) ($_POST['offer_reason'] ?? '')),
        'amenities' => $_POST['amenities'] ?? [],
        'amenity_scope' => $_POST['amenity_scope'] ?? [],
        'hidden_details' => $_POST['hidden_details'] ?? [],
        'new_image_descriptions' => array_map('trim', (array) ($_POST['new_image_descriptions'] ?? [])),
        'hidden_image_flags' => array_map('intval', (array) ($_POST['hidden_image_flags'] ?? [])),
        'cover_image_index' => max(0, (int) ($_POST['cover_image_index'] ?? 0)),
    ];
    save_listing_draft((int) $user['id'], 'property_owner', $draftPayload);

    if ($action === 'navigate_next') {
        header('Location: /DigiHome/owner/listing.php?step=' . urlencode($nextTab($activeTab)));
        exit;
    }

    if ($action === 'navigate_prev') {
        header('Location: /DigiHome/owner/listing.php?step=' . urlencode($prevTab($activeTab)));
        exit;
    }

    if ($action === 'navigate_tab') {
        header('Location: /DigiHome/owner/listing.php?step=' . urlencode($targetTab));
        exit;
    }

    if ($action === 'save_draft') {
        add_flash('success', 'Draft saved.');
        header('Location: /DigiHome/owner/listing.php?step=' . urlencode($targetTab));
        exit;
    }

    if ($action === 'create_property') {
        $propertyData = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'listing_type' => (string) ($_POST['listing_type'] ?? ''),
            'category' => (string) ($_POST['category'] ?? ''),
            'property_type' => trim((string) ($_POST['property_type'] ?? '')),
            'room_type' => trim((string) ($_POST['room_type'] ?? '')),
            'price' => (float) ($_POST['price'] ?? 0),
            'deposit' => (float) ($_POST['deposit'] ?? 0),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'status' => (string) ($_POST['status'] ?? ''),
            'owner_name' => $user['name'],
            'contact' => trim((string) ($_POST['contact'] ?? '')),
            'building_name' => trim((string) ($_POST['building_name'] ?? '')),
            'floor' => trim((string) ($_POST['floor'] ?? '')),
            'wing' => trim((string) ($_POST['wing'] ?? '')),
            'room_number' => trim((string) ($_POST['room_number'] ?? '')),
            'listing_scope' => (string) ($_POST['listing_scope'] ?? ''),
            'purpose' => (string) ($_POST['purpose'] ?? ''),
            'bedrooms' => $_POST['bedrooms'] !== '' ? (int) $_POST['bedrooms'] : null,
            'bathrooms' => $_POST['bathrooms'] !== '' ? (int) $_POST['bathrooms'] : null,
            'parking' => $_POST['parking'] !== '' ? (int) $_POST['parking'] : null,
            'furnished' => !empty($_POST['furnished']) ? 1 : 0,
            'serviced' => !empty($_POST['serviced']) ? 1 : 0,
            'pet_friendly' => !empty($_POST['pet_friendly']) ? 1 : 0,
            'wheelchair_access' => !empty($_POST['wheelchair_access']) ? 1 : 0,
            'property_condition' => trim((string) ($_POST['property_condition'] ?? '')),
            'property_context' => trim((string) ($_POST['property_context'] ?? '')),
            'country' => trim((string) ($_POST['country'] ?? '')),
            'county' => trim((string) ($_POST['county'] ?? '')),
            'sub_county' => trim((string) ($_POST['sub_county'] ?? ($_POST['city'] ?? ''))),
            'ward' => trim((string) ($_POST['ward'] ?? '')),
            'city' => trim((string) ($_POST['sub_county'] ?? ($_POST['city'] ?? ''))),
            'estate' => trim((string) ($_POST['estate'] ?? '')),
            'street' => trim((string) ($_POST['street'] ?? '')),
            'block' => trim((string) ($_POST['block'] ?? '')),
            'unit_number' => trim((string) ($_POST['unit_number'] ?? '')),
            'postal_code' => trim((string) ($_POST['postal_address'] ?? ($_POST['postal_code'] ?? ''))),
            'postal_address' => trim((string) ($_POST['postal_address'] ?? ($_POST['postal_code'] ?? ''))),
            'landmark' => trim((string) ($_POST['landmark'] ?? '')),
            'google_maps_link' => trim((string) ($_POST['google_maps_link'] ?? '')),
            'total_units' => (int) ($_POST['total_units'] ?? ($_POST['available_units'] ?? 1)),
            'offer_enabled' => !empty($_POST['offer_enabled']) ? 1 : 0,
            'offer_price' => (float) ($_POST['offer_price'] ?? 0),
            'offer_reason' => trim((string) ($_POST['offer_reason'] ?? '')),
            'created_by_role' => 'property_owner',
            'images' => ['/DigiHome/assets/img/system/kplc 1.png'],
            'cover_image' => '/DigiHome/assets/img/system/kplc 1.png',
            'hidden_images' => [],
            'amenities' => ['_scope' => $_POST['amenity_scope'] ?? []] + (array) ($_POST['amenities'] ?? []),
            'hidden_details' => (array) ($_POST['hidden_details'] ?? []),
        ];

        $requiredFields = [
            'title' => 'Property title',
            'property_type' => 'Property type',
            'room_type' => 'Property subtype',
            'listing_type' => 'Listing type',
            'category' => 'Category',
            'price' => 'Price',
            'property_context' => 'Property context',
            'description' => 'Description',
            'contact' => 'Contact number',
            'country' => 'Country',
            'county' => 'County',
            'sub_county' => 'Sub-County',
            'ward' => 'Ward',
            'estate' => 'Estate / neighbourhood / suburb',
            'google_maps_link' => 'Google Maps link',
            'building_name' => 'Building / estate name',
        ];

        $missing = [];
        foreach ($requiredFields as $field => $label) {
            $value = $propertyData[$field] ?? '';
            if (is_string($value) && trim($value) === '') {
                $missing[] = $label;
            } elseif (is_numeric($value) && (float) $value <= 0) {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            add_flash('danger', 'Please complete these required fields before publishing: ' . implode(', ', $missing));
            header('Location: /DigiHome/owner/listing.php?step=submit');
            exit;
        }

        $created = create_property($propertyData, (int) $user['id']);
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

            delete_listing_draft((int) $user['id'], 'property_owner');
            add_flash('success', 'Your listing has been submitted. It will remain pending until an admin reviews it.');
            header('Location: /DigiHome/owner/dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'DigiHome | Create Listing';
$pageDescription = 'Submit listings with a guided multi-step form, verification-ready details, and polished presentation.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="panel">
    <div class="hero-inline">
        <div>
            <h2>Create a polished listing</h2>
            <p>Guide your property through clear tabs for essential details, location, amenities, and a final review. Owner-submitted listings enter the admin verification queue.</p>
        </div>
        <div class="stats-row">
            <div class="mini-stat"><strong>5</strong><span>Tabs</span></div>
            <div class="mini-stat"><strong>Live</strong><span>Preview</span></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="wizard-progress">
        <a class="step <?= $activeTab === 'general' ? 'active' : '' ?>" href="/DigiHome/owner/listing.php?step=general"><?= htmlspecialchars($content['listing_tab_general'] ?? 'General') ?></a>
        <a class="step <?= $activeTab === 'details' ? 'active' : '' ?>" href="/DigiHome/owner/listing.php?step=details"><?= htmlspecialchars($content['listing_tab_details'] ?? 'Details') ?></a>
        <a class="step <?= $activeTab === 'location' ? 'active' : '' ?>" href="/DigiHome/owner/listing.php?step=location"><?= htmlspecialchars($content['listing_tab_location'] ?? 'Location') ?></a>
        <a class="step <?= $activeTab === 'images' ? 'active' : '' ?>" href="/DigiHome/owner/listing.php?step=images"><?= htmlspecialchars($content['listing_tab_images'] ?? 'Images') ?></a>
        <a class="step <?= $activeTab === 'submit' ? 'active' : '' ?>" href="/DigiHome/owner/listing.php?step=submit"><?= htmlspecialchars($content['listing_tab_submit'] ?? 'Submit') ?></a>
    </div>

    <form method="post" class="wizard-form" enctype="multipart/form-data">
        <?php if ($activeTab === 'general'): ?>
            <div class="grid">
                <label class="field-card"><span><?= htmlspecialchars($content['listing_label_title'] ?? 'Property title') ?></span><input type="text" name="title" value="<?= htmlspecialchars((string) ($formData['title'] ?? '')) ?>" placeholder="<?= htmlspecialchars($content['listing_placeholder_title'] ?? 'e.g. Premium 2-bedroom apartment') ?>" required></label>
                <label class="field-card"><span>Category</span><select name="category" id="listing-category" required><option value="">Select category</option><option value="residential" <?= ($formData['category'] ?? '') === 'residential' ? 'selected' : '' ?>>Residential</option><option value="office" <?= ($formData['category'] ?? '') === 'office' ? 'selected' : '' ?>>Office</option><option value="business" <?= ($formData['category'] ?? '') === 'business' ? 'selected' : '' ?>>Business</option></select></label>
                <label class="field-card"><span>Property type</span><select name="property_type" required><option value="">Select property type</option><?php foreach ($propertyTypes as $type): ?><option value="<?= htmlspecialchars($type['name']) ?>" <?= ($formData['property_type'] ?? '') === $type['name'] ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option><?php endforeach; ?></select></label>
                <label class="field-card"><span>Listing type</span><select name="listing_type" required><option value="">Select listing type</option><option value="rent" <?= ($formData['listing_type'] ?? '') === 'rent' ? 'selected' : '' ?>>Rent</option><option value="sale" <?= ($formData['listing_type'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale</option><option value="lease" <?= ($formData['listing_type'] ?? '') === 'lease' ? 'selected' : '' ?>>Lease</option><option value="hire_purchase" <?= ($formData['listing_type'] ?? '') === 'hire_purchase' ? 'selected' : '' ?>>Hire Purchase</option><option value="rent_to_own" <?= ($formData['listing_type'] ?? '') === 'rent_to_own' ? 'selected' : '' ?>>Rent-to-Own</option></select></label>
                <label class="field-card"><span>Listing scope</span><select name="listing_scope" required><option value="">Select listing scope</option><option value="entire_property" <?= ($formData['listing_scope'] ?? '') === 'entire_property' ? 'selected' : '' ?>>Entire property</option><option value="unit" <?= ($formData['listing_scope'] ?? '') === 'unit' ? 'selected' : '' ?>>Individual unit</option></select></label>
                <label class="field-card"><span>Purpose</span><select name="purpose" required><option value="rent" <?= ($formData['purpose'] ?? 'rent') === 'rent' ? 'selected' : '' ?>>Rent</option><option value="sale" <?= ($formData['purpose'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale</option><option value="lease" <?= ($formData['purpose'] ?? '') === 'lease' ? 'selected' : '' ?>>Lease</option><option value="hire_purchase" <?= ($formData['purpose'] ?? '') === 'hire_purchase' ? 'selected' : '' ?>>Hire Purchase</option><option value="airbnb" <?= ($formData['purpose'] ?? '') === 'airbnb' ? 'selected' : '' ?>>Airbnb</option><option value="hotel_booking" <?= ($formData['purpose'] ?? '') === 'hotel_booking' ? 'selected' : '' ?>>Hotel Booking</option><option value="auction" <?= ($formData['purpose'] ?? '') === 'auction' ? 'selected' : '' ?>>Auction</option></select></label>
                <label class="field-card"><span>Status</span><select name="status" required><option value="">Select status</option><option value="available" <?= ($formData['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option><option value="reserved" <?= ($formData['status'] ?? '') === 'reserved' ? 'selected' : '' ?>>Reserved</option><option value="occupied" <?= ($formData['status'] ?? '') === 'occupied' ? 'selected' : '' ?>>Occupied</option><option value="under_offer" <?= ($formData['status'] ?? '') === 'under_offer' ? 'selected' : '' ?>>Under offer</option><option value="sold" <?= ($formData['status'] ?? '') === 'sold' ? 'selected' : '' ?>>Sold</option><option value="rented" <?= ($formData['status'] ?? '') === 'rented' ? 'selected' : '' ?>>Rented</option></select></label>
                <label class="field-card"><span>Property subtype</span><input type="text" name="room_type" value="<?= htmlspecialchars((string) ($formData['room_type'] ?? '')) ?>" placeholder="e.g. 2 Bedroom, Studio" required></label>
                <label class="field-card"><span>Property context</span><select name="property_context" required><option value="">Select context</option><option value="standalone" <?= ($formData['property_context'] ?? '') === 'standalone' ? 'selected' : '' ?>>Standalone</option><option value="apartment" <?= ($formData['property_context'] ?? '') === 'apartment' ? 'selected' : '' ?>>Within an apartment</option><option value="estate" <?= ($formData['property_context'] ?? '') === 'estate' ? 'selected' : '' ?>>Within an estate</option><option value="commercial_building" <?= ($formData['property_context'] ?? '') === 'commercial_building' ? 'selected' : '' ?>>Within a commercial building</option></select></label>
            </div>
        <?php elseif ($activeTab === 'details'): ?>
            <div class="grid">
                <label class="field-card"><span><?= htmlspecialchars($content['listing_label_price'] ?? 'Price') ?></span><input type="number" name="price" min="1" value="<?= htmlspecialchars((string) ($formData['price'] ?? '')) ?>" placeholder="e.g. 45000" required></label>
                <label class="field-card"><span>Deposit</span><input type="number" name="deposit" min="0" value="<?= htmlspecialchars((string) ($formData['deposit'] ?? '')) ?>" placeholder="e.g. 120000" required></label>
                <label class="field-card"><span>Total Units</span><input type="number" name="total_units" min="0" value="<?= htmlspecialchars((string) ($formData['total_units'] ?? ($formData['available_units'] ?? '1'))) ?>" placeholder="e.g. 24"></label>
                <label class="field-card"><span>Owner contact</span><input type="text" name="contact" value="<?= htmlspecialchars((string) ($formData['contact'] ?? '')) ?>" required></label>
                <label class="field-card" data-residential-field><span>Bedrooms</span><input id="bedrooms" type="number" name="bedrooms" min="0" value="<?= htmlspecialchars((string) ($formData['bedrooms'] ?? '')) ?>" placeholder="e.g. 2"></label>
                <label class="field-card" data-residential-field><span>Bathrooms</span><input id="bathrooms" type="number" name="bathrooms" min="0" value="<?= htmlspecialchars((string) ($formData['bathrooms'] ?? '')) ?>" placeholder="e.g. 2"></label>
                <label class="field-card"><span>Parking spaces</span><input type="number" name="parking" min="0" value="<?= htmlspecialchars((string) ($formData['parking'] ?? '')) ?>" placeholder="e.g. 1"></label>
                <label class="field-card"><span>Property condition</span><input type="text" name="property_condition" value="<?= htmlspecialchars((string) ($formData['property_condition'] ?? '')) ?>" placeholder="Excellent, Fair, Newly renovated" required></label>
                <label class="field-card"><span><input type="checkbox" name="offer_enabled" value="1" <?= !empty($formData['offer_enabled']) ? 'checked' : '' ?>> Enable offer price</span></label>
                <label class="field-card"><span>Offer price</span><input type="number" name="offer_price" min="0" value="<?= htmlspecialchars((string) ($formData['offer_price'] ?? '')) ?>" placeholder="e.g. 42000"></label>
                <label class="field-card full-span"><span>Offer reason</span><input type="text" name="offer_reason" value="<?= htmlspecialchars((string) ($formData['offer_reason'] ?? '')) ?>" placeholder="e.g. December Offer"></label>
                <label class="field-card full-span"><span><?= htmlspecialchars($content['listing_label_description'] ?? 'Description') ?></span><textarea name="description" placeholder="Describe the property in a compelling way" required><?= htmlspecialchars((string) ($formData['description'] ?? '')) ?></textarea></label>
                <label class="field-card full-span"><span>Features</span><div class="checkbox-group"><label class="checkbox-pill"><input type="checkbox" name="furnished" value="1" <?= !empty($formData['furnished']) ? 'checked' : '' ?>> Furnished</label><label class="checkbox-pill"><input type="checkbox" name="serviced" value="1" <?= !empty($formData['serviced']) ? 'checked' : '' ?>> Serviced</label><label class="checkbox-pill"><input type="checkbox" name="pet_friendly" value="1" <?= !empty($formData['pet_friendly']) ? 'checked' : '' ?>> Pet friendly</label><label class="checkbox-pill"><input type="checkbox" name="wheelchair_access" value="1" <?= !empty($formData['wheelchair_access']) ? 'checked' : '' ?>> Wheelchair access</label></div></label>
                <label class="field-card full-span"><span>Amenities (now under details)</span><div class="amenity-collection"><?php foreach ($amenities as $amenity): $aid = (int) $amenity['id']; $selected = in_array($aid, array_map('intval', (array) ($formData['amenities'] ?? [])), true); ?><div class="amenity-row"><label class="checkbox-pill"><input type="checkbox" name="amenities[]" value="<?= $aid ?>" <?= $selected ? 'checked' : '' ?>> <?= htmlspecialchars($amenity['name']) ?></label><select name="amenity_scope[<?= $aid ?>]"><option value="private" <?= (($formData['amenity_scope'][$aid] ?? 'shared') === 'private') ? 'selected' : '' ?>>In-Unit (Private)</option><option value="shared" <?= (($formData['amenity_scope'][$aid] ?? 'shared') === 'shared') ? 'selected' : '' ?>>Communal (Shared)</option></select></div><?php endforeach; ?></div></label>
            </div>
        <?php elseif ($activeTab === 'location'): ?>
            <div class="grid">
                <label class="field-card"><span><?= htmlspecialchars($content['listing_label_location'] ?? 'Location') ?></span><input type="text" name="location" value="<?= htmlspecialchars((string) ($formData['location'] ?? '')) ?>" placeholder="<?= htmlspecialchars($content['listing_placeholder_location'] ?? 'e.g. Kilimani, Nairobi') ?>" readonly></label>
                <label class="field-card"><span>Country</span><input type="text" name="country" value="<?= htmlspecialchars((string) ($formData['country'] ?? 'Kenya')) ?>" placeholder="e.g. Kenya" required data-location-select="country" list="location-country-options"></label>
                <datalist id="location-country-options"><option value="Kenya"></option></datalist>
                <label class="field-card"><span>County</span><input type="text" name="county" value="<?= htmlspecialchars((string) ($formData['county'] ?? '')) ?>" placeholder="e.g. Nairobi" required data-location-select="county" list="location-county-options"></label>
                <datalist id="location-county-options"></datalist>
                <label class="field-card"><span>Sub-County</span><input type="text" name="sub_county" value="<?= htmlspecialchars((string) ($formData['sub_county'] ?? ($formData['city'] ?? ''))) ?>" placeholder="e.g. Westlands" required data-location-select="sub_county" list="location-subcounty-options"></label>
                <datalist id="location-subcounty-options"></datalist>
                <label class="field-card"><span>Ward</span><input type="text" name="ward" value="<?= htmlspecialchars((string) ($formData['ward'] ?? '')) ?>" placeholder="e.g. Parklands" required data-location-select="ward" list="location-ward-options"></label>
                <datalist id="location-ward-options"></datalist>
                <label class="field-card"><span>Estate / Neighbourhood / Suburb</span><input type="text" name="estate" value="<?= htmlspecialchars((string) ($formData['estate'] ?? '')) ?>" placeholder="e.g. Parklands Heights" required></label>
                <label class="field-card"><span>Street / Road</span><input type="text" name="street" value="<?= htmlspecialchars((string) ($formData['street'] ?? '')) ?>" placeholder="e.g. 5th Avenue"></label>
                <label class="field-card"><span>Google Maps link</span><input type="url" name="google_maps_link" value="<?= htmlspecialchars((string) ($formData['google_maps_link'] ?? '')) ?>" placeholder="https://maps.google.com/..." required></label>
                <label class="field-card"><span>Building / Estate name</span><input type="text" name="building_name" value="<?= htmlspecialchars((string) ($formData['building_name'] ?? '')) ?>" required></label>
                <label class="field-card"><span>Wing</span><input type="text" name="wing" value="<?= htmlspecialchars((string) ($formData['wing'] ?? '')) ?>" placeholder="e.g. North Wing"></label>
                <label class="field-card"><span>Block</span><input type="text" name="block" value="<?= htmlspecialchars((string) ($formData['block'] ?? '')) ?>" placeholder="e.g. Block B"></label>
                <label class="field-card"><span>House / Unit number</span><input type="text" name="unit_number" value="<?= htmlspecialchars((string) ($formData['unit_number'] ?? ($formData['room_number'] ?? ''))) ?>" placeholder="e.g. A3"></label>
                <label class="field-card"><span>Postal Address</span><input type="text" name="postal_address" value="<?= htmlspecialchars((string) ($formData['postal_address'] ?? ($formData['postal_code'] ?? ''))) ?>" placeholder="e.g. P.O. Box 12345"></label>
                <label class="field-card full-span"><span>Directions / Landmark</span><textarea name="landmark" placeholder="Provide clear additional directions or landmark details."><?= htmlspecialchars((string) ($formData['landmark'] ?? '')) ?></textarea></label>
            </div>
        <?php elseif ($activeTab === 'images'): ?>
            <div class="grid">
                <div class="field-card full-span">
                    <span>Upload images with descriptions</span>
                    <div class="property-image-builder" data-image-builder data-index-offset="0"></div>
                    <small>Choose one image at a time. Each selected image gets its own preview, description, cover toggle, and hidden toggle.</small>
                </div>
            </div>
        <?php else: ?>
            <div class="summary-card">
                <h3>Review your listing before publishing</h3>
                <div class="summary-list">
                    <?php $summaryFields = [
                        'title' => ['Property title', 'general'],
                        'category' => ['Category', 'general'],
                        'property_type' => ['Property type', 'general'],
                        'room_type' => ['Property subtype', 'general'],
                        'listing_type' => ['Listing type', 'general'],
                        'listing_scope' => ['Listing scope', 'general'],
                        'status' => ['Status', 'general'],
                        'price' => ['Price', 'details'],
                        'deposit' => ['Deposit', 'details'],
                        'total_units' => ['Total units', 'details'],
                        'offer_price' => ['Offer price', 'details'],
                        'offer_reason' => ['Offer reason', 'details'],
                        'description' => ['Description', 'details'],
                        'location' => ['Location', 'location'],
                        'country' => ['Country', 'location'],
                        'county' => ['County', 'location'],
                        'sub_county' => ['Sub-County', 'location'],
                        'ward' => ['Ward', 'location'],
                        'estate' => ['Estate / neighbourhood / suburb', 'location'],
                        'building_name' => ['Building / estate name', 'location'],
                        'google_maps_link' => ['Google map', 'location'],
                    ]; ?>
                    <?php foreach ($summaryFields as $field => $meta): ?>
                        <?php $label = $meta[0]; $tab = $meta[1]; $value = trim((string) ($formData[$field] ?? '')); ?>
                        <p><strong><?= htmlspecialchars($label) ?>:</strong> <?php if ($value === ''): ?><span style="color:#dc2626;">Missing</span><?php else: ?><?= htmlspecialchars($value) ?><?php endif; ?> <a href="/DigiHome/owner/listing.php?step=<?= urlencode($tab) ?>">Edit</a></p>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="action" value="create_property">Publish listing</button>
            </div>
        <?php endif; ?>

        <div class="wizard-actions">
            <input type="hidden" name="target_tab" value="<?= htmlspecialchars($activeTab) ?>">
            <?php if ($activeTab !== 'general'): ?><button class="ghost-button" type="submit" name="action" value="navigate_prev">Back</button><?php endif; ?>
            <?php if ($activeTab !== 'submit'): ?><button class="ghost-button" type="submit" name="action" value="navigate_next">Continue</button><?php endif; ?>
        </div>
    </form>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
