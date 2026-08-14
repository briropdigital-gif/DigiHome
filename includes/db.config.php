<?php
session_start();

$dbHost = 'sql200.infinityfree.com';
$dbUser = 'if0_42656623';
$dbPass = 'aUfBuHBil1UgJw';
$dbName = 'if0_42656623_digihome';

function connect_db() {
    global $dbHost, $dbUser, $dbPass, $dbName;
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function canonical_role($role) {
    $map = [
        'visitor' => 'property_seeker',
        'owner' => 'property_owner',
        'superadmin' => 'admin',
        'property seeker' => 'property_seeker',
        'property owner' => 'property_owner',
    ];
    $value = strtolower(trim((string) $role));
    return $map[$value] ?? $value;
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

function get_properties($filters = []) {
    try {
        $db = connect_db();
    } catch (Throwable $e) {
        return [];
    }

    $sql = 'SELECT * FROM properties WHERE 1=1';
    $params = [];
    $types = '';

    if (!empty($filters['location'])) {
        $term = '%' . $filters['location'] . '%';
        $sql .= ' AND (location LIKE ? OR estate LIKE ? OR city LIKE ? OR county LIKE ?)';
        $params = array_merge($params, [$term, $term, $term, $term]);
        $types .= 'ssss';
    }
    if (!empty($filters['listing_type'])) {
        $sql .= ' AND (listing_type = ? OR purpose = ?)';
        $params = array_merge($params, [$filters['listing_type'], $filters['listing_type']]);
        $types .= 'ss';
    }
    if (!empty($filters['category'])) {
        $sql .= ' AND category = ?';
        $params[] = $filters['category'];
        $types .= 's';
    }
    if (!empty($filters['price_range'])) {
        [$min, $max] = parse_price_range_filter((string) $filters['price_range']);
        if ($min !== null) {
            $sql .= ' AND price >= ?';
            $params[] = $min;
            $types .= 'd';
        }
        if ($max !== null) {
            $sql .= ' AND price < ?';
            $params[] = $max;
            $types .= 'd';
        }
    }

    $sql .= ' ORDER BY created_at DESC';
    $stmt = $db->prepare($sql);
    if ($params !== []) {
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

function get_properties_near_user_region($userId, $limit = 6) {
    $db = connect_db();
    $sql = 'SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC LIMIT ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function listing_purpose_label($property) {
    $purpose = strtolower((string) ($property['purpose'] ?? $property['listing_type'] ?? 'rent'));
    if ($purpose === 'sale') {
        return 'For Sale';
    }
    if ($purpose === 'lease') {
        return 'For Lease';
    }
    if ($purpose === 'airbnb') {
        return 'Airbnb';
    }
    return 'For Rent';
}

function listing_scope_label($property) {
    $scope = strtolower((string) ($property['listing_scope'] ?? 'entire_property'));
    return $scope === 'unit' ? 'Unit' : 'Entire Property';
}

function property_status_label($property) {
    $status = strtolower((string) ($property['status'] ?? 'available'));
    if ($status === 'occupied' || $status === 'pending') {
        return 'Pending';
    }
    if ($status === 'sold' || $status === 'rented') {
        return 'Closed';
    }
    return 'Available';
}

function add_flash($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash'] = $_SESSION['flash'] ?? [];
    $_SESSION['flash'][] = ['type' => strtolower((string) $type), 'message' => (string) $message];
}

function get_flashes() {
    return $_SESSION['flash'] ?? [];
}

function role_config($role) {
    $role = canonical_role($role);
    $roles = [
        'property_seeker' => ['label' => 'Property Seeker', 'description' => 'Browse trusted listings, save favorites, and unlock private details from real property opportunities.', 'portal' => '/DigiHome/seeker/home.php', 'home' => '/DigiHome/seeker/home.php', 'listings' => '/DigiHome/seeker/listings.php', 'about' => '/DigiHome/seeker/about.php', 'contact' => '/DigiHome/seeker/contact.php', 'login' => '/DigiHome/seeker/login.php', 'register' => '/DigiHome/seeker/register.php', 'profile' => '/DigiHome/seeker/profile.php'],
        'property_owner' => ['label' => 'Property Owner', 'description' => 'List your property, monitor verification, and keep your portfolio organized.', 'portal' => '/DigiHome/owner/dashboard.php', 'home' => '/DigiHome/owner/home.php', 'listings' => '/DigiHome/owner/listings.php', 'about' => '/DigiHome/owner/about.php', 'contact' => '/DigiHome/owner/contact.php', 'login' => '/DigiHome/owner/login.php', 'register' => '/DigiHome/owner/register.php', 'profile' => '/DigiHome/owner/profile.php'],
        'marketer' => ['label' => 'Marketer', 'description' => 'Promote listings, manage owner relationships, and track commissions from your assigned properties.', 'portal' => '/DigiHome/marketer/dashboard.php', 'home' => '/DigiHome/marketer/home.php', 'listings' => '/DigiHome/marketer/listings.php', 'about' => '/DigiHome/marketer/about.php', 'contact' => '/DigiHome/marketer/contact.php', 'login' => '/DigiHome/marketer/login.php', 'register' => '/DigiHome/marketer/register.php', 'profile' => '/DigiHome/marketer/profile.php'],
        'admin' => ['label' => 'Admin', 'description' => 'Manage controls, support requests, and marketplace operations from the admin dashboard.', 'portal' => '/DigiHome/admin/dashboard.php', 'home' => '/DigiHome/admin/dashboard.php', 'listings' => '/DigiHome/admin/listings.php', 'about' => '/DigiHome/admin/about.php', 'contact' => '/DigiHome/admin/contact.php', 'login' => '/DigiHome/admin/login.php', 'register' => null, 'profile' => '/DigiHome/admin/profile.php'],
    ];
    return $roles[$role] ?? $roles['property_seeker'];
}

function role_label($role) {
    return role_config($role)['label'];
}

function account_hub_path($mode = '') {
    $base = '/DigiHome/includes/account.php';
    return $mode === '' ? $base : $base . '?mode=' . urlencode($mode);
}

function logout_path() {
    return '/DigiHome/includes/logout.php';
}

function role_login_path($role) {
    return role_config($role)['login'] ?? '/DigiHome/seeker/login.php';
}

function role_register_path($role) {
    $route = role_config($role)['register'] ?? null;
    return $route ?: '/DigiHome/includes/account.php?mode=register';
}

function role_dashboard_path($role) {
    return role_config($role)['portal'] ?? '/DigiHome/seeker/home.php';
}

function role_home_path($role) {
    return role_config($role)['home'] ?? '/DigiHome/seeker/home.php';
}

function role_listings_path($role) {
    return role_config($role)['listings'] ?? '/DigiHome/seeker/listings.php';
}

function role_profile_path($role) {
    return role_config($role)['profile'] ?? '/DigiHome/seeker/profile.php';
}

function role_about_path($role) {
    return role_config($role)['about'] ?? '/DigiHome/seeker/about.php';
}

function role_contact_path($role) {
    return role_config($role)['contact'] ?? '/DigiHome/seeker/contact.php';
}

function is_registerable_role($role) {
    $role = canonical_role($role);
    return in_array($role, ['property_seeker', 'property_owner', 'marketer'], true);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function get_public_roles($includeAdmin = true) {
    $roles = [
        'property_seeker' => ['label' => 'Property Seeker', 'description' => 'Browse listings and unlock trusted details.'],
        'property_owner' => ['label' => 'Property Owner', 'description' => 'List properties and manage your portfolio.'],
        'marketer' => ['label' => 'Marketer', 'description' => 'Grow listings and track commissions.'],
    ];
    if ($includeAdmin) {
        $roles['admin'] = ['label' => 'Admin', 'description' => 'Marketplace administration.'];
    }
    return $roles;
}

function user_has_any_role($roles) {
    $user = current_user();
    if (!$user) {
        return false;
    }
    foreach ((array) $roles as $role) {
        if (canonical_role($user['role'] ?? '') === canonical_role($role)) {
            return true;
        }
    }
    return false;
}

function remembered_accounts() {
    return $_SESSION['remembered_accounts'] ?? [];
}

function remembered_accounts_by_role() {
    $rows = remembered_accounts();
    $grouped = [];
    foreach ($rows as $row) {
        $role = canonical_role((string) ($row['role'] ?? 'property_seeker'));
        $grouped[$role][] = $row;
    }
    return $grouped;
}

function remember_device_user($user) {
    $user = $user ?: current_user();
    if (!$user || empty($user['id'])) {
        return false;
    }
    $role = canonical_role((string) ($user['role'] ?? 'property_seeker'));
    $account = [
        'id' => (int) $user['id'],
        'name' => (string) ($user['name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))),
        'email' => (string) ($user['email'] ?? ''),
        'username' => (string) ($user['username'] ?? $user['email'] ?? ''),
        'role' => $role,
        'profile_picture' => (string) ($user['profile_picture'] ?? default_profile_picture($role)),
    ];
    $existing = remembered_accounts();
    $filtered = [];
    foreach ($existing as $row) {
        if ((int) ($row['id'] ?? 0) !== (int) $user['id'] || canonical_role((string) ($row['role'] ?? '')) !== $role) {
            $filtered[] = $row;
        }
    }
    array_unshift($filtered, $account);
    $_SESSION['remembered_accounts'] = array_slice($filtered, 0, 6);
    return true;
}

function forget_remembered_account($userId, $role = '') {
    $role = canonical_role((string) $role);
    $existing = remembered_accounts();
    $_SESSION['remembered_accounts'] = array_values(array_filter($existing, function ($account) use ($userId, $role) {
        if ((int) ($account['id'] ?? 0) === (int) $userId && canonical_role((string) ($account['role'] ?? '')) === $role) {
            return false;
        }
        return true;
    }));
    return true;
}

function login_from_remembered_account($userId, $role = '') {
    $role = canonical_role((string) $role);
    $db = connect_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND role = ? LIMIT 1');
    $stmt->bind_param('is', $userId, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        return null;
    }
    $_SESSION['user'] = $user;
    return $user;
}

function authenticate_user($email, $password) {
    $db = connect_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        return null;
    }
    if (!isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    if (strtolower((string) ($user['status'] ?? 'active')) !== 'active') {
        return null;
    }
    return $user;
}

function login_user($user) {
    if (!$user) {
        return null;
    }
    $_SESSION['user'] = $user;
    if (!empty($user['role'])) {
        remember_device_user($user);
    }
    return $user;
}

function logout_user($role = null) {
    $user = current_user();
    if ($role !== null && $user && canonical_role((string) ($user['role'] ?? '')) !== canonical_role((string) $role)) {
        return false;
    }
    unset($_SESSION['user']);
    return true;
}

function default_profile_picture($role) {
    return '/DigiHome/assets/img/users/avatar-placeholder.svg';
}

function user_initials($user) {
    $first = trim((string) ($user['first_name'] ?? ''));
    $last = trim((string) ($user['last_name'] ?? ''));
    $name = trim((string) ($user['name'] ?? ($first . ' ' . $last)));
    $parts = preg_split('/\s+/', $name);
    $letters = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $letters[] = strtoupper(substr($part, 0, 1));
        }
    }
    if ($letters === []) {
        return 'U';
    }
    return substr(implode('', array_slice($letters, 0, 2)), 0, 2);
}

function get_site_content($key, $default = '') {
    $db = connect_db();
    $stmt = $db->prepare('SELECT content_value FROM site_content WHERE content_key = ? LIMIT 1');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['content_value'] ?? $default;
}

function get_site_content_map() {
    $db = connect_db();
    $result = $db->query('SELECT content_key, content_value FROM site_content');
    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[$row['content_key']] = $row['content_value'];
    }
    return $map;
}

function get_location_hierarchy_data() {
    $db = connect_db();
    $data = ['countries' => [], 'counties' => [], 'sub_counties' => [], 'wards' => []];
    $tables = ['countries' => 'countries', 'counties' => 'counties', 'sub_counties' => 'sub_counties', 'wards' => 'wards'];
    foreach ($tables as $key => $table) {
        $result = $db->query('SELECT * FROM `' . $table . '` LIMIT 200');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$key][] = $row;
            }
        }
    }
    return $data;
}

function store_profile_image_upload($fieldName, $existing = '', $role = '') {
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    $file = $_FILES[$fieldName];
    if (!is_uploaded_file($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        return $existing;
    }
    $dir = dirname(__DIR__) . '/assets/img/users';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $name = 'profile_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return $existing;
    }
    return '/DigiHome/assets/img/users/' . $name;
}

function create_user($payload) {
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $firstName = trim((string) ($payload['first_name'] ?? ''));
    $lastName = trim((string) ($payload['last_name'] ?? ''));
    $role = canonical_role((string) ($payload['role'] ?? 'property_seeker'));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $address = trim((string) ($payload['address_line'] ?? ''));
    $county = trim((string) ($payload['county'] ?? ''));
    $town = trim((string) ($payload['town'] ?? ''));
    $profilePicture = (string) ($payload['profile_picture'] ?? default_profile_picture($role));
    $name = trim($firstName . ' ' . $lastName);
    $username = strtolower(str_replace(' ', '.', trim($name))) ?: preg_replace('/[^a-z0-9]/i', '', strtolower($email));
    $db = connect_db();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, status, first_name, last_name, username, phone, county, town, profile_picture, address_line) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $status = 'active';
    $stmt->bind_param('sssssssssssss', $name, $email, $hash, $role, $status, $firstName, $lastName, $username, $phone, $county, $town, $profilePicture, $address);
    if (!$stmt->execute()) {
        return null;
    }
    $id = $db->insert_id;
    $user = $db->query('SELECT * FROM users WHERE id = ' . (int) $id . ' LIMIT 1')->fetch_assoc();
    return $user;
}

