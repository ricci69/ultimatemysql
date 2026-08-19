<?php
/**
 * Example: Write Operations (Insert, Update, Delete, Truncate, AutoInsertUpdate)
 * Covers: InsertRow, UpdateRows, DeleteRows, TruncateTable, AutoInsertUpdate
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

section("Setup: Clean Table");
$db->Query("DROP TABLE IF EXISTS `write_test`");
$db->Query("CREATE TABLE `write_test` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(20) UNIQUE NOT NULL,
    `name` VARCHAR(100),
    `price` DECIMAL(10,2),
    `stock` INT DEFAULT 0
) ENGINE=InnoDB");

section("1. InsertRow");
$id1 = $db->InsertRow('write_test', [
    'sku' => MySQL::SQLValue('SKU-001'),
    'name' => MySQL::SQLValue('Widget Alpha'),
    'price' => MySQL::SQLValue(19.99, MySQL::SQLVALUE_NUMBER),
    'stock' => MySQL::SQLValue(100, MySQL::SQLVALUE_NUMBER)
]);
success("InsertRow ID: $id1");

section("2. UpdateRows");
$updated = $db->UpdateRows('write_test', 
    ['price' => MySQL::SQLValue(22.50, MySQL::SQLVALUE_NUMBER), 'stock' => MySQL::SQLValue(95, MySQL::SQLVALUE_NUMBER)],
    ['sku' => MySQL::SQLValue('SKU-001')]
);
success("UpdateRows affected: " . $db->RowCount() . " rows");

section("3. AutoInsertUpdate (UPSERT - Race Condition Safe)");
// Insert new
$newId = $db->AutoInsertUpdate('write_test', 
    ['name' => MySQL::SQLValue('Gadget Beta'), 'price' => MySQL::SQLValue(50.00, MySQL::SQLVALUE_NUMBER), 'stock' => MySQL::SQLVALUE_NUMBER],
    ['sku' => MySQL::SQLValue('SKU-002')] // WHERE clause
);
success("AutoInsertUpdate (Insert) returned ID: $newId");

// Update existing (sku exists)
$result = $db->AutoInsertUpdate('write_test', 
    ['stock' => MySQL::SQLValue(200, MySQL::SQLVALUE_NUMBER)], // Update stock only
    ['sku' => MySQL::SQLValue('SKU-002')]
);
success("AutoInsertUpdate (Update) returned: " . ($result === true ? 'true (updated)' : $result));

section("4. DeleteRows");
$deleted = $db->DeleteRows('write_test', ['sku' => MySQL::SQLValue('SKU-001')]);
success("DeleteRows affected: " . $db->RowCount() . " rows");

section("5. TruncateTable");
$db->InsertRow('write_test', ['sku' => MySQL::SQLValue('TMP-001'), 'name' => MySQL::SQLValue('Temp')]);
success("Inserted temp row. Count: " . $db->QuerySingleValue("SELECT COUNT(*) FROM write_test"));
$db->TruncateTable('write_test');
success("Truncated table. Count: " . $db->QuerySingleValue("SELECT COUNT(*) FROM write_test"));
