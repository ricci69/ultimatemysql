<?php
use PHPUnit\Framework\TestCase;

final class SeekTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testSeek()
    {
        $expected = array("0"=>"2", "1"=>"John2", "2"=>"2022-06-01", "3"=>"Yellow");

        $this->db->Query("SELECT * FROM `test_table`");
        $actual = $this->db->Seek(1);

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testSeekInvalidRow()
    {
        $this->db->Query("SELECT * FROM `test_table`");
        $this->assertFalse($this->db->Seek(5));
    }

    public function testSeekQueryWithoutResult()
    {
        $this->db->Query("SELECT * FROM `test_table` WHERE `id`=10");
        $this->assertFalse($this->db->Seek(5));
    }

    public function testSeekPosition()
    {
        $expected = 1;

        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->Seek(1);
        $actual = $this->db->SeekPosition();

        $this->assertSame($expected, $actual);
    }

    public function testBeginningOfSeek()
    {
        # 1
        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->Seek(1);
        $this->assertFalse($this->db->BeginningOfSeek());

        # 2
        $this->db->Seek(0);
        $this->assertTrue($this->db->BeginningOfSeek());

        # 3
        $this->db->Seek(5);
        $this->assertFalse($this->db->BeginningOfSeek());
    }

    public function testEndOfSeek()
    {
        # 1
        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->Seek(1);
        $this->assertTrue($this->db->EndOfSeek());

        # 2
        $this->db->Seek(0);
        $this->assertFalse($this->db->EndOfSeek());

        # 3
        $this->db->Seek(5);
        $this->assertFalse($this->db->EndOfSeek());
    }

    public function testMoveFirst()
    {
        $expected = 0;

        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->MoveFirst();

        $actual = $this->db->SeekPosition();

        $this->assertSame($expected, $actual);
    }

    public function testMoveLast()
    {
        $expected = 1;

        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->MoveLast();

        $actual = $this->db->SeekPosition();

        $this->assertSame($expected, $actual);
    }

}
