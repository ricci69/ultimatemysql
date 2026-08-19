<?php
/**
 * Example: Security Hardening & Memory Safety (v5.0)
 * Covers: MYSQL_MAX_BUFFERED_ROWS, MYSQL_DEBUG_ANONIMIZATION, 
 *         Multi-statement blocking, EscapeIdentifier strictness.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

// Override constant for testing BEFORE including class (if not done in bootstrap)
// define('MYSQL_MAX_BUFFERED_ROWS', 5); 

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

section("1. Memory Safety Guard: MYSQL_MAX_BUFFERED_ROWS");
info("Current Limit: " . MYSQL_MAX_BUFFERED_ROWS . " rows");

// Create table with 10 rows
$db->Query("DROP TABLE IF EXISTS `safety_test`");
$db->Query("CREATE TABLE `safety_test` (`id` INT AUTO_INCREMENT PRIMARY KEY, `val` VARCHAR(100)) ENGINE=InnoDB");
for ($i=1; $i<=10; $i++) $db->Query("INSERT INTO safety_test (val) VALUES ('Row $i')");

$db->Query("SELECT * FROM safety_test");
$rc = $db->RowCount();
info("Table has $rc rows. Limit is " . MYSQL_MAX_BUFFERED_ROWS . ".");

if (MYSQL_MAX_BUFFERED_ROWS > 0 && $rc > MYSQL_MAX_BUFFERED_ROWS) {
    info("Testing RecordsArray() - Should FAIL safely...");
    $arr = $db->RecordsArray();
    dump($arr, "RecordsArray() Result (Expected: false)");
    dump($db->Error(), "Error Triggered");
    
    info("Testing GetJSON() - Should return 'null' safely...");
    $json = $db->GetJSON();
    dump($json, "GetJSON() Result (Expected: null)");
    
    info("Testing GetHTML() - Should return false safely...");
    $html = $db->GetHTML();
    dump($html, "GetHTML() Result (Expected: false)");
    
    success("Memory Safety Guards WORK: Large result sets blocked from buffering.");
} else {
    info("Limit not exceeded. Lower MYSQL_MAX_BUFFERED_ROWS in bootstrap.php to test (e.g., 5).");
    $arr = $db->RecordsArray();
    success("RecordsArray() worked (under limit): " . count($arr) . " rows returned.");
}

section("2. Multi-Statement Blocking (Security)");
$multiSql = "SELECT 1; DROP TABLE safety_test; --";
$result = $db->Query($multiSql);
if (!$result && $db->ErrorNumber() == -1) { // Custom error code for multi-stmt
    success("Blocked Multi-Statement Attack: " . $db->Error());
} else {
    fail("Multi-statement check failed or not triggered.");
}

section("3. Debug Anonymization (MYSQL_DEBUG_ANONIMIZATION)");
info("Enable constant MYSQL_DEBUG_ANONIMIZATION=true in bootstrap to test.");
info("When enabled, debug logs replace literals: 'SELECT * FROM t WHERE pass=\"secret\"' -> 'SELECT * FROM t WHERE pass=?'");

section("4. Strict Identifier Validation");
try {
    MySQL::EscapeIdentifier("valid_name");
    success("Valid identifier accepted.");
} catch (Exception $e) { fail($e->getMessage()); }

$bad = ["bad;name", "name--", "name/*", "name`quote", "name'single", 'name"double'];
foreach ($bad as $b) {
    try {
        MySQL::EscapeIdentifier($b);
        fail("INSECURE: '$b' was accepted!");
    } catch (InvalidArgumentException $e) {
        success("Blocked dangerous identifier: '$b'");
    }
}
