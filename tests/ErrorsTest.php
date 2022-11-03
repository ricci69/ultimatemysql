<?php
use PHPUnit\Framework\TestCase;

final class ErrorsTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","localhost","root","root");
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
    }    
    
    public function testGetErrorNumber()
    {
        # 1
        $expected = 0;
        $actual = $this->db->ErrorNumber();
        $this->assertSame($expected, $actual);

        # 2
        $this->db->Query("SELECT * FROM NonExistentTable");
        $actual = $this->db->ErrorNumber();

        $this->assertIsNumeric($actual);
    }
}
