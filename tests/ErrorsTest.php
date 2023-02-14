<?php
use PHPUnit\Framework\TestCase;

final class ErrorsTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testGetErrorText()
    {
        # 1
        $this->assertFalse($this->db->Error());

        # 2
        $expected = "Table 'testdb.NonExistentTable' doesn't exist";

        $this->db->Query("SELECT * FROM NonExistentTable");
        $actual = $this->db->Error();

        $this->assertStringContainsString($expected, $actual);

        # 3
        $this->db->Query("SELECT * FROM `test_table`");
        $actual = $this->db->Error();

        $this->assertFalse($actual);
    }    
    
    public function testGetErrorNumber()
    {
        # 1
        $actual = $this->db->ErrorNumber();
        $this->assertFalse($actual);

        # 2
        $this->db->Query("SELECT * FROM `NonExistentTable`");
        $actual = $this->db->ErrorNumber();

        $this->assertIsNumeric($actual);

        # 3
        $this->db->Query("SELECT * FROM `test_table`");
        $actual = $this->db->ErrorNumber();

        $this->assertFalse($actual);
    }
}
