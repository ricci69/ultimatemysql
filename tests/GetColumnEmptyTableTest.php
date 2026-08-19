<?php
use PHPUnit\Framework\TestCase;

final class GetColumnEmptyTableTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
    }

    protected function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test GetColumnComments on empty table */
    public function testGetColumnCommentsEmptyTable(): void
    {
        // Create empty table
        $this->db->Query("CREATE TEMPORARY TABLE IF NOT EXISTS `empty_table` (`id` INT, `name` VARCHAR(50))");
        $result = $this->db->GetColumnComments("empty_table");
        $this->assertIsArray($result);
        $this->db->Query("DROP TEMPORARY TABLE IF EXISTS `empty_table`");
    }

    /** @test GetColumnCount on empty result */
    public function testGetColumnCountEmptyResult(): void
    {
        $this->db->Query("SELECT * FROM test_table WHERE 1=0");
        $count = $this->db->GetColumnCount();
        $this->assertSame(4, $count); // test_table has 4 columns
    }

    /** @test GetColumnDataType with valid index */
    public function testGetColumnDataTypeValidIndex(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $type = $this->db->GetColumnDataType(0);
        $this->assertNotEmpty($type);
    }

    /** @test GetColumnDataTypeName with valid index */
    public function testGetColumnDataTypeNameValidIndex(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $name = $this->db->GetColumnDataTypeName(0);
        $this->assertNotEmpty($name);
    }

    /** @test GetColumnLength with valid index */
    public function testGetColumnLengthValidIndex(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $len = $this->db->GetColumnLength(0);
        $this->assertGreaterThanOrEqual(0, $len);
    }

    /** @test GetColumnName with valid index */
    public function testGetColumnNameValidIndex(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $name = $this->db->GetColumnName(0);
        $this->assertNotEmpty($name);
    }
}