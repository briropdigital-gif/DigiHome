<?php
session_start();

$dbHost = 'sql200.infinityfree.com';
$dbUser = 'if0_42656623';
$dbPass = 'aUfBuHBil1UgJw';
$dbName = 'if0_42656623_digihome';

$conn = null;

const DIGIHOME_ROLES = [
    'property_seeker' => [
        'label' => 'Property Seeker',
        'portal' => '/DigiHome/seeker/home.php',
        'home' => '/DigiHome/seeker/home.php',
        'listings' => '/DigiHome/seeker/listings.php',
        'about' => '/DigiHome/seeker/about.php',
        'contact' => '/DigiHome/seeker/contact.php',
        'login' => '/DigiHome/seeker/login.php',
        'register' => '/DigiHome/seeker/register.php',
        'profile' => '/DigiHome/seeker/profile.php',
        'description' => 'Browse and unlock verified property listings.',
        'theme' => 'theme-seeker',
    ],
    'property_owner' => [
        'label' => 'Property Owner',
        'portal' => '/DigiHome/owner/dashboard.php',
        'home' => '/DigiHome/owner/home.php',
        'listings' => '/DigiHome/owner/listings.php',
        'about' => '/DigiHome/owner/about.php',
        'contact' => '/DigiHome/owner/contact.php',
        'login' => '/DigiHome/owner/login.php',
        'register' => '/DigiHome/owner/register.php',
        'profile' => '/DigiHome/owner/profile.php',
        'description' => 'Manage and advertise your own properties.',
        'theme' => 'theme-owner',
    ],
    'marketer' => [
        'label' => 'Marketer',
        'portal' => '/DigiHome/marketer/dashboard.php',
        'home' => '/DigiHome/marketer/home.php',
        'listings' => '/DigiHome/marketer/listings.php',
        'about' => '/DigiHome/marketer/about.php',
        'contact' => '/DigiHome/marketer/contact.php',
        'login' => '/DigiHome/marketer/login.php',
        'register' => '/DigiHome/marketer/register.php',
        'profile' => '/DigiHome/marketer/profile.php',
        'description' => 'Market properties and earn commission.',
        'theme' => 'theme-marketer',
    ],
    'admin' => [
        'label' => 'Admin',
        'portal' => '/DigiHome/admin/dashboard.php',
        'home' => '/DigiHome/admin/dashboard.php',
        'listings' => '/DigiHome/admin/listings.php',
        'about' => '/DigiHome/admin/about.php',
        'contact' => '/DigiHome/admin/contact.php',
        'login' => '/DigiHome/admin/login.php',
        'register' => null,
        'profile' => '/DigiHome/admin/profile.php',
        'description' => 'Manage users, listings, settings, and platform operations.',
        'theme' => 'theme-admin',
    ],
];

const DIGIHOME_REGISTERABLE_ROLES = ['property_seeker', 'property_owner', 'marketer'];

function canonical_role($role) {
    $map = [
        'visitor' => 'property_seeker',
        'owner' => 'property_owner',
        'superadmin' => 'admin',
        'property seeker' => 'property_seeker',
        'property owner' => 'property_owner',
    ];

    $roleKey = strtolower(trim((string) $role));
    return $map[$roleKey] ?? $roleKey;
}

function role_config($role) {
    $role = canonical_role($role);
    return DIGIHOME_ROLES[$role] ?? DIGIHOME_ROLES['property_seeker'];
}

function role_label($role) {
    return role_config($role)['label'];
}

function role_compact_label($role) {
    $normalized = canonical_role($role);
    $map = [
        'property_seeker' => 'Seeker',
        'property_owner' => 'Owner',
        'marketer' => 'Marketer',
        'admin' => 'Admin',
    ];
    if (isset($map[$normalized])) {
        return $map[$normalized];
    }
    if ($normalized === '') {
        return 'User';
    }
    return ucwords(str_replace('_', ' ', $normalized));
}

function person_name_label($firstName, $lastName, $fallback = 'User') {
    $name = trim((string) $firstName . ' ' . (string) $lastName);
    return $name !== '' ? $name : (string) $fallback;
}

function conversation_route_labels(array $conversation): array {
    $scope = (string) ($conversation['conversation_scope'] ?? 'support');
    $fromRole = canonical_role((string) ($conversation['requester_role'] ?? ''));
    $fromName = person_name_label(
        (string) ($conversation['requester_first_name'] ?? $conversation['first_name'] ?? ''),
        (string) ($conversation['requester_last_name'] ?? $conversation['last_name'] ?? ''),
        'User'
    );
    $from = role_compact_label($fromRole) . ': ' . $fromName;

    $recipientRole = canonical_role((string) ($conversation['recipient_role'] ?? ''));
    $recipientName = trim(person_name_label(
        (string) ($conversation['recipient_first_name'] ?? ''),
        (string) ($conversation['recipient_last_name'] ?? ''),
        ''
    ));

    $assignedName = trim(person_name_label(
        (string) ($conversation['assigned_first_name'] ?? ''),
        (string) ($conversation['assigned_last_name'] ?? ''),
        ''
    ));

    if ($scope === 'support') {
        $to = $assignedName !== ''
            ? 'Admin: ' . $assignedName . ' (Assigned)'
            : 'Admin Team (Unassigned)';
    } elseif ($scope === 'admin_broadcast') {
        $to = $recipientName !== ''
            ? 'Admin: ' . $recipientName . ' (Broadcast)'
            : 'Admin Team (Broadcast)';
    } else {
        $toRoleLabel = role_compact_label($recipientRole !== '' ? $recipientRole : 'admin');
        $to = $recipientName !== ''
            ? $toRoleLabel . ': ' . $recipientName
            : $toRoleLabel;
    }

    return ['from' => $from, 'to' => $to];
}

function role_theme($role) {
    return role_config($role)['theme'];
}

function role_dashboard_path($role) {
    return role_config($role)['portal'];
}

function role_login_path($role) {
    return role_config($role)['login'];
}

function role_register_path($role) {
    return role_config($role)['register'];
}

function role_profile_path($role) {
    return role_config($role)['profile'];
}

function role_home_path($role) {
    $config = role_config($role);
    return $config['home'] ?? $config['portal'];
}

function role_chat_path($role) {
    $role = canonical_role($role);
    if ($role === 'admin') {
        return '/DigiHome/admin/chat.php';
    }
    if ($role === 'property_owner') {
        return '/DigiHome/owner/chat.php';
    }
    if ($role === 'marketer') {
        return '/DigiHome/marketer/chat.php';
    }
    return '/DigiHome/seeker/chat.php';
}

function role_listings_path($role) {
    $config = role_config($role);
    return $config['listings'] ?? '/DigiHome/seeker/listings.php';
}

function role_about_path($role) {
    $config = role_config($role);
    return $config['about'] ?? '/DigiHome/seeker/about.php';
}

function role_contact_path($role) {
    $config = role_config($role);
    return $config['contact'] ?? '/DigiHome/seeker/contact.php';
}

function account_hub_path($mode = '') {
    $base = '/DigiHome/includes/account.php';
    return $mode === '' ? $base : $base . '?mode=' . urlencode($mode);
}

function logout_path() {
    return '/DigiHome/includes/logout.php';
}

function is_registerable_role($role) {
    return in_array(canonical_role($role), DIGIHOME_REGISTERABLE_ROLES, true);
}

function get_public_roles($includeAdmin = true) {
    $roles = DIGIHOME_ROLES;
    if (!$includeAdmin) {
        unset($roles['admin']);
    }
    return $roles;
}

