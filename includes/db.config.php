<?php
session_start();

$dbConfig = [
    'dbHost' => 'sql200.infinityfree.com',
    'dbUser' => 'if0_42656623',
    'dbPass' => 'aUfBuHBil1UgJw',
    'dbName' => 'if0_42656623_digihome',
];
$dbHost = (string) ($dbConfig['dbHost'] ?? '');
$dbUser = (string) ($dbConfig['dbUser'] ?? '');
$dbPass = (string) ($dbConfig['dbPass'] ?? '');
$dbName = (string) ($dbConfig['dbName'] ?? '');

$conn = null;

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
require_once __DIR__ . '/site_helpers.php';
