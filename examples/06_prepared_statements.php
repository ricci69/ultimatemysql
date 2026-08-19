<?php
/**
 * Example: Prepared Statements (v5.0 Core Feature)
 * Covers: Prepare, BindParam, BindParams, Execute, Fetch, FetchAll, CloseStatement, PreparedRowCount
 * Demonstrates: Unbuffered fallback (no mysqlnd), Memory Safety, Binding types.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

section("Setup");
$db->Query("DROP TABLE IF EXISTS `prep_test`");
$db->Query("CREATE TABLE `prep_test` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(50), `score` INT, `active` BOOLEAN) ENGINE=InnoDB");
for ($i=1; $i<=5; $i++) {
    $db->InsertRow('prep_test', ['name' => MySQL::SQLValue("User $i"), 'score' => MySQL::SQLValue($i*10, MySQL::SQLVALUE_NUMBER), 'active' => MySQL::SQLValue($i%2==0, MySQL::SQLVALUE_BOOLEAN)]);
}

section("1. Prepare & BindParam (Single)");
$prepared = $db->Prepare("SELECT * FROM prep_test WHERE score > ? AND active = ?");
if (!$prepared) $db->Kill("Prepare failed: " . $db->Error());

// Bind: score (int='i'), active (bool->int='i')
$db->BindParam(15, 'i'); 
$db->BindParam(1, 'i'); // true = 1
success("Bound 2 parameters via BindParam.");

section("2. Execute & Fetch Loop (Streaming / Unbuffered)");
$executed = $db->Execute();
if (!$executed) $db->Kill("Execute failed: " . $db->Error());

info("Fetching rows one by one (Memory Efficient - Unbuffered):");
$count = 0;
while ($row = $db->Fetch(MYSQLI_ASSOC)) {
    $count++;
    echo "- {$row['name']}: Score {$row['score']}<br>";
}
success("Fetched $count rows via Fetch() loop. Pointer at end.");

// FetchAll is also available but buffers in PHP memory (safety limit applies)
$db->CloseStatement(); // Reset for next example

section("3. BindParams (Bulk) & FetchAll");
$db->Prepare("SELECT name, score FROM prep_test WHERE score BETWEEN ? AND ?");
$db->BindParams([10, 30], 'ii'); // Types: integer, integer
$db->Execute();

$allRows = $db->FetchAll(MYSQLI_ASSOC);
dump($allRows, "FetchAll() Result (Buffered in PHP)");
$db->CloseStatement();

section("4. PreparedRowCount & Limitations");
$db->Prepare("SELECT * FROM prep_test WHERE id < ?");
$db->BindParam(3, 'i');
$db->Execute();

$rc = $db->PreparedRowCount();
if ($rc === false) {
    info("PreparedRowCount() returned false (Expected without mysqlnd). Use COUNT(*) query instead.");
} else {
    success("PreparedRowCount: $rc");
}
$db->CloseStatement();

section("5. DML Prepared Statement (Insert)");
$db->Prepare("INSERT INTO prep_test (name, score, active) VALUES (?, ?, ?)");
$db->BindParams(['Prepared User', 999, 1], 'sii');
$db->Execute();
success("Insert via Prepare/Execute. Last ID: " . $db->GetLastInsertID());
$db->CloseStatement();
