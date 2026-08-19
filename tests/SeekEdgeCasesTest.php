<?php
use PHPUnit\Framework\TestCase;

final class SeekEdgeCasesTest extends TestCase
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

    /** @test Seek returns false for out of bounds position */
    public function testSeekOutOfBounds(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 2");
        $result = $this->db->Seek(100);
        $this->assertFalse($result);
    }

    /** @test MoveFirst returns false when no result set */
    public function testMoveFirstNoResult(): void
    {
        $result = $this->db->MoveFirst();
        $this->assertFalse($result);
    }

    /** @test MoveLast returns false when no result set */
    public function testMoveLastNoResult(): void
    {
        $result = $this->db->MoveLast();
        $this->assertFalse($result);
    }

    /** @test RowCount returns false when no result set */
    public function testRowCountNoResult(): void
    {
        $result = $this->db->RowCount();
        $this->assertFalse($result);
    }

    /** @test RowCount for UPDATE query returns affected rows */
    public function testRowCountUpdate(): void
    {
        $this->db->Query("UPDATE test_table SET value = 'test' WHERE id = -1");
        $count = $this->db->RowCount();
        $this->assertSame(0, $count);
    }
}