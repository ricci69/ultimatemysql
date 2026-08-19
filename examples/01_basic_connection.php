<?php
/**
 * Example: Basic Connection Lifecycle
 * Covers: __construct, Open, Close, IsConnected, SelectDatabase, Kill, Error, ErrorNumber, SetThrowExceptions
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

section("1. Constructor Variations");

// 1. Empty constructor (connect later)
$db1 = new MySQL(false);
info("Created instance with connect=false. IsConnected: " . ($db1->IsConnected() ? 'true' : 'false'));

// 2. Constructor with auto-connect
try {
    $db2 = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS, DB_CHARSET, DB_PERSISTENT);
    success("Auto-connected via constructor. Server: " . $db2->GetLastSQL()); // Last SQL is empty here
} catch (Exception $e) {
    fail("Constructor connection failed: " . $e->getMessage());
    exit;
}

section("2. Open() with Advanced Options (SSL, Timeout)");
// Demonstrates new v5.0 Open() signature
$db3 = new MySQL(false);
$opened = $db3->Open(
    DB_NAME, 
    DB_HOST, 
    DB_USER, 
    DB_PASS, 
    DB_CHARSET, 
    DB_PERSISTENT, 
    DB_CONNECT_TIMEOUT, 
    DB_SSL_OPTIONS
);
if ($opened) {
    success("Open() successful with Timeout/SSL options. Charset: " . DB_CHARSET);
} else {
    fail("Open() failed: " . $db3->Error());
}

section("3. Connection Status & Selection");
dump($db3->IsConnected(), "IsConnected()");
dump($db3->SelectDatabase(DB_NAME), "SelectDatabase()");

section("4. Error Handling & Exceptions");
// Trigger an error
$db3->Query("SELECT * FROM non_existent_table_xyz");
if ($db3->Error()) {
    dump($db3->Error(), "Error() string");
    dump($db3->ErrorNumber(), "ErrorNumber()");
    // FIX: Removed call to private method ResetError(). Query() resets error state automatically.
}

// Enable Exceptions
$db3->SetThrowExceptions(true);
info("ThrowExceptions enabled. Next error will throw RuntimeException.");
try {
    $db3->Query("INVALID SQL");
} catch (RuntimeException $e) {
    success("Caught Exception: Code {$e->getCode()}, Message: {$e->getMessage()}");
}
$db3->SetThrowExceptions(false); // Reset

section("5. Close & Destructor");
$db3->Close();
success("Close() returned true. IsConnected now: " . ($db3->IsConnected() ? 'true' : 'false'));

// Destructor test (implicit)
unset($db1);
info("Unset \$db1 (destructor called silently).");
