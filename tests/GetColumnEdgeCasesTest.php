<?php
use PHPUnit\Framework\TestCase;

final class GetColumnEdgeCasesTest extends TestCase
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

    /** @test GetColumnComments returns array for table */
    public function testGetColumnComments(): void
    {
        $result = $this->db->GetColumnComments("test_table");
        $this->assertIsArray($result);
    }

    /** @test GetColumnCount returns column count for valid query */
    public function testGetColumnCount(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $count = $this->db->GetColumnCount();
        $this->assertGreaterThan(0, $count);
    }

    /** @test GetColumnDataType returns type for valid column index */
    public function testGetColumnDataTypeValid(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $result = $this->db->GetColumnDataType(0);
        $this->assertNotEmpty($result);
    }

    /** @test GetColumnDataTypeName returns type name for valid column index */
    public function testGetColumnDataTypeNameValid(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $result = $this->db->GetColumnDataTypeName(0);
        $this->assertNotEmpty($result);
    }

    /** @test GetColumnLength returns length for valid column index */
    public function testGetColumnLengthValid(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $result = $this->db->GetColumnLength(0);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /** @test GetColumnName returns name for valid column index */
    public function testGetColumnNameValid(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 1");
        $result = $this->db->GetColumnName(0);
        $this->assertNotEmpty($result);
    }

    /** @test GetTables returns array */
    public function testGetTables(): void
    {
        $result = $this->db->GetTables();
        $this->assertIsArray($result);
    }
}