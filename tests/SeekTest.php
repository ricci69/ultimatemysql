<?php
use PHPUnit\Framework\TestCase;

final class SeekTest extends TestCase
{
    protected $db;

    private static int $testTableRows = 3;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }

    public function testSeek()
    {
        $expected = array("0"=>"2", "1"=>"John2", "2"=>"2022-06-01", "3"=>"Yellow");

        $this->db->Query("SELECT * FROM `test_table`");
        
        // BUG-015 FIX: Seek() now returns bool (positioning), not the row.
        // We verify positioning succeeded.
        $this->assertTrue($this->db->Seek(1));
        
        // Row is read with RowArray() AFTER Seek.
        // Test expects only numeric keys (MYSQLI_NUM), as defined in $expected
        $actual = $this->db->RowArray(null, MYSQLI_NUM);

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
        $this->db->Seek(20);
        $this->assertFalse($this->db->BeginningOfSeek());
    }

    public function testEndOfSeek()
    {
        # 1
        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->Seek(self::$testTableRows);
        $this->assertTrue($this->db->EndOfSeek());

        # 2
        $this->db->Seek(0);
        $this->assertFalse($this->db->EndOfSeek());

        # 3
        $this->db->Seek(20);
        $this->assertFalse($this->db->EndOfSeek());
    }

    public function testMoveFirst()
    {
        $expected = 0;

        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->MoveFirst();

        $actual = $this->db->SeekPosition();

        $this->assertSame($expected, $actual);
        $this->assertTrue($this->db->BeginningOfSeek());
    }

    public function testMoveLast()
    {
        $expected = self::$testTableRows - 1;

        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->MoveLast();

        $actual = $this->db->SeekPosition();

        $this->assertSame($expected, $actual);
        $this->assertFalse($this->db->EndOfSeek());
    }

    public function testBeginningOfSeekNoConnection()
    {
        $this->db->Close();
        $this->db->Seek(0);
        $this->assertFalse($this->db->BeginningOfSeek());
    }

    public function testEndOfSeekNoConnection()
    {
        $this->db->Close();
        $this->db->Seek(0);
        $this->assertFalse($this->db->EndOfSeek());
    }

    public function testMoveFirstNoConnection()
    {
        $this->db->Close();
        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->Seek(0);
        $this->assertFalse($this->db->MoveFirst());
    }

    public function testMoveLastNoConnection()
    {
        $this->db->Close();
        $this->db->Query("SELECT * FROM `test_table`");
        $this->db->Seek(0);
        $this->assertFalse($this->db->MoveLast());
    }

    /**
     * Test based on some real world code which worked in version 3.0
     * and was broken with release 4.0.
     */
    public function testSeekLoop()
    {
        $this->db->Query("SELECT * FROM `test_table`");
        $i = 0;
        $this->db->MoveFirst();
        while (!$this->db->EndOfSeek()) {
            $this->db->Row();
            $i++;
        }
        $this->assertSame(self::$testTableRows, $i);
    }

}
