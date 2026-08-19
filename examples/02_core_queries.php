<?php
/**
 * Example: Core Query Execution & Result Navigation
 * Covers: Query, QueryArray, QuerySingleRow, QuerySingleRowArray, QuerySingleValue, QueryTimed,
 *         RowCount, Row, RowArray, MoveFirst, MoveLast, Seek, EndOfSeek, BeginningOfSeek, SeekPosition,
 *         Release, GetLastSQL, GetLastInsertID, HasRecords, Records, RecordsArray
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

section("Setup: Create Test Table & Seed Data");
$db->Query("DROP TABLE IF EXISTS `example_users`");
$sql = "CREATE TABLE `example_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `age` TINYINT UNSIGNED NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$db->Query($sql) or $db->Kill("Create table failed");

$users = [
    ['username' => 'alice', 'email' => 'alice@example.com', 'age' => 28, 'active' => true],
    ['username' => 'bob', 'email' => 'bob@example.com', 'age' => 35, 'active' => false],
    ['username' => 'charlie', 'email' => 'charlie@example.com', 'age' => 42, 'active' => true],
];
foreach ($users as $u) {
    $db->InsertRow('example_users', [
        'username' => MySQL::SQLValue($u['username']),
        'email' => MySQL::SQLValue($u['email']),
        'age' => MySQL::SQLValue($u['age'], MySQL::SQLVALUE_NUMBER),
        'active' => MySQL::SQLValue($u['active'], MySQL::SQLVALUE_BOOLEAN),
    ]);
}
success("Seeded " . count($users) . " users. Last ID: " . $db->GetLastInsertID());

section("1. Query() & Result Resource");
$result = $db->Query("SELECT * FROM example_users WHERE active = 1");
dump($result, "Query() returns mysqli_result object");
dump($db->GetLastSQL(), "GetLastSQL()");

section("2. RowCount() & HasRecords()");
dump($db->RowCount(), "RowCount()");
dump($db->HasRecords(), "HasRecords()");

section("3. Single Value / Row Helpers");
dump($db->QuerySingleValue("SELECT COUNT(*) FROM example_users"), "QuerySingleValue (Count)");
dump($db->QuerySingleRow("SELECT * FROM example_users LIMIT 1"), "QuerySingleRow (Object)");
dump($db->QuerySingleRowArray("SELECT * FROM example_users LIMIT 1", MYSQLI_ASSOC), "QuerySingleRowArray (Assoc)");

section("4. QueryArray (Fetch All at once)");
$all = $db->QueryArray("SELECT username, email FROM example_users ORDER BY id", MYSQLI_ASSOC);
dump($all, "QueryArray()");

section("5. Navigation: MoveFirst, MoveLast, Seek, Row, RowArray");
$db->Query("SELECT * FROM example_users ORDER BY id");
dump($db->MoveFirst(), "MoveFirst()");
dump($db->Row(MYSQLI_ASSOC), "Row(0) -> Object");
dump($db->SeekPosition(), "SeekPosition()");

$db->MoveLast();
dump($db->Row(), "MoveLast() -> Last Row Object");
dump($db->SeekPosition(), "SeekPosition at end");

$db->Seek(0);
dump($db->RowArray(null, MYSQLI_NUM), "Seek(0) -> RowArray (Numeric)");

section("6. Iteration Helpers: BeginningOfSeek, EndOfSeek");
$db->MoveFirst();
echo "<strong>While Loop (EndOfSeek):</strong><br>";
while (!$db->EndOfSeek()) {
    $r = $db->Row();
    echo "- {$r->username} ({$r->email})<br>";
}

section("7. QueryTimed (Performance)");
$res = $db->QueryTimed("SELECT SLEEP(0.01)"); // Simulate work
success("QueryTimed executed. Duration: " . $db->TimerDuration(6) . " seconds");

section("8. RecordsArray & Release");
$db->Query("SELECT * FROM example_users");
$arr = $db->RecordsArray(MYSQLI_ASSOC);
dump($arr, "RecordsArray()");
$db->Release();
success("Release() freed result memory.");

section("9. Records Property (Raw Access)");
$db->Query("SELECT 1 as test");
$raw = $db->Records();
dump(get_class($raw), "Records() returns class");
