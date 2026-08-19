<?php
use PHPUnit\Framework\TestCase;

final class AutoInsertUpdateTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        // Create test table
        $this->db->Query("CREATE TEMPORARY TABLE IF NOT EXISTS `aiu_test` (`id` INT AUTO_INCREMENT PRIMARY KEY, `key` VARCHAR(50) UNIQUE, `value` VARCHAR(50))");
    }

    protected function tearDown(): void
    {
        $this->db->Query("DROP TEMPORARY TABLE IF EXISTS `aiu_test`");
        if ($this->db->IsConnected()) {
            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test AutoInsertUpdate inserts new record */
    public function testAutoInsertUpdateInsert(): void
    {
        $result = $this->db->AutoInsertUpdate("aiu_test", 
            ["key" => MySQL::SQLValue("new_key"), "value" => MySQL::SQLValue("new_val")],
            ["key" => MySQL::SQLValue("new_key")]
        );
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /** @test AutoInsertUpdate updates existing record */
    public function testAutoInsertUpdateUpdate(): void
    {
        // First insert
        $this->db->AutoInsertUpdate("aiu_test",
            ["key" => MySQL::SQLValue("exist_key"), "value" => MySQL::SQLValue("old_val")],
            ["key" => MySQL::SQLValue("exist_key")]
        );
        
        // Then update
        $result = $this->db->AutoInsertUpdate("aiu_test",
            ["key" => MySQL::SQLValue("exist_key"), "value" => MySQL::SQLValue("new_val")],
            ["key" => MySQL::SQLValue("exist_key")]
        );
        $this->assertTrue($result);
        
        // Verify update
        $row = $this->db->QuerySingleRowArray("SELECT `value` FROM `aiu_test` WHERE `key` = 'exist_key'");
        $this->assertSame("new_val", $row['value']);
    }

    /** @test AutoInsertUpdate rejects empty table name */
    public function testAutoInsertUpdateEmptyTable(): void
    {
        $result = $this->db->AutoInsertUpdate("", 
            ["key" => MySQL::SQLValue("test")],
            ["key" => MySQL::SQLValue("test")]
        );
        $this->assertFalse($result);
        $this->assertStringContainsString("cannot be empty", $this->db->Error());
    }

    /** @test AutoInsertUpdate rejects empty values array */
    public function testAutoInsertUpdateEmptyValues(): void
    {
        $result = $this->db->AutoInsertUpdate("aiu_test", [], 
            ["key" => MySQL::SQLValue("test")]
        );
        $this->assertFalse($result);
        $this->assertStringContainsString("cannot be empty", $this->db->Error());
    }

    /** @test AutoInsertUpdate rejects empty where array */
    public function testAutoInsertUpdateEmptyWhere(): void
    {
        $result = $this->db->AutoInsertUpdate("aiu_test",
            ["key" => MySQL::SQLValue("test"), "value" => MySQL::SQLValue("test")],
            []
        );
        $this->assertFalse($result);
        $this->assertStringContainsString("whereArray", $this->db->Error());
    }

    /** @test AutoInsertUpdate returns false for non-existent table */
    public function testAutoInsertUpdateNonExistentTable(): void
    {
        $result = $this->db->AutoInsertUpdate("non_existent_table",
            ["key" => MySQL::SQLValue("test"), "value" => MySQL::SQLValue("test")],
            ["key" => MySQL::SQLValue("test")]
        );
        $this->assertFalse($result);
    }
}