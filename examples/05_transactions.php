<?php
/**
 * Example: Transaction Handling
 * Covers: TransactionBegin, TransactionEnd, TransactionRollback, IsInTransaction, GetTransactionDepth, ThrowExceptions
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

$db->Query("DROP TABLE IF EXISTS `tx_test`");
$db->Query("CREATE TABLE `tx_test` (`id` INT AUTO_INCREMENT PRIMARY KEY, `val` INT) ENGINE=InnoDB");

section("1. Basic Transaction (Commit)");
$db->TransactionBegin();
$db->Query("INSERT INTO tx_test (val) VALUES (10)");
$db->Query("INSERT INTO tx_test (val) VALUES (20)");
success("Inside Transaction. Depth: " . $db->GetTransactionDepth() . ", InTx: " . ($db->IsInTransaction() ? 'yes' : 'no'));
$db->TransactionEnd();
success("Committed. Count: " . $db->QuerySingleValue("SELECT COUNT(*) FROM tx_test"));

section("2. Rollback Transaction");
$db->TransactionBegin();
$db->Query("INSERT INTO tx_test (val) VALUES (999)");
success("Inside Transaction. Count so far: " . $db->QuerySingleValue("SELECT COUNT(*) FROM tx_test"));
$db->TransactionRollback();
success("Rolled back. Count: " . $db->QuerySingleValue("SELECT COUNT(*) FROM tx_test"));

section("3. Exception Mode & Try/Catch");
$db->SetThrowExceptions(true);
try {
    $db->TransactionBegin();
    $db->Query("INSERT INTO tx_test (val) VALUES (100)");
    $db->Query("INVALID SQL TO TRIGGER EXCEPTION"); // This throws
    $db->TransactionEnd(); // Never reached
} catch (RuntimeException $e) {
    fail("Caught Exception: " . $e->getMessage());
    $db->TransactionRollback();
    success("Rollback executed in catch block.");
}
$db->SetThrowExceptions(false);
success("Final Count: " . $db->QuerySingleValue("SELECT COUNT(*) FROM tx_test"));
