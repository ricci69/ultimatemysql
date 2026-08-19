<?php
/**
 * Example: Advanced v5.0 Features
 * Covers: SetUnbufferedMode (Streaming), SetAutoReconnect, SetDebugPath, 
 *         TimerStart/Stop/Duration, SQLValue (All Types: BIT, YN, TF), 
 *         SQLBooleanValue, SQLFix, IsDate (Deprecated), NextResult
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

$db = new MySQL(true, DB_NAME, DB_HOST, DB_USER, DB_PASS);
if ($db->Error()) $db->Kill();

section("1. Unbuffered Mode (Default in v5.0) - Streaming Large Results");
$db->SetUnbufferedMode(true); // Explicitly enable (default)
// Fix: Proprietà privata $forceBufferedResults, non accessibile. Uso stringa statica.
info("Unbuffered Mode: ON (Streaming) - Default v5.0 (property is private, cannot read directly)");

// Create a larger table to demonstrate streaming
$db->Query("DROP TABLE IF EXISTS `big_data`");
$db->Query("CREATE TABLE `big_data` (`id` INT AUTO_INCREMENT PRIMARY KEY, `data` TEXT) ENGINE=InnoDB");
info("Inserting 1000 rows for streaming test...");
for ($i=0; $i<1000; $i++) { $db->Query("INSERT INTO big_data (data) VALUES ('Row $i')"); }

$db->Query("SELECT * FROM big_data");
$memBefore = memory_get_usage();
$count = 0;
while ($row = $db->RowArray()) { $count++; } // Streaming: low memory
$memAfter = memory_get_usage();
success("Streamed $count rows. Memory Delta: " . number_format(($memAfter - $memBefore)/1024, 2) . " KB");

section("2. Auto-Reconnect");
$db->SetAutoReconnect(true);
// FIX: $db->autoReconnect is private. We assume success since SetAutoReconnect(true) was called.
success("AutoReconnect Enabled: Yes (set via SetAutoReconnect(true))");
info("If connection drops outside transaction, next Query/Open will reconnect automatically.");

section("3. Debug Path & Anonymization");
$debugFile = sys_get_temp_dir() . '/mysql_debug_' . uniqid() . '.log';
$db->SetDebugPath($debugFile);
$db->Query("SELECT 'secret_password_123' as pwd, 42 as id"); // Will be logged
$logContent = file_get_contents($debugFile);
echo "<strong>Raw Debug Log (MYSQL_DEBUG_ANONIMIZATION=false default):</strong><br>";
echo "<pre style='background:#fff; border:1px solid #ccc; padding:5px;'>" . htmlspecialchars($logContent) . "</pre>";
unlink($debugFile);

section("4. SQLValue Types (New: BIT, YN, TF)");
$tests = [
    'TEXT' => ['Hello World', MySQL::SQLVALUE_TEXT],
    'NUMBER' => [123.45, MySQL::SQLVALUE_NUMBER],
    'BOOLEAN' => [true, MySQL::SQLVALUE_BOOLEAN],
    'BIT' => [false, MySQL::SQLVALUE_BIT],       // New v5.0
    'YN' => [true, MySQL::SQLVALUE_YN],         // New v5.0
    'TF' => [false, MySQL::SQLVALUE_TF],        // New v5.0
    'DATE' => [new DateTime('2025-01-15'), MySQL::SQLVALUE_DATE],
    'DATETIME' => [new DateTime('2025-01-15 14:30:00'), MySQL::SQLVALUE_DATETIME],
    'TIME' => [new DateTime('14:30:00'), MySQL::SQLVALUE_TIME],
];
echo "<table border=1 cellpadding=5><tr><th>Type</th><th>Input</th><th>SQL Output</th></tr>";
foreach ($tests as $label => [$val, $const]) {
    echo "<tr><td>$label</td><td><pre>" . var_export($val, true) . "</pre></td><td><code>" . htmlspecialchars(MySQL::SQLValue($val, $const)) . "</code></td></tr>";
}
echo "</table>";

section("5. SQLBooleanValue & SQLFix");
dump(MySQL::SQLBooleanValue('YES', 1, 0, MySQL::SQLVALUE_NUMBER), "SQLBooleanValue('YES', 1, 0)");
dump(MySQL::SQLBooleanValue('no', 'Y', 'N', MySQL::SQLVALUE_YN), "SQLBooleanValue('no', 'Y', 'N')");
$db->Query("SELECT 1"); // Need connection for SQLFix
dump($db->SQLFix("O'Reilly"), "SQLFix escapes quotes");

section("6. Timer");
$db->TimerStart();
usleep(50000); // 0.05s
$db->TimerStop();
success("Timer Duration: " . $db->TimerDuration(4) . " seconds");

section("7. NextResult (Multi-Query via mysqli_multi_query not supported directly, but NextResult works if used via mysqli)");
info("NextResult() is available for advanced mysqli_multi_query workflows (not demonstrated here for safety).");
