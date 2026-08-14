<?php
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
        die('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

connect_db();