$sampleProperties = [
    [
        'id' => 1,
        'title' => 'Modern 2 Bedroom Apartment',
        'listing_type' => 'rent',
        'category' => 'residential',
        'property_type' => 'apartment',
        'room_type' => '2 Bedroom',
        'location' => 'Westlands',
        'price' => 45000,
        'deposit' => 120000,
        'status' => 'available',
        'owner_name' => 'Grace Mwangi',
        'hidden_location' => 'https://maps.google.com/?q=-1.2674,36.8103',
        'description' => 'Bright and secure apartment with parking and high-speed internet.',
        'images' => ['https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=900&q=80', 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=900&q=80'],
        'hidden_images' => ['https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80'],
        'contact' => '+254 712 345 678',
        'building_name' => 'Blue Ridge Towers',
        'floor' => '3rd Floor',
        'wing' => 'North Wing',
        'room_number' => 'A3',
        'verified' => true,
        'marketer_id' => 2,
    ],
    [
        'id' => 2,
        'title' => 'Executive Office Space',
        'listing_type' => 'sale',
        'category' => 'office',
        'property_type' => 'office',
        'room_type' => 'Studio',
        'location' => 'Upper Hill',
        'price' => 18500000,
        'deposit' => 2500000,
        'status' => 'available',
        'owner_name' => 'Daniel Otieno',
        'hidden_location' => 'https://maps.google.com/?q=-1.3007,36.8219',
        'description' => 'Flexible office space near major corporate buildings.',
        'images' => ['https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80'],
        'hidden_images' => ['https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=900&q=80'],
        'contact' => '+254 720 111 222',
        'building_name' => 'Delta Plaza',
        'floor' => '8th Floor',
        'wing' => 'West Wing',
        'room_number' => '801',
        'verified' => true,
        'marketer_id' => 1,
    ],
    [
        'id' => 3,
        'title' => 'Luxury  Bedroom Estate',
        'listing_type' => 'rent',
        'category' => 'residential',
        'property_type' => 'estate',
        'room_type' => '3 Bedroom',
        'location' => 'Kileleshwa',
        'price' => 95000,
        'deposit' => 250000,
        'status' => 'occupied',
        'owner_name' => 'Njeri Kimani',
        'hidden_location' => 'https://maps.google.com/?q=-1.2686,36.8065',
        'description' => 'Spacious estate with a private garden and concierge services.',
        'images' => ['https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?auto=format&fit=crop&w=900&q=80'],
        'hidden_images' => ['https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80'],
        'contact' => '+254 733 445 566',
        'building_name' => 'Mavuno Estate',
        'floor' => 'Ground Floor',
        'wing' => 'Main Wing',
        'room_number' => 'G1',
        'verified' => false,
        'marketer_id' => 0,
    ]
];

function connect_db() {
    global $conn, $dbHost, $dbUser, $dbPass, $dbName;

    if ($conn !== null) {
        return $conn;
    }

    if ($dbHost === '' || $dbUser === '' || $dbName === '') {
        throw new RuntimeException('Database configuration is missing. Ensure includes/db.config.php exists with the live InfinityFree DB credentials.');
    }

    try {
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    } catch (Exception $e) {
        $conn = null;
    }

    return $conn;
}

function ensure_schema() {
    global $conn;

    $db = connect_db();
    if (!$db) {
        return;
    }

    $db->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        name VARCHAR(150) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(30) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        county VARCHAR(100) DEFAULT '',
        town VARCHAR(100) DEFAULT '',
        profile_picture TEXT DEFAULT NULL,
        created_by_marketer_id INT DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'active',
        last_login_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        marketer_id INT DEFAULT NULL,
        title VARCHAR(150) NOT NULL,
        listing_type ENUM('sale','rent','lease','airbnb') NOT NULL,
        category ENUM('residential','business','office') NOT NULL,
        property_type VARCHAR(50) NOT NULL,
        room_type VARCHAR(50) NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        deposit DECIMAL(12,2) DEFAULT 0,
        location VARCHAR(150) NOT NULL,
        hidden_location TEXT,
        description TEXT,
        status VARCHAR(30) DEFAULT 'available',
        verified TINYINT(1) DEFAULT 0,
        owner_name VARCHAR(100) DEFAULT '',
        contact VARCHAR(100) DEFAULT '',
        images TEXT DEFAULT NULL,
        hidden_images TEXT DEFAULT NULL,
        building_name VARCHAR(100) DEFAULT '',
        floor VARCHAR(50) DEFAULT '',
        wing VARCHAR(50) DEFAULT '',
        room_number VARCHAR(50) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS countries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS counties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        country_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_county_country_name (country_id, name),
        INDEX idx_counties_country (country_id),
        CONSTRAINT fk_counties_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS sub_counties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        county_id INT NOT NULL,
        name VARCHAR(120) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_sub_county_name (county_id, name),
        INDEX idx_sub_counties_county (county_id),
        CONSTRAINT fk_sub_counties_county FOREIGN KEY (county_id) REFERENCES counties(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS wards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sub_county_id INT NOT NULL,
        name VARCHAR(120) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_ward_name (sub_county_id, name),
        INDEX idx_wards_sub_county (sub_county_id),
        CONSTRAINT fk_wards_sub_county FOREIGN KEY (sub_county_id) REFERENCES sub_counties(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS property_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        category VARCHAR(50) NOT NULL DEFAULT 'residential',
        description TEXT DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS amenities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS property_amenities (
        property_id INT NOT NULL,
        amenity_id INT NOT NULL,
        usage_scope VARCHAR(20) DEFAULT 'shared',
        PRIMARY KEY(property_id, amenity_id),
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS listing_drafts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL,
        owner_id INT DEFAULT NULL,
        payload LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_listing_draft_user_role_owner (user_id, role, owner_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(60) NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user (user_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        action VARCHAR(80) NOT NULL,
        entity_type VARCHAR(80) NOT NULL,
        entity_id INT DEFAULT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_user (user_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS site_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_key VARCHAR(120) NOT NULL UNIQUE,
        content_value TEXT NOT NULL,
        updated_by INT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS commissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        marketer_id INT NOT NULL,
        property_id INT NOT NULL,
        property_owner_id INT NOT NULL,
        property_seeker_id INT NOT NULL,
        unlock_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        payment_status VARCHAR(30) NOT NULL DEFAULT 'accrued'
    )");

    $db->query("CREATE TABLE IF NOT EXISTS commission_policies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scope_type VARCHAR(20) NOT NULL,
        marketer_id INT DEFAULT NULL,
        property_id INT DEFAULT NULL,
        rate_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_commission_scope (scope_type, marketer_id, property_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS withdrawal_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        marketer_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        account_name VARCHAR(150) NOT NULL,
        account_number VARCHAR(100) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        reason TEXT DEFAULT NULL,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL DEFAULT NULL,
        processed_by_admin_id INT DEFAULT NULL,
        INDEX idx_withdrawal_marketer (marketer_id),
        INDEX idx_withdrawal_status (status)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS withdrawal_payouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        withdrawal_request_id INT NOT NULL UNIQUE,
        marketer_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        processed_by_admin_id INT NOT NULL,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_withdrawal_payout_marketer (marketer_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS unlock_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        property_owner_id INT NOT NULL,
        property_seeker_id INT NOT NULL,
        marketer_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL,
        rating TINYINT NOT NULL,
        review_text TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'inactive',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_reviews_user (user_id),
        INDEX idx_reviews_status (status)
    )");

    $reviewColumns = [];
    $reviewColumnResult = $db->query('SHOW COLUMNS FROM reviews');
    if ($reviewColumnResult) {
        while ($row = $reviewColumnResult->fetch_assoc()) {
            $reviewColumns[] = $row['Field'];
        }
    }

    if (!in_array('status', $reviewColumns, true)) {
        $db->query("ALTER TABLE reviews ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'inactive' AFTER review_text");
    }

    $db->query("UPDATE reviews SET status = 'inactive' WHERE status IS NULL OR status = ''");

    $reviewIndexes = [];
    $reviewIndexResult = $db->query('SHOW INDEX FROM reviews');
    if ($reviewIndexResult) {
        while ($row = $reviewIndexResult->fetch_assoc()) {
            $reviewIndexes[] = (string) ($row['Key_name'] ?? '');
        }
    }

    if (!in_array('idx_reviews_status', $reviewIndexes, true)) {
        $db->query('CREATE INDEX idx_reviews_status ON reviews(status)');
    }

    $db->query("CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_name VARCHAR(150) NOT NULL,
        role_title VARCHAR(150) NOT NULL,
        short_description TEXT NOT NULL,
        profile_picture TEXT DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS team_contact_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_key VARCHAR(80) NOT NULL UNIQUE,
        label VARCHAR(120) NOT NULL,
        icon_html VARCHAR(255) NOT NULL,
        link_prefix VARCHAR(120) NOT NULL DEFAULT '',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS team_member_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_member_id INT NOT NULL,
        contact_type_id INT NOT NULL,
        account_value VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_member_id) REFERENCES team_members(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_type_id) REFERENCES team_contact_types(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS favorite_properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        property_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_property_favorite (user_id, property_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS seeker_unlocked_properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seeker_id INT NOT NULL,
        property_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        removed_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uniq_seeker_property_unlock (seeker_id, property_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requester_user_id INT NOT NULL,
        requester_role VARCHAR(50) NOT NULL,
        assigned_admin_id INT DEFAULT NULL,
        conversation_scope VARCHAR(30) NOT NULL DEFAULT 'support',
        recipient_user_id INT DEFAULT NULL,
        recipient_role VARCHAR(50) DEFAULT NULL,
        typing_user_id INT DEFAULT NULL,
        typing_role VARCHAR(50) DEFAULT NULL,
        typing_at TIMESTAMP NULL DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        subject VARCHAR(180) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_message_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        closed_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_conversations_assigned (assigned_admin_id),
        INDEX idx_conversations_status (status),
        INDEX idx_conversations_requester (requester_user_id, requester_role)
    )");

    $conversationColumns = [];
    $conversationResult = $db->query('SHOW COLUMNS FROM conversations');
    if ($conversationResult) {
        while ($row = $conversationResult->fetch_assoc()) {
            $conversationColumns[] = $row['Field'];
        }
    }

    $addConversationColumn = function ($column, $definition) use ($db, $conversationColumns) {
        if (!in_array($column, $conversationColumns, true)) {
            $db->query('ALTER TABLE conversations ADD COLUMN ' . $column . ' ' . $definition);
        }
    };

    $addConversationColumn('conversation_scope', "VARCHAR(30) NOT NULL DEFAULT 'support'");
    $addConversationColumn('recipient_user_id', 'INT DEFAULT NULL');
    $addConversationColumn('recipient_role', 'VARCHAR(50) DEFAULT NULL');
    $addConversationColumn('typing_user_id', 'INT DEFAULT NULL');
    $addConversationColumn('typing_role', 'VARCHAR(50) DEFAULT NULL');
    $addConversationColumn('typing_at', 'TIMESTAMP NULL DEFAULT NULL');

    $db->query("CREATE TABLE IF NOT EXISTS conversation_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_user_id INT NOT NULL,
        sender_role VARCHAR(50) NOT NULL,
        message_body TEXT NOT NULL,
        edited_at TIMESTAMP NULL DEFAULT NULL,
        media_path VARCHAR(255) DEFAULT NULL,
        media_type VARCHAR(120) DEFAULT NULL,
        media_name VARCHAR(255) DEFAULT NULL,
        is_system_event TINYINT(1) NOT NULL DEFAULT 0,
        delivered_at TIMESTAMP NULL DEFAULT NULL,
        read_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversation_messages_conversation (conversation_id),
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    )");

    $messageColumns = [];
    $messageResult = $db->query('SHOW COLUMNS FROM conversation_messages');
    if ($messageResult) {
        while ($row = $messageResult->fetch_assoc()) {
            $messageColumns[] = $row['Field'];
        }
    }

    if (!in_array('edited_at', $messageColumns, true)) {
        $db->query('ALTER TABLE conversation_messages ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL AFTER message_body');
    }
    if (!in_array('media_path', $messageColumns, true)) {
        $db->query('ALTER TABLE conversation_messages ADD COLUMN media_path VARCHAR(255) DEFAULT NULL AFTER edited_at');
    }
    if (!in_array('media_type', $messageColumns, true)) {
        $db->query('ALTER TABLE conversation_messages ADD COLUMN media_type VARCHAR(120) DEFAULT NULL AFTER media_path');
    }
    if (!in_array('media_name', $messageColumns, true)) {
        $db->query('ALTER TABLE conversation_messages ADD COLUMN media_name VARCHAR(255) DEFAULT NULL AFTER media_type');
    }
    if (!in_array('is_system_event', $messageColumns, true)) {
        $db->query('ALTER TABLE conversation_messages ADD COLUMN is_system_event TINYINT(1) NOT NULL DEFAULT 0 AFTER media_name');
        $db->query("UPDATE conversation_messages SET is_system_event = 1 WHERE message_body LIKE 'Assigned to %by %' OR message_body LIKE 'Conversation Closed by %' OR message_body LIKE 'Conversation Opened by %' OR message_body LIKE 'Closed by %' OR message_body LIKE 'Opened by %'");
    }

    $db->query("CREATE TABLE IF NOT EXISTS conversation_message_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        media_path VARCHAR(255) NOT NULL,
        media_type VARCHAR(120) DEFAULT NULL,
        media_name VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversation_message_attachments_message (message_id),
        FOREIGN KEY (message_id) REFERENCES conversation_messages(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS conversation_admin_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        admin_id INT NOT NULL,
        last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_conversation_admin_read (conversation_id, admin_id),
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    )");

    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM properties');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }

    $addColumn = function ($column, $definition) use ($db, $columns) {
        if (!in_array($column, $columns, true)) {
            $db->query('ALTER TABLE properties ADD COLUMN ' . $column . ' ' . $definition);
        }
    };

    $userColumns = [];
    $userResult = $db->query('SHOW COLUMNS FROM users');
    if ($userResult) {
        while ($row = $userResult->fetch_assoc()) {
            $userColumns[] = $row['Field'];
        }
    }

    $addUserColumn = function ($column, $definition) use ($db, $userColumns) {
        if (!in_array($column, $userColumns, true)) {
            $db->query('ALTER TABLE users ADD COLUMN ' . $column . ' ' . $definition);
        }
    };

    if (in_array('role', $userColumns, true)) {
        $db->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL");
    }

    $addUserColumn('first_name', "VARCHAR(100) NOT NULL DEFAULT ''");
    $addUserColumn('last_name', "VARCHAR(100) NOT NULL DEFAULT ''");
    $addUserColumn('username', "VARCHAR(100) NOT NULL DEFAULT ''");
    $addUserColumn('phone', "VARCHAR(30) NOT NULL DEFAULT ''");
    $addUserColumn('county', "VARCHAR(100) DEFAULT ''");
    $addUserColumn('town', "VARCHAR(100) DEFAULT ''");
    $addUserColumn('profile_picture', 'TEXT DEFAULT NULL');
    $addUserColumn('created_by_marketer_id', 'INT DEFAULT NULL');
    $addUserColumn('last_login_at', 'TIMESTAMP NULL DEFAULT NULL');
    $addUserColumn('id_number', "VARCHAR(50) DEFAULT ''");
    $addUserColumn('gender', "VARCHAR(20) DEFAULT ''");
    $addUserColumn('date_of_birth', 'DATE DEFAULT NULL');
    $addUserColumn('address_line', "VARCHAR(255) DEFAULT ''");
    $addUserColumn('last_seen_at', 'TIMESTAMP NULL DEFAULT NULL');

    $db->query("UPDATE users SET role = 'property_seeker' WHERE role = 'visitor'");
    $db->query("UPDATE users SET role = 'property_owner' WHERE role = 'owner'");
    $db->query("UPDATE users SET role = 'admin' WHERE role = 'superadmin'");
    $db->query("UPDATE users SET first_name = SUBSTRING_INDEX(name, ' ', 1) WHERE first_name = '' AND name <> ''");
    $db->query("UPDATE users SET last_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1)) WHERE last_name = '' AND name LIKE '% %'");
    $db->query("UPDATE users SET last_name = 'Account' WHERE last_name = ''");
    $db->query("UPDATE users SET username = LOWER(REPLACE(email, '@', '_')) WHERE username = '' AND email <> ''");
    $db->query("UPDATE users SET phone = CONCAT('pending-', id) WHERE phone = ''");

    $userIndexes = [];
    $indexResult = $db->query('SHOW INDEX FROM users');
    if ($indexResult) {
        while ($row = $indexResult->fetch_assoc()) {
            $userIndexes[] = $row['Key_name'];
        }
    }

    $addUserIndexSafely = static function ($sql) use ($db) {
        try {
            $db->query($sql);
        } catch (mysqli_sql_exception $error) {
            // 1061: Duplicate key name (concurrent request already created it).
            if ((int) $error->getCode() !== 1061) {
                throw $error;
            }
        }
    };

    if (!in_array('uniq_users_username', $userIndexes, true)) {
        $addUserIndexSafely('ALTER TABLE users ADD UNIQUE KEY uniq_users_username (username)');
    }

    if (!in_array('uniq_users_phone', $userIndexes, true)) {
        $addUserIndexSafely('ALTER TABLE users ADD UNIQUE KEY uniq_users_phone (phone)');
    }

    $userHasIndex = function ($indexName) use ($db) {
        $result = $db->query('SHOW INDEX FROM users');
        if (!$result) {
            return false;
        }
        while ($row = $result->fetch_assoc()) {
            if ((string) ($row['Key_name'] ?? '') === $indexName) {
                return true;
            }
        }
        return false;
    };

    $dropUserIndexSafely = static function ($indexName) use ($db) {
        try {
            $db->query('ALTER TABLE users DROP INDEX ' . $indexName);
        } catch (mysqli_sql_exception $error) {
            // 1091: Can't DROP ... check that it exists.
            if ((int) $error->getCode() !== 1091) {
                throw $error;
            }
        }
    };

    // Allow same email or phone on different roles while preventing duplicates within the same role.
    if ($userHasIndex('email')) {
        $dropUserIndexSafely('email');
    }
    if ($userHasIndex('phone')) {
        $dropUserIndexSafely('phone');
    }
    if ($userHasIndex('uniq_users_phone')) {
        $dropUserIndexSafely('uniq_users_phone');
    }

    $userIndexes = [];
    $indexResult = $db->query('SHOW INDEX FROM users');
    if ($indexResult) {
        while ($row = $indexResult->fetch_assoc()) {
            $userIndexes[] = $row['Key_name'];
        }
    }

    if (!in_array('uniq_users_role_email', $userIndexes, true)) {
        $db->query('ALTER TABLE users ADD UNIQUE KEY uniq_users_role_email (role, email)');
    }

    if (!in_array('uniq_users_role_phone', $userIndexes, true)) {
        $db->query('ALTER TABLE users ADD UNIQUE KEY uniq_users_role_phone (role, phone)');
    }

    $addColumn('owner_name', "VARCHAR(100) DEFAULT ''");
    $addColumn('user_id', 'INT DEFAULT NULL');
    $addColumn('contact', "VARCHAR(100) DEFAULT ''");
    $addColumn('images', 'TEXT DEFAULT NULL');
    $addColumn('hidden_images', 'TEXT DEFAULT NULL');
    $addColumn('cover_image', 'TEXT DEFAULT NULL');
    $addColumn('building_name', "VARCHAR(100) DEFAULT ''");
    $addColumn('floor', "VARCHAR(50) DEFAULT ''");
    $addColumn('wing', "VARCHAR(50) DEFAULT ''");
    $addColumn('room_number', "VARCHAR(50) DEFAULT ''");
    $addColumn('property_type_id', 'INT DEFAULT NULL');
    $addColumn('listing_scope', "VARCHAR(30) DEFAULT 'entire_property'");
    $addColumn('purpose', "VARCHAR(50) DEFAULT 'rent'");
    $addColumn('parent_property_id', 'INT DEFAULT NULL');
    $addColumn('bedrooms', 'INT DEFAULT NULL');
    $addColumn('bathrooms', 'INT DEFAULT NULL');
    $addColumn('parking', 'INT DEFAULT NULL');
    $addColumn('furnished', 'TINYINT(1) DEFAULT 0');
    $addColumn('serviced', 'TINYINT(1) DEFAULT 0');
    $addColumn('pet_friendly', 'TINYINT(1) DEFAULT 0');
    $addColumn('wheelchair_access', 'TINYINT(1) DEFAULT 0');
    $addColumn('property_condition', "VARCHAR(50) DEFAULT ''");
    $addColumn('property_context', "VARCHAR(50) DEFAULT 'standalone'");
    $addColumn('country', "VARCHAR(100) DEFAULT ''");
    $addColumn('country_id', 'INT DEFAULT NULL');
    $addColumn('county', "VARCHAR(100) DEFAULT ''");
    $addColumn('county_id', 'INT DEFAULT NULL');
    $addColumn('city', "VARCHAR(100) DEFAULT ''");
    $addColumn('sub_county_id', 'INT DEFAULT NULL');
    $addColumn('ward', "VARCHAR(100) DEFAULT ''");
    $addColumn('ward_id', 'INT DEFAULT NULL');
    $addColumn('estate', "VARCHAR(100) DEFAULT ''");
    $addColumn('street', "VARCHAR(150) DEFAULT ''");
    $addColumn('block', "VARCHAR(100) DEFAULT ''");
    $addColumn('floor_number', "VARCHAR(50) DEFAULT ''");
    $addColumn('unit_number', "VARCHAR(50) DEFAULT ''");
    $addColumn('postal_code', "VARCHAR(20) DEFAULT ''");
    $addColumn('landmark', "VARCHAR(150) DEFAULT ''");
    $addColumn('google_maps_link', 'TEXT DEFAULT NULL');
    $addColumn('verification_status', "VARCHAR(40) DEFAULT 'pending_verification'");
    $addColumn('verification_reason', 'TEXT DEFAULT NULL');
    $addColumn('created_by_role', "VARCHAR(50) DEFAULT 'property_owner'");
    $addColumn('available_units', 'INT DEFAULT 1');
    $addColumn('total_units', 'INT DEFAULT 1');
    $db->query('UPDATE properties SET total_units = available_units WHERE available_units IS NOT NULL');
    $addColumn('offer_enabled', 'TINYINT(1) DEFAULT 0');
    $addColumn('offer_price', 'DECIMAL(12,2) DEFAULT NULL');
    $addColumn('offer_reason', "VARCHAR(150) DEFAULT ''");
    $addColumn('image_descriptions', 'LONGTEXT DEFAULT NULL');
    $addColumn('hidden_details', 'LONGTEXT DEFAULT NULL');

    $commissionColumns = [];
    $commissionResult = $db->query('SHOW COLUMNS FROM commissions');
    if ($commissionResult) {
        while ($row = $commissionResult->fetch_assoc()) {
            $commissionColumns[] = $row['Field'];
        }
    }

    $commissionHasColumn = static function ($columnName) use (&$commissionColumns) {
        return in_array($columnName, $commissionColumns, true);
    };

    $addCommissionColumn = static function ($columnName, $definition, $afterColumn = null) use ($db, &$commissionColumns, $commissionHasColumn) {
        if ($commissionHasColumn($columnName)) {
            return;
        }
        $sql = 'ALTER TABLE commissions ADD COLUMN ' . $columnName . ' ' . $definition;
        if ($afterColumn !== null && $commissionHasColumn($afterColumn)) {
            $sql .= ' AFTER ' . $afterColumn;
        }
        $db->query($sql);
        $commissionColumns[] = $columnName;
    };

    // Backfill core columns for older commission table definitions.
    $addCommissionColumn('property_owner_id', 'INT DEFAULT NULL', 'property_id');
    $addCommissionColumn('property_seeker_id', 'INT DEFAULT NULL', 'property_owner_id');
    $addCommissionColumn('commission_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0', 'unlock_date');
    $addCommissionColumn('payment_status', "VARCHAR(30) NOT NULL DEFAULT 'accrued'", 'commission_amount');

    // Extended commission tracking columns.
    $addCommissionColumn('commission_rate_percent', 'DECIMAL(6,2) NOT NULL DEFAULT 0', 'commission_amount');
    $addCommissionColumn('unlock_fee_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0', 'commission_rate_percent');

    $amenityColumns = [];
    $amenityResult = $db->query('SHOW COLUMNS FROM property_amenities');
    if ($amenityResult) {
        while ($row = $amenityResult->fetch_assoc()) {
            $amenityColumns[] = $row['Field'];
        }
    }
    if (!in_array('usage_scope', $amenityColumns, true)) {
        $db->query("ALTER TABLE property_amenities ADD COLUMN usage_scope VARCHAR(20) DEFAULT 'shared'");
    }

    $teamContactTypeColumns = [];
    $teamContactTypeColumnResult = $db->query('SHOW COLUMNS FROM team_contact_types');
    if ($teamContactTypeColumnResult) {
        while ($row = $teamContactTypeColumnResult->fetch_assoc()) {
            $teamContactTypeColumns[] = $row['Field'];
        }
    }
    if (!in_array('link_prefix', $teamContactTypeColumns, true)) {
        $db->query("ALTER TABLE team_contact_types ADD COLUMN link_prefix VARCHAR(120) NOT NULL DEFAULT '' AFTER icon_html");
    }

    $contactTypeCount = $db->query('SELECT COUNT(*) as total FROM team_contact_types')->fetch_assoc()['total'] ?? 0;
    if ((int) $contactTypeCount === 0) {
        $defaults = [
            ['whatsapp', 'WhatsApp', '<i class="fa-brands fa-whatsapp"></i>', 'https://wa.me/'],
            ['email', 'Email', '<i class="fa-solid fa-envelope"></i>', 'mailto:'],
            ['facebook', 'Facebook', '<i class="fa-brands fa-facebook"></i>', 'https://'],
        ];

        foreach ($defaults as $type) {
            $stmt = $db->prepare('INSERT INTO team_contact_types (type_key, label, icon_html, link_prefix) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $type[0], $type[1], $type[2], $type[3]);
            $stmt->execute();
        }
    }
}

function default_profile_picture($role = 'property_seeker') {
    return '/DigiHome/assets/img/system/logo.png';
}

function company_logo_path() {
    $canonicalLogo = '/DigiHome/assets/img/system/company-logo.png';
    if (property_image_web_to_file($canonicalLogo) !== null) {
        return $canonicalLogo;
    }
    return '/DigiHome/assets/img/system/logo.png';
}

function default_property_images() {
    return [
        '/DigiHome/assets/img/system/kplc 1.png',
        '/DigiHome/assets/img/system/kplc 2.png',
        '/DigiHome/assets/img/system/kplc 3.png',
        '/DigiHome/assets/img/system/kplc 4.png',
        '/DigiHome/assets/img/system/kplc 5.png',
        '/DigiHome/assets/img/system/kplc 6.png',
    ];
}

function normalize_user($row) {
    if (!$row) {
        return null;
    }

    $role = canonical_role($row['role'] ?? 'property_seeker');
    $firstName = trim((string) ($row['first_name'] ?? ''));
    $lastName = trim((string) ($row['last_name'] ?? ''));
    $fullName = trim((string) ($row['name'] ?? trim($firstName . ' ' . $lastName)));
    if ($fullName === '') {
        $fullName = trim($firstName . ' ' . $lastName);
    }

    $profilePicture = !empty($row['profile_picture']) ? (string) $row['profile_picture'] : '';
    if ($profilePicture === '' || property_image_web_to_file($profilePicture) === null) {
        $profilePicture = default_profile_picture($role);
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'first_name' => $firstName,
        'last_name' => $lastName,
        'name' => $fullName,
        'username' => $firstName !== '' ? $firstName : (string) ($row['username'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'role' => $role,
        'role_label' => role_label($role),
        'county' => (string) ($row['county'] ?? ''),
        'town' => (string) ($row['town'] ?? ''),
        'address_line' => (string) ($row['address_line'] ?? ''),
        'profile_picture' => $profilePicture,
        'created_by_marketer_id' => isset($row['created_by_marketer_id']) ? (int) $row['created_by_marketer_id'] : null,
        'status' => (string) ($row['status'] ?? 'active'),
    ];
}

function decode_json_array($value) {
    if (empty($value)) {
        return [];
    }

    if (is_array($value)) {
        return array_values($value);
    }

    if (!is_string($value)) {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function encode_json_array($value) {
    return json_encode(array_values((array) $value));
}

function find_or_create_country_id($name) {
    $db = connect_db();
    $cleanName = trim((string) $name);
    if (!$db || $cleanName === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT id FROM countries WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
    $stmt->bind_param('s', $cleanName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int) $row['id'];
    }

    $insert = $db->prepare('INSERT INTO countries (name) VALUES (?)');
    $insert->bind_param('s', $cleanName);
    $insert->execute();
    return (int) $insert->insert_id;
}

function find_or_create_county_id($countryId, $name) {
    $db = connect_db();
    $cleanName = trim((string) $name);
    $countryId = (int) $countryId;
    if (!$db || $countryId <= 0 || $cleanName === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT id FROM counties WHERE country_id = ? AND LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
    $stmt->bind_param('is', $countryId, $cleanName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int) $row['id'];
    }

    $insert = $db->prepare('INSERT INTO counties (country_id, name) VALUES (?, ?)');
    $insert->bind_param('is', $countryId, $cleanName);
    $insert->execute();
    return (int) $insert->insert_id;
}

function find_or_create_sub_county_id($countyId, $name) {
    $db = connect_db();
    $cleanName = trim((string) $name);
    $countyId = (int) $countyId;
    if (!$db || $countyId <= 0 || $cleanName === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT id FROM sub_counties WHERE county_id = ? AND LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
    $stmt->bind_param('is', $countyId, $cleanName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int) $row['id'];
    }

    $insert = $db->prepare('INSERT INTO sub_counties (county_id, name) VALUES (?, ?)');
    $insert->bind_param('is', $countyId, $cleanName);
    $insert->execute();
    return (int) $insert->insert_id;
}

function find_or_create_ward_id($subCountyId, $name) {
    $db = connect_db();
    $cleanName = trim((string) $name);
    $subCountyId = (int) $subCountyId;
    if (!$db || $subCountyId <= 0 || $cleanName === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT id FROM wards WHERE sub_county_id = ? AND LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
    $stmt->bind_param('is', $subCountyId, $cleanName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int) $row['id'];
    }

    $insert = $db->prepare('INSERT INTO wards (sub_county_id, name) VALUES (?, ?)');
    $insert->bind_param('is', $subCountyId, $cleanName);
    $insert->execute();
    return (int) $insert->insert_id;
}

function resolve_location_hierarchy_ids($countryName, $countyName, $subCountyName, $wardName) {
    $countryId = find_or_create_country_id($countryName);
    $countyId = find_or_create_county_id($countryId, $countyName);
    $subCountyId = find_or_create_sub_county_id($countyId, $subCountyName);
    $wardId = find_or_create_ward_id($subCountyId, $wardName);

    return [
        'country_id' => $countryId,
        'county_id' => $countyId,
        'sub_county_id' => $subCountyId,
        'ward_id' => $wardId,
    ];
}

function get_location_hierarchy_data() {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = "SELECT c.name AS country_name, co.name AS county_name, sc.name AS sub_county_name, w.name AS ward_name
            FROM countries c
            INNER JOIN counties co ON co.country_id = c.id
            INNER JOIN sub_counties sc ON sc.county_id = co.id
            INNER JOIN wards w ON w.sub_county_id = sc.id
            WHERE c.is_active = 1 AND co.is_active = 1 AND sc.is_active = 1 AND w.is_active = 1
            ORDER BY c.name, co.name, sc.name, w.name";

    $result = $db->query($sql);
    if (!$result) {
        return [];
    }

    $tree = [];
    while ($row = $result->fetch_assoc()) {
        $country = (string) ($row['country_name'] ?? '');
        $county = (string) ($row['county_name'] ?? '');
        $subCounty = (string) ($row['sub_county_name'] ?? '');
        $ward = (string) ($row['ward_name'] ?? '');
        if ($country === '' || $county === '' || $subCounty === '' || $ward === '') {
            continue;
        }
        if (!isset($tree[$country])) {
            $tree[$country] = [];
        }
        if (!isset($tree[$country][$county])) {
            $tree[$country][$county] = [];
        }
        if (!isset($tree[$country][$county][$subCounty])) {
            $tree[$country][$county][$subCounty] = [];
        }
        $tree[$country][$county][$subCounty][] = $ward;
    }

    return $tree;
}

function ensure_default_location_hierarchy_seed() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $countResult = $db->query('SELECT COUNT(*) AS total FROM wards');
    $wardCount = (int) (($countResult ? $countResult->fetch_assoc()['total'] : 0) ?? 0);
    if ($wardCount > 0) {
        return;
    }

    $seed = [
        'Kenya' => [
            'Nairobi' => [
                'Westlands' => ['Parklands', 'Kitisuru', 'Karura'],
                'Kasarani' => ['Mwiki', 'Clay City', 'Ruai'],
                'Embakasi' => ['Upper Savannah', 'Kwa Njenga', 'Utawala'],
                'Dagoretti' => ['Kawangware', 'Mutu-ini', 'Ngando'],
            ],
            'Kiambu' => [
                'Ruiru' => ['Gitothua', 'Kahawa Sukari', 'Mwiki'],
                'Thika' => ['Township', 'Hospital', 'Kamenu'],
                'Juja' => ['Kalimoni', 'Witeithie', 'Murera'],
            ],
            'Mombasa' => [
                'Nyali' => ['Frere Town', 'Kongowea', 'Kadzandani'],
                'Kisauni' => ['Mjambere', 'Junda', 'Bamburi'],
            ],
            'Machakos' => [
                'Athi River' => ['Kinanie', 'Mavoko', 'Syokimau'],
            ],
        ],
    ];

    foreach ($seed as $countryName => $counties) {
        foreach ($counties as $countyName => $subCounties) {
            foreach ($subCounties as $subCountyName => $wards) {
                foreach ((array) $wards as $wardName) {
                    resolve_location_hierarchy_ids($countryName, $countyName, $subCountyName, (string) $wardName);
                }
            }
        }
    }
}

function normalize_property($row) {
    $decodedImages = decode_json_array($row['images'] ?? '');
    if ($decodedImages === [] || str_starts_with((string) ($decodedImages[0] ?? ''), 'http')) {
        $decodedImages = default_property_images();
    }
    $coverImage = (string) ($row['cover_image'] ?? '');
    if ($coverImage === '' && !empty($decodedImages[0])) {
        $coverImage = (string) $decodedImages[0];
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'owner_id' => (int) ($row['owner_id'] ?? 0),
        'user_id' => (int) ($row['user_id'] ?? ($row['owner_id'] ?? 0)),
        'marketer_id' => (int) ($row['marketer_id'] ?? 0),
        'title' => $row['title'] ?? '',
        'listing_type' => $row['listing_type'] ?? 'rent',
        'category' => $row['category'] ?? 'residential',
        'property_type' => $row['property_type'] ?? '',
        'property_type_id' => (int) ($row['property_type_id'] ?? 0),
        'room_type' => $row['room_type'] ?? '',
        'location' => $row['location'] ?? '',
        'price' => (float) ($row['price'] ?? 0),
        'deposit' => (float) ($row['deposit'] ?? 0),
        'status' => $row['status'] ?? 'available',
        'owner_name' => !empty($row['owner_name']) ? $row['owner_name'] : 'Owner',
        'hidden_location' => $row['hidden_location'] ?? '',
        'description' => $row['description'] ?? '',
        'images' => $decodedImages,
        'cover_image' => $coverImage,
        'hidden_images' => decode_json_array($row['hidden_images'] ?? ''),
        'image_descriptions' => decode_json_array($row['image_descriptions'] ?? ''),
        'contact' => $row['contact'] ?? '',
        'building_name' => $row['building_name'] ?? '',
        'floor' => $row['floor'] ?? '',
        'wing' => $row['wing'] ?? '',
        'room_number' => $row['room_number'] ?? '',
        'listing_scope' => $row['listing_scope'] ?? 'entire_property',
        'purpose' => $row['purpose'] ?? 'rent',
        'parent_property_id' => (int) ($row['parent_property_id'] ?? 0),
        'bedrooms' => array_key_exists('bedrooms', $row) && $row['bedrooms'] !== null ? (int) $row['bedrooms'] : null,
        'bathrooms' => array_key_exists('bathrooms', $row) && $row['bathrooms'] !== null ? (int) $row['bathrooms'] : null,
        'parking' => array_key_exists('parking', $row) && $row['parking'] !== null ? (int) $row['parking'] : null,
        'furnished' => (bool) ($row['furnished'] ?? 0),
        'serviced' => (bool) ($row['serviced'] ?? 0),
        'pet_friendly' => (bool) ($row['pet_friendly'] ?? 0),
        'wheelchair_access' => (bool) ($row['wheelchair_access'] ?? 0),
        'property_condition' => $row['property_condition'] ?? '',
        'property_context' => $row['property_context'] ?? 'standalone',
        'country' => $row['country'] ?? '',
        'country_id' => isset($row['country_id']) ? (int) $row['country_id'] : null,
        'county' => $row['county'] ?? '',
        'county_id' => isset($row['county_id']) ? (int) $row['county_id'] : null,
        'city' => $row['city'] ?? '',
        'sub_county_id' => isset($row['sub_county_id']) ? (int) $row['sub_county_id'] : null,
        'ward' => $row['ward'] ?? '',
        'ward_id' => isset($row['ward_id']) ? (int) $row['ward_id'] : null,
        'estate' => $row['estate'] ?? '',
        'street' => $row['street'] ?? '',
        'block' => $row['block'] ?? '',
        'floor_number' => $row['floor_number'] ?? '',
        'unit_number' => $row['unit_number'] ?? '',
        'postal_code' => $row['postal_code'] ?? '',
        'landmark' => $row['landmark'] ?? '',
        'google_maps_link' => $row['google_maps_link'] ?? '',
        'total_units' => max(0, (int) ($row['total_units'] ?? ($row['available_units'] ?? 1))),
        'available_units' => max(0, (int) ($row['total_units'] ?? ($row['available_units'] ?? 1))),
        'offer_enabled' => !empty($row['offer_enabled']),
        'offer_price' => array_key_exists('offer_price', $row) && $row['offer_price'] !== null ? (float) $row['offer_price'] : null,
        'offer_reason' => $row['offer_reason'] ?? '',
        'hidden_details' => json_decode((string) ($row['hidden_details'] ?? '{}'), true) ?: [],
        'verified' => (bool) ($row['verified'] ?? 0),
        'verification_status' => $row['verification_status'] ?? (!empty($row['verified']) ? 'approved' : 'pending_verification'),
        'verification_reason' => $row['verification_reason'] ?? '',
        'created_by_role' => canonical_role($row['created_by_role'] ?? 'property_owner'),
        'amenities' => [],
        'property_type_name' => '',
    ];
}

function get_property_types($category = '', $includeInactive = false) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT * FROM property_types WHERE 1=1';
    $params = [];
    $types = '';
    if (!$includeInactive) {
        $sql .= ' AND is_active = 1';
    }
    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
        $types .= 's';
    }

    $sql .= ' ORDER BY name ASC';
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function get_amenities() {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT * FROM amenities WHERE is_active = 1 ORDER BY name ASC');
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function get_all_amenities($includeInactive = true) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT * FROM amenities';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY name ASC';

    $result = $db->query($sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function create_property_type($name, $category = 'residential') {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('INSERT INTO property_types (name, category) VALUES (?, ?)');
    $stmt->bind_param('ss', $name, $category);
    $stmt->execute();
    return $stmt->insert_id;
}

function create_amenity($name) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('INSERT INTO amenities (name) VALUES (?)');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    return $stmt->insert_id;
}

function update_property_type_option($id, $name, $category, $isActive = 1) {
    $db = connect_db();
    if (!$db || (int) $id <= 0) {
        return false;
    }

    $name = trim((string) $name);
    $category = trim((string) $category);
    $isActive = (int) $isActive === 1 ? 1 : 0;
    if ($name === '' || $category === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE property_types SET name = ?, category = ?, is_active = ? WHERE id = ?');
    $stmt->bind_param('ssii', $name, $category, $isActive, $id);
    return $stmt->execute();
}

function deactivate_property_type_option($id) {
    $db = connect_db();
    if (!$db || (int) $id <= 0) {
        return false;
    }

    $stmt = $db->prepare('UPDATE property_types SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

function update_amenity_option($id, $name, $isActive = 1) {
    $db = connect_db();
    if (!$db || (int) $id <= 0) {
        return false;
    }

    $name = trim((string) $name);
    $isActive = (int) $isActive === 1 ? 1 : 0;
    if ($name === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE amenities SET name = ?, is_active = ? WHERE id = ?');
    $stmt->bind_param('sii', $name, $isActive, $id);
    return $stmt->execute();
}

function deactivate_amenity_option($id) {
    $db = connect_db();
    if (!$db || (int) $id <= 0) {
        return false;
    }

    $stmt = $db->prepare('UPDATE amenities SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

function get_countries($includeInactive = false) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT id, name, is_active, created_at FROM countries';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY name ASC';

    $result = $db->query($sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_counties($countryId = 0, $includeInactive = false) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT co.id, co.country_id, c.name AS country_name, co.name, co.is_active, co.created_at
            FROM counties co
            INNER JOIN countries c ON c.id = co.country_id
            WHERE 1=1';
    $params = [];
    $types = '';

    if ((int) $countryId > 0) {
        $sql .= ' AND co.country_id = ?';
        $params[] = (int) $countryId;
        $types .= 'i';
    }
    if (!$includeInactive) {
        $sql .= ' AND co.is_active = 1';
    }
    $sql .= ' ORDER BY c.name ASC, co.name ASC';

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_sub_counties($countyId = 0, $includeInactive = false) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT sc.id, sc.county_id, co.country_id, c.name AS country_name, co.name AS county_name, sc.name, sc.is_active, sc.created_at
            FROM sub_counties sc
            INNER JOIN counties co ON co.id = sc.county_id
            INNER JOIN countries c ON c.id = co.country_id
            WHERE 1=1';
    $params = [];
    $types = '';

    if ((int) $countyId > 0) {
        $sql .= ' AND sc.county_id = ?';
        $params[] = (int) $countyId;
        $types .= 'i';
    }
    if (!$includeInactive) {
        $sql .= ' AND sc.is_active = 1';
    }
    $sql .= ' ORDER BY c.name ASC, co.name ASC, sc.name ASC';

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_wards($subCountyId = 0, $includeInactive = false) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT w.id, w.sub_county_id, sc.county_id, co.country_id, c.name AS country_name, co.name AS county_name, sc.name AS sub_county_name, w.name, w.is_active, w.created_at
            FROM wards w
            INNER JOIN sub_counties sc ON sc.id = w.sub_county_id
            INNER JOIN counties co ON co.id = sc.county_id
            INNER JOIN countries c ON c.id = co.country_id
            WHERE 1=1';
    $params = [];
    $types = '';

    if ((int) $subCountyId > 0) {
        $sql .= ' AND w.sub_county_id = ?';
        $params[] = (int) $subCountyId;
        $types .= 'i';
    }
    if (!$includeInactive) {
        $sql .= ' AND w.is_active = 1';
    }
    $sql .= ' ORDER BY c.name ASC, co.name ASC, sc.name ASC, w.name ASC';

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function create_location_country($name) {
    $db = connect_db();
    $name = trim((string) $name);
    if (!$db || $name === '') {
        return null;
    }
    return find_or_create_country_id($name);
}

function create_location_county($countryId, $name) {
    $db = connect_db();
    $name = trim((string) $name);
    $countryId = (int) $countryId;
    if (!$db || $countryId <= 0 || $name === '') {
        return null;
    }
    return find_or_create_county_id($countryId, $name);
}

function create_location_sub_county($countyId, $name) {
    $db = connect_db();
    $name = trim((string) $name);
    $countyId = (int) $countyId;
    if (!$db || $countyId <= 0 || $name === '') {
        return null;
    }
    return find_or_create_sub_county_id($countyId, $name);
}

function create_location_ward($subCountyId, $name) {
    $db = connect_db();
    $name = trim((string) $name);
    $subCountyId = (int) $subCountyId;
    if (!$db || $subCountyId <= 0 || $name === '') {
        return null;
    }
    return find_or_create_ward_id($subCountyId, $name);
}

function update_location_country($id, $name, $isActive = 1) {
    $db = connect_db();
    $id = (int) $id;
    $name = trim((string) $name);
    $isActive = (int) $isActive === 1 ? 1 : 0;
    if (!$db || $id <= 0 || $name === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE countries SET name = ?, is_active = ? WHERE id = ?');
    $stmt->bind_param('sii', $name, $isActive, $id);
    return $stmt->execute();
}

function update_location_county($id, $countryId, $name, $isActive = 1) {
    $db = connect_db();
    $id = (int) $id;
    $countryId = (int) $countryId;
    $name = trim((string) $name);
    $isActive = (int) $isActive === 1 ? 1 : 0;
    if (!$db || $id <= 0 || $countryId <= 0 || $name === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE counties SET country_id = ?, name = ?, is_active = ? WHERE id = ?');
    $stmt->bind_param('isii', $countryId, $name, $isActive, $id);
    return $stmt->execute();
}

function update_location_sub_county($id, $countyId, $name, $isActive = 1) {
    $db = connect_db();
    $id = (int) $id;
    $countyId = (int) $countyId;
    $name = trim((string) $name);
    $isActive = (int) $isActive === 1 ? 1 : 0;
    if (!$db || $id <= 0 || $countyId <= 0 || $name === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE sub_counties SET county_id = ?, name = ?, is_active = ? WHERE id = ?');
    $stmt->bind_param('isii', $countyId, $name, $isActive, $id);
    return $stmt->execute();
}

function update_location_ward($id, $subCountyId, $name, $isActive = 1) {
    $db = connect_db();
    $id = (int) $id;
    $subCountyId = (int) $subCountyId;
    $name = trim((string) $name);
    $isActive = (int) $isActive === 1 ? 1 : 0;
    if (!$db || $id <= 0 || $subCountyId <= 0 || $name === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE wards SET sub_county_id = ?, name = ?, is_active = ? WHERE id = ?');
    $stmt->bind_param('isii', $subCountyId, $name, $isActive, $id);
    return $stmt->execute();
}

function deactivate_location_country($countryId) {
    $db = connect_db();
    $countryId = (int) $countryId;
    if (!$db || $countryId <= 0) {
        return false;
    }

    $db->begin_transaction();
    try {
        $stmtCountry = $db->prepare('UPDATE countries SET is_active = 0 WHERE id = ?');
        $stmtCountry->bind_param('i', $countryId);
        $stmtCountry->execute();

        $stmtCounty = $db->prepare('UPDATE counties SET is_active = 0 WHERE country_id = ?');
        $stmtCounty->bind_param('i', $countryId);
        $stmtCounty->execute();

        $stmtSubCounty = $db->prepare('UPDATE sub_counties sc INNER JOIN counties co ON co.id = sc.county_id SET sc.is_active = 0 WHERE co.country_id = ?');
        $stmtSubCounty->bind_param('i', $countryId);
        $stmtSubCounty->execute();

        $stmtWard = $db->prepare('UPDATE wards w INNER JOIN sub_counties sc ON sc.id = w.sub_county_id INNER JOIN counties co ON co.id = sc.county_id SET w.is_active = 0 WHERE co.country_id = ?');
        $stmtWard->bind_param('i', $countryId);
        $stmtWard->execute();

        $db->commit();
        return true;
    } catch (Throwable $error) {
        $db->rollback();
        return false;
    }
}

function deactivate_location_county($countyId) {
    $db = connect_db();
    $countyId = (int) $countyId;
    if (!$db || $countyId <= 0) {
        return false;
    }

    $db->begin_transaction();
    try {
        $stmtCounty = $db->prepare('UPDATE counties SET is_active = 0 WHERE id = ?');
        $stmtCounty->bind_param('i', $countyId);
        $stmtCounty->execute();

        $stmtSubCounty = $db->prepare('UPDATE sub_counties SET is_active = 0 WHERE county_id = ?');
        $stmtSubCounty->bind_param('i', $countyId);
        $stmtSubCounty->execute();

        $stmtWard = $db->prepare('UPDATE wards w INNER JOIN sub_counties sc ON sc.id = w.sub_county_id SET w.is_active = 0 WHERE sc.county_id = ?');
        $stmtWard->bind_param('i', $countyId);
        $stmtWard->execute();

        $db->commit();
        return true;
    } catch (Throwable $error) {
        $db->rollback();
        return false;
    }
}

function deactivate_location_sub_county($subCountyId) {
    $db = connect_db();
    $subCountyId = (int) $subCountyId;
    if (!$db || $subCountyId <= 0) {
        return false;
    }

    $db->begin_transaction();
    try {
        $stmtSubCounty = $db->prepare('UPDATE sub_counties SET is_active = 0 WHERE id = ?');
        $stmtSubCounty->bind_param('i', $subCountyId);
        $stmtSubCounty->execute();

        $stmtWard = $db->prepare('UPDATE wards SET is_active = 0 WHERE sub_county_id = ?');
        $stmtWard->bind_param('i', $subCountyId);
        $stmtWard->execute();

        $db->commit();
        return true;
    } catch (Throwable $error) {
        $db->rollback();
        return false;
    }
}

function deactivate_location_ward($wardId) {
    $db = connect_db();
    $wardId = (int) $wardId;
    if (!$db || $wardId <= 0) {
        return false;
    }

    $stmt = $db->prepare('UPDATE wards SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $wardId);
    return $stmt->execute();
}

function get_property_type_name($propertyTypeId, $fallback = '') {
    $db = connect_db();
    if (!$db || !$propertyTypeId) {
        return $fallback;
    }

    $stmt = $db->prepare('SELECT name FROM property_types WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $propertyTypeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['name'] ?? $fallback;
}

function get_property_amenities($propertyId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT a.id, a.name, pa.usage_scope FROM property_amenities pa JOIN amenities a ON a.id = pa.amenity_id WHERE pa.property_id = ? ORDER BY a.name ASC');
    $stmt->bind_param('i', $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function sync_property_amenities($propertyId, $amenityIds) {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $stmt = $db->prepare('DELETE FROM property_amenities WHERE property_id = ?');
    $stmt->bind_param('i', $propertyId);
    $stmt->execute();

    $amenityScopeMap = [];
    if (is_array($amenityIds) && isset($amenityIds['_scope']) && is_array($amenityIds['_scope'])) {
        $amenityScopeMap = $amenityIds['_scope'];
        unset($amenityIds['_scope']);
    }

    foreach (array_unique(array_map('intval', (array) $amenityIds)) as $amenityId) {
        if ($amenityId <= 0) {
            continue;
        }
        $scope = strtolower(trim((string) ($amenityScopeMap[$amenityId] ?? 'shared')));
        if (!in_array($scope, ['private', 'shared'], true)) {
            $scope = 'shared';
        }
        $insert = $db->prepare('INSERT INTO property_amenities (property_id, amenity_id, usage_scope) VALUES (?, ?, ?)');
        $insert->bind_param('iis', $propertyId, $amenityId, $scope);
        $insert->execute();
    }
}

function ensure_properties_have_amenities() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $sql = "INSERT INTO property_amenities (property_id, amenity_id, usage_scope)
            SELECT p.id, a.id, 'shared'
            FROM properties p
            JOIN amenities a ON a.is_active = 1
            LEFT JOIN property_amenities pa ON pa.property_id = p.id AND pa.amenity_id = a.id
            WHERE pa.property_id IS NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM property_amenities existing
                  WHERE existing.property_id = p.id
              )";
    $db->query($sql);
}

function ensure_default_amenities() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $defaults = [
        'WiFi',
        'Parking',
        'Swimming Pool',
        'Gym',
        'CCTV',
        'Lift',
        'Backup Generator',
        'Garden',
        'Borehole Water',
        'Solar Water Heating',
        'Kids Play Area',
        'Rooftop Terrace',
        'Laundry Area',
        'Electric Fence',
    ];

    $existing = [];
    $result = $db->query('SELECT name FROM amenities');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[strtolower(trim((string) ($row['name'] ?? '')))] = true;
        }
    }

    foreach ($defaults as $amenityName) {
        $key = strtolower(trim($amenityName));
        if (isset($existing[$key])) {
            continue;
        }
        create_amenity($amenityName);
        $existing[$key] = true;
    }
}

function ensure_property_role_assignments() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $propertyColumns = [];
    $propertyColumnsResult = $db->query('SHOW COLUMNS FROM properties');
    if ($propertyColumnsResult) {
        while ($row = $propertyColumnsResult->fetch_assoc()) {
            $propertyColumns[] = (string) ($row['Field'] ?? '');
        }
    }
    if (!in_array('user_id', $propertyColumns, true)) {
        $db->query('ALTER TABLE properties ADD COLUMN user_id INT DEFAULT NULL');
        $propertyColumns[] = 'user_id';
    }
    if (!in_array('marketer_id', $propertyColumns, true)) {
        $db->query('ALTER TABLE properties ADD COLUMN marketer_id INT DEFAULT NULL');
        $propertyColumns[] = 'marketer_id';
    }

    $owners = [];
    $ownerResult = $db->query("SELECT id, name, phone FROM users WHERE role = 'property_owner' ORDER BY id ASC");
    if ($ownerResult) {
        while ($row = $ownerResult->fetch_assoc()) {
            $owners[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => trim((string) ($row['name'] ?? '')),
                'phone' => trim((string) ($row['phone'] ?? '')),
            ];
        }
    }

    $marketers = [];
    $marketerResult = $db->query("SELECT id FROM users WHERE role = 'marketer' ORDER BY id ASC");
    if ($marketerResult) {
        while ($row = $marketerResult->fetch_assoc()) {
            $marketers[] = (int) ($row['id'] ?? 0);
        }
    }

    if ($owners !== []) {
        $orphanProperties = $db->query("SELECT id, user_id FROM properties WHERE user_id IS NULL OR user_id = 0 ORDER BY id ASC");
        if ($orphanProperties) {
            $ownerIndex = 0;
            while ($property = $orphanProperties->fetch_assoc()) {
                $propertyId = (int) ($property['id'] ?? 0);
                if ($propertyId <= 0) {
                    continue;
                }
                $owner = $owners[$ownerIndex % count($owners)];
                $ownerIndex++;
                $stmt = $db->prepare("UPDATE properties SET user_id = ?, owner_name = ?, contact = COALESCE(NULLIF(contact, ''), ?) WHERE id = ?");
                $stmt->bind_param('issi', $owner['id'], $owner['name'], $owner['phone'], $propertyId);
                $stmt->execute();
            }
        }
    }

    $ownerNameFix = $db->query("SELECT p.id, p.user_id, u.name, u.phone FROM properties p JOIN users u ON u.id = p.user_id WHERE (p.owner_name IS NULL OR p.owner_name = '')");
    if ($ownerNameFix) {
        while ($row = $ownerNameFix->fetch_assoc()) {
            $propertyId = (int) ($row['id'] ?? 0);
            $ownerName = trim((string) ($row['name'] ?? ''));
            $ownerPhone = trim((string) ($row['phone'] ?? ''));
            if ($propertyId <= 0 || $ownerName === '') {
                continue;
            }
            $stmt = $db->prepare("UPDATE properties SET owner_name = ?, contact = COALESCE(NULLIF(contact, ''), ?) WHERE id = ?");
            $stmt->bind_param('ssi', $ownerName, $ownerPhone, $propertyId);
            $stmt->execute();
        }
    }

    if ($marketers !== []) {
        $unassigned = $db->query('SELECT id FROM properties WHERE marketer_id IS NULL OR marketer_id = 0 ORDER BY id ASC');
        if ($unassigned) {
            $marketerIndex = 0;
            while ($row = $unassigned->fetch_assoc()) {
                $propertyId = (int) ($row['id'] ?? 0);
                if ($propertyId <= 0) {
                    continue;
                }
                $marketerId = $marketers[$marketerIndex % count($marketers)];
                $marketerIndex++;
                $stmt = $db->prepare('UPDATE properties SET marketer_id = ? WHERE id = ?');
                $stmt->bind_param('ii', $marketerId, $propertyId);
                $stmt->execute();
            }
        }
    }
}

function backfill_location_hierarchy_from_properties() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $result = $db->query('SELECT id, country, county, city, ward FROM properties');
    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $country = trim((string) ($row['country'] ?? ''));
        $county = trim((string) ($row['county'] ?? ''));
        $subCounty = trim((string) ($row['city'] ?? ''));
        $ward = trim((string) ($row['ward'] ?? ''));
        if ($country === '' || $county === '' || $subCounty === '' || $ward === '') {
            continue;
        }

        $ids = resolve_location_hierarchy_ids($country, $county, $subCounty, $ward);
        $countryId = (int) ($ids['country_id'] ?? 0);
        $countyId = (int) ($ids['county_id'] ?? 0);
        $subCountyId = (int) ($ids['sub_county_id'] ?? 0);
        $wardId = (int) ($ids['ward_id'] ?? 0);
        $propertyId = (int) ($row['id'] ?? 0);

        if ($countryId <= 0 || $countyId <= 0 || $subCountyId <= 0 || $wardId <= 0 || $propertyId <= 0) {
            continue;
        }

        $update = $db->prepare('UPDATE properties SET country_id = ?, county_id = ?, sub_county_id = ?, ward_id = ? WHERE id = ?');
        $update->bind_param('iiiii', $countryId, $countyId, $subCountyId, $wardId, $propertyId);
        $update->execute();
    }
}

function enrich_property($property) {
    if (!empty($property['id'])) {
        $property['amenities'] = get_property_amenities((int) $property['id']);
        $property['property_type_name'] = get_property_type_name((int) ($property['property_type_id'] ?? 0), $property['property_type'] ?? '');
    }
    return $property;
}

function seed_demo_data() {
    global $conn;
    $db = connect_db();
    if (!$db) {
        return;
    }

    $userCount = $db->query('SELECT COUNT(*) as total FROM users')->fetch_assoc()['total'] ?? 0;
    if ((int) $userCount === 0) {
        foreach ([
            ['first_name' => 'Grace', 'last_name' => 'Mwangi', 'username' => 'grace.mwangi', 'email' => 'owner@example.com', 'phone' => '+254700000101', 'password' => 'owner123', 'role' => 'property_owner', 'county' => 'Nairobi', 'town' => 'Westlands'],
            ['first_name' => 'Paul', 'last_name' => 'Njoroge', 'username' => 'paul.njoroge', 'email' => 'owner2@example.com', 'phone' => '+254700000111', 'password' => 'owner223', 'role' => 'property_owner', 'county' => 'Nairobi', 'town' => 'Kilimani'],
            ['first_name' => 'Lydia', 'last_name' => 'Kimani', 'username' => 'lydia.kimani', 'email' => 'owner3@example.com', 'phone' => '+254700000112', 'password' => 'owner323', 'role' => 'property_owner', 'county' => 'Kiambu', 'town' => 'Ruiru'],
            ['first_name' => 'Dennis', 'last_name' => 'Mugo', 'username' => 'dennis.mugo', 'email' => 'owner4@example.com', 'phone' => '+254700000115', 'password' => 'owner423', 'role' => 'property_owner', 'county' => 'Machakos', 'town' => 'Athi River'],
            ['first_name' => 'System', 'last_name' => 'Admin', 'username' => 'system.admin', 'email' => 'admin@example.com', 'phone' => '+254700000102', 'password' => 'admin123', 'role' => 'admin', 'county' => 'Nairobi', 'town' => 'Upper Hill'],
            ['first_name' => 'Alice', 'last_name' => 'Naliaka', 'username' => 'alice.naliaka', 'email' => 'admin2@example.com', 'phone' => '+254700000116', 'password' => 'admin223', 'role' => 'admin', 'county' => 'Nairobi', 'town' => 'Riverside'],
            ['first_name' => 'Peter', 'last_name' => 'Kariuki', 'username' => 'peter.kariuki', 'email' => 'admin3@example.com', 'phone' => '+254700000117', 'password' => 'admin323', 'role' => 'admin', 'county' => 'Kiambu', 'town' => 'Thika'],
            ['first_name' => 'Faith', 'last_name' => 'Chebet', 'username' => 'faith.chebet', 'email' => 'admin4@example.com', 'phone' => '+254700000118', 'password' => 'admin423', 'role' => 'admin', 'county' => 'Nakuru', 'town' => 'Nakuru'],
            ['first_name' => 'Brian', 'last_name' => 'Kiprop', 'username' => 'brian.kiprop', 'email' => 'seeker@example.com', 'phone' => '+254700000103', 'password' => 'visitor123', 'role' => 'property_seeker', 'county' => 'Nairobi', 'town' => 'Kasarani'],
            ['first_name' => 'Janet', 'last_name' => 'Atieno', 'username' => 'janet.atieno', 'email' => 'seeker2@example.com', 'phone' => '+254700000113', 'password' => 'visitor223', 'role' => 'property_seeker', 'county' => 'Nairobi', 'town' => 'South B'],
            ['first_name' => 'Esther', 'last_name' => 'Wafula', 'username' => 'esther.wafula', 'email' => 'seeker3@example.com', 'phone' => '+254700000119', 'password' => 'visitor323', 'role' => 'property_seeker', 'county' => 'Uasin Gishu', 'town' => 'Eldoret'],
            ['first_name' => 'Noah', 'last_name' => 'Kamau', 'username' => 'noah.kamau', 'email' => 'seeker4@example.com', 'phone' => '+254700000120', 'password' => 'visitor423', 'role' => 'property_seeker', 'county' => 'Mombasa', 'town' => 'Nyali'],
            ['first_name' => 'Mercy', 'last_name' => 'Achieng', 'username' => 'mercy.achieng', 'email' => 'marketer@example.com', 'phone' => '+254700000104', 'password' => 'marketer123', 'role' => 'marketer', 'county' => 'Nairobi', 'town' => 'Kilimani'],
            ['first_name' => 'Kevin', 'last_name' => 'Mutua', 'username' => 'kevin.mutua', 'email' => 'marketer2@example.com', 'phone' => '+254700000114', 'password' => 'marketer223', 'role' => 'marketer', 'county' => 'Nairobi', 'town' => 'Embakasi'],
            ['first_name' => 'Diana', 'last_name' => 'Wairimu', 'username' => 'diana.wairimu', 'email' => 'marketer3@example.com', 'phone' => '+254700000121', 'password' => 'marketer323', 'role' => 'marketer', 'county' => 'Nairobi', 'town' => 'Karen'],
            ['first_name' => 'Sam', 'last_name' => 'Muli', 'username' => 'sam.muli', 'email' => 'marketer4@example.com', 'phone' => '+254700000122', 'password' => 'marketer423', 'role' => 'marketer', 'county' => 'Kiambu', 'town' => 'Juja'],
        ] as $seedUser) {
            create_user($seedUser);
        }
    }

    $propertyTypeCount = $db->query('SELECT COUNT(*) as total FROM property_types')->fetch_assoc()['total'] ?? 0;
    if ((int) $propertyTypeCount === 0) {
        create_property_type('Apartment Unit', 'residential');
        create_property_type('Office', 'office');
        create_property_type('Hotel Room', 'residential');
        create_property_type('Residential Plot', 'residential');
    }

    ensure_default_amenities();

    $propertyCount = $db->query('SELECT COUNT(*) as total FROM properties')->fetch_assoc()['total'] ?? 0;
    if ((int) $propertyCount < 12) {
        $ownerMap = [];
        foreach (['owner@example.com', 'owner2@example.com', 'owner3@example.com', 'owner4@example.com'] as $ownerEmail) {
            $stmtOwner = $db->prepare('SELECT id, name FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1');
            $normalizedEmail = strtolower(trim($ownerEmail));
            $stmtOwner->bind_param('s', $normalizedEmail);
            $stmtOwner->execute();
            $ownerRow = $stmtOwner->get_result()->fetch_assoc();
            if ($ownerRow) {
                $ownerMap[$ownerEmail] = ['id' => (int) $ownerRow['id'], 'name' => (string) $ownerRow['name']];
            }
        }

        $marketerMap = [];
        foreach (['marketer@example.com', 'marketer2@example.com', 'marketer3@example.com', 'marketer4@example.com'] as $marketerEmail) {
            $stmtMarketer = $db->prepare('SELECT id FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1');
            $normalizedEmail = strtolower(trim($marketerEmail));
            $stmtMarketer->bind_param('s', $normalizedEmail);
            $stmtMarketer->execute();
            $marketerRow = $stmtMarketer->get_result()->fetch_assoc();
            if ($marketerRow) {
                $marketerMap[$marketerEmail] = (int) $marketerRow['id'];
            }
        }

        $seedProperties = [
            ['title' => 'Modern 2 Bedroom Apartment', 'owner_email' => 'owner@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Apartment Unit', 'room_type' => '2 Bedroom', 'price' => 45000, 'deposit' => 120000, 'location' => 'Westlands', 'description' => 'Bright and secure apartment with parking and high-speed internet.', 'status' => 'available', 'listing_scope' => 'unit', 'purpose' => 'rent', 'bedrooms' => 2, 'bathrooms' => 2, 'parking' => 1, 'furnished' => 1, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.2674,36.8103', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Westlands', 'amenities' => [1, 2, 4, 5], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'Executive Office Space', 'owner_email' => 'owner@example.com', 'listing_type' => 'sale', 'category' => 'office', 'property_type' => 'Office', 'room_type' => 'Studio', 'price' => 18500000, 'deposit' => 2500000, 'location' => 'Upper Hill', 'description' => 'Flexible office space near major corporate buildings.', 'status' => 'available', 'listing_scope' => 'entire_property', 'purpose' => 'sale', 'bedrooms' => 0, 'bathrooms' => 2, 'parking' => 3, 'serviced' => 1, 'wheelchair_access' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.3007,36.8219', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Upper Hill', 'amenities' => [1, 5, 6], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'Luxury 3 Bedroom Estate', 'owner_email' => 'owner2@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Residential Plot', 'room_type' => '3 Bedroom', 'price' => 95000, 'deposit' => 250000, 'location' => 'Kileleshwa', 'description' => 'Spacious estate with a private garden and concierge services.', 'status' => 'occupied', 'listing_scope' => 'entire_property', 'purpose' => 'rent', 'bedrooms' => 3, 'bathrooms' => 3, 'parking' => 2, 'furnished' => 1, 'pet_friendly' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.2686,36.8065', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Kileleshwa', 'amenities' => [3, 4, 7, 8], 'property_condition' => 'excellent', 'verified' => 0],
            ['title' => 'Garden View Maisonette', 'owner_email' => 'owner2@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Apartment Unit', 'room_type' => '4 Bedroom', 'price' => 160000, 'deposit' => 300000, 'location' => 'Lavington', 'description' => 'Quiet family maisonette with private garden and secure parking.', 'status' => 'available', 'listing_scope' => 'entire_property', 'purpose' => 'rent', 'bedrooms' => 4, 'bathrooms' => 4, 'parking' => 2, 'furnished' => 1, 'serviced' => 0, 'google_maps_link' => 'https://www.google.com/maps?q=-1.2762,36.7686', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Lavington', 'amenities' => [1, 2, 7], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'South B Studio Apartment', 'owner_email' => 'owner3@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Apartment Unit', 'room_type' => 'Studio', 'price' => 28000, 'deposit' => 56000, 'location' => 'South B', 'description' => 'Affordable studio close to transport routes and shopping points.', 'status' => 'available', 'listing_scope' => 'unit', 'purpose' => 'rent', 'bedrooms' => 1, 'bathrooms' => 1, 'parking' => 0, 'furnished' => 0, 'serviced' => 0, 'google_maps_link' => 'https://www.google.com/maps?q=-1.3184,36.8452', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'South B', 'amenities' => [1, 5], 'property_condition' => 'good', 'verified' => 0],
            ['title' => 'Ruiru Business Front Shop', 'owner_email' => 'owner3@example.com', 'listing_type' => 'lease', 'category' => 'business', 'property_type' => 'Office', 'room_type' => 'Retail Unit', 'price' => 72000, 'deposit' => 144000, 'location' => 'Ruiru', 'description' => 'Street-facing business space with high foot traffic and frontage.', 'status' => 'available', 'listing_scope' => 'unit', 'purpose' => 'lease', 'bedrooms' => 0, 'bathrooms' => 1, 'parking' => 1, 'furnished' => 0, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.1466,36.9601', 'country' => 'Kenya', 'county' => 'Kiambu', 'city' => 'Ruiru', 'estate' => 'Ruiru CBD', 'amenities' => [2, 5, 6], 'property_condition' => 'good', 'verified' => 0],
            ['title' => 'Kilimani Two-Bedroom Airbnb', 'owner_email' => 'owner@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Apartment Unit', 'room_type' => '2 Bedroom', 'price' => 9000, 'deposit' => 0, 'location' => 'Kilimani', 'description' => 'Fully serviced short-stay apartment ideal for business travelers.', 'status' => 'available', 'listing_scope' => 'unit', 'purpose' => 'airbnb', 'bedrooms' => 2, 'bathrooms' => 2, 'parking' => 1, 'furnished' => 1, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.2928,36.7830', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Kilimani', 'amenities' => [1, 2, 3, 4], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'Upper Hill Corporate Suite', 'owner_email' => 'owner2@example.com', 'listing_type' => 'sale', 'category' => 'office', 'property_type' => 'Office', 'room_type' => 'Executive Floor', 'price' => 32500000, 'deposit' => 3500000, 'location' => 'Upper Hill', 'description' => 'Premium corporate suite with boardroom, lobby, and backup systems.', 'status' => 'available', 'listing_scope' => 'entire_property', 'purpose' => 'sale', 'bedrooms' => 0, 'bathrooms' => 4, 'parking' => 8, 'furnished' => 0, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.3018,36.8190', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Upper Hill', 'amenities' => [1, 2, 5, 6, 7], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'Athi River Courtyard Villas', 'owner_email' => 'owner4@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Apartment Unit', 'room_type' => '3 Bedroom', 'price' => 68000, 'deposit' => 136000, 'location' => 'Athi River', 'description' => 'Modern gated villas with landscaped courtyard and family amenities.', 'status' => 'available', 'listing_scope' => 'entire_property', 'purpose' => 'rent', 'bedrooms' => 3, 'bathrooms' => 2, 'parking' => 2, 'furnished' => 0, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.4568,36.9783', 'country' => 'Kenya', 'county' => 'Machakos', 'city' => 'Athi River', 'estate' => 'Mutongoni', 'amenities' => [1, 2, 3, 8, 11], 'property_condition' => 'good', 'verified' => 0],
            ['title' => 'Karen Retail Arcade Unit', 'owner_email' => 'owner4@example.com', 'listing_type' => 'lease', 'category' => 'business', 'property_type' => 'Office', 'room_type' => 'Retail Unit', 'price' => 125000, 'deposit' => 250000, 'location' => 'Karen', 'description' => 'Prime retail arcade space with strong visibility and customer parking.', 'status' => 'available', 'listing_scope' => 'unit', 'purpose' => 'lease', 'bedrooms' => 0, 'bathrooms' => 1, 'parking' => 4, 'furnished' => 0, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-1.3188,36.7073', 'country' => 'Kenya', 'county' => 'Nairobi', 'city' => 'Nairobi', 'estate' => 'Karen', 'amenities' => [1, 2, 5, 6, 14], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'Nyali Sea Breeze Apartment', 'owner_email' => 'owner3@example.com', 'listing_type' => 'rent', 'category' => 'residential', 'property_type' => 'Apartment Unit', 'room_type' => '1 Bedroom', 'price' => 52000, 'deposit' => 104000, 'location' => 'Nyali', 'description' => 'Coastal one-bedroom apartment with balcony views and resort facilities.', 'status' => 'available', 'listing_scope' => 'unit', 'purpose' => 'rent', 'bedrooms' => 1, 'bathrooms' => 1, 'parking' => 1, 'furnished' => 1, 'serviced' => 1, 'google_maps_link' => 'https://www.google.com/maps?q=-4.0369,39.7192', 'country' => 'Kenya', 'county' => 'Mombasa', 'city' => 'Mombasa', 'estate' => 'Nyali', 'amenities' => [1, 2, 3, 4, 12], 'property_condition' => 'excellent', 'verified' => 1],
            ['title' => 'Thika Industrial Warehouse Bay', 'owner_email' => 'owner2@example.com', 'listing_type' => 'sale', 'category' => 'business', 'property_type' => 'Office', 'room_type' => 'Warehouse Bay', 'price' => 41000000, 'deposit' => 5000000, 'location' => 'Thika', 'description' => 'Large warehouse bay ideal for distribution, light manufacturing, and storage.', 'status' => 'available', 'listing_scope' => 'entire_property', 'purpose' => 'sale', 'bedrooms' => 0, 'bathrooms' => 2, 'parking' => 10, 'furnished' => 0, 'serviced' => 0, 'google_maps_link' => 'https://www.google.com/maps?q=-1.0396,37.0900', 'country' => 'Kenya', 'county' => 'Kiambu', 'city' => 'Thika', 'estate' => 'Makongeni', 'amenities' => [2, 5, 7, 13], 'property_condition' => 'good', 'verified' => 0],
        ];

        foreach ($seedProperties as $propertySeed) {
            $title = (string) $propertySeed['title'];
            $stmtExisting = $db->prepare('SELECT id FROM properties WHERE title = ? LIMIT 1');
            $stmtExisting->bind_param('s', $title);
            $stmtExisting->execute();
            if ($stmtExisting->get_result()->fetch_assoc()) {
                continue;
            }

            $ownerRef = $ownerMap[$propertySeed['owner_email']] ?? null;
            if (!$ownerRef) {
                continue;
            }

            $propertyPayload = $propertySeed;
            unset($propertyPayload['owner_email']);
            $propertyPayload['owner_name'] = (string) ($ownerRef['name'] ?? 'Property Owner');
            $propertyPayload['contact'] = (string) ($propertyPayload['contact'] ?? '+254 700 000 000');
            $propertyPayload['created_by_role'] = 'property_owner';
            $propertyPayload['images'] = default_property_images();
            $propertyPayload['cover_image'] = default_property_images()[0];
            $propertyPayload['hidden_images'] = [default_property_images()[1]];

            $created = create_property($propertyPayload, (int) $ownerRef['id']);
            if ($created) {
                $createdId = (int) ($created['id'] ?? 0);
                if ($createdId > 0) {
                    $marketerEmails = array_keys($marketerMap);
                    if ($marketerEmails !== []) {
                        $marketerKey = $marketerEmails[$createdId % count($marketerEmails)];
                        $assignedMarketerId = (int) ($marketerMap[$marketerKey] ?? 0);
                        if ($assignedMarketerId > 0) {
                            $db->query('UPDATE properties SET marketer_id = ' . $assignedMarketerId . ' WHERE id = ' . $createdId);
                        }
                    }
                }
            }
        }
    }

    $contentDefaults = site_content_defaults();
    foreach ($contentDefaults as $contentKey => $contentValue) {
        $stmt = $db->prepare('SELECT id FROM site_content WHERE content_key = ? LIMIT 1');
        $stmt->bind_param('s', $contentKey);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $insert = $db->prepare('INSERT INTO site_content (content_key, content_value) VALUES (?, ?)');
            $insert->bind_param('ss', $contentKey, $contentValue);
            $insert->execute();
        }
    }

    // Keep known demo credentials usable even when the database already has these records.
    ensure_demo_account_credentials();

    migrate_legacy_images_to_structured_folders();
}

function is_app_bootstrap_complete() {
    $db = connect_db();
    if (!$db) {
        return true; // Cannot connect; don't keep retrying heavy work on every request.
    }

    // The site_content table must exist before we can read the flag.
    $tableCheck = $db->query("SHOW TABLES LIKE 'site_content'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }

    return get_site_content('app_bootstrap_done', '0') === '1';
}

// Run the heavy schema/seed/backfill work only once, then remember that it's
// done. Previously this ran on every request (including every chat API poll),
// causing dozens of redundant DB queries per request and is the main reason
// sending/opening/closing chat felt slow.
$schemaOutdated = false;
$schemaCheckDb = connect_db();
if ($schemaCheckDb) {
    $tableCheck = $schemaCheckDb->query("SHOW TABLES LIKE 'conversation_message_attachments'");
    $schemaOutdated = !$tableCheck || $tableCheck->num_rows === 0;
    if (!$schemaOutdated) {
        $columnCheck = $schemaCheckDb->query("SHOW COLUMNS FROM conversation_messages LIKE 'is_system_event'");
        $schemaOutdated = !$columnCheck || $columnCheck->num_rows === 0;
    }
    if (!$schemaOutdated) {
        $wardColumnCheck = $schemaCheckDb->query("SHOW COLUMNS FROM properties LIKE 'ward'");
        $schemaOutdated = !$wardColumnCheck || $wardColumnCheck->num_rows === 0;
    }
    if (!$schemaOutdated) {
        $totalUnitsColumnCheck = $schemaCheckDb->query("SHOW COLUMNS FROM properties LIKE 'total_units'");
        $schemaOutdated = !$totalUnitsColumnCheck || $totalUnitsColumnCheck->num_rows === 0;
    }
    if (!$schemaOutdated) {
        $wardTableCheck = $schemaCheckDb->query("SHOW TABLES LIKE 'wards'");
        $schemaOutdated = !$wardTableCheck || $wardTableCheck->num_rows === 0;
    }
}

if (!is_app_bootstrap_complete() || $schemaOutdated) {
    ensure_schema();
    ensure_default_location_hierarchy_seed();
    seed_demo_data();
    ensure_property_role_assignments();
    backfill_location_hierarchy_from_properties();
    ensure_properties_have_amenities();
    migrate_legacy_profile_images();
    update_site_content('app_bootstrap_done', '1', 0);
}

function ensure_demo_account_credentials() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $demoAccounts = [
        [
            'first_name' => 'Grace',
            'last_name' => 'Mwangi',
            'username' => 'grace.mwangi',
            'email' => 'owner@example.com',
            'phone' => '+254700000101',
            'password' => 'owner123',
            'role' => 'property_owner',
            'county' => 'Nairobi',
            'town' => 'Westlands',
            'id_number' => 'DGH-OWNER-001',
        ],
        [
            'first_name' => 'System',
            'last_name' => 'Admin',
            'username' => 'system.admin',
            'email' => 'admin@example.com',
            'phone' => '+254700000102',
            'password' => 'admin123',
            'role' => 'admin',
            'county' => 'Nairobi',
            'town' => 'Upper Hill',
            'id_number' => 'DGH-ADMIN-001',
        ],
        [
            'first_name' => 'Brian',
            'last_name' => 'Kiprop',
            'username' => 'brian.kiprop',
            'email' => 'seeker@example.com',
            'phone' => '+254700000103',
            'password' => 'visitor123',
            'role' => 'property_seeker',
            'county' => 'Nairobi',
            'town' => 'Kasarani',
            'id_number' => 'DGH-SEEKER-001',
        ],
        [
            'first_name' => 'Mercy',
            'last_name' => 'Achieng',
            'username' => 'mercy.achieng',
            'email' => 'marketer@example.com',
            'phone' => '+254700000104',
            'password' => 'marketer123',
            'role' => 'marketer',
            'county' => 'Nairobi',
            'town' => 'Kilimani',
            'id_number' => 'DGH-MARKETER-001',
        ],
        [
            'first_name' => 'Paul',
            'last_name' => 'Njoroge',
            'username' => 'paul.njoroge',
            'email' => 'owner2@example.com',
            'phone' => '+254700000111',
            'password' => 'owner223',
            'role' => 'property_owner',
            'county' => 'Nairobi',
            'town' => 'Kilimani',
            'id_number' => 'DGH-OWNER-002',
        ],
        [
            'first_name' => 'Lydia',
            'last_name' => 'Kimani',
            'username' => 'lydia.kimani',
            'email' => 'owner3@example.com',
            'phone' => '+254700000112',
            'password' => 'owner323',
            'role' => 'property_owner',
            'county' => 'Kiambu',
            'town' => 'Ruiru',
            'id_number' => 'DGH-OWNER-003',
        ],
        [
            'first_name' => 'Dennis',
            'last_name' => 'Mugo',
            'username' => 'dennis.mugo',
            'email' => 'owner4@example.com',
            'phone' => '+254700000115',
            'password' => 'owner423',
            'role' => 'property_owner',
            'county' => 'Machakos',
            'town' => 'Athi River',
            'id_number' => 'DGH-OWNER-004',
        ],
        [
            'first_name' => 'Kevin',
            'last_name' => 'Mutua',
            'username' => 'kevin.mutua',
            'email' => 'marketer2@example.com',
            'phone' => '+254700000114',
            'password' => 'marketer223',
            'role' => 'marketer',
            'county' => 'Nairobi',
            'town' => 'Embakasi',
            'id_number' => 'DGH-MARKETER-002',
        ],
        [
            'first_name' => 'Diana',
            'last_name' => 'Wairimu',
            'username' => 'diana.wairimu',
            'email' => 'marketer3@example.com',
            'phone' => '+254700000121',
            'password' => 'marketer323',
            'role' => 'marketer',
            'county' => 'Nairobi',
            'town' => 'Karen',
            'id_number' => 'DGH-MARKETER-003',
        ],
        [
            'first_name' => 'Sam',
            'last_name' => 'Muli',
            'username' => 'sam.muli',
            'email' => 'marketer4@example.com',
            'phone' => '+254700000122',
            'password' => 'marketer423',
            'role' => 'marketer',
            'county' => 'Kiambu',
            'town' => 'Juja',
            'id_number' => 'DGH-MARKETER-004',
        ],
        [
            'first_name' => 'Janet',
            'last_name' => 'Atieno',
            'username' => 'janet.atieno',
            'email' => 'seeker2@example.com',
            'phone' => '+254700000113',
            'password' => 'visitor223',
            'role' => 'property_seeker',
            'county' => 'Nairobi',
            'town' => 'South B',
            'id_number' => 'DGH-SEEKER-002',
        ],
        [
            'first_name' => 'Esther',
            'last_name' => 'Wafula',
            'username' => 'esther.wafula',
            'email' => 'seeker3@example.com',
            'phone' => '+254700000119',
            'password' => 'visitor323',
            'role' => 'property_seeker',
            'county' => 'Uasin Gishu',
            'town' => 'Eldoret',
            'id_number' => 'DGH-SEEKER-003',
        ],
        [
            'first_name' => 'Noah',
            'last_name' => 'Kamau',
            'username' => 'noah.kamau',
            'email' => 'seeker4@example.com',
            'phone' => '+254700000120',
            'password' => 'visitor423',
            'role' => 'property_seeker',
            'county' => 'Mombasa',
            'town' => 'Nyali',
            'id_number' => 'DGH-SEEKER-004',
        ],
        [
            'first_name' => 'Alice',
            'last_name' => 'Naliaka',
            'username' => 'alice.naliaka',
            'email' => 'admin2@example.com',
            'phone' => '+254700000116',
            'password' => 'admin223',
            'role' => 'admin',
            'county' => 'Nairobi',
            'town' => 'Riverside',
            'id_number' => 'DGH-ADMIN-002',
        ],
        [
            'first_name' => 'Peter',
            'last_name' => 'Kariuki',
            'username' => 'peter.kariuki',
            'email' => 'admin3@example.com',
            'phone' => '+254700000117',
            'password' => 'admin323',
            'role' => 'admin',
            'county' => 'Kiambu',
            'town' => 'Thika',
            'id_number' => 'DGH-ADMIN-003',
        ],
        [
            'first_name' => 'Faith',
            'last_name' => 'Chebet',
            'username' => 'faith.chebet',
            'email' => 'admin4@example.com',
            'phone' => '+254700000118',
            'password' => 'admin423',
            'role' => 'admin',
            'county' => 'Nakuru',
            'town' => 'Nakuru',
            'id_number' => 'DGH-ADMIN-004',
        ],
    ];

foreach ($demoAccounts as $demoUser) {
        $email = strtolower(trim((string) $demoUser['email']));
        $password = $demoUser['password'];

        $check = $db->prepare('SELECT id, password_hash, status, role FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();

        if (!$row) {
            create_user($demoUser);
            continue;
        }

        $expectedRole = canonical_role($demoUser['role'] ?? 'property_seeker');
        $currentHash = (string) ($row['password_hash'] ?? '');
        $currentStatus = (string) ($row['status'] ?? '');
        $currentRole = canonical_role((string) ($row['role'] ?? ''));
        $needsPassword = $currentHash === '' || !password_verify($password, $currentHash);
        $needsStatus = $currentStatus !== 'active';

        // Only update when something actually changed, so existing demo accounts
        // don't get re-hashed (and re-written) on every request.
        if ($needsPassword || $needsStatus || $currentRole !== $expectedRole) {
            $passwordHash = $needsPassword ? password_hash($password, PASSWORD_DEFAULT) : $currentHash;
            $status = 'active';
            $update = $db->prepare('UPDATE users SET password_hash = ?, status = ?, role = ? WHERE id = ?');
            $update->bind_param('sssi', $passwordHash, $status, $expectedRole, (int) $row['id']);
            $update->execute();
        }
    }
}

function create_user($data, $email = null, $password = null, $role = 'property_seeker') {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    if (!is_array($data)) {
        $fullName = trim((string) $data);
        $parts = preg_split('/\s+/', $fullName) ?: ['Account'];
        $firstName = $parts[0] ?? 'Account';
        $lastName = trim(implode(' ', array_slice($parts, 1))) ?: 'User';
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string) $email,
            'phone' => 'pending-' . time(),
            'password' => (string) $password,
            'role' => $role,
        ];
    }

    $firstName = trim((string) ($data['first_name'] ?? ''));
    $lastName = trim((string) ($data['last_name'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);
    $username = trim((string) ($data['username'] ?? ''));
    if ($username === '') {
        $username = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $firstName) ?: 'user');
        $username .= '.' . substr(md5(uniqid((string) mt_rand(), true)), 0, 6);
    }
    $emailAddress = strtolower(trim((string) ($data['email'] ?? '')));
    $phoneNumber = trim((string) ($data['phone'] ?? ''));
    $plainPassword = (string) ($data['password'] ?? '');
    $userRole = canonical_role($data['role'] ?? 'property_seeker');
    $county = trim((string) ($data['county'] ?? ''));
    $subCounty = trim((string) ($data['sub_county'] ?? ''));
    $ward = trim((string) ($data['ward'] ?? ''));
    $town = trim((string) ($data['town'] ?? ''));
    if ($town === '' && ($subCounty !== '' || $ward !== '')) {
        $town = trim($subCounty . ', ' . $ward, ', ');
    }
    $addressLine = trim((string) ($data['address_line'] ?? ''));
    $profilePicture = trim((string) ($data['profile_picture'] ?? ''));
    $createdByMarketerId = !empty($data['created_by_marketer_id']) ? (int) $data['created_by_marketer_id'] : null;

    if (!isset(DIGIHOME_ROLES[$userRole])) {
        $userRole = 'property_seeker';
    }

    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (first_name, last_name, name, username, email, phone, password_hash, role, county, town, address_line, profile_picture, created_by_marketer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssssssssssi', $firstName, $lastName, $fullName, $username, $emailAddress, $phoneNumber, $passwordHash, $userRole, $county, $town, $addressLine, $profilePicture, $createdByMarketerId);
    $stmt->execute();

    return get_user_by_id((int) $stmt->insert_id);
}

function authenticate_user($email, $password) {
    $db = connect_db();
    if (!$db) {
        $email = strtolower(trim((string) $email));
        $fallbackUsers = [
            'owner@example.com' => ['password' => 'owner123', 'role' => 'property_owner', 'first_name' => 'Grace', 'last_name' => 'Mwangi'],
            'admin@example.com' => ['password' => 'admin123', 'role' => 'admin', 'first_name' => 'System', 'last_name' => 'Admin'],
            'seeker@example.com' => ['password' => 'visitor123', 'role' => 'property_seeker', 'first_name' => 'Brian', 'last_name' => 'Kiprop'],
            'marketer@example.com' => ['password' => 'marketer123', 'role' => 'marketer', 'first_name' => 'Mercy', 'last_name' => 'Achieng'],
        ];

        if (isset($fallbackUsers[$email]) && hash_equals($fallbackUsers[$email]['password'], (string) $password)) {
            $user = $fallbackUsers[$email];
            return normalize_user([
                'id' => 0,
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'username' => $user['first_name'],
                'email' => $email,
                'phone' => '',
                'role' => $user['role'],
                'status' => 'active',
                'profile_picture' => default_profile_picture($user['role']),
            ]);
        }

        return null;
    }

    $email = strtolower(trim((string) $email));
    $stmt = $db->prepare('SELECT * FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $passwordHash = (string) ($user['password_hash'] ?? '');
    $validPassword = $user && password_verify((string) $password, $passwordHash);

    if ($user && !$validPassword && $passwordHash !== '' && hash_equals($passwordHash, (string) $password)) {
        $rehash = password_hash((string) $password, PASSWORD_DEFAULT);
        $reset = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $userId = (int) ($user['id'] ?? 0);
        $reset->bind_param('si', $rehash, $userId);
        $reset->execute();
        $validPassword = true;
    }

    if (!$user || !$validPassword) {
        audit_log(null, 'login_failed', 'user', null, 'Failed login for ' . $email);
        return null;
    }

    if (($user['status'] ?? 'active') !== 'active') {
        audit_log((int) $user['id'], 'login_blocked', 'user', (int) $user['id'], 'Blocked login for inactive account');
        return null;
    }

    $db->query('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ' . (int) $user['id']);
    audit_log((int) $user['id'], 'login_success', 'user', (int) $user['id'], 'User logged in');

    return normalize_user($user);
}

function create_property($data, $owner_id) {
    global $conn;
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $createdByRole = canonical_role($data['created_by_role'] ?? 'property_owner');

    $defaultHiddenDetailPayload = [
        'owner_contact' => 1,
        'owner_name' => 1,
        'building_name' => 1,
        'street' => 1,
        'google_map' => 1,
        'wing' => 1,
        'block' => 1,
        'unit_number' => 1,
        'postal_address' => 1,
        'directions_landmark' => 1,
        'hidden_images' => 1,
    ];

    $images = encode_json_array($data['images'] ?? []);
    $hiddenImages = encode_json_array($data['hidden_images'] ?? []);
    $imageDescriptions = encode_json_array($data['image_descriptions'] ?? []);
    $hiddenDetailPayload = $createdByRole === 'admin' ? (array) ($data['hidden_details'] ?? []) : [];
    if ($hiddenDetailPayload === []) {
        $hiddenDetailPayload = $defaultHiddenDetailPayload;
    }
    $hiddenDetails = json_encode($hiddenDetailPayload);
    $coverImage = trim((string) ($data['cover_image'] ?? ''));
    if ($coverImage === '') {
        $decoded = decode_json_array($images);
        $coverImage = (string) ($decoded[0] ?? '');
    }
    $marketerId = isset($data['marketer_id']) ? (int) $data['marketer_id'] : 0;
    $title = $data['title'] ?? '';
    $listingType = in_array($data['listing_type'] ?? 'rent', ['sale','rent','lease','airbnb'], true) ? ($data['listing_type'] ?? 'rent') : 'rent';
    $category = $data['category'] ?? 'residential';
    $propertyType = $data['property_type'] ?? '';
    $roomType = $data['room_type'] ?? '';
    $price = (float) ($data['price'] ?? 0);
    $deposit = (float) ($data['deposit'] ?? 0);
    $location = trim((string) ($data['location'] ?? ''));
    $hiddenLocation = $data['hidden_location'] ?? '';
    $description = $data['description'] ?? '';
    $status = $data['status'] ?? 'available';
    $verified = (int) ($data['verified'] ?? 0);
    $ownerName = $data['owner_name'] ?? '';
    $contact = $data['contact'] ?? '';
    $buildingName = $data['building_name'] ?? '';
    $floor = $data['floor'] ?? '';
    $wing = $data['wing'] ?? '';
    $roomNumber = trim((string) ($data['room_number'] ?? ($data['unit_number'] ?? '')));
    $listingScope = in_array($data['listing_scope'] ?? 'entire_property', ['entire_property', 'unit'], true) ? ($data['listing_scope'] ?? 'entire_property') : 'entire_property';
    $purpose = in_array($data['purpose'] ?? 'rent', ['rent', 'sale', 'lease', 'hire_purchase', 'airbnb', 'hotel_booking', 'auction'], true) ? ($data['purpose'] ?? 'rent') : 'rent';
    $parentPropertyId = !empty($data['parent_property_id']) ? (int) $data['parent_property_id'] : null;
    $bedrooms = isset($data['bedrooms']) ? (int) $data['bedrooms'] : null;
    $bathrooms = isset($data['bathrooms']) ? (int) $data['bathrooms'] : null;
    $parking = isset($data['parking']) ? (int) $data['parking'] : null;
    $furnished = !empty($data['furnished']) ? 1 : 0;
    $serviced = !empty($data['serviced']) ? 1 : 0;
    $petFriendly = !empty($data['pet_friendly']) ? 1 : 0;
    $wheelchairAccess = !empty($data['wheelchair_access']) ? 1 : 0;
    $propertyCondition = $data['property_condition'] ?? '';
    $propertyContext = trim($data['property_context'] ?? 'standalone');
    $country = $data['country'] ?? '';
    $county = $data['county'] ?? '';
    $city = trim((string) ($data['city'] ?? ($data['sub_county'] ?? '')));
    $ward = trim((string) ($data['ward'] ?? ''));
    $estate = $data['estate'] ?? '';
    $street = $data['street'] ?? '';
    $block = $data['block'] ?? '';
    $floorNumber = $data['floor_number'] ?? '';
    $unitNumber = $data['unit_number'] ?? '';
    $postalCode = $data['postal_code'] ?? ($data['postal_address'] ?? '');
    $landmark = $data['landmark'] ?? '';
    $googleMapsLink = $data['google_maps_link'] ?? '';
    $totalUnits = max(0, (int) ($data['total_units'] ?? ($data['available_units'] ?? 1)));
    $offerEnabled = !empty($data['offer_enabled']) ? 1 : 0;
    $offerPrice = ($offerEnabled && isset($data['offer_price']) && (float) $data['offer_price'] > 0) ? (float) $data['offer_price'] : null;
    $offerReason = trim((string) ($data['offer_reason'] ?? ''));
    $verificationStatus = $data['verification_status'] ?? ($createdByRole === 'admin' ? 'approved' : 'pending_verification');
    $verificationReason = $data['verification_reason'] ?? '';

    $locationIds = resolve_location_hierarchy_ids($country, $county, $city, $ward);
    $countryId = isset($locationIds['country_id']) ? (int) $locationIds['country_id'] : null;
    $countyId = isset($locationIds['county_id']) ? (int) $locationIds['county_id'] : null;
    $subCountyId = isset($locationIds['sub_county_id']) ? (int) $locationIds['sub_county_id'] : null;
    $wardId = isset($locationIds['ward_id']) ? (int) $locationIds['ward_id'] : null;

    if ($location === '') {
        $locationParts = array_values(array_filter([$estate, $ward, $city, $county, $country], static function ($value) {
            return trim((string) $value) !== '';
        }));
        $location = implode(', ', $locationParts);
    }

    if ($createdByRole === 'admin') {
        $verified = 1;
        $verificationStatus = 'approved';
    } elseif ($verified) {
        $verificationStatus = 'approved';
    }

    $propertyTypeId = null;
    if (!empty($data['property_type_id'])) {
        $propertyTypeId = (int) $data['property_type_id'];
    } elseif ($propertyType !== '') {
        $stmtType = $db->prepare('SELECT id FROM property_types WHERE name = ? LIMIT 1');
        $stmtType->bind_param('s', $propertyType);
        $stmtType->execute();
        $typeRow = $stmtType->get_result()->fetch_assoc();
        if ($typeRow) {
            $propertyTypeId = (int) $typeRow['id'];
        } else {
            $propertyTypeId = create_property_type($propertyType, $category);
        }
    }

    $stmt = $db->prepare('INSERT INTO properties (owner_id, marketer_id, title, listing_type, category, property_type, room_type, price, deposit, location, hidden_location, description, status, verified, owner_name, contact, images, hidden_images, cover_image, image_descriptions, building_name, floor, wing, room_number, property_type_id, listing_scope, purpose, parent_property_id, bedrooms, bathrooms, parking, furnished, serviced, pet_friendly, wheelchair_access, property_condition, property_context, country, country_id, county, county_id, city, sub_county_id, ward, ward_id, estate, street, block, floor_number, unit_number, postal_code, landmark, google_maps_link, total_units, offer_enabled, offer_price, offer_reason, hidden_details, verification_status, verification_reason, created_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $bindValues = [
        &$owner_id,
        &$marketerId,
        &$title,
        &$listingType,
        &$category,
        &$propertyType,
        &$roomType,
        &$price,
        &$deposit,
        &$location,
        &$hiddenLocation,
        &$description,
        &$status,
        &$verified,
        &$ownerName,
        &$contact,
        &$images,
        &$hiddenImages,
        &$coverImage,
        &$imageDescriptions,
        &$buildingName,
        &$floor,
        &$wing,
        &$roomNumber,
        &$propertyTypeId,
        &$listingScope,
        &$purpose,
        &$parentPropertyId,
        &$bedrooms,
        &$bathrooms,
        &$parking,
        &$furnished,
        &$serviced,
        &$petFriendly,
        &$wheelchairAccess,
        &$propertyCondition,
        &$propertyContext,
        &$country,
        &$countryId,
        &$county,
        &$countyId,
        &$city,
        &$subCountyId,
        &$ward,
        &$wardId,
        &$estate,
        &$street,
        &$block,
        &$floorNumber,
        &$unitNumber,
        &$postalCode,
        &$landmark,
        &$googleMapsLink,
        &$totalUnits,
        &$offerEnabled,
        &$offerPrice,
        &$offerReason,
        &$hiddenDetails,
        &$verificationStatus,
        &$verificationReason,
        &$createdByRole,
    ];

    $types = '';
    foreach ($bindValues as $value) {
        $types .= is_int($value) ? 'i' : (is_float($value) || is_double($value) ? 'd' : 's');
    }

    $stmt->bind_param($types, ...$bindValues);
    $stmt->execute();

    $propertyId = $stmt->insert_id;
    if ($propertyId && !empty($data['amenities'])) {
        sync_property_amenities($propertyId, $data['amenities']);
    }

    $row = $db->query('SELECT * FROM properties WHERE id = ' . (int) $propertyId)->fetch_assoc();
    return enrich_property(normalize_property($row));
}

function get_properties($filters = []) {
    global $sampleProperties;
    $db = connect_db();

    if ($db) {
        $sql = 'SELECT * FROM properties WHERE 1=1';
        $params = [];
        $types = '';

        if (!empty($filters['location'])) {
            $sql .= ' AND (location LIKE ? OR estate LIKE ? OR city LIKE ? OR county LIKE ?)';
            $search = '%' . $filters['location'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'ssss';
        }

        if (!empty($filters['listing_type'])) {
            $sql .= ' AND (listing_type = ? OR purpose = ?)';
            $params[] = $filters['listing_type'];
            $params[] = $filters['listing_type'];
            $types .= 'ss';
        }

        if (!empty($filters['category'])) {
            $sql .= ' AND category = ?';
            $params[] = $filters['category'];
            $types .= 's';
        }

        if (!empty($filters['purpose'])) {
            $sql .= ' AND purpose = ?';
            $params[] = $filters['purpose'];
            $types .= 's';
        }

        if (!empty($filters['listing_scope'])) {
            $sql .= ' AND listing_scope = ?';
            $params[] = $filters['listing_scope'];
            $types .= 's';
        }

        if (!empty($filters['property_type_id'])) {
            $sql .= ' AND property_type_id = ?';
            $params[] = (int) $filters['property_type_id'];
            $types .= 'i';
        }

        if (!empty($filters['bedrooms'])) {
            $sql .= ' AND bedrooms >= ?';
            $params[] = (int) $filters['bedrooms'];
            $types .= 'i';
        }

        if (!empty($filters['bathrooms'])) {
            $sql .= ' AND bathrooms >= ?';
            $params[] = (int) $filters['bathrooms'];
            $types .= 'i';
        }

        if (!empty($filters['furnished'])) {
            $sql .= ' AND furnished = 1';
        }

        if (!empty($filters['serviced'])) {
            $sql .= ' AND serviced = 1';
        }

        if (!empty($filters['pet_friendly'])) {
            $sql .= ' AND pet_friendly = 1';
        }

        if (!empty($filters['price_range'])) {
            [$minPrice, $maxPrice] = parse_price_range_filter((string) $filters['price_range']);
            if ($minPrice !== null) {
                $sql .= ' AND price >= ?';
                $params[] = $minPrice;
                $types .= 'd';
            }
            if ($maxPrice !== null) {
                $sql .= ' AND price < ?';
                $params[] = $maxPrice;
                $types .= 'd';
            }
        }

        $sql .= ' ORDER BY created_at DESC';
        $stmt = $db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = enrich_property(normalize_property($row));
        }

        if (!empty($rows)) {
            return $rows;
        }
    }

    $results = $sampleProperties;

    if (!empty($filters['location'])) {
        $results = array_values(array_filter($results, fn($item) => stripos($item['location'], $filters['location']) !== false));
    }

    if (!empty($filters['listing_type'])) {
        $results = array_values(array_filter($results, fn($item) => $item['listing_type'] === $filters['listing_type']));
    }

    if (!empty($filters['category'])) {
        $results = array_values(array_filter($results, fn($item) => $item['category'] === $filters['category']));
    }

    if (!empty($filters['price_range'])) {
        [$minPrice, $maxPrice] = parse_price_range_filter((string) $filters['price_range']);
        $results = array_values(array_filter($results, static function ($item) use ($minPrice, $maxPrice) {
            $price = (float) ($item['price'] ?? 0);
            if ($minPrice !== null && $price < $minPrice) {
                return false;
            }
            if ($maxPrice !== null && $price >= $maxPrice) {
                return false;
            }
            return true;
        }));
    }

    return array_map(static function ($item) {
        return enrich_property(normalize_property($item));
    }, $results);
}

function parse_price_range_filter($rangeKey) {
    $ranges = [
        '0-5000' => [0, 5000],
        '5000-10000' => [5000, 10000],
        '10000-15000' => [10000, 15000],
        '15000-20000' => [15000, 20000],
        '20000-30000' => [20000, 30000],
        '30000-40000' => [30000, 40000],
        '40000-60000' => [40000, 60000],
        '60000-80000' => [60000, 80000],
        '80000-100000' => [80000, 100000],
        '100000+' => [100000, null],
    ];
    return $ranges[$rangeKey] ?? [null, null];
}

function get_property_by_id($id) {
    global $sampleProperties;
    $db = connect_db();

    if ($db) {
        $stmt = $db->prepare('SELECT * FROM properties WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row) {
            return enrich_property(normalize_property($row));
        }
    }

    foreach ($sampleProperties as $property) {
        if ((int) $property['id'] === (int) $id) {
            return enrich_property(normalize_property($property));
        }
    }
    return null;
}

function get_owner_properties($ownerId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = enrich_property(normalize_property($row));
    }

    return $rows;
}

function get_dashboard_stats($userId, $role) {
    $db = connect_db();
    if (!$db) {
        return ['listings' => 0, 'pending' => 0, 'verified' => 0];
    }

    if (canonical_role($role) === 'property_owner') {
        $listings = $db->query('SELECT COUNT(*) as total FROM properties WHERE owner_id = ' . (int) $userId)->fetch_assoc()['total'] ?? 0;
        $verified = $db->query('SELECT COUNT(*) as total FROM properties WHERE owner_id = ' . (int) $userId . ' AND verified = 1')->fetch_assoc()['total'] ?? 0;
        return ['listings' => (int) $listings, 'verified' => (int) $verified, 'pending' => (int) $listings - (int) $verified];
    }

    if (canonical_role($role) === 'marketer') {
        $owners = $db->query('SELECT COUNT(*) as total FROM users WHERE role = "property_owner" AND created_by_marketer_id = ' . (int) $userId)->fetch_assoc()['total'] ?? 0;
        $listings = $db->query('SELECT COUNT(*) as total FROM properties WHERE marketer_id = ' . (int) $userId)->fetch_assoc()['total'] ?? 0;
        $pending = $db->query('SELECT COUNT(*) as total FROM properties WHERE marketer_id = ' . (int) $userId . ' AND verified = 0')->fetch_assoc()['total'] ?? 0;
        return ['owners' => (int) $owners, 'listings' => (int) $listings, 'pending' => (int) $pending, 'verified' => (int) $listings - (int) $pending];
    }

    $total = $db->query('SELECT COUNT(*) as total FROM properties')->fetch_assoc()['total'] ?? 0;
    $pending = $db->query('SELECT COUNT(*) as total FROM properties WHERE verified = 0')->fetch_assoc()['total'] ?? 0;
    $verified = $db->query('SELECT COUNT(*) as total FROM properties WHERE verified = 1')->fetch_assoc()['total'] ?? 0;

    return ['listings' => (int) $total, 'pending' => (int) $pending, 'verified' => (int) $verified];
}

function current_user() {
    $roleUsers = $_SESSION['role_users'] ?? [];
    if (!is_array($roleUsers)) {
        $roleUsers = [];
    }

    $requestedRole = request_role_context();
    if ($requestedRole !== '' && !empty($roleUsers[$requestedRole])) {
        $_SESSION['active_role'] = $requestedRole;
        $_SESSION['user'] = normalize_user($roleUsers[$requestedRole]);
    } elseif (!empty($_SESSION['active_role']) && !empty($roleUsers[$_SESSION['active_role']])) {
        $_SESSION['user'] = normalize_user($roleUsers[$_SESSION['active_role']]);
    } elseif (isset($_SESSION['user'])) {
        $normalized = normalize_user($_SESSION['user']);
        if ($normalized) {
            $roleUsers[$normalized['role']] = $normalized;
            $_SESSION['role_users'] = $roleUsers;
            $_SESSION['active_role'] = $normalized['role'];
            $_SESSION['user'] = $normalized;
        }
    }

    $user = isset($_SESSION['user']) ? normalize_user($_SESSION['user']) : null;
    if ($user) {
        touch_user_presence((int) $user['id']);
    }
    return $user;
}

function is_role($role) {
    $user = current_user();
    return $user && canonical_role($user['role'] ?? '') === canonical_role($role);
}

function user_has_any_role($roles) {
    $user = current_user();
    if (!$user) {
        return false;
    }

    foreach ((array) $roles as $role) {
        if (canonical_role($user['role']) === canonical_role($role)) {
            return true;
        }
    }

    return false;
}

function get_user_by_id($userId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return normalize_user($result->fetch_assoc());
}

function get_all_users($role = '') {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT * FROM users';
    $params = [];
    $types = '';
    if ($role !== '') {
        $sql .= ' WHERE role = ?';
        $params[] = canonical_role($role);
        $types .= 's';
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = normalize_user($row);
    }

    return $users;
}

function find_user_by_email_or_username($identity) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->bind_param('ss', $identity, $identity);
    $stmt->execute();
    return normalize_user($stmt->get_result()->fetch_assoc());
}

function validate_registration_data($data, $allowedRoles = DIGIHOME_REGISTERABLE_ROLES) {
    $errors = [];
    $role = canonical_role($data['role'] ?? 'property_seeker');
    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = 'The selected role is not available for self-registration.';
    }

    foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email address', 'phone' => 'Phone number', 'county' => 'County', 'sub_county' => 'Sub-County', 'ward' => 'Ward'] as $field => $label) {
        if (trim((string) ($data[$field] ?? '')) === '') {
            $errors[] = $label . ' is required.';
        }
    }

    $emailValue = trim((string) ($data['email'] ?? ''));
    if ($emailValue !== '' && !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    $phoneValue = trim((string) ($data['phone'] ?? ''));
    if ($phoneValue !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phoneValue)) {
        $errors[] = 'Enter a valid phone number.';
    }

    $password = (string) ($data['password'] ?? '');
    $confirmPassword = (string) ($data['confirm_password'] ?? '');
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    $db = connect_db();
    if ($db) {
        $requestedRole = canonical_role($data['role'] ?? 'property_seeker');
        foreach (['email' => 'Email address', 'phone' => 'Phone number'] as $field => $label) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            $stmt = $db->prepare('SELECT id FROM users WHERE ' . $field . ' = ? AND role = ? LIMIT 1');
            $stmt->bind_param('ss', $value, $requestedRole);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = $label . ' is already in use for this role.';
            }
        }
    }

    return $errors;
}

function login_user($user) {
    $normalizedUser = normalize_user($user);
    session_regenerate_id(true);
    $role = canonical_role($normalizedUser['role'] ?? 'property_seeker');
    if (!isset($_SESSION['role_users']) || !is_array($_SESSION['role_users'])) {
        $_SESSION['role_users'] = [];
    }
    $_SESSION['role_users'][$role] = $normalizedUser;
    $_SESSION['active_role'] = $role;
    $_SESSION['user'] = $normalizedUser;
    remember_device_user($normalizedUser);
    touch_user_presence((int) ($normalizedUser['id'] ?? 0));
    return $normalizedUser;
}

function issue_chat_auth_token($user) {
    $normalizedUser = normalize_user($user);
    if (!$normalizedUser || empty($normalizedUser['id'])) {
        return '';
    }

    if (!isset($_SESSION['chat_auth_tokens']) || !is_array($_SESSION['chat_auth_tokens'])) {
        $_SESSION['chat_auth_tokens'] = [];
    }

    $cutoff = time() - 86400;
    foreach ($_SESSION['chat_auth_tokens'] as $token => $payload) {
        if ((int) ($payload['issued_at'] ?? 0) < $cutoff) {
            unset($_SESSION['chat_auth_tokens'][$token]);
        }
    }

    $token = bin2hex(random_bytes(24));
    $_SESSION['chat_auth_tokens'][$token] = [
        'user_id' => (int) $normalizedUser['id'],
        'role' => canonical_role($normalizedUser['role'] ?? 'property_seeker'),
        'issued_at' => time(),
    ];

    return $token;
}

function resolve_chat_auth_user($token) {
    $token = trim((string) $token);
    if ($token === '') {
        return null;
    }

    $tokens = $_SESSION['chat_auth_tokens'] ?? [];
    if (!is_array($tokens) || empty($tokens[$token])) {
        return null;
    }

    $payload = $tokens[$token];
    if ((int) ($payload['issued_at'] ?? 0) < (time() - 86400)) {
        unset($_SESSION['chat_auth_tokens'][$token]);
        return null;
    }

    $user = get_user_by_id((int) ($payload['user_id'] ?? 0));
    if (!$user) {
        unset($_SESSION['chat_auth_tokens'][$token]);
        return null;
    }

    if (canonical_role($user['role'] ?? '') !== canonical_role($payload['role'] ?? '')) {
        unset($_SESSION['chat_auth_tokens'][$token]);
        return null;
    }

    touch_user_presence((int) ($user['id'] ?? 0));
    return $user;
}

function logout_user($role = null) {
    if ($role === null || $role === '') {
        unset($_SESSION['user'], $_SESSION['role_users'], $_SESSION['active_role'], $_SESSION['unlocked_properties']);
        return;
    }

    $canonical = canonical_role($role);
    if (!empty($_SESSION['role_users'][$canonical])) {
        unset($_SESSION['role_users'][$canonical]);
    }

    if (($_SESSION['active_role'] ?? '') === $canonical) {
        $remaining = $_SESSION['role_users'] ?? [];
        if (!empty($remaining) && is_array($remaining)) {
            $next = reset($remaining);
            $_SESSION['user'] = normalize_user($next);
            $_SESSION['active_role'] = $_SESSION['user']['role'];
        } else {
            unset($_SESSION['user'], $_SESSION['active_role']);
        }
    }
}

function update_user_profile($userId, $data) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $user = get_user_by_id($userId);
    if (!$user) {
        return false;
    }

    $firstName = trim((string) ($data['first_name'] ?? $user['first_name']));
    $lastName = trim((string) ($data['last_name'] ?? $user['last_name']));
    $name = trim($firstName . ' ' . $lastName);
    $username = trim((string) ($data['username'] ?? $user['first_name']));
    if ($username === '') {
        $username = trim((string) ($firstName !== '' ? $firstName : $user['username']));
    }
    $email = trim((string) ($data['email'] ?? $user['email']));
    $phone = trim((string) ($data['phone'] ?? $user['phone']));
    $county = trim((string) ($data['county'] ?? $user['county']));
    $town = trim((string) ($data['town'] ?? $user['town']));
    $addressLine = trim((string) ($data['address_line'] ?? $user['address_line']));
    $profilePicture = trim((string) ($data['profile_picture'] ?? $user['profile_picture']));

    $stmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, name = ?, username = ?, email = ?, phone = ?, county = ?, town = ?, address_line = ?, profile_picture = ? WHERE id = ?');
    $stmt->bind_param('ssssssssssi', $firstName, $lastName, $name, $username, $email, $phone, $county, $town, $addressLine, $profilePicture, $userId);
    $ok = $stmt->execute();

    if ($ok && !empty($data['password'])) {
        $passwordHash = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $passwordStmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $passwordStmt->bind_param('si', $passwordHash, $userId);
        $passwordStmt->execute();
    }

    audit_log((int) $userId, 'profile_updated', 'user', (int) $userId, 'User profile updated');
    return $ok;
}

function create_notification($userId, $type, $title, $message) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $type, $title, $message);
    $stmt->execute();
    return $stmt->insert_id;
}

function get_notifications($userId, $limit = 5) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function audit_log($userId, $action, $entityType, $entityId = null, $details = null) {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $userId, $action, $entityType, $entityId, $details, $ipAddress);
    $stmt->execute();
}

function role_allows_user_management($role) {
    return canonical_role($role) === 'admin';
}

function property_status_label($property) {
    $status = (string) ($property['verification_status'] ?? '');
    if ($status === 'verified') {
        return 'Approved';
    }

    if (!empty($property['verified']) && in_array($status, ['', 'pending_verification'], true)) {
        return 'Approved';
    }

    if ($status !== '') {
        return ucwords(str_replace('_', ' ', $status));
    }

    return !empty($property['verified']) ? 'Approved' : 'Pending Verification';
}

function listing_purpose_label($property) {
    $purposeMap = [
        'rent' => 'For Rent',
        'sale' => 'For Sale',
        'lease' => 'For Lease',
        'hire_purchase' => 'Hire Purchase',
        'airbnb' => 'Airbnb',
        'hotel_booking' => 'Hotel Booking',
        'auction' => 'For Auction',
    ];
    $purpose = strtolower(trim((string) ($property['purpose'] ?? '')));
    if ($purpose !== '' && isset($purposeMap[$purpose])) {
        return $purposeMap[$purpose];
    }
    $listingType = strtolower(trim((string) ($property['listing_type'] ?? '')));
    return $purposeMap[$listingType] ?? ucwords(str_replace('_', ' ', $listingType !== '' ? $listingType : 'rent'));
}

function listing_scope_label($property) {
    $scope = strtolower(trim((string) ($property['listing_scope'] ?? 'entire_property')));
    return $scope === 'unit' ? 'Unit' : 'Entire Property';
}

function get_favorite_count($propertyId) {
    $db = connect_db();
    if (!$db || (int) $propertyId <= 0) {
        return 0;
    }

    $stmt = $db->prepare('SELECT COUNT(*) AS total FROM favorite_properties WHERE property_id = ?');
    $stmt->bind_param('i', $propertyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function marketer_registered_owners($marketerId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE role = ? AND created_by_marketer_id = ? ORDER BY created_at DESC');
    $role = 'property_owner';
    $stmt->bind_param('si', $role, $marketerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = normalize_user($row);
    }
    return $rows;
}

function get_marketer_properties($marketerId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT * FROM properties WHERE marketer_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = enrich_property(normalize_property($row));
    }
    return $rows;
}

function get_property_owner_marketer($ownerId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT created_by_marketer_id FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (empty($row['created_by_marketer_id'])) {
        return null;
    }

    return get_user_by_id((int) $row['created_by_marketer_id']);
}

function set_user_status($userId, $status) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $userId);
    $ok = $stmt->execute();
    if ($ok) {
        audit_log((int) (current_user()['id'] ?? 0), 'user_status_changed', 'user', $userId, 'Status updated to ' . $status);
    }
    return $ok;
}

function delete_user_account($userId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    if ($ok) {
        audit_log((int) (current_user()['id'] ?? 0), 'user_deleted', 'user', $userId, 'User deleted');
    }
    return $ok;
}

function get_verification_queue() {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT * FROM properties WHERE verification_status IN ('pending_verification', 'needs_changes') ORDER BY created_at DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = enrich_property(normalize_property($row));
    }
    return $rows;
}

function update_property_verification($propertyId, $status, $reason = '') {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $validStatuses = ['pending_verification', 'approved', 'rejected', 'needs_changes'];
    if (!in_array($status, $validStatuses, true)) {
        return false;
    }

    $verified = $status === 'approved' ? 1 : 0;
    $stmt = $db->prepare('UPDATE properties SET verification_status = ?, verification_reason = ?, verified = ? WHERE id = ?');
    $stmt->bind_param('ssii', $status, $reason, $verified, $propertyId);
    $ok = $stmt->execute();
    if ($ok) {
        audit_log((int) (current_user()['id'] ?? 0), 'property_verification_updated', 'property', $propertyId, 'Verification set to ' . $status);
    }
    return $ok;
}

function can_user_edit_property($property, $actor = null) {
    if (!$property) {
        return false;
    }

    $actor = $actor ?: current_user();
    if (!$actor) {
        return false;
    }

    $role = canonical_role((string) ($actor['role'] ?? ''));
    $actorId = (int) ($actor['id'] ?? 0);
    $ownerId = (int) ($property['owner_id'] ?? 0);
    $marketerId = (int) ($property['marketer_id'] ?? 0);
    $createdByRole = canonical_role((string) ($property['created_by_role'] ?? 'property_owner'));

    if ($role === 'admin') {
        return true;
    }

    if ($role === 'property_owner') {
        return $ownerId > 0 && $ownerId === $actorId;
    }

    if ($role === 'marketer') {
        if ($marketerId <= 0 || $marketerId !== $actorId) {
            return false;
        }
        if ($createdByRole === 'marketer') {
            return true;
        }
        return get_site_content('marketer_can_edit_owner_properties', '1') === '1';
    }

    return false;
}

function update_property_details($propertyId, $data, $actor = null) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $property = get_property_by_id((int) $propertyId);
    if (!$property || !can_user_edit_property($property, $actor)) {
        return false;
    }

    $actorRole = canonical_role((string) ($actor['role'] ?? ''));

    $title = trim((string) ($data['title'] ?? ''));
    $listingType = in_array((string) ($data['listing_type'] ?? ''), ['sale', 'rent', 'lease', 'airbnb', 'hire_purchase', 'rent_to_own'], true) ? (string) $data['listing_type'] : 'rent';
    $category = trim((string) ($data['category'] ?? 'residential'));
    $propertyType = trim((string) ($data['property_type'] ?? ''));
    $roomType = trim((string) ($data['room_type'] ?? ''));
    $price = (float) ($data['price'] ?? 0);
    $deposit = (float) ($data['deposit'] ?? 0);
    $location = trim((string) ($data['location'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'available'));
    $ownerName = trim((string) ($data['owner_name'] ?? ''));
    $contact = trim((string) ($data['contact'] ?? ''));
    $buildingName = trim((string) ($data['building_name'] ?? ''));
    $floor = trim((string) ($data['floor'] ?? ''));
    $wing = trim((string) ($data['wing'] ?? ''));
    $roomNumber = trim((string) ($data['room_number'] ?? ($data['unit_number'] ?? '')));
    $listingScope = in_array((string) ($data['listing_scope'] ?? ''), ['entire_property', 'unit'], true) ? (string) $data['listing_scope'] : 'entire_property';
    $purpose = in_array((string) ($data['purpose'] ?? ''), ['rent', 'sale', 'lease', 'hire_purchase', 'airbnb', 'hotel_booking', 'auction'], true) ? (string) $data['purpose'] : $listingType;
    $bedrooms = max(0, (int) ($data['bedrooms'] ?? 0));
    $bathrooms = max(0, (int) ($data['bathrooms'] ?? 0));
    $parking = max(0, (int) ($data['parking'] ?? 0));
    $furnished = !empty($data['furnished']) ? 1 : 0;
    $serviced = !empty($data['serviced']) ? 1 : 0;
    $petFriendly = !empty($data['pet_friendly']) ? 1 : 0;
    $wheelchairAccess = !empty($data['wheelchair_access']) ? 1 : 0;
    $propertyCondition = trim((string) ($data['property_condition'] ?? ''));
    $propertyContext = trim((string) ($data['property_context'] ?? 'standalone'));
    $country = trim((string) ($data['country'] ?? ''));
    $county = trim((string) ($data['county'] ?? ''));
    $city = trim((string) ($data['city'] ?? ($data['sub_county'] ?? '')));
    $ward = trim((string) ($data['ward'] ?? ''));
    $estate = trim((string) ($data['estate'] ?? ''));
    $street = trim((string) ($data['street'] ?? ''));
    $block = trim((string) ($data['block'] ?? ''));
    $floorNumber = trim((string) ($data['floor_number'] ?? ''));
    $unitNumber = trim((string) ($data['unit_number'] ?? ''));
    $postalCode = trim((string) ($data['postal_code'] ?? ($data['postal_address'] ?? '')));
    $landmark = trim((string) ($data['landmark'] ?? ''));
    $googleMaps = trim((string) ($data['google_maps_link'] ?? ''));
    $totalUnits = max(0, (int) ($data['total_units'] ?? ($data['available_units'] ?? 1)));
    $offerEnabled = !empty($data['offer_enabled']) ? 1 : 0;
    $offerPrice = ($offerEnabled && isset($data['offer_price']) && (float) $data['offer_price'] > 0) ? (float) $data['offer_price'] : null;
    $offerReason = trim((string) ($data['offer_reason'] ?? ''));

    $locationIds = resolve_location_hierarchy_ids($country, $county, $city, $ward);
    $countryId = isset($locationIds['country_id']) ? (int) $locationIds['country_id'] : null;
    $countyId = isset($locationIds['county_id']) ? (int) $locationIds['county_id'] : null;
    $subCountyId = isset($locationIds['sub_county_id']) ? (int) $locationIds['sub_county_id'] : null;
    $wardId = isset($locationIds['ward_id']) ? (int) $locationIds['ward_id'] : null;

    if ($location === '') {
        $locationParts = array_values(array_filter([$estate, $ward, $city, $county, $country], static function ($value) {
            return trim((string) $value) !== '';
        }));
        $location = implode(', ', $locationParts);
    }

    $existingHidden = (array) ($property['hidden_details'] ?? []);
    $defaultHiddenDetails = [
        'owner_contact' => 1,
        'owner_name' => 1,
        'building_name' => 1,
        'street' => 1,
        'google_map' => 1,
        'wing' => 1,
        'block' => 1,
        'unit_number' => 1,
        'postal_address' => 1,
        'directions_landmark' => 1,
        'hidden_images' => 1,
    ];
    if ($actorRole === 'admin') {
        $submittedHidden = (array) ($data['hidden_details'] ?? $existingHidden);
        if ($submittedHidden === []) {
            $submittedHidden = $defaultHiddenDetails;
        }
        $hiddenDetails = json_encode($submittedHidden);
    } else {
        $hiddenDetails = json_encode($existingHidden === [] ? $defaultHiddenDetails : $existingHidden);
    }
    $existingImages = array_values((array) ($property['images'] ?? []));
    $existingCoverImage = (string) ($property['cover_image'] ?? ($existingImages[0] ?? ''));
    $existingHiddenImages = array_values((array) ($property['hidden_images'] ?? []));
    $existingDescriptions = array_values((array) ($property['image_descriptions'] ?? []));

    if ($title === '' || $propertyType === '' || $price <= 0 || $location === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE properties SET title = ?, listing_type = ?, category = ?, property_type = ?, room_type = ?, price = ?, deposit = ?, location = ?, description = ?, status = ?, owner_name = ?, contact = ?, building_name = ?, floor = ?, wing = ?, room_number = ?, listing_scope = ?, purpose = ?, bedrooms = ?, bathrooms = ?, parking = ?, furnished = ?, serviced = ?, pet_friendly = ?, wheelchair_access = ?, property_condition = ?, property_context = ?, country = ?, country_id = ?, county = ?, county_id = ?, city = ?, sub_county_id = ?, ward = ?, ward_id = ?, estate = ?, street = ?, block = ?, floor_number = ?, unit_number = ?, postal_code = ?, landmark = ?, google_maps_link = ?, total_units = ?, offer_enabled = ?, offer_price = ?, offer_reason = ?, hidden_details = ? WHERE id = ?');

    $bindValues = [
        &$title,
        &$listingType,
        &$category,
        &$propertyType,
        &$roomType,
        &$price,
        &$deposit,
        &$location,
        &$description,
        &$status,
        &$ownerName,
        &$contact,
        &$buildingName,
        &$floor,
        &$wing,
        &$roomNumber,
        &$listingScope,
        &$purpose,
        &$bedrooms,
        &$bathrooms,
        &$parking,
        &$furnished,
        &$serviced,
        &$petFriendly,
        &$wheelchairAccess,
        &$propertyCondition,
        &$propertyContext,
        &$country,
        &$countryId,
        &$county,
        &$countyId,
        &$city,
        &$subCountyId,
        &$ward,
        &$wardId,
        &$estate,
        &$street,
        &$block,
        &$floorNumber,
        &$unitNumber,
        &$postalCode,
        &$landmark,
        &$googleMaps,
        &$totalUnits,
        &$offerEnabled,
        &$offerPrice,
        &$offerReason,
        &$hiddenDetails,
        &$propertyId,
    ];

    $types = '';
    foreach ($bindValues as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value) || is_double($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    $stmt->bind_param($types, ...$bindValues);
    $ok = $stmt->execute();
    if (!$ok) {
        return false;
    }

    if (isset($data['amenities']) && is_array($data['amenities'])) {
        $amenityPayload = ['_scope' => (array) ($data['amenity_scope'] ?? [])] + (array) $data['amenities'];
        sync_property_amenities((int) $propertyId, $amenityPayload);
    }

    $uploaded = store_property_images_upload('property_images', (int) $propertyId, (string) get_site_content('system_name', 'DigiHome'));
    $allImages = array_values(array_unique(array_merge($existingImages, $uploaded)));
    if ($allImages === []) {
        $allImages = $existingImages;
    }

    $coverImage = $existingCoverImage;
    $hasCoverSelection = array_key_exists('cover_image_index', $data) && trim((string) ($data['cover_image_index'] ?? '')) !== '';
    if ($hasCoverSelection) {
        $coverIndex = max(0, (int) $data['cover_image_index']);
        if (!empty($allImages[$coverIndex])) {
            $coverImage = (string) $allImages[$coverIndex];
        } else {
            return false;
        }
    } elseif ($coverImage !== '' && in_array($coverImage, $allImages, true)) {
        // Keep the existing cover when no new cover is explicitly selected.
    } elseif ($allImages !== []) {
        return false;
    }

    $hiddenImages = [];
    $hiddenFlags = array_map('intval', (array) ($data['hidden_image_flags'] ?? []));
    foreach ($hiddenFlags as $index) {
        if (isset($allImages[$index])) {
            $hiddenImages[] = (string) $allImages[$index];
        }
    }
    $rawHidden = trim((string) ($data['hidden_image_indexes'] ?? ''));
    if ($rawHidden !== '') {
        foreach ((array) preg_split('/\s*,\s*/', $rawHidden) as $chunk) {
            if ($chunk === '' || !ctype_digit((string) $chunk)) {
                continue;
            }
            $index = (int) $chunk;
            if (isset($allImages[$index])) {
                $hiddenImages[] = (string) $allImages[$index];
            }
        }
    }
    if ($hiddenImages === [] && $rawHidden === '' && $hiddenFlags === []) {
        $hiddenImages = $existingHiddenImages;
    }
    $hiddenImages = array_values(array_unique($hiddenImages));
    if ($coverImage !== '') {
        $hiddenImages = array_values(array_filter($hiddenImages, static function ($img) use ($coverImage) {
            return (string) $img !== $coverImage;
        }));
    }

    $imageDescriptions = $existingDescriptions;
    $descriptionMap = (array) ($data['image_descriptions_by_index'] ?? []);
    if ($descriptionMap !== [] || array_key_exists('new_image_descriptions_csv', $data) || array_key_exists('new_image_descriptions', $data)) {
        $imageDescriptions = [];
        foreach ($allImages as $index => $unusedImage) {
            $indexKey = (string) $index;
            $value = isset($descriptionMap[$indexKey]) ? trim((string) $descriptionMap[$indexKey]) : ((string) ($existingDescriptions[$index] ?? ''));
            $imageDescriptions[] = $value;
        }

        $newDescriptionLines = array_values(array_filter(array_map('trim', (array) ($data['new_image_descriptions'] ?? [])), static function ($line) {
            return $line !== '';
        }));
        if ($newDescriptionLines === [] && array_key_exists('new_image_descriptions_csv', $data)) {
            $newDescriptionLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($data['new_image_descriptions_csv'] ?? '')))));
        }
        if ($uploaded !== [] && $newDescriptionLines !== []) {
            $baseIndex = count($existingImages);
            foreach ($newDescriptionLines as $offset => $line) {
                $target = $baseIndex + $offset;
                if (isset($imageDescriptions[$target])) {
                    $imageDescriptions[$target] = $line;
                }
            }
        }
    }

    if ($allImages !== []) {
        $ok = update_property_media((int) $propertyId, $coverImage, $allImages, $hiddenImages, $imageDescriptions) && $ok;
    }

    $actorId = (int) (($actor['id'] ?? current_user()['id'] ?? 0));
    audit_log($actorId, 'property_details_updated', 'property', (int) $propertyId, 'Listing details updated by ' . canonical_role((string) ($actor['role'] ?? 'system')));
    return (bool) $ok;
}

function assign_property_marketer($propertyId, $marketerId, $adminId = null) {
    $db = connect_db();
    if (!$db || $propertyId <= 0) {
        return false;
    }

    $nextMarketerId = (int) $marketerId;
    if ($nextMarketerId > 0) {
        $marketer = get_user_by_id($nextMarketerId);
        if (!$marketer || canonical_role((string) ($marketer['role'] ?? '')) !== 'marketer') {
            return false;
        }
    }

    $stmt = $db->prepare('UPDATE properties SET marketer_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $nextMarketerId, $propertyId);
    $ok = $stmt->execute();
    if ($ok) {
        $detail = $nextMarketerId > 0 ? ('Assigned marketer #' . $nextMarketerId) : 'Removed marketer assignment';
        audit_log((int) $adminId, 'property_marketer_assignment_updated', 'property', $propertyId, $detail);
    }
    return $ok;
}

function site_content_defaults() {
    return [
        'about_title' => 'About DigiHome',
        'about_body' => '',
        'about_intro_note' => 'This page is managed by platform administrators and published for all user roles.',
        'about_feature_cards' => '[{"title":"Role-based access control","description":"Delivered through secure workflows and consistent multi-role governance."},{"title":"Verified listing workflows","description":"Delivered through secure workflows and consistent multi-role governance."},{"title":"Transparent user governance","description":"Delivered through secure workflows and consistent multi-role governance."},{"title":"Enterprise-ready reporting","description":"Delivered through secure workflows and consistent multi-role governance."}]',
        'about_page_body' => '',
        'header_description' => 'Secure role-based property platform',
        'system_name' => 'DigiHome',
        'default_theme' => 'light',
        'contact_title' => 'Contact DigiHome',
        'contact_body' => 'Contact the DigiHome operations desk for support, listing guidance, and platform administration.',
        'contact_phone' => '+254 700 123 456',
        'contact_email' => 'support@digihome.local',
        'contact_address' => 'Nairobi, Kenya',
        'contact_hours' => 'Monday to Friday, 8:00 AM - 5:00 PM',
        'footer_about_text' => 'Professional property discovery, listing operations, marketer workflows, and administrative control in one system.',
        'footer_legal_left' => 'Enterprise-ready property operations for modern teams.',
        'footer_legal_right' => 'All rights reserved.',
        'chat_response_time_minutes' => '20',
        'chat_greeting_message' => 'Hello and welcome to DigiHome support. We have received your message and an admin will assist you shortly.',
        'unlock_fee_amount' => '250',
        'withdrawal_min_amount' => '500',
        'withdrawal_max_amount' => '200000',
        'default_commission_rate_percent' => '15',
        'marketer_can_edit_owner_properties' => '1',
        'listing_tab_general' => 'General',
        'listing_tab_details' => 'Details',
        'listing_tab_location' => 'Location',
        'listing_tab_images' => 'Images',
        'listing_tab_submit' => 'Submit',
        'listing_label_title' => 'Property title',
        'listing_placeholder_title' => 'e.g. Premium 2-bedroom apartment',
        'listing_label_price' => 'Price',
        'listing_label_location' => 'Location',
        'listing_placeholder_location' => 'e.g. Kilimani, Nairobi',
        'listing_label_description' => 'Description',
        'listing_label_category' => 'Category',
        'listing_label_property_type' => 'Property type',
        'listing_label_listing_type' => 'Listing type',
        'listing_label_listing_scope' => 'Listing scope',
        'listing_label_status' => 'Status',
        'listing_label_room_type' => 'Property subtype',
        'listing_label_property_context' => 'Property context',
        'listing_label_purpose' => 'Purpose',
        'listing_label_deposit' => 'Deposit',
        'listing_label_available_units' => 'Total Units',
        'listing_label_owner_contact' => 'Owner contact',
        'listing_label_bedrooms' => 'Bedrooms',
        'listing_label_bathrooms' => 'Bathrooms',
        'listing_label_parking' => 'Parking spaces',
        'listing_label_property_condition' => 'Property condition',
        'listing_label_offer_enabled' => 'Enable offer price',
        'listing_label_offer_price' => 'Offer price',
        'listing_label_offer_reason' => 'Offer reason',
        'listing_label_features' => 'Features',
        'listing_label_amenities' => 'Amenities',
        'listing_label_country' => 'Country',
        'listing_label_county' => 'County',
        'listing_label_city' => 'Sub-County',
        'listing_label_ward' => 'Ward',
        'listing_label_estate' => 'Estate / neighbourhood / suburb',
        'listing_label_street' => 'Street',
        'listing_label_google_maps_link' => 'Google Maps link',
        'listing_label_building_name' => 'Building name',
        'listing_label_floor' => 'Floor(s) (comma separated)',
        'listing_label_wing' => 'Wing',
        'listing_label_room_number' => 'Room',
        'listing_label_block' => 'Block',
        'listing_label_floor_number' => 'Floor number',
        'listing_label_unit_number' => 'Unit number',
        'listing_label_postal_code' => 'Postal code',
        'listing_label_landmark' => 'Landmark',
        'listing_label_hidden_details' => 'Hidden details visibility',
        'listing_label_upload_images' => 'Upload images',
        'listing_label_cover_image' => 'Cover image index',
        'listing_label_hidden_image_indexes' => 'Hidden image indexes (comma separated)',
        'listing_label_image_descriptions' => 'Image descriptions (one per line)',
        'listing_placeholder_room_type' => 'e.g. 2 Bedroom, Studio',
        'listing_placeholder_price' => 'e.g. 45000',
        'listing_placeholder_deposit' => 'e.g. 120000',
        'listing_placeholder_available_units' => 'e.g. 24',
        'listing_placeholder_bedrooms' => 'e.g. 2',
        'listing_placeholder_bathrooms' => 'e.g. 2',
        'listing_placeholder_parking' => 'e.g. 1',
        'listing_placeholder_property_condition' => 'Excellent, Fair, Newly renovated',
        'listing_placeholder_offer_price' => 'e.g. 42000',
        'listing_placeholder_offer_reason' => 'e.g. December Offer',
        'listing_placeholder_description' => 'Describe the property in a compelling way',
        'listing_placeholder_country' => 'e.g. Kenya',
        'listing_placeholder_county' => 'e.g. Nairobi',
        'listing_placeholder_city' => 'e.g. Westlands',
        'listing_placeholder_ward' => 'e.g. Parklands',
        'listing_placeholder_estate' => 'e.g. Westlands',
        'listing_placeholder_street' => 'e.g. 5th Street',
        'listing_placeholder_google_maps_link' => 'https://maps.google.com/...',
        'listing_placeholder_floor' => 'Ground, 1st, 4th',
        'listing_placeholder_wing' => 'North Wing, Block B',
        'listing_placeholder_hidden_image_indexes' => 'e.g. 2,4',
    ];
}

function get_about_feature_cards($contentMap = null) {
    $content = is_array($contentMap) ? $contentMap : get_site_content_map();
    $cards = [];
    $raw = trim((string) ($content['about_feature_cards'] ?? ''));

    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $title = trim((string) ($card['title'] ?? ''));
                $description = trim((string) ($card['description'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $cards[] = [
                    'title' => $title,
                    'description' => $description !== '' ? $description : 'Delivered through secure workflows and consistent multi-role governance.',
                ];
            }
        }
    }

    if ($cards !== []) {
        return $cards;
    }

    return $cards;
}

function normalize_team_contact_href($accountValue, $linkPrefix = '') {
    $account = trim((string) $accountValue);
    if ($account === '') {
        return '#';
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $account)) {
        return $account;
    }

    $prefix = trim((string) $linkPrefix);
    if ($prefix !== '') {
        return $prefix . $account;
    }

    if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $account;
    }

    if (preg_match('/^\+?[0-9][0-9\s\-\(\)]{5,}$/', $account)) {
        return 'tel:' . preg_replace('/\s+/', '', $account);
    }

    return 'https://' . ltrim($account, '/');
}

function get_site_content_map() {
    $db = connect_db();
    $defaults = site_content_defaults();
    if (!$db) {
        return $defaults;
    }

    $result = $db->query('SELECT content_key, content_value FROM site_content');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $defaults[$row['content_key']] = (string) ($row['content_value'] ?? '');
        }
    }

    return $defaults;
}

function get_site_content($key, $fallback = '') {
    $content = get_site_content_map();
    return $content[$key] ?? $fallback;
}

function update_site_content($key, $value, $updatedBy = null) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('INSERT INTO site_content (content_key, content_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE content_value = VALUES(content_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
    $stmt->bind_param('ssi', $key, $value, $updatedBy);
    return $stmt->execute();
}

function assign_property_owner($propertyId, $ownerId, $adminId = null) {
    $db = connect_db();
    if (!$db || $propertyId <= 0 || $ownerId <= 0) {
        return false;
    }

    $owner = get_user_by_id((int) $ownerId);
    if (!$owner || canonical_role((string) ($owner['role'] ?? '')) !== 'property_owner') {
        return false;
    }

    $ownerName = trim((string) ($owner['name'] ?? ''));
    if ($ownerName === '') {
        $ownerName = trim((string) (($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? '')));
    }
    if ($ownerName === '') {
        $ownerName = 'Property Owner';
    }
    $ownerContact = trim((string) ($owner['phone'] ?? ''));

    $stmt = $db->prepare('UPDATE properties SET owner_id = ?, user_id = ?, owner_name = ?, contact = COALESCE(NULLIF(?, \'\'), contact) WHERE id = ?');
    $stmt->bind_param('iissi', $ownerId, $ownerId, $ownerName, $ownerContact, $propertyId);
    return $stmt->execute();
}

function update_site_content_bulk($items, $updatedBy = null) {
    $ok = true;
    foreach ($items as $key => $value) {
        $saved = update_site_content((string) $key, (string) $value, $updatedBy);
        $ok = $ok && $saved;
    }
    if ($ok) {
        audit_log((int) $updatedBy, 'site_content_updated', 'site_content', null, 'Updated about/contact content');
    }
    return $ok;
}

function record_property_unlock($propertyId, $propertySeekerId) {
    $db = connect_db();
    $property = get_property_by_id($propertyId);
    if (!$db || !$property) {
        return;
    }

    $propertyOwnerId = (int) ($property['owner_id'] ?? 0);
    $marketerId = (int) ($property['marketer_id'] ?? 0);
    try {
        $stmt = $db->prepare('INSERT INTO unlock_history (property_id, property_owner_id, property_seeker_id, marketer_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiii', $propertyId, $propertyOwnerId, $propertySeekerId, $marketerId);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        try {
            $stmt = $db->prepare('INSERT INTO unlock_history (property_id, owner_id, seeker_id, marketer_id) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiii', $propertyId, $propertyOwnerId, $propertySeekerId, $marketerId);
            $stmt->execute();
        } catch (mysqli_sql_exception $ignored) {
            // Keep unlock flow non-blocking even on legacy schema mismatches.
        }
    }

    if ($marketerId > 0) {
        $unlockFee = get_unlock_fee_amount();
        $ratePercent = get_commission_policy_rate($marketerId, (int) $propertyId);
        $commissionAmount = round(($unlockFee * $ratePercent) / 100, 2);
        $paymentStatus = 'accrued';

        try {
            $commission = $db->prepare('INSERT INTO commissions (marketer_id, property_id, property_owner_id, property_seeker_id, commission_amount, commission_rate_percent, unlock_fee_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $commission->bind_param('iiiiddds', $marketerId, $propertyId, $propertyOwnerId, $propertySeekerId, $commissionAmount, $ratePercent, $unlockFee, $paymentStatus);
            $commission->execute();
        } catch (mysqli_sql_exception $e) {
            try {
                $commission = $db->prepare('INSERT INTO commissions (marketer_id, property_id, owner_id, seeker_id, commission_amount, commission_rate_percent, unlock_fee_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $commission->bind_param('iiiiddds', $marketerId, $propertyId, $propertyOwnerId, $propertySeekerId, $commissionAmount, $ratePercent, $unlockFee, $paymentStatus);
                $commission->execute();
            } catch (mysqli_sql_exception $ignored) {
                // Do not fail unlock if commission tracking table still has an unexpected schema.
            }
        }
    }

    $upsert = $db->prepare('INSERT INTO seeker_unlocked_properties (seeker_id, property_id, removed_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE removed_at = NULL, unlocked_at = CURRENT_TIMESTAMP');
    $upsert->bind_param('ii', $propertySeekerId, $propertyId);
    $upsert->execute();
}

function remembered_accounts() {
    $raw = $_COOKIE['digihome_accounts'] ?? '[]';
    $accounts = json_decode($raw, true);
    if (is_array($accounts) && isset($accounts['accounts']) && is_array($accounts['accounts'])) {
        $accounts = $accounts['accounts'];
    }
    if (!is_array($accounts)) {
        return [];
    }

    $roleAliasMap = [
        'seeker' => 'property_seeker',
        'property seeker' => 'property_seeker',
        'owner' => 'property_owner',
        'property owner' => 'property_owner',
        'marketer' => 'marketer',
        'admin' => 'admin',
        'visitor' => 'property_seeker',
        'superadmin' => 'admin',
    ];

    $clean = [];
    $seen = [];
    foreach ($accounts as $account) {
        if (!is_array($account)) {
            continue;
        }

        $id = (int) ($account['id'] ?? ($account['user_id'] ?? ($account['account_id'] ?? 0)));
        if ($id <= 0) {
            continue;
        }

        $rawRole = strtolower(trim((string) ($account['role'] ?? ($account['user_role'] ?? ($account['role_key'] ?? ($account['role_label'] ?? ''))))));
        $mappedRole = $roleAliasMap[$rawRole] ?? $rawRole;
        $role = canonical_role($mappedRole);
        if (!array_key_exists($role, DIGIHOME_ROLES)) {
            $role = 'property_seeker';
        }

        $name = trim((string) ($account['name'] ?? ''));
        if ($name === '') {
            $firstName = trim((string) ($account['first_name'] ?? ''));
            $lastName = trim((string) ($account['last_name'] ?? ''));
            $name = trim($firstName . ' ' . $lastName);
        }
        $username = trim((string) ($account['username'] ?? ''));
        $email = trim((string) ($account['email'] ?? ''));
        if ($username === '') {
            if ($email !== '' && str_contains($email, '@')) {
                $username = (string) strstr($email, '@', true);
            }
            if ($username === '') {
                $username = $name !== '' ? $name : ('User ' . $id);
            }
        }
        if ($name === '') {
            $name = $username;
        }

        $dedupeKey = $role . ':' . $id;
        if (isset($seen[$dedupeKey])) {
            continue;
        }
        $seen[$dedupeKey] = true;

        $clean[] = [
            'id' => $id,
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'role_label' => role_label($role),
            'profile_picture' => (string) ($account['profile_picture'] ?? default_profile_picture($role)),
        ];
    }

    return $clean;
}

function remember_device_user($user) {
    $accounts = remembered_accounts();
    $normalizedUser = normalize_user($user);
    if (!$normalizedUser) {
        return;
    }

    $accountPayload = [
        'id' => (int) $normalizedUser['id'],
        'name' => $normalizedUser['name'],
        'username' => $normalizedUser['username'],
        'email' => $normalizedUser['email'],
        'role' => $normalizedUser['role'],
        'role_label' => $normalizedUser['role_label'],
        'profile_picture' => $normalizedUser['profile_picture'],
    ];

    $accounts = array_values(array_filter($accounts, static function ($account) use ($accountPayload) {
        return !(
            (int) ($account['id'] ?? 0) === (int) $accountPayload['id']
            && canonical_role((string) ($account['role'] ?? '')) === canonical_role((string) $accountPayload['role'])
        );
    }));
    array_unshift($accounts, $accountPayload);

    // Keep a larger history so each role can remember multiple profiles.
    $accounts = array_slice($accounts, 0, 30);

    setcookie('digihome_accounts', json_encode($accounts), [
        'expires' => time() + (86400 * 180),
        'path' => '/DigiHome/',
        'samesite' => 'Lax',
    ]);
}

function forget_remembered_account($accountId, $role = '') {
    $targetRole = canonical_role($role);
    $accounts = remembered_accounts();
    $filtered = array_values(array_filter($accounts, static function ($account) use ($accountId, $targetRole) {
        $sameRole = $targetRole === '' || canonical_role($account['role'] ?? '') === $targetRole;
        return !($sameRole && (int) ($account['id'] ?? 0) === (int) $accountId);
    }));

    setcookie('digihome_accounts', json_encode($filtered), [
        'expires' => time() + (86400 * 180),
        'path' => '/DigiHome/',
        'samesite' => 'Lax',
    ]);
}

function remembered_accounts_by_role() {
    $grouped = [];
    foreach (remembered_accounts() as $account) {
        $role = canonical_role($account['role'] ?? 'property_seeker');
        if (!isset($grouped[$role])) {
            $grouped[$role] = [];
        }
        $grouped[$role][] = $account;
    }
    return $grouped;
}

function login_from_remembered_account($accountId, $role = '') {
    $targetRole = canonical_role($role);
    $matched = null;
    foreach (remembered_accounts() as $account) {
        if ((int) ($account['id'] ?? 0) === (int) $accountId) {
            if ($targetRole !== '' && canonical_role($account['role'] ?? '') !== $targetRole) {
                continue;
            }
            $matched = $account;
            break;
        }
    }

    if (!$matched) {
        return null;
    }

    $db = connect_db();
    if (!$db) {
        return null;
    }

    $userId = (int) ($matched['id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }

    $user = normalize_user($row);
    if (!$user || ($targetRole !== '' && canonical_role($user['role']) !== $targetRole)) {
        return null;
    }

    return login_user($user);
}

function get_unlock_history_for_user($userId, $role) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $canonicalRole = canonical_role($role);
    if ($canonicalRole === 'property_seeker') {
        $stmt = $db->prepare('SELECT * FROM unlock_history WHERE property_seeker_id = ? ORDER BY created_at DESC');
    } elseif ($canonicalRole === 'property_owner') {
        $stmt = $db->prepare('SELECT * FROM unlock_history WHERE property_owner_id = ? ORDER BY created_at DESC');
    } else {
        $stmt = $db->prepare('SELECT * FROM unlock_history WHERE marketer_id = ? ORDER BY created_at DESC');
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function can_view_hidden_details($propertyId) {
    if (!empty($_SESSION['unlocked_properties'][$propertyId])) {
        return true;
    }

    $user = current_user();
    if (!$user || canonical_role($user['role']) !== 'property_seeker') {
        return false;
    }

    $db = connect_db();
    if (!$db) {
        return false;
    }

    $seekerId = (int) $user['id'];
    $stmt = $db->prepare('SELECT id FROM seeker_unlocked_properties WHERE seeker_id = ? AND property_id = ? AND removed_at IS NULL LIMIT 1');
    $stmt->bind_param('ii', $seekerId, $propertyId);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_assoc();
    if ($exists) {
        $_SESSION['unlocked_properties'][$propertyId] = true;
    }
    return $exists;
}

function get_seeker_unlocked_properties($seekerId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT property_id, unlocked_at FROM seeker_unlocked_properties WHERE seeker_id = ? AND removed_at IS NULL ORDER BY unlocked_at DESC');
    $stmt->bind_param('i', $seekerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $property = get_property_by_id((int) $row['property_id']);
        if ($property) {
            $property['unlocked_at'] = $row['unlocked_at'];
            $rows[] = $property;
        }
    }

    return $rows;
}

function remove_unlocked_property_for_seeker($seekerId, $propertyId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('UPDATE seeker_unlocked_properties SET removed_at = CURRENT_TIMESTAMP WHERE seeker_id = ? AND property_id = ?');
    $stmt->bind_param('ii', $seekerId, $propertyId);
    $ok = $stmt->execute();
    unset($_SESSION['unlocked_properties'][$propertyId]);
    return $ok;
}

function is_favorite_property($userId, $propertyId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('SELECT id FROM favorite_properties WHERE user_id = ? AND property_id = ? LIMIT 1');
    $stmt->bind_param('ii', $userId, $propertyId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function set_favorite_property($userId, $propertyId, $isFavorite = true) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    if ($isFavorite) {
        $stmt = $db->prepare('INSERT INTO favorite_properties (user_id, property_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP');
        $stmt->bind_param('ii', $userId, $propertyId);
        return $stmt->execute();
    }

    $stmt = $db->prepare('DELETE FROM favorite_properties WHERE user_id = ? AND property_id = ?');
    $stmt->bind_param('ii', $userId, $propertyId);
    return $stmt->execute();
}

function get_favorite_properties($userId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT property_id, created_at FROM favorite_properties WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $property = get_property_by_id((int) $row['property_id']);
        if ($property) {
            $property['favorited_at'] = $row['created_at'];
            $favorites[] = $property;
        }
    }

    return $favorites;
}

function get_properties_near_user_region($userId, $limit = 6) {
    $user = get_user_by_id((int) $userId);
    if (!$user) {
        return [];
    }

    $county = trim((string) ($user['county'] ?? ''));
    $town = trim((string) ($user['town'] ?? ''));
    $db = connect_db();
    if (!$db || ($county === '' && $town === '')) {
        return [];
    }

    $sql = 'SELECT * FROM properties WHERE 1=1';
    $params = [];
    $types = '';
    if ($county !== '') {
        $sql .= ' AND county = ?';
        $params[] = $county;
        $types .= 's';
    }
    if ($town !== '') {
        $sql .= ' AND (city = ? OR estate = ? OR location LIKE ?)';
        $params[] = $town;
        $params[] = $town;
        $params[] = '%' . $town . '%';
        $types .= 'sss';
    }
    $sql .= ' ORDER BY verified DESC, created_at DESC LIMIT ?';
    $params[] = (int) $limit;
    $types .= 'i';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = enrich_property(normalize_property($row));
    }
    return $rows;
}

function chat_response_time_minutes() {
    $minutes = (int) get_site_content('chat_response_time_minutes', '20');
    return max(5, $minutes);
}

function get_chat_greeting_message() {
    $default = 'Hello and welcome to DigiHome support. We have received your message and an admin will assist you shortly.';
    return trim((string) get_site_content('chat_greeting_message', $default));
}

function online_admin_count() {
    $db = connect_db();
    if (!$db) {
        return 0;
    }

    $result = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND status = 'active' AND last_seen_at >= (NOW() - INTERVAL 5 MINUTE)");
    $row = $result ? $result->fetch_assoc() : null;
    return (int) ($row['total'] ?? 0);
}

function is_admin_online() {
    return online_admin_count() > 0;
}

function is_user_online($userId, $windowMinutes = 5) {
    $db = connect_db();
    if (!$db || (int) $userId <= 0) {
        return false;
    }

    $minutes = max(1, (int) $windowMinutes);
    $stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND status = ? AND last_seen_at >= (NOW() - INTERVAL ? MINUTE) LIMIT 1');
    $active = 'active';
    $stmt->bind_param('isi', $userId, $active, $minutes);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function format_user_last_seen($userId) {
    $db = connect_db();
    if (!$db || (int) $userId <= 0) {
        return '';
    }

    $stmt = $db->prepare('SELECT last_seen_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $lastSeen = trim((string) ($row['last_seen_at'] ?? ''));
    if ($lastSeen === '') {
        return '';
    }

    $timestamp = strtotime($lastSeen);
    if ($timestamp === false) {
        return '';
    }

    return date('M j, Y g:i A', $timestamp);
}

function pick_idle_admin_for_assignment() {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $sql = "SELECT u.id,
                   COALESCE(MAX(c.last_message_at), '1970-01-01 00:00:00') AS last_conversation_at
            FROM users u
            LEFT JOIN conversations c ON c.assigned_admin_id = u.id
            WHERE u.role = 'admin' AND u.status = 'active'
            GROUP BY u.id
            ORDER BY last_conversation_at ASC, u.id ASC
            LIMIT 1";
    $row = $db->query($sql);
    if (!$row) {
        return null;
    }
    $admin = $row->fetch_assoc();
    return $admin ? (int) $admin['id'] : null;
}

function create_or_get_open_conversation($requesterUserId, $requesterRole, $subject = '', $forceNew = false) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $role = canonical_role($requesterRole);
    if (!$forceNew) {
        $stmt = $db->prepare("SELECT * FROM conversations WHERE requester_user_id = ? AND requester_role = ? AND conversation_scope = 'support' AND status = 'open' ORDER BY updated_at DESC LIMIT 1");
        $stmt->bind_param('is', $requesterUserId, $role);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            return $existing;
        }
    }

    $assignedAdminId = pick_idle_admin_for_assignment();
    $cleanSubject = trim((string) $subject);
    $scope = 'support';
    $insert = $db->prepare('INSERT INTO conversations (requester_user_id, requester_role, assigned_admin_id, conversation_scope, subject, status, last_message_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
    $status = 'open';
    $insert->bind_param('isisss', $requesterUserId, $role, $assignedAdminId, $scope, $cleanSubject, $status);
    $insert->execute();

    $id = (int) $insert->insert_id;
    return get_conversation_by_id($id);
}

function create_direct_conversation($senderUserId, $senderRole, $recipientUserId, $recipientRole, $subject = '', $scope = 'direct') {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $senderRole = canonical_role($senderRole);
    $recipientRole = canonical_role($recipientRole);
    $scope = in_array($scope, ['direct', 'admin_direct', 'admin_broadcast'], true) ? $scope : 'direct';
    $cleanSubject = trim((string) $subject);
    $assignedAdminId = $recipientRole === 'admin' && $scope !== 'admin_broadcast' ? $recipientUserId : null;

    $insert = $db->prepare('INSERT INTO conversations (requester_user_id, requester_role, assigned_admin_id, conversation_scope, recipient_user_id, recipient_role, subject, status, last_message_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
    $status = 'open';
    $insert->bind_param('isisisss', $senderUserId, $senderRole, $assignedAdminId, $scope, $recipientUserId, $recipientRole, $cleanSubject, $status);
    $insert->execute();

    return get_conversation_by_id((int) $insert->insert_id);
}

function get_conversation_by_id($conversationId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }
    $stmt = $db->prepare('SELECT * FROM conversations WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function store_chat_message_attachment($messageId, $mediaPath, $mediaType = '', $mediaName = '') {
    $db = connect_db();
    if (!$db || (int) $messageId <= 0) {
        return false;
    }

    $mediaPath = trim((string) $mediaPath);
    if ($mediaPath === '') {
        return false;
    }

    $stmt = $db->prepare('INSERT INTO conversation_message_attachments (message_id, media_path, media_type, media_name) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $messageId, $mediaPath, $mediaType, $mediaName);
    return $stmt->execute();
}

function add_conversation_message($conversationId, $senderUserId, $senderRole, $messageBody, $mediaPath = '', $mediaType = '', $mediaName = '', $attachments = [], $isSystemEvent = false) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $body = trim((string) $messageBody);
    $mediaPath = trim((string) $mediaPath);
    $mediaType = trim((string) $mediaType);
    $mediaName = trim((string) $mediaName);
    $attachments = is_array($attachments) ? array_values($attachments) : [];
    if ($body === '' && $mediaPath === '' && $attachments === []) {
        return false;
    }

    $role = canonical_role($senderRole);
    $isSystemEventInt = $isSystemEvent ? 1 : 0;
    $stmt = $db->prepare('INSERT INTO conversation_messages (conversation_id, sender_user_id, sender_role, message_body, media_path, media_type, media_name, is_system_event) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iisssssi', $conversationId, $senderUserId, $role, $body, $mediaPath, $mediaType, $mediaName, $isSystemEventInt);
    $ok = $stmt->execute();
    $messageId = (int) $stmt->insert_id;

    if ($ok && $messageId > 0) {
        $seenAttachmentPaths = [];
        if ($mediaPath !== '') {
            $seenAttachmentPaths[$mediaPath] = true;
        }

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $attachmentPath = trim((string) ($attachment['media_path'] ?? ''));
            if ($attachmentPath === '' || isset($seenAttachmentPaths[$attachmentPath])) {
                continue;
            }
            $seenAttachmentPaths[$attachmentPath] = true;
            store_chat_message_attachment(
                $messageId,
                $attachmentPath,
                trim((string) ($attachment['media_type'] ?? '')),
                trim((string) ($attachment['media_name'] ?? ''))
            );
        }

        $update = $db->prepare('UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $update->bind_param('i', $conversationId);
        $update->execute();
    }

    return $ok ? $messageId : false;
}

function send_support_greeting_if_missing($conversationId) {
    $db = connect_db();
    if (!$db || (int) $conversationId <= 0) {
        return false;
    }

    $conversation = get_conversation_by_id((int) $conversationId);
    if (!$conversation || (string) ($conversation['conversation_scope'] ?? '') !== 'support') {
        return false;
    }

    $message = get_chat_greeting_message();
    if ($message === '') {
        return false;
    }

    $existingStmt = $db->prepare("SELECT id FROM conversation_messages WHERE conversation_id = ? AND sender_role = 'admin' AND message_body = ? LIMIT 1");
    $existingStmt->bind_param('is', $conversationId, $message);
    $existingStmt->execute();
    if ($existingStmt->get_result()->fetch_assoc()) {
        return false;
    }

    $adminId = (int) ($conversation['assigned_admin_id'] ?? 0);
    if ($adminId <= 0) {
        $adminId = (int) (pick_idle_admin_for_assignment() ?? 0);
    }
    if ($adminId <= 0) {
        return false;
    }

    return add_conversation_message((int) $conversationId, $adminId, 'admin', $message);
}

function get_conversation_message_by_id($messageId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM conversation_messages WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function update_conversation_message($messageId, $editorUserId, $newBody) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $message = get_conversation_message_by_id((int) $messageId);
    if (!$message || (int) ($message['sender_user_id'] ?? 0) !== (int) $editorUserId) {
        return false;
    }
    if (!empty($message['is_system_event'])) {
        return false;
    }

    $body = trim((string) $newBody);
    if ($body === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE conversation_messages SET message_body = ?, edited_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->bind_param('si', $body, $messageId);
    $ok = $stmt->execute();
    if ($ok) {
        $conversationId = (int) ($message['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            $update = $db->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP, last_message_at = CURRENT_TIMESTAMP WHERE id = ?');
            $update->bind_param('i', $conversationId);
            $update->execute();
        }
    }

    return $ok;
}

function ensure_conversation_greeting_message($conversationId) {
    $db = connect_db();
    if (!$db || (int) $conversationId <= 0) {
        return false;
    }

    $message = get_chat_greeting_message();
    if ($message === '') {
        return false;
    }

    $countStmt = $db->prepare('SELECT COUNT(*) AS total FROM conversation_messages WHERE conversation_id = ?');
    $countStmt->bind_param('i', $conversationId);
    $countStmt->execute();
    $total = (int) (($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
    if ($total > 0) {
        return false;
    }

    $conversation = get_conversation_by_id((int) $conversationId);
    if (!$conversation) {
        return false;
    }

    $adminId = (int) ($conversation['assigned_admin_id'] ?? 0);
    if ($adminId <= 0) {
        $adminId = (int) (pick_idle_admin_for_assignment() ?? 0);
    }
    if ($adminId <= 0) {
        return false;
    }

    return add_conversation_message((int) $conversationId, $adminId, 'admin', $message);
}

function mark_messages_delivered_for_role($conversationId, $receiverRole, $receiverUserId = 0) {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $receiverUserId = (int) $receiverUserId;
    if ($receiverUserId <= 0) {
        $stmt = $db->prepare("UPDATE conversation_messages SET delivered_at = COALESCE(delivered_at, CURRENT_TIMESTAMP) WHERE conversation_id = ? AND sender_role <> ?");
        $role = canonical_role($receiverRole);
        $stmt->bind_param('is', $conversationId, $role);
    } else {
        $stmt = $db->prepare('UPDATE conversation_messages SET delivered_at = COALESCE(delivered_at, CURRENT_TIMESTAMP) WHERE conversation_id = ? AND sender_user_id <> ?');
        $stmt->bind_param('ii', $conversationId, $receiverUserId);
    }
    $stmt->execute();
}

function mark_conversation_read($conversationId, $readerUserId, $readerRole) {
    $db = connect_db();
    if (!$db) {
        return;
    }

    $stmt = $db->prepare('UPDATE conversation_messages SET read_at = COALESCE(read_at, CURRENT_TIMESTAMP), delivered_at = COALESCE(delivered_at, CURRENT_TIMESTAMP) WHERE conversation_id = ? AND sender_user_id <> ?');
    $stmt->bind_param('ii', $conversationId, $readerUserId);
    $stmt->execute();

    if (canonical_role($readerRole) === 'admin') {
        $upsert = $db->prepare('INSERT INTO conversation_admin_reads (conversation_id, admin_id, last_read_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE last_read_at = CURRENT_TIMESTAMP');
        $upsert->bind_param('ii', $conversationId, $readerUserId);
        $upsert->execute();
    }
}

function get_conversation_messages($conversationId, $role = null) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT cm.*, u.first_name, u.last_name, u.role AS sender_user_role, u.profile_picture AS sender_profile_picture
                         FROM conversation_messages cm
                         LEFT JOIN users u ON u.id = cm.sender_user_id
                         WHERE cm.conversation_id = ?
                         ORDER BY cm.created_at ASC, cm.id ASC');
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    // System event notices (assignment/status changes) are admin-only.
    $includeSystemEvents = $role === null || canonical_role($role) === 'admin';
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if (!$includeSystemEvents && !empty($row['is_system_event'])) {
            continue;
        }
        $row['attachments'] = get_chat_message_attachments((int) ($row['id'] ?? 0));
        $rows[] = $row;
    }
    return $rows;
}

function get_chat_message_attachments($messageId) {
    $db = connect_db();
    if (!$db || (int) $messageId <= 0) {
        return [];
    }

    $stmt = $db->prepare('SELECT id, media_path, media_type, media_name, created_at FROM conversation_message_attachments WHERE message_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_user_open_conversation($userId, $role) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $canonical = canonical_role($role);
    $stmt = $db->prepare("SELECT * FROM conversations WHERE requester_user_id = ? AND requester_role = ? AND status = 'open' ORDER BY updated_at DESC LIMIT 1");
    $stmt->bind_param('is', $userId, $canonical);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function get_conversation_unread_count_map($userId, $role) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $userId = (int) $userId;
    $role = canonical_role($role);

    if ($role === 'admin') {
        $sql = "SELECT c.id AS conversation_id, COUNT(*) AS unread_count
                FROM conversation_messages m
                JOIN conversations c ON c.id = m.conversation_id
                WHERE m.read_at IS NULL
                  AND m.sender_user_id <> ?
                  AND (
                    c.conversation_scope = 'support'
                    OR c.conversation_scope = 'admin_broadcast'
                    OR c.requester_user_id = ?
                    OR c.recipient_user_id = ?
                  )
                GROUP BY c.id";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iii', $userId, $userId, $userId);
    } else {
        $sql = "SELECT c.id AS conversation_id, COUNT(*) AS unread_count
                FROM conversation_messages m
                JOIN conversations c ON c.id = m.conversation_id
                WHERE m.read_at IS NULL
                  AND m.sender_user_id <> ?
                  AND (
                    (c.requester_user_id = ? AND c.requester_role = ?)
                    OR (c.recipient_user_id = ? AND c.recipient_role = ?)
                  )
                GROUP BY c.id";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iisis', $userId, $userId, $role, $userId, $role);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $counts = [];
    while ($result && $row = $result->fetch_assoc()) {
        $conversationId = (int) ($row['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            $counts[$conversationId] = (int) ($row['unread_count'] ?? 0);
        }
    }

    return $counts;
}

function append_unread_counts_to_conversations(array $conversations, $userId, $role) {
    if ($conversations === []) {
        return $conversations;
    }

    $unreadMap = get_conversation_unread_count_map((int) $userId, $role);
    foreach ($conversations as &$conversation) {
        $conversationId = (int) ($conversation['id'] ?? 0);
        $conversation['unread_count'] = (int) ($unreadMap[$conversationId] ?? 0);
    }
    unset($conversation);

    return $conversations;
}

function get_user_conversations($userId, $role) {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $canonical = canonical_role($role);
    if ($canonical === 'admin') {
        $stmt = $db->prepare("SELECT c.*,
                                     requester.first_name AS requester_first_name,
                                     requester.last_name AS requester_last_name,
                                     recipient.first_name AS recipient_first_name,
                                     recipient.last_name AS recipient_last_name,
                                     assignee.first_name AS assigned_first_name,
                                     assignee.last_name AS assigned_last_name
                              FROM conversations c
                              JOIN users requester ON requester.id = c.requester_user_id
                              LEFT JOIN users recipient ON recipient.id = c.recipient_user_id
                              LEFT JOIN users assignee ON assignee.id = c.assigned_admin_id
                              WHERE c.conversation_scope IN ('support', 'admin_direct', 'admin_broadcast', 'direct')
                                AND (c.requester_user_id = ? OR c.recipient_user_id = ? OR c.conversation_scope = 'admin_broadcast')
                              ORDER BY c.updated_at DESC");
        $stmt->bind_param('ii', $userId, $userId);
    } else {
        $stmt = $db->prepare('SELECT c.*,
                                     requester.first_name AS requester_first_name,
                                     requester.last_name AS requester_last_name,
                                     recipient.first_name AS recipient_first_name,
                                     recipient.last_name AS recipient_last_name,
                                     assignee.first_name AS assigned_first_name,
                                     assignee.last_name AS assigned_last_name
                              FROM conversations c
                              JOIN users requester ON requester.id = c.requester_user_id
                              LEFT JOIN users recipient ON recipient.id = c.recipient_user_id
                              LEFT JOIN users assignee ON assignee.id = c.assigned_admin_id
                              WHERE (c.requester_user_id = ? AND c.requester_role = ?)
                                 OR (c.recipient_user_id = ? AND c.recipient_role = ?)
                              ORDER BY c.updated_at DESC');
        $stmt->bind_param('isis', $userId, $canonical, $userId, $canonical);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return append_unread_counts_to_conversations($rows, $userId, $canonical);
}

function get_admin_conversations($adminId = 0) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $adminId = (int) $adminId;
    $delayMinutes = chat_response_time_minutes();

    $sql = "SELECT c.*, requester.first_name, requester.last_name,
                   requester.email, requester.phone,
                   requester.profile_picture AS requester_profile_picture,
                   requester.first_name AS requester_first_name,
                   requester.last_name AS requester_last_name,
                   recipient.profile_picture AS recipient_profile_picture,
                   recipient.first_name AS recipient_first_name,
                   recipient.last_name AS recipient_last_name,
                   recipient.email AS recipient_email,
                   recipient.phone AS recipient_phone,
                   CASE
                       WHEN requester.status = 'active' AND requester.last_seen_at >= (NOW() - INTERVAL 5 MINUTE) THEN 1
                       ELSE 0
                   END AS requester_online,
                   CASE
                       WHEN recipient.id IS NOT NULL AND recipient.status = 'active' AND recipient.last_seen_at >= (NOW() - INTERVAL 5 MINUTE) THEN 1
                       ELSE 0
                   END AS recipient_online,
                   assignee.first_name AS assigned_first_name,
                   assignee.last_name AS assigned_last_name,
                   last_admin.sender_user_id AS last_admin_responder_id,
                   responder.first_name AS responder_first_name,
                   responder.last_name AS responder_last_name,
                   CASE
                       WHEN c.status = 'open' AND TIMESTAMPDIFF(MINUTE, COALESCE(c.last_message_at, c.created_at), NOW()) >= ? THEN 1
                       ELSE 0
                   END AS is_delayed
            FROM conversations c
            JOIN users requester ON requester.id = c.requester_user_id
            LEFT JOIN users recipient ON recipient.id = c.recipient_user_id
            LEFT JOIN users assignee ON assignee.id = c.assigned_admin_id
            LEFT JOIN conversation_messages last_admin ON last_admin.id = (
                SELECT cm.id FROM conversation_messages cm
                WHERE cm.conversation_id = c.id AND cm.sender_role = 'admin'
                ORDER BY cm.created_at DESC, cm.id DESC LIMIT 1
            )
            LEFT JOIN users responder ON responder.id = last_admin.sender_user_id
            WHERE c.conversation_scope IN ('support', 'admin_direct', 'admin_broadcast', 'direct')
              AND (
                c.conversation_scope = 'support'
                OR c.conversation_scope = 'admin_broadcast'
                OR c.requester_user_id = ?
                OR c.recipient_user_id = ?
              )
            ORDER BY (c.status = 'open') DESC, c.updated_at DESC";

    $stmt = $db->prepare($sql);
        $stmt->bind_param('iii', $delayMinutes, $adminId, $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return append_unread_counts_to_conversations($rows, $adminId, 'admin');
}

function get_chat_unread_count($userId, $role) {
    $db = connect_db();
    if (!$db) {
        return 0;
    }

    $role = canonical_role($role);
    if ($role === 'admin') {
        $sql = "SELECT COUNT(*) AS total
                FROM conversation_messages m
                JOIN conversations c ON c.id = m.conversation_id
                WHERE m.read_at IS NULL
                  AND m.sender_user_id <> ?
                  AND (
                    c.conversation_scope = 'support'
                    OR c.conversation_scope = 'admin_broadcast'
                    OR c.requester_user_id = ?
                    OR c.recipient_user_id = ?
                  )";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iii', $userId, $userId, $userId);
    } else {
        $sql = "SELECT COUNT(*) AS total
                FROM conversation_messages m
                JOIN conversations c ON c.id = m.conversation_id
                WHERE m.read_at IS NULL
                  AND m.sender_user_id <> ?
                  AND (
                    (c.requester_user_id = ? AND c.requester_role = ?)
                    OR (c.recipient_user_id = ? AND c.recipient_role = ?)
                  )";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iisis', $userId, $userId, $role, $userId, $role);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function set_conversation_typing_state($conversationId, $userId, $role, $isTyping) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $role = canonical_role($role);
    if ($isTyping) {
        $stmt = $db->prepare('UPDATE conversations SET typing_user_id = ?, typing_role = ?, typing_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->bind_param('isi', $userId, $role, $conversationId);
        return $stmt->execute();
    }

    $stmt = $db->prepare('UPDATE conversations SET typing_user_id = NULL, typing_role = NULL, typing_at = NULL WHERE id = ? AND typing_user_id = ?');
    $stmt->bind_param('ii', $conversationId, $userId);
    return $stmt->execute();
}

function get_conversation_typing_state($conversationId, $viewerUserId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT typing_user_id, typing_role, typing_at FROM conversations WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $conversation = $stmt->get_result()->fetch_assoc();
    if (!$conversation) {
        return null;
    }

    $typingUserId = (int) ($conversation['typing_user_id'] ?? 0);
    $typingAt = strtotime((string) ($conversation['typing_at'] ?? '')) ?: 0;
    if ($typingUserId <= 0 || $typingUserId === (int) $viewerUserId || $typingAt < (time() - 5)) {
        return null;
    }

    $sender = get_user_by_id($typingUserId);
    return [
        'user_id' => $typingUserId,
        'role' => canonical_role($conversation['typing_role'] ?? ($sender['role'] ?? '')),
        'label' => $sender ? trim((string) ($sender['first_name'] ?? '') . ' ' . (string) ($sender['last_name'] ?? '')) : 'Typing user',
        'profile_picture' => $sender ? (string) ($sender['profile_picture'] ?? '') : '',
    ];
}

function get_chat_status_summary($userId, $role) {
    $role = canonical_role($role);
    return [
        'unread_count' => get_chat_unread_count($userId, $role),
        'admin_online' => is_admin_online(),
        'chat_path' => role_chat_path($role),
        'role' => $role,
    ];
}

function assign_conversation_admin($conversationId, $adminId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $conversationId = (int) $conversationId;
    $adminId = (int) $adminId;
    if ($conversationId <= 0 || $adminId <= 0) {
        return false;
    }

    $conversation = get_conversation_by_id($conversationId);
    $currentAdminId = (int) ($conversation['assigned_admin_id'] ?? 0);
    if ($currentAdminId === $adminId) {
        return true;
    }

    $stmt = $db->prepare('UPDATE conversations SET assigned_admin_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->bind_param('ii', $adminId, $conversationId);
    $ok = $stmt->execute();

    if ($ok) {
        $actor = current_user();
        $actorName = trim((string) (($actor['first_name'] ?? '') . ' ' . ($actor['last_name'] ?? '')));
        if ($actorName === '') {
            $actorName = (string) ($actor['name'] ?? 'Admin');
        }

        $newAdmin = get_user_by_id($adminId);
        $newAdminName = trim((string) ((($newAdmin['first_name'] ?? '') . ' ' . ($newAdmin['last_name'] ?? ''))));
        if ($newAdminName === '') {
            $newAdminName = (string) ($newAdmin['name'] ?? 'Admin');
        }

        add_conversation_message(
            $conversationId,
            (int) ($actor['id'] ?? 0),
            (string) ($actor['role'] ?? 'admin'),
            "Assigned to {$newAdminName} by {$actorName}",
            '',
            '',
            '',
            [],
            true
        );
    }

    return $ok;
}

function set_conversation_status($conversationId, $status) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $conversationId = (int) $conversationId;
    $previousConversation = get_conversation_by_id($conversationId);
    $previousStatus = (string) ($previousConversation['status'] ?? 'open');
    $nextStatus = $status === 'closed' ? 'closed' : 'open';
    if ($nextStatus === 'closed') {
        $stmt = $db->prepare('UPDATE conversations SET status = ?, closed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    } else {
        $stmt = $db->prepare('UPDATE conversations SET status = ?, closed_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    }
    $stmt->bind_param('si', $nextStatus, $conversationId);
    $ok = $stmt->execute();

    if ($ok && $previousStatus !== $nextStatus) {
        $actor = current_user();
        $actorName = trim((string) (($actor['first_name'] ?? '') . ' ' . ($actor['last_name'] ?? '')));
        if ($actorName === '') {
            $actorName = (string) ($actor['name'] ?? 'Admin');
        }
        $eventBody = $nextStatus === 'closed'
            ? 'Closed by ' . $actorName
            : 'Opened by ' . $actorName;
        add_conversation_message($conversationId, (int) ($actor['id'] ?? 0), (string) ($actor['role'] ?? 'admin'), $eventBody, '', '', '', [], true);
    }

    return $ok;
}

function get_admin_readers_for_conversation($conversationId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT car.admin_id, car.last_read_at, u.first_name, u.last_name
            FROM conversation_admin_reads car
            JOIN users u ON u.id = car.admin_id
            WHERE car.conversation_id = ?
            ORDER BY car.last_read_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_delayed_open_conversations() {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $minutes = chat_response_time_minutes();
    $stmt = $db->prepare("SELECT * FROM conversations WHERE status = 'open' AND TIMESTAMPDIFF(MINUTE, COALESCE(last_message_at, created_at), NOW()) >= ? ORDER BY last_message_at ASC");
    $stmt->bind_param('i', $minutes);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function request_role_context() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (str_contains($path, '/DigiHome/admin/')) {
        return 'admin';
    }
    if (str_contains($path, '/DigiHome/owner/')) {
        return 'property_owner';
    }
    if (str_contains($path, '/DigiHome/marketer/')) {
        return 'marketer';
    }
    if (str_contains($path, '/DigiHome/seeker/')) {
        return 'property_seeker';
    }
    return '';
}

function touch_user_presence($userId) {
    static $updated = false;
    if ($updated || $userId <= 0) {
        return;
    }
    $db = connect_db();
    if (!$db) {
        return;
    }
    $stmt = $db->prepare('UPDATE users SET last_seen_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $updated = true;
}

function user_initials($user) {
    $first = strtoupper(substr((string) ($user['first_name'] ?? ''), 0, 1));
    $last = strtoupper(substr((string) ($user['last_name'] ?? ''), 0, 1));
    $combined = trim($first . $last);
    if ($combined === '') {
        $combined = strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 2));
    }
    return $combined;
}

function get_unlock_fee_amount() {
    return max(0, (float) get_site_content('unlock_fee_amount', '250'));
}

function get_default_commission_rate_percent() {
    return max(0, min(100, (float) get_site_content('default_commission_rate_percent', '15')));
}

function get_commission_policy_rate($marketerId, $propertyId) {
    $db = connect_db();
    $defaultRate = get_default_commission_rate_percent();
    if (!$db || $marketerId <= 0) {
        return $defaultRate;
    }

    $stmt = $db->prepare("SELECT rate_percent FROM commission_policies WHERE scope_type = 'property' AND property_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('i', $propertyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return max(0, min(100, (float) $row['rate_percent']));
    }

    $stmt = $db->prepare("SELECT rate_percent FROM commission_policies WHERE scope_type = 'marketer' AND marketer_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return max(0, min(100, (float) $row['rate_percent']));
    }

    $stmt = $db->prepare("SELECT rate_percent FROM commission_policies WHERE scope_type = 'global' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return max(0, min(100, (float) $row['rate_percent']));
    }

    return $defaultRate;
}

function set_commission_policy_rate($scopeType, $ratePercent, $marketerId = null, $propertyId = null) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $scope = in_array($scopeType, ['global', 'marketer', 'property'], true) ? $scopeType : 'global';
    $rate = max(0, min(100, (float) $ratePercent));
    $marketer = $marketerId !== null ? (int) $marketerId : null;
    $property = $propertyId !== null ? (int) $propertyId : null;

    if ($scope === 'global') {
        $stmt = $db->prepare("INSERT INTO commission_policies (scope_type, marketer_id, property_id, rate_percent, is_active) VALUES ('global', NULL, NULL, ?, 1) ON DUPLICATE KEY UPDATE rate_percent = VALUES(rate_percent), is_active = 1");
        $stmt->bind_param('d', $rate);
        return $stmt->execute();
    }

    if ($scope === 'marketer') {
        if ($marketer === null || $marketer <= 0) {
            return false;
        }
        $stmt = $db->prepare("INSERT INTO commission_policies (scope_type, marketer_id, property_id, rate_percent, is_active) VALUES ('marketer', ?, NULL, ?, 1) ON DUPLICATE KEY UPDATE rate_percent = VALUES(rate_percent), is_active = 1");
        $stmt->bind_param('id', $marketer, $rate);
        return $stmt->execute();
    }

    if ($property === null || $property <= 0) {
        return false;
    }
    $stmt = $db->prepare("INSERT INTO commission_policies (scope_type, marketer_id, property_id, rate_percent, is_active) VALUES ('property', ?, ?, ?, 1) ON DUPLICATE KEY UPDATE rate_percent = VALUES(rate_percent), is_active = 1");
    $stmt->bind_param('iid', $marketer, $property, $rate);
    return $stmt->execute();
}

function get_commission_policies_map() {
    $db = connect_db();
    if (!$db) {
        return ['global' => null, 'marketer' => [], 'property' => []];
    }

    $result = $db->query('SELECT * FROM commission_policies WHERE is_active = 1');
    $map = ['global' => null, 'marketer' => [], 'property' => []];
    while ($result && $row = $result->fetch_assoc()) {
        $scope = (string) ($row['scope_type'] ?? '');
        if ($scope === 'global') {
            $map['global'] = (float) $row['rate_percent'];
        } elseif ($scope === 'marketer') {
            $map['marketer'][(int) $row['marketer_id']] = (float) $row['rate_percent'];
        } elseif ($scope === 'property') {
            $map['property'][(int) $row['property_id']] = (float) $row['rate_percent'];
        }
    }
    return $map;
}

function verify_user_password($userId, $password) {
    $db = connect_db();
    if (!$db || $userId <= 0 || $password === '') {
        return false;
    }

    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return false;
    }
    $hash = (string) ($row['password_hash'] ?? '');
    return $hash !== '' && password_verify((string) $password, $hash);
}

function marketer_commission_balance($marketerId) {
    $db = connect_db();
    if (!$db) {
        return 0.0;
    }
    $stmt = $db->prepare("SELECT COALESCE(SUM(commission_amount), 0) AS total FROM commissions WHERE marketer_id = ? AND payment_status = 'accrued'");
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float) ($row['total'] ?? 0);
}

function marketer_commission_by_property($marketerId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $sql = "SELECT c.property_id, p.title, COUNT(*) AS unlocks, SUM(c.unlock_fee_amount) AS unlock_value,
                   SUM(c.commission_amount) AS commission_total
            FROM commissions c
            JOIN properties p ON p.id = c.property_id
            WHERE c.marketer_id = ?
            GROUP BY c.property_id, p.title
            ORDER BY commission_total DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function marketer_withdrawal_requests($marketerId) {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $stmt = $db->prepare('SELECT wr.*, wp.created_at AS payout_at FROM withdrawal_requests wr LEFT JOIN withdrawal_payouts wp ON wp.withdrawal_request_id = wr.id WHERE wr.marketer_id = ? ORDER BY wr.requested_at DESC');
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function has_pending_withdrawal_request($marketerId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT id FROM withdrawal_requests WHERE marketer_id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function create_withdrawal_request($marketerId, $amount, $accountName, $accountNumber, $accountPassword) {
    $db = connect_db();
    if (!$db) {
        return [false, 'Unable to connect to the database.'];
    }

    $amountValue = (float) $amount;
    $name = trim((string) $accountName);
    $number = trim((string) $accountNumber);
    if ($name === '' || $number === '') {
        return [false, 'Account name and account number are required.'];
    }
    if (!verify_user_password((int) $marketerId, (string) $accountPassword)) {
        return [false, 'Invalid account password.'];
    }

    if (has_pending_withdrawal_request((int) $marketerId)) {
        return [false, 'You already have a pending withdrawal request.'];
    }

    $min = max(0, (float) get_site_content('withdrawal_min_amount', '500'));
    $max = max($min, (float) get_site_content('withdrawal_max_amount', '200000'));
    if ($amountValue < $min || $amountValue > $max) {
        return [false, 'Amount must be between KES ' . number_format($min) . ' and KES ' . number_format($max) . '.'];
    }

    $balance = marketer_commission_balance((int) $marketerId);
    if ($amountValue > $balance) {
        return [false, 'Amount cannot exceed available balance.'];
    }

    $stmt = $db->prepare('INSERT INTO withdrawal_requests (marketer_id, amount, account_name, account_number, status, reason) VALUES (?, ?, ?, ?, ?, ?)');
    $status = 'pending';
    $reason = 'Awaiting admin review';
    $stmt->bind_param('idssss', $marketerId, $amountValue, $name, $number, $status, $reason);
    $ok = $stmt->execute();
    return [$ok, $ok ? 'Withdrawal request submitted.' : 'Failed to submit withdrawal request.'];
}

function get_all_withdrawal_requests() {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $sql = "SELECT wr.*, u.first_name, u.last_name, u.email,
                   admin.first_name AS admin_first_name, admin.last_name AS admin_last_name,
                   wp.id AS payout_id, wp.created_at AS payout_at
            FROM withdrawal_requests wr
            JOIN users u ON u.id = wr.marketer_id
            LEFT JOIN users admin ON admin.id = wr.processed_by_admin_id
            LEFT JOIN withdrawal_payouts wp ON wp.withdrawal_request_id = wr.id
            ORDER BY wr.requested_at DESC";
    $result = $db->query($sql);
    $rows = [];
    while ($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_withdrawal_payouts($limit = 100) {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $limitValue = max(1, min(500, (int) $limit));
    $sql = "SELECT wp.*, wr.account_name, wr.account_number,
                   m.first_name AS marketer_first_name, m.last_name AS marketer_last_name,
                   a.first_name AS admin_first_name, a.last_name AS admin_last_name
            FROM withdrawal_payouts wp
            JOIN withdrawal_requests wr ON wr.id = wp.withdrawal_request_id
            JOIN users m ON m.id = wp.marketer_id
            JOIN users a ON a.id = wp.processed_by_admin_id
            ORDER BY wp.created_at DESC
            LIMIT " . $limitValue;
    $result = $db->query($sql);
    $rows = [];
    while ($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function marketer_withdrawal_total_paid($marketerId) {
    $db = connect_db();
    if (!$db) {
        return 0.0;
    }
    $stmt = $db->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM withdrawal_payouts WHERE marketer_id = ?');
    $stmt->bind_param('i', $marketerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float) ($row['total'] ?? 0);
}

function update_withdrawal_request_status($requestId, $status, $adminId, $reason = '') {
    $db = connect_db();
    if (!$db) {
        return false;
    }
    $request = $db->prepare('SELECT id, marketer_id, amount, status FROM withdrawal_requests WHERE id = ? LIMIT 1');
    $request->bind_param('i', $requestId);
    $request->execute();
    $current = $request->get_result()->fetch_assoc();
    if (!$current) {
        return false;
    }

    $currentStatus = (string) ($current['status'] ?? 'pending');
    if ($currentStatus !== 'pending') {
        return false;
    }

    $next = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';
    $message = trim((string) $reason);
    if ($next === 'approved' && $message === '') {
        $message = 'Withdrawal approved successfully.';
    }
    if ($next === 'pending' && $message === '') {
        $message = 'Awaiting admin review';
    }

    $db->begin_transaction();
    try {
        $stmt = $db->prepare('UPDATE withdrawal_requests SET status = ?, reason = ?, processed_at = CURRENT_TIMESTAMP, processed_by_admin_id = ? WHERE id = ?');
        $stmt->bind_param('ssii', $next, $message, $adminId, $requestId);
        $ok = $stmt->execute();
        if (!$ok) {
            throw new RuntimeException('Failed to update request status.');
        }

        if ($next === 'approved') {
            $marketerId = (int) $current['marketer_id'];
            $amount = (float) $current['amount'];
            $remaining = $amount;

            $exists = $db->prepare('SELECT id FROM withdrawal_payouts WHERE withdrawal_request_id = ? LIMIT 1');
            $exists->bind_param('i', $requestId);
            $exists->execute();
            if ($exists->get_result()->fetch_assoc()) {
                throw new RuntimeException('Payout already logged for this request.');
            }

            $commissionRows = $db->query("SELECT id, commission_amount FROM commissions WHERE marketer_id = " . $marketerId . " AND payment_status = 'accrued' ORDER BY unlock_date ASC, id ASC");
            while ($commissionRows && $item = $commissionRows->fetch_assoc()) {
                if ($remaining <= 0) {
                    break;
                }
                $commissionId = (int) $item['id'];
                $commissionAmount = (float) $item['commission_amount'];
                if ($commissionAmount <= $remaining + 0.0001) {
                    $db->query("UPDATE commissions SET payment_status = 'paid_out' WHERE id = " . $commissionId);
                    $remaining -= $commissionAmount;
                }
            }

            $payout = $db->prepare('INSERT INTO withdrawal_payouts (withdrawal_request_id, marketer_id, amount, processed_by_admin_id, notes) VALUES (?, ?, ?, ?, ?)');
            $payout->bind_param('iidis', $requestId, $marketerId, $amount, $adminId, $message);
            if (!$payout->execute()) {
                throw new RuntimeException('Failed to record payout.');
            }
        }

        $db->commit();
        return true;
    } catch (Throwable $e) {
        $db->rollback();
        return false;
    }
}

function add_flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function store_profile_image_upload($fileInputName, $existingPath = '', $role = 'property_seeker') {
    if (empty($_FILES[$fileInputName]) || !is_array($_FILES[$fileInputName])) {
        return $existingPath;
    }

    $file = $_FILES[$fileInputName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = mime_content_type($tmp) ?: '';
    if (!isset($allowed[$mime])) {
        return $existingPath;
    }

    $normalizedRole = canonical_role($role);
    $folderMap = [
        'property_seeker' => 'seeker',
        'property_owner' => 'owner',
        'marketer' => 'marketer',
        'admin' => 'admin',
    ];
    $folder = $folderMap[$normalizedRole] ?? 'seeker';
    $extension = $allowed[$mime];
    $targetDir = dirname(__DIR__) . '/assets/img/users/' . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $uniqueName = 'profile_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $targetPath = $targetDir . '/' . $uniqueName;
    if (!move_uploaded_file($tmp, $targetPath)) {
        return $existingPath;
    }

    return '/DigiHome/assets/img/users/' . $folder . '/' . $uniqueName;
}

function store_team_member_image_upload($fileInputName, $existingPath = '') {
    if (empty($_FILES[$fileInputName]) || !is_array($_FILES[$fileInputName])) {
        return $existingPath;
    }

    $file = $_FILES[$fileInputName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return $existingPath;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = mime_content_type($tmp) ?: '';
    if (!isset($allowed[$mime])) {
        return $existingPath;
    }

    $extension = $allowed[$mime];
    $targetDir = dirname(__DIR__) . '/assets/img/team';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $uniqueName = 'team_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $targetPath = $targetDir . '/' . $uniqueName;
    if (!move_uploaded_file($tmp, $targetPath)) {
        return $existingPath;
    }

    return '/DigiHome/assets/img/team/' . $uniqueName;
}

function store_company_logo_upload($fileInputName) {
    if (empty($_FILES[$fileInputName]) || !is_array($_FILES[$fileInputName])) {
        return false;
    }

    $file = $_FILES[$fileInputName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return false;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return false;
    }

    $targetDir = dirname(__DIR__) . '/assets/img/system';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . '/company-logo.png';
    $binary = @file_get_contents($tmp);
    if ($binary === false) {
        return false;
    }

    if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }
        $saved = @imagepng($image, $targetPath);
        if (function_exists('imagedestroy')) {
            imagedestroy($image);
        }
        return $saved ? company_logo_path() : false;
    }

    $mime = mime_content_type($tmp) ?: '';
    if ($mime !== 'image/png') {
        return false;
    }

    if (!move_uploaded_file($tmp, $targetPath)) {
        return false;
    }

    return company_logo_path();
}

function ensure_image_directories() {
    $base = dirname(__DIR__) . '/assets/img';
    foreach (['system', 'users', 'properties'] as $folder) {
        $path = $base . '/' . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    $systemFiles = ['logo.png', 'kplc 1.png', 'kplc 2.png', 'kplc 3.png', 'kplc 4.png', 'kplc 5.png', 'kplc 6.png'];
    foreach ($systemFiles as $fileName) {
        $legacyPath = $base . '/' . $fileName;
        $systemPath = $base . '/system/' . $fileName;
        if (is_file($legacyPath) && !is_file($systemPath)) {
            @copy($legacyPath, $systemPath);
        }
    }

    // Migrate legacy logo to the canonical company logo filename.
    $legacyLogo = $base . '/system/logo.png';
    $companyLogo = $base . '/system/company-logo.png';
    if (is_file($legacyLogo) && !is_file($companyLogo)) {
        @copy($legacyLogo, $companyLogo);
    }
}

function normalize_system_name($systemName) {
    $name = preg_replace('/[^a-zA-Z0-9]+/', '', (string) $systemName);
    return $name !== '' ? $name : 'DigiHome';
}

function property_id_segment($propertyId) {
    $id = max(0, (int) $propertyId);
    $raw = (string) $id;
    $width = max(2, strlen($raw));
    return str_pad($raw, $width, '0', STR_PAD_LEFT);
}

function property_image_filename($systemName, $propertyId, $sequence, $extension) {
    $nameRoot = normalize_system_name((string) $systemName);
    $pid = property_id_segment((int) $propertyId);
    $seq = str_pad((string) max(1, (int) $sequence), 4, '0', STR_PAD_LEFT);
    $ext = strtolower(trim((string) $extension));
    if ($ext === '') {
        $ext = 'jpg';
    }
    return $nameRoot . '-' . $pid . '-' . $seq . '.' . $ext;
}

function property_image_web_to_file($webPath) {
    $clean = ltrim(str_replace('/DigiHome/', '', (string) $webPath), '/');
    if ($clean === '') {
        return null;
    }
    $fullPath = dirname(__DIR__) . '/' . $clean;
    return is_file($fullPath) ? $fullPath : null;
}

function next_property_image_sequence($propertyId, $systemName = 'DigiHome') {
    ensure_image_directories();
    $targetDir = dirname(__DIR__) . '/assets/img/properties';
    $nameRoot = normalize_system_name($systemName);
    $pid = property_id_segment((int) $propertyId);
    $pattern = $targetDir . '/' . $nameRoot . '-' . $pid . '-*.*';
    $maxSeq = 0;
    foreach ((array) glob($pattern) as $filePath) {
        if (preg_match('/-(\d{4})\.[a-z0-9]+$/i', basename($filePath), $matches)) {
            $maxSeq = max($maxSeq, (int) $matches[1]);
        }
    }
    return $maxSeq + 1;
}

function web_path_exists($webPath) {
    return property_image_web_to_file($webPath) !== null;
}

function migrate_legacy_images_to_structured_folders() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    if ((string) get_site_content('property_image_naming_v2_done', '0') === '1') {
        return;
    }

    ensure_image_directories();
    $baseDir = dirname(__DIR__) . '/assets/img';
    $propertiesDir = $baseDir . '/properties';
    $legacyFiles = glob($baseDir . '/*') ?: [];
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $systemName = (string) get_site_content('system_name', 'DigiHome');

    // Move legacy files from assets/img root to properties folder to build a local image pool.
    foreach ($legacyFiles as $legacyFile) {
        if (is_dir($legacyFile)) {
            continue;
        }

        $baseName = basename($legacyFile);
        $lowerName = strtolower($baseName);
        if ($lowerName === 'logo.png' || str_starts_with($lowerName, 'kplc ')) {
            continue;
        }

        $extension = strtolower(pathinfo($legacyFile, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExt, true)) {
            continue;
        }

        $targetPath = $propertiesDir . '/legacy-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
        @rename($legacyFile, $targetPath);
    }

    $poolFiles = glob($propertiesDir . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];
    sort($poolFiles);

    // Build fallback pool from system images when properties folder is empty.
    if ($poolFiles === []) {
        foreach (default_property_images() as $defaultWebPath) {
            $defaultFull = property_image_web_to_file($defaultWebPath);
            if ($defaultFull === null) {
                continue;
            }
            $ext = strtolower((string) pathinfo($defaultFull, PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = 'png';
            }
            $fallbackName = 'fallback-' . bin2hex(random_bytes(3)) . '.' . $ext;
            $fallbackPath = $propertiesDir . '/' . $fallbackName;
            if (@copy($defaultFull, $fallbackPath)) {
                $poolFiles[] = $fallbackPath;
            }
        }
    }

    if ($poolFiles === []) {
        update_site_content('property_image_naming_v2_done', '1', 0);
        return;
    }

    $poolWeb = array_map(static function ($path) {
        return '/DigiHome/assets/img/properties/' . basename($path);
    }, $poolFiles);

    $rows = $db->query('SELECT id, images, hidden_images, cover_image, image_descriptions FROM properties ORDER BY id ASC');
    if ($rows) {
        $cursor = 0;
        $poolCount = count($poolWeb);
        while ($row = $rows->fetch_assoc()) {
            $propertyId = (int) ($row['id'] ?? 0);
            if ($propertyId <= 0) {
                continue;
            }

            $existingImages = array_values(array_filter(array_map('strval', decode_json_array($row['images'] ?? '')), static function ($imagePath) {
                return trim((string) $imagePath) !== '';
            }));
            $existingHidden = array_values(array_filter(array_map('strval', decode_json_array($row['hidden_images'] ?? '')), static function ($imagePath) {
                return trim((string) $imagePath) !== '';
            }));
            $existingDescriptions = array_values(array_map('strval', decode_json_array($row['image_descriptions'] ?? '')));

            $sourceImages = [];
            foreach ($existingImages as $imagePath) {
                if (property_image_web_to_file($imagePath) !== null) {
                    $sourceImages[] = $imagePath;
                }
            }

            while (count($sourceImages) < 3) {
                $sourceImages[] = $poolWeb[$cursor % $poolCount];
                $cursor++;
            }

            $targetImages = [];
            $targetDescriptions = [];
            $countToBuild = max(3, count($sourceImages));
            for ($i = 0; $i < $countToBuild; $i++) {
                $sourceWeb = $sourceImages[$i] ?? $poolWeb[$cursor % $poolCount];
                if (!isset($sourceImages[$i])) {
                    $cursor++;
                }

                $sourceFile = property_image_web_to_file($sourceWeb);
                if ($sourceFile === null) {
                    $sourceWeb = $poolWeb[$cursor % $poolCount];
                    $cursor++;
                    $sourceFile = property_image_web_to_file($sourceWeb);
                }
                if ($sourceFile === null) {
                    continue;
                }

                $ext = strtolower((string) pathinfo($sourceFile, PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'jpg';
                }
                $fileName = property_image_filename($systemName, $propertyId, $i + 1, $ext);
                $targetFile = $propertiesDir . '/' . $fileName;
                $targetWeb = '/DigiHome/assets/img/properties/' . $fileName;

                if ($sourceFile !== $targetFile && !is_file($targetFile)) {
                    @copy($sourceFile, $targetFile);
                }
                if (!is_file($targetFile)) {
                    continue;
                }

                $targetImages[] = $targetWeb;
                $targetDescriptions[] = trim((string) ($existingDescriptions[$i] ?? ('Property image ' . ($i + 1))));
            }

            if (count($targetImages) < 3) {
                while (count($targetImages) < 3) {
                    $fallbackWeb = $poolWeb[$cursor % $poolCount];
                    $cursor++;
                    $fallbackFile = property_image_web_to_file($fallbackWeb);
                    if ($fallbackFile === null) {
                        continue;
                    }
                    $seq = count($targetImages) + 1;
                    $ext = strtolower((string) pathinfo($fallbackFile, PATHINFO_EXTENSION));
                    $fileName = property_image_filename($systemName, $propertyId, $seq, $ext !== '' ? $ext : 'jpg');
                    $targetFile = $propertiesDir . '/' . $fileName;
                    $targetWeb = '/DigiHome/assets/img/properties/' . $fileName;
                    if ($fallbackFile !== $targetFile && !is_file($targetFile)) {
                        @copy($fallbackFile, $targetFile);
                    }
                    if (!is_file($targetFile)) {
                        continue;
                    }
                    $targetImages[] = $targetWeb;
                    $targetDescriptions[] = 'Property image ' . $seq;
                }
            }

            $cover = (string) ($targetImages[0] ?? '');
            $hidden = [];
            foreach ($existingHidden as $hiddenImagePath) {
                $hiddenIndex = array_search($hiddenImagePath, $existingImages, true);
                if ($hiddenIndex !== false && isset($targetImages[$hiddenIndex])) {
                    $hidden[] = $targetImages[$hiddenIndex];
                }
            }
            if ($hidden === [] && isset($targetImages[1])) {
                $hidden[] = $targetImages[1];
            }
            $hidden = array_values(array_unique(array_filter($hidden, static function ($imagePath) use ($cover) {
                return trim((string) $imagePath) !== '' && (string) $imagePath !== $cover;
            })));

            update_property_media($propertyId, $cover, $targetImages, $hidden, $targetDescriptions);
        }
    }

    update_site_content('legacy_image_reorg_done', '1', 0);
    update_site_content('property_image_naming_v2_done', '1', 0);
}

function store_property_images_upload($fileInput = 'property_images', $propertyId = 0, $systemName = 'DigiHome') {
    ensure_image_directories();
    $saved = [];
    if (empty($_FILES[$fileInput]) || !is_array($_FILES[$fileInput])) {
        return $saved;
    }

    $files = $_FILES[$fileInput];
    $names = (array) ($files['name'] ?? []);
    $tmpNames = (array) ($files['tmp_name'] ?? []);
    $errors = (array) ($files['error'] ?? []);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $targetDir = dirname(__DIR__) . '/assets/img/properties';
    $counter = next_property_image_sequence((int) $propertyId, (string) $systemName);
    foreach ($names as $index => $unusedName) {
        if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmp = (string) ($tmpNames[$index] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }

        $mime = mime_content_type($tmp) ?: '';
        if (!isset($allowed[$mime])) {
            continue;
        }

        $ext = $allowed[$mime];
        $fileName = property_image_filename((string) $systemName, (int) $propertyId, (int) $counter, $ext);
        $counter++;

        $targetPath = $targetDir . '/' . $fileName;
        if (!move_uploaded_file($tmp, $targetPath)) {
            continue;
        }

        $saved[] = '/DigiHome/assets/img/properties/' . $fileName;
    }

    return $saved;
}

function ensure_minimum_property_images($propertyId, $images, $systemName = 'DigiHome') {
    ensure_image_directories();
    $images = array_values(array_filter(array_map('strval', (array) $images), static function ($img) {
        return trim((string) $img) !== '';
    }));

    if (count($images) >= 3) {
        return $images;
    }

    $propertiesDir = dirname(__DIR__) . '/assets/img/properties';
    $fallbackPool = array_values(array_filter((array) default_property_images(), static function ($path) {
        return property_image_web_to_file((string) $path) !== null;
    }));

    while (count($images) < 3) {
        $sourceWeb = (string) ($images[0] ?? ($fallbackPool[(count($images) - 1) % max(1, count($fallbackPool))] ?? ''));
        $sourceFile = property_image_web_to_file($sourceWeb);
        if ($sourceFile === null) {
            break;
        }

        $ext = strtolower((string) pathinfo($sourceFile, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $seq = next_property_image_sequence((int) $propertyId, (string) $systemName);
        $fileName = property_image_filename((string) $systemName, (int) $propertyId, $seq, $ext);
        $targetFile = $propertiesDir . '/' . $fileName;
        $targetWeb = '/DigiHome/assets/img/properties/' . $fileName;

        if ($sourceFile !== $targetFile && !is_file($targetFile)) {
            @copy($sourceFile, $targetFile);
        }

        if (!is_file($targetFile)) {
            break;
        }

        $images[] = $targetWeb;
    }

    return $images;
}

function update_property_media($propertyId, $coverImage, $images, $hiddenImages = [], $imageDescriptions = []) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $images = array_values(array_filter(array_map('strval', (array) $images), static function ($img) {
        return trim($img) !== '';
    }));
    $images = ensure_minimum_property_images((int) $propertyId, $images, (string) get_site_content('system_name', 'DigiHome'));
    $coverImage = trim((string) $coverImage);
    if ($coverImage === '' || !in_array($coverImage, $images, true)) {
        $coverImage = (string) ($images[0] ?? '');
    }

    $hiddenImages = array_values(array_unique(array_filter(array_map('strval', (array) $hiddenImages), static function ($img) use ($images, $coverImage) {
        return $img !== '' && in_array($img, $images, true) && $img !== $coverImage;
    })));

    $descriptionOffset = 0;
    $placeholderImages = default_property_images();
    if (count($images) > 1 && !empty($images[0]) && in_array((string) $images[0], $placeholderImages, true)) {
        $descriptionOffset = 1;
    }

    $normalizedDescriptions = [];
    foreach ($images as $index => $unusedImage) {
        $descriptionIndex = $index - $descriptionOffset;
        $normalizedDescriptions[] = $descriptionIndex >= 0 ? trim((string) ((array) $imageDescriptions)[$descriptionIndex] ?? '') : '';
    }

    $imagesJson = encode_json_array($images);
    $hiddenJson = encode_json_array($hiddenImages);
    $descriptionJson = encode_json_array($normalizedDescriptions);
    $stmt = $db->prepare('UPDATE properties SET cover_image = ?, images = ?, hidden_images = ?, image_descriptions = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $coverImage, $imagesJson, $hiddenJson, $descriptionJson, $propertyId);
    return $stmt->execute();
}

function delete_property_image($propertyId, $imageIndex, $actor = null) {
    $propertyId = (int) $propertyId;
    $imageIndex = (int) $imageIndex;
    if ($propertyId <= 0 || $imageIndex < 0) {
        return ['ok' => false, 'message' => 'Invalid image selection.'];
    }

    $property = get_property_by_id($propertyId);
    if (!$property) {
        return ['ok' => false, 'message' => 'Property not found.'];
    }

    $images = array_values(array_filter(array_map('strval', (array) ($property['images'] ?? [])), static function ($path) {
        return trim((string) $path) !== '';
    }));
    if (!isset($images[$imageIndex])) {
        return ['ok' => false, 'message' => 'Selected image no longer exists.'];
    }
    if (count($images) <= 1) {
        return ['ok' => false, 'message' => 'At least one image (cover) must remain on the listing.'];
    }

    $coverImage = trim((string) ($property['cover_image'] ?? ''));
    if ($coverImage === '') {
        $coverImage = (string) ($images[0] ?? '');
    }

    $targetImage = (string) $images[$imageIndex];
    if ($targetImage !== '' && $targetImage === $coverImage) {
        return ['ok' => false, 'message' => 'This is the current cover image. Select another image as cover (or upload a new one and set it as cover) before deleting this one.'];
    }

    $hiddenImages = array_values(array_filter(array_map('strval', (array) ($property['hidden_images'] ?? [])), static function ($path) {
        return trim((string) $path) !== '';
    }));
    $imageDescriptions = array_values(array_map('strval', (array) ($property['image_descriptions'] ?? [])));

    unset($images[$imageIndex]);
    $images = array_values($images);
    unset($imageDescriptions[$imageIndex]);
    $imageDescriptions = array_values($imageDescriptions);

    $hiddenImages = array_values(array_filter($hiddenImages, static function ($path) use ($targetImage) {
        return (string) $path !== (string) $targetImage;
    }));

    $saved = update_property_media($propertyId, $coverImage, $images, $hiddenImages, $imageDescriptions);
    if (!$saved) {
        return ['ok' => false, 'message' => 'Failed to delete image. Please try again.'];
    }

    $actorId = (int) (($actor['id'] ?? current_user()['id'] ?? 0));
    audit_log($actorId, 'property_image_deleted', 'property', $propertyId, 'Deleted image index ' . $imageIndex);
    return ['ok' => true, 'message' => 'Image deleted successfully.'];
}

function save_listing_draft($userId, $role, $payload, $ownerId = null) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $json = json_encode((array) $payload);
    $canonicalRole = canonical_role($role);
    if ($ownerId === null) {
        $stmt = $db->prepare('INSERT INTO listing_drafts (user_id, role, owner_id, payload) VALUES (?, ?, NULL, ?) ON DUPLICATE KEY UPDATE payload = VALUES(payload), updated_at = CURRENT_TIMESTAMP');
        $stmt->bind_param('iss', $userId, $canonicalRole, $json);
    } else {
        $owner = (int) $ownerId;
        $stmt = $db->prepare('INSERT INTO listing_drafts (user_id, role, owner_id, payload) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE payload = VALUES(payload), updated_at = CURRENT_TIMESTAMP');
        $stmt->bind_param('isis', $userId, $canonicalRole, $owner, $json);
    }
    return $stmt->execute();
}

function get_listing_draft($userId, $role, $ownerId = null) {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $canonicalRole = canonical_role($role);
    if ($ownerId === null) {
        $stmt = $db->prepare('SELECT payload FROM listing_drafts WHERE user_id = ? AND role = ? AND owner_id IS NULL LIMIT 1');
        $stmt->bind_param('is', $userId, $canonicalRole);
    } else {
        $owner = (int) $ownerId;
        $stmt = $db->prepare('SELECT payload FROM listing_drafts WHERE user_id = ? AND role = ? AND owner_id = ? LIMIT 1');
        $stmt->bind_param('isi', $userId, $canonicalRole, $owner);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return [];
    }
    $decoded = json_decode((string) $row['payload'], true);
    return is_array($decoded) ? $decoded : [];
}

function delete_listing_draft($userId, $role, $ownerId = null) {
    $db = connect_db();
    if (!$db) {
        return false;
    }
    $canonicalRole = canonical_role($role);
    if ($ownerId === null) {
        $stmt = $db->prepare('DELETE FROM listing_drafts WHERE user_id = ? AND role = ? AND owner_id IS NULL');
        $stmt->bind_param('is', $userId, $canonicalRole);
    } else {
        $owner = (int) $ownerId;
        $stmt = $db->prepare('DELETE FROM listing_drafts WHERE user_id = ? AND role = ? AND owner_id = ?');
        $stmt->bind_param('isi', $userId, $canonicalRole, $owner);
    }
    return $stmt->execute();
}

function submit_review($userId, $role, $rating, $reviewText) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $ratingValue = max(1, min(5, (int) $rating));
    $review = trim((string) $reviewText);
    if ($review === '') {
        return false;
    }

    $stmt = $db->prepare('INSERT INTO reviews (user_id, role, rating, review_text, status) VALUES (?, ?, ?, ?, ?)');
    $status = 'inactive';
    $canonicalRole = canonical_role($role);
    $stmt->bind_param('isiss', $userId, $canonicalRole, $ratingValue, $review, $status);
    return $stmt->execute();
}

function set_review_status($reviewId, $status) {
    $db = connect_db();
    if (!$db) {
        return false;
    }
    if (!in_array($status, ['inactive', 'active'], true)) {
        return false;
    }
    $stmt = $db->prepare('UPDATE reviews SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $reviewId);
    return $stmt->execute();
}

function get_reviews_for_display($viewerUserId = null, $limit = 50) {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    if ($viewerUserId) {
        $stmt = $db->prepare('SELECT r.*, u.first_name, u.profile_picture FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.status = ? OR r.user_id = ? ORDER BY r.created_at DESC LIMIT ?');
        $active = 'active';
        $stmt->bind_param('sii', $active, $viewerUserId, $limit);
    } else {
        $stmt = $db->prepare('SELECT r.*, u.first_name, u.profile_picture FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.status = ? ORDER BY r.created_at DESC LIMIT ?');
        $active = 'active';
        $stmt->bind_param('si', $active, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_reviews_for_admin() {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $result = $db->query('SELECT r.*, u.first_name, u.last_name, u.email FROM reviews r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC');
    $rows = [];
    while ($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function create_team_contact_type($label, $iconHtml, $linkPrefix = '') {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $cleanLabel = trim((string) $label);
    $cleanIcon = trim((string) $iconHtml);
    $cleanPrefix = trim((string) $linkPrefix);
    if ($cleanLabel === '' || $cleanIcon === '') {
        return false;
    }

    $typeKey = strtolower(preg_replace('/[^a-z0-9]+/', '_', $cleanLabel));
    $stmt = $db->prepare('INSERT INTO team_contact_types (type_key, label, icon_html, link_prefix) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label), icon_html = VALUES(icon_html), link_prefix = VALUES(link_prefix), is_active = 1');
    $stmt->bind_param('ssss', $typeKey, $cleanLabel, $cleanIcon, $cleanPrefix);
    return $stmt->execute();
}

function get_team_contact_types() {
    $db = connect_db();
    if (!$db) {
        return [];
    }
    $result = $db->query('SELECT * FROM team_contact_types WHERE is_active = 1 ORDER BY label ASC');
    $rows = [];
    while ($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function create_team_member($name, $roleTitle, $description, $profilePicture = '') {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $memberName = trim((string) $name);
    $memberRole = trim((string) $roleTitle);
    $shortDescription = trim((string) $description);
    $picture = trim((string) $profilePicture);
    if ($memberName === '' || $memberRole === '' || $shortDescription === '') {
        return false;
    }

    $stmt = $db->prepare('INSERT INTO team_members (member_name, role_title, short_description, profile_picture) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $memberName, $memberRole, $shortDescription, $picture);
    return $stmt->execute();
}

function add_team_member_contact($teamMemberId, $contactTypeId, $accountValue) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $account = trim((string) $accountValue);
    if ($teamMemberId <= 0 || $contactTypeId <= 0 || $account === '') {
        return false;
    }

    $stmt = $db->prepare('INSERT INTO team_member_contacts (team_member_id, contact_type_id, account_value) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $teamMemberId, $contactTypeId, $account);
    return $stmt->execute();
}

function get_team_members_with_contacts() {
    $db = connect_db();
    if (!$db) {
        return [];
    }

    $members = [];
    $memberRows = $db->query('SELECT * FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC, id DESC');
    while ($memberRows && $member = $memberRows->fetch_assoc()) {
        $member['contacts'] = [];
        $members[(int) $member['id']] = $member;
    }

    if ($members === []) {
        return [];
    }

    $contactRows = $db->query('SELECT tmc.*, tct.label, tct.icon_html, tct.link_prefix FROM team_member_contacts tmc JOIN team_contact_types tct ON tct.id = tmc.contact_type_id WHERE tmc.is_active = 1 AND tct.is_active = 1 ORDER BY tmc.sort_order ASC, tmc.id ASC');
    while ($contactRows && $contact = $contactRows->fetch_assoc()) {
        $memberId = (int) ($contact['team_member_id'] ?? 0);
        if (isset($members[$memberId])) {
            $href = normalize_team_contact_href((string) ($contact['account_value'] ?? ''), (string) ($contact['link_prefix'] ?? ''));
            $contact['href'] = $href;
            $contact['tooltip'] = trim((string) ($contact['label'] ?? 'Contact') . ': ' . (string) ($contact['account_value'] ?? ''));
            $members[$memberId]['contacts'][] = $contact;
        }
    }

    return array_values($members);
}

function get_team_contact_type_by_id($contactTypeId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM team_contact_types WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $contactTypeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function update_team_contact_type($contactTypeId, $label, $iconHtml, $linkPrefix = '') {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $cleanLabel = trim((string) $label);
    $cleanIcon = trim((string) $iconHtml);
    $cleanPrefix = trim((string) $linkPrefix);
    if ($cleanLabel === '' || $cleanIcon === '') {
        return false;
    }

    $typeKey = strtolower(preg_replace('/[^a-z0-9]+/', '_', $cleanLabel));
    $stmt = $db->prepare('UPDATE team_contact_types SET type_key = ?, label = ?, icon_html = ?, link_prefix = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $typeKey, $cleanLabel, $cleanIcon, $cleanPrefix, $contactTypeId);
    return $stmt->execute();
}

function delete_team_contact_type($contactTypeId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('DELETE FROM team_contact_types WHERE id = ?');
    $stmt->bind_param('i', $contactTypeId);
    return $stmt->execute();
}

function get_team_member_by_id($teamMemberId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM team_members WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $teamMemberId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function update_team_member($teamMemberId, $name, $roleTitle, $description, $profilePicture = '') {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $memberName = trim((string) $name);
    $memberRole = trim((string) $roleTitle);
    $shortDescription = trim((string) $description);
    $picture = trim((string) $profilePicture);
    if ($memberName === '' || $memberRole === '' || $shortDescription === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE team_members SET member_name = ?, role_title = ?, short_description = ?, profile_picture = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $memberName, $memberRole, $shortDescription, $picture, $teamMemberId);
    return $stmt->execute();
}

function delete_team_member($teamMemberId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('DELETE FROM team_members WHERE id = ?');
    $stmt->bind_param('i', $teamMemberId);
    return $stmt->execute();
}

function get_team_member_contact_by_id($contactId) {
    $db = connect_db();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM team_member_contacts WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $contactId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function update_team_member_contact($contactId, $contactTypeId, $accountValue) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $account = trim((string) $accountValue);
    if ($contactTypeId <= 0 || $account === '') {
        return false;
    }

    $stmt = $db->prepare('UPDATE team_member_contacts SET contact_type_id = ?, account_value = ? WHERE id = ?');
    $stmt->bind_param('isi', $contactTypeId, $account, $contactId);
    return $stmt->execute();
}

function delete_team_member_contact($contactId) {
    $db = connect_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('DELETE FROM team_member_contacts WHERE id = ?');
    $stmt->bind_param('i', $contactId);
    return $stmt->execute();
}

function role_profile_image_dir($role) {
    $normalizedRole = canonical_role($role);
    $folderMap = [
        'property_seeker' => 'seeker',
        'property_owner' => 'owner',
        'marketer' => 'marketer',
        'admin' => 'admin',
    ];
    $folder = $folderMap[$normalizedRole] ?? 'seeker';
    return dirname(__DIR__) . '/assets/img/users/' . $folder;
}

function migrate_legacy_profile_images() {
    $db = connect_db();
    if (!$db) {
        return;
    }

    if ((string) get_site_content('profile_image_role_folders_done', '0') === '1') {
        return;
    }

    $baseDir = dirname(__DIR__) . '/assets/img';
    $legacyProfileDir = dirname(__DIR__) . '/assets/profile';
    $flatUsersDir = $baseDir . '/users';
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $folderMap = [
        'property_seeker' => 'seeker',
        'property_owner' => 'owner',
        'marketer' => 'marketer',
        'admin' => 'admin',
    ];

    $legacyFiles = [];

    if (is_dir($legacyProfileDir)) {
        foreach ((array) glob($legacyProfileDir . '/*') as $file) {
            if (!is_file($file)) {
                continue;
            }
            $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt, true)) {
                $legacyFiles[] = $file;
            }
        }
    }

    if (is_dir($flatUsersDir)) {
        foreach ((array) scandir($flatUsersDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $fullPath = $flatUsersDir . '/' . $entry;
            if (is_dir($fullPath) || !is_file($fullPath)) {
                continue;
            }
            $ext = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt, true)) {
                $legacyFiles[] = $fullPath;
            }
        }
    }

    $userRows = $db->query("SELECT id, role, profile_picture FROM users WHERE profile_picture IS NOT NULL AND profile_picture <> ''");
    $fileToRole = [];
    $fileToUserId = [];
    if ($userRows) {
        while ($row = $userRows->fetch_assoc()) {
            $webPath = (string) ($row['profile_picture'] ?? '');
            if ($webPath === '') {
                continue;
            }
            $clean = ltrim(str_replace('/DigiHome/', '', $webPath), '/');
            if ($clean === '') {
                continue;
            }
            $fullPath = dirname(__DIR__) . '/' . $clean;
            if (!isset($fileToRole[$fullPath])) {
                $fileToRole[$fullPath] = canonical_role((string) ($row['role'] ?? 'property_seeker'));
                $fileToUserId[$fullPath] = (int) ($row['id'] ?? 0);
            }
        }
    }

    foreach ($legacyFiles as $legacyFile) {
        $role = $fileToRole[$legacyFile] ?? null;
        $userId = $fileToUserId[$legacyFile] ?? 0;
        if ($role === null || $userId <= 0) {
            // If a legacy file is not referenced by any user, leave it in place.
            continue;
        }

        $folder = $folderMap[$role] ?? 'seeker';
        $targetDir = $flatUsersDir . '/' . $folder;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (strpos($legacyFile, $targetDir . DIRECTORY_SEPARATOR) === 0) {
            continue; // Already inside the correct role folder.
        }

        $baseName = basename($legacyFile);
        $targetPath = $targetDir . '/' . $baseName;
        if (is_file($targetPath)) {
            $ext = strtolower((string) pathinfo($legacyFile, PATHINFO_EXTENSION));
            $targetPath = $targetDir . '/migrated-' . date('Ymd_His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        }
        @copy($legacyFile, $targetPath);

        $webPath = '/DigiHome/assets/img/users/' . $folder . '/' . basename($targetPath);
        $update = $db->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
        $update->bind_param('si', $webPath, $userId);
        $update->execute();
    }

    update_site_content('profile_image_role_folders_done', '1', 0);
}