function validate_registration_data($payload) {
    $errors = [];
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $confirm = (string) ($payload['confirm_password'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    $db = connect_db();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $errors[] = 'An account with this email already exists.';
    }
    return $errors;
}

function audit_log($userId, $action, $entityType, $entityId, $details = '', $ipAddress = null) {
    $db = connect_db();
    $ip = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $userId, $action, $entityType, $entityId, $details, $ip);
    return $stmt->execute();
}

function create_notification($userId, $type, $title, $message) {
    $db = connect_db();
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
    $stmt->bind_param('isss', $userId, $type, $title, $message);
    return $stmt->execute();
}

function is_favorite_property($userId, $propertyId) {
    $db = connect_db();
    $stmt = $db->prepare('SELECT id FROM favorite_properties WHERE user_id = ? AND property_id = ? AND removed_at IS NULL LIMIT 1');
    $stmt->bind_param('ii', $userId, $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() !== null;
}

function set_favorite_property($userId, $propertyId, $add = true) {
    $db = connect_db();
    if ($add) {
        $existing = is_favorite_property($userId, $propertyId);
        if ($existing) {
            return true;
        }
        $stmt = $db->prepare('INSERT INTO favorite_properties (user_id, property_id, created_at) VALUES (?, ?, NOW())');
        $stmt->bind_param('ii', $userId, $propertyId);
        return $stmt->execute();
    }
    $stmt = $db->prepare('DELETE FROM favorite_properties WHERE user_id = ? AND property_id = ?');
    $stmt->bind_param('ii', $userId, $propertyId);
    return $stmt->execute();
}

function get_favorite_properties($userId) {
    $db = connect_db();
    $stmt = $db->prepare('SELECT p.* FROM favorite_properties f INNER JOIN properties p ON p.id = f.property_id WHERE f.user_id = ? ORDER BY f.created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = $row;
    }
    return $list;
}

function get_favorite_count($userId) {
    $db = connect_db();
    $stmt = $db->prepare('SELECT COUNT(*) AS total FROM favorite_properties WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function get_property_by_id($id) {
    $db = connect_db();
    $stmt = $db->prepare('SELECT * FROM properties WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_seeker_unlocked_properties($userId) {
    $db = connect_db();
    $stmt = $db->prepare('SELECT p.* FROM seeker_unlocked_properties u INNER JOIN properties p ON p.id = u.property_id WHERE u.seeker_id = ? AND u.removed_at IS NULL ORDER BY u.unlocked_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

try {
    connect_db();
} catch (Throwable $e) {
    // Keep the application bootstrapping resilient when the database is temporarily unreachable.
}
