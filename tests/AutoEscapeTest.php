<?php
use PHPUnit\Framework\TestCase;

final class AutoEscapeTest extends TestCase
{
    protected $db;
    // Use a persistent table name to avoid TEMPORARY table permission/engine issues
    protected string $testTable = 'ae_test_persistent';

    public function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        MySQL::SetGlobalAutoEscapeValues(false);
        $this->db->SetAutoEscapeValues(false);
        
        // Create persistent table (IF NOT EXISTS) and truncate for clean state
        $created = $this->db->Query("CREATE TABLE IF NOT EXISTS `{$this->testTable}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50),
            `description` TEXT,
            `is_active` TINYINT(1)
        ) ENGINE=InnoDB"); // InnoDB is safer default than MEMORY
        
        if (!$created) {
            $this->fail("Failed to create test table: " . $this->db->Error());
        }
        
        $truncated = $this->db->Query("TRUNCATE TABLE `{$this->testTable}`");
        if (!$truncated) {
            $this->fail("Failed to truncate test table: " . $this->db->Error());
        }
    }

    public function tearDown(): void
    {
        // Clean up persistent table
        $this->db->Query("DROP TABLE IF EXISTS `{$this->testTable}`");
        // Cleanup global test table if it exists
        $this->db->Query("DROP TABLE IF EXISTS `ae_test_global`");
        MySQL::SetGlobalAutoEscapeValues(false);
        parent::tearDown();
    }

    // --- INSTANCE LEVEL ---

    public function testInstanceAutoEscapeInsert(): void
    {
        $this->db->SetAutoEscapeValues(true);
        
        $name = "O'Reilly & Sons";
        $desc = "Test \"quotes\" and 'apostrophes'";
        
        $id = $this->db->InsertRow($this->testTable, [
            'name' => $name,
            'description' => $desc,
            'is_active' => true
        ]);
        
        if ($id === false) {
            $this->fail("InsertRow failed: " . $this->db->Error() . " | SQL: " . $this->db->GetLastSQL());
        }
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        $row = $this->db->QuerySingleRowArray("SELECT * FROM `{$this->testTable}` WHERE `id` = $id", MYSQLI_ASSOC);
        $this->assertSame($name, $row['name']);
        $this->assertSame($desc, $row['description']);
        // FIX: assertEquals allows '1' (string) == 1 (int) from DB driver
        $this->assertEquals(1, $row['is_active']);
    }

    public function testInstanceAutoEscapeUpdate(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $id = $this->db->InsertRow($this->testTable, ['name' => 'Original', 'description' => 'Desc', 'is_active' => 0]);
        
        if ($id === false) {
            $this->fail("Setup InsertRow failed: " . $this->db->Error());
        }
        
        $newName = "Updated 'Name'";
        $result = $this->db->UpdateRows($this->testTable, ['name' => $newName], ['id' => $id]);
        
        if ($result === false) {
            $this->fail("UpdateRows failed: " . $this->db->Error() . " | SQL: " . $this->db->GetLastSQL());
        }
        
        $this->assertTrue($result);
        
        $row = $this->db->QuerySingleRowArray("SELECT `name` FROM `{$this->testTable}` WHERE `id` = $id", MYSQLI_ASSOC);
        $this->assertSame($newName, $row['name']);
    }

    public function testInstanceAutoEscapeBuildSQLSelect(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $sql = MySQL::BuildSQLSelect($this->testTable, ["name" => "O'Reilly"], null, null, true, null, null, true);
        
        $this->assertStringContainsString("O''Reilly", $sql);
        $this->assertStringNotContainsString("O'Reilly", $sql);
    }

    public function testInstanceAutoEscapeBuildSQLInsert(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $sql = MySQL::BuildSQLInsert($this->testTable, ["name" => "Test's"], true);
        $this->assertStringContainsString("Test''s", $sql);
    }

    public function testInstanceAutoEscapeBuildSQLUpdate(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $sql = MySQL::BuildSQLUpdate($this->testTable, ["name" => "Upd'ate"], ["id" => 1], true);
        $this->assertStringContainsString("Upd''ate", $sql);
    }

    public function testInstanceAutoEscapeDisabledByDefault(): void
    {
        $db2 = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        $sql = MySQL::BuildSQLInsert($this->testTable, ["name" => "Test's"], false);
        $this->assertStringContainsString("Test's", $sql);
        $db2->Close();
    }

    // --- GLOBAL LEVEL ---

    public function testGlobalAutoEscapeAffectsNewInstances(): void
    {
        MySQL::SetGlobalAutoEscapeValues(true);
        
        $dbGlobal = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        
        // Create table on THIS connection (persistent)
        $created = $dbGlobal->Query("CREATE TABLE IF NOT EXISTS `ae_test_global` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50)
        ) ENGINE=InnoDB");
        if (!$created) { $this->fail("Global table create failed: " . $dbGlobal->Error()); }
        
        $dbGlobal->Query("TRUNCATE TABLE `ae_test_global`");
        
        $id = $dbGlobal->InsertRow("ae_test_global", ['name' => "Global 'Test'"]);
        
        if ($id === false) {
            $this->fail("Global InsertRow failed: " . $dbGlobal->Error() . " | SQL: " . $dbGlobal->GetLastSQL());
        }
        
        $this->assertIsInt($id);
        
        $row = $dbGlobal->QuerySingleRowArray("SELECT `name` FROM `ae_test_global` WHERE `id` = $id", MYSQLI_ASSOC);
        $this->assertSame("Global 'Test'", $row['name']);
        
        $dbGlobal->Close();
        MySQL::SetGlobalAutoEscapeValues(false);
    }

    public function testInstanceOverrideGlobal(): void
    {
        MySQL::SetGlobalAutoEscapeValues(true);
        // Uses $this->db (same connection as setUp, sees ae_test_persistent)
        $this->db->SetAutoEscapeValues(false); // Override: disable auto-escape
        
        // Value with quote -> should cause SQL syntax error because auto-escape is OFF
        $result = $this->db->InsertRow($this->testTable, ['name' => "Fail'Test"]);
        
        $this->assertFalse($result, "InsertRow should fail when auto-escape is off and value contains quote");
        $this->assertStringContainsString("syntax", $this->db->Error());
        
        MySQL::SetGlobalAutoEscapeValues(false);
    }

    // --- DATA TYPES ---

    public function testAutoEscapeHandlesNull(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $id = $this->db->InsertRow($this->testTable, ['name' => 'NullTest', 'description' => null]);
        
        if ($id === false) {
            $this->fail("Null InsertRow failed: " . $this->db->Error());
        }
        
        $this->assertIsInt($id);
        $row = $this->db->QuerySingleRowArray("SELECT `description` FROM `{$this->testTable}` WHERE `id` = $id", MYSQLI_ASSOC);
        $this->assertNull($row['description']);
    }

    public function testAutoEscapeHandlesBoolean(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $id = $this->db->InsertRow($this->testTable, ['name' => 'BoolTest', 'is_active' => true]);
        
        if ($id === false) {
            $this->fail("Boolean InsertRow failed: " . $this->db->Error());
        }
        
        $this->assertIsInt($id);
        $row = $this->db->QuerySingleRowArray("SELECT `is_active` FROM `{$this->testTable}` WHERE `id` = $id", MYSQLI_ASSOC);
        // FIX: assertEquals allows '1' (string) == 1 (int)
        $this->assertEquals(1, $row['is_active']);
        
        $id2 = $this->db->InsertRow($this->testTable, ['name' => 'BoolTest2', 'is_active' => false]);
        if ($id2 === false) { $this->fail("Boolean InsertRow 2 failed: " . $this->db->Error()); }
        
        $row2 = $this->db->QuerySingleRowArray("SELECT `is_active` FROM `{$this->testTable}` WHERE `id` = $id2", MYSQLI_ASSOC);
        // FIX: assertEquals allows '0' (string) == 0 (int)
        $this->assertEquals(0, $row2['is_active']);
    }

    public function testAutoEscapeHandlesDateTime(): void
    {
        $this->db->SetAutoEscapeValues(true);
        $dt = new DateTime('2023-01-01 12:30:00');
        $val = MySQL::SQLValue($dt, MySQL::SQLVALUE_DATETIME);
        $this->assertSame("'2023-01-01 12:30:00'", $val);
    }
}
