<?php
/**
 * Example: Export Formats & Column/Table Metadata
 * Covers: GetHTML, GetJSON, GetXML, GetTables, GetColumnNames, GetColumnCount,
 *         GetColumnDataType, GetColumnDataTypeName, GetColumnLength, GetColumnID,
 *         GetColumnName, GetColumnComments
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

section("Setup: Ensure Table Exists");
$db->Query("DROP TABLE IF EXISTS `export_test`");
$db->Query("CREATE TABLE `export_test` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) COMMENT 'User full name',
    `data` JSON COMMENT 'Arbitrary JSON data',
    `score` DECIMAL(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Test table for export'");
$db->InsertRow('export_test', [
    'name' => MySQL::SQLValue('John Doe'),
    'data' => MySQL::SQLValue('{"role": "admin"}'),
    'score' => MySQL::SQLValue(99.5, MySQL::SQLVALUE_NUMBER)
]);
$db->InsertRow('export_test', [
    'name' => MySQL::SQLValue('Jane Smith'),
    'data' => MySQL::SQLValue('{"role": "user"}'),
    'score' => MySQL::SQLValue(88.0, MySQL::SQLVALUE_NUMBER)
]);

section("1. GetHTML");
$db->Query("SELECT * FROM export_test");
echo $db->GetHTML(true, "width:100%; border:1px solid #ccc;", "background:#333; color:white;", "padding:5px;");

section("2. GetJSON");
$db->Query("SELECT * FROM export_test");
echo "<strong>JSON Output:</strong><br>";
echo "<pre style='background:#f0f0f0; padding:10px;'>" . htmlspecialchars($db->GetJSON()) . "</pre>";

section("3. GetXML");
$db->Query("SELECT * FROM export_test");
echo "<strong>XML Output:</strong><br>";
echo "<pre style='background:#f0f0f0; padding:10px;'>" . htmlspecialchars($db->GetXML()) . "</pre>";

section("4. Table Metadata: GetTables");
$tables = $db->GetTables();
dump($tables, "GetTables()");

section("5. Column Metadata (Current Result Set)");
$db->Query("SELECT * FROM export_test");

// Fix: Controlla che ci siano righe/campi prima di chiamare metodi metadata sensibili
if ($db->RowCount() > 0) {
    // GetColumnNames e GetColumnCount sono relativamente sicuri ma proteggiamo tutto
    try {
        dump($db->GetColumnNames(), "GetColumnNames()");
        dump($db->GetColumnCount(), "GetColumnCount()");
    } catch (ValueError $e) {
        fail("Metadata base failed (Class Bug): " . $e->getMessage());
    }

    try {
        dump($db->GetColumnDataType('name'), "GetColumnDataType('name')");
        dump($db->GetColumnDataTypeName('name'), "GetColumnDataTypeName('name')");
    } catch (ValueError $e) {
        fail("DataType failed (Class Bug): " . $e->getMessage());
    }
    
    // GetColumnLength lancia ValueError se index out of bounds (bug classe), proteggiamo
    try {
        dump($db->GetColumnLength('name'), "GetColumnLength('name')");
    } catch (ValueError $e) {
        fail("GetColumnLength failed (Class Bug): " . $e->getMessage());
    }
    
    // FIX: Anche GetColumnID e GetColumnName possono lanciare ValueError internamente
    try {
        dump($db->GetColumnID('name'), "GetColumnID('name')");
    } catch (ValueError $e) {
        fail("GetColumnID failed (Class Bug): " . $e->getMessage());
    }

    try {
        dump($db->GetColumnName(1), "GetColumnName(1)");
    } catch (ValueError $e) {
        fail("GetColumnName failed (Class Bug): " . $e->getMessage());
    }
} else {
    info("Result set vuoto, saltato test metadata su result set corrente.");
}

dump($db->GetColumnComments(), "GetColumnComments() (Result Set)");

section("6. Column Metadata (Via SHOW COLUMNS - Table Name)");
dump($db->GetColumnComments('export_test'), "GetColumnComments('export_test')");
dump($db->GetColumnDataType('score', 'export_test'), "GetColumnDataType('score', 'export_test')");
