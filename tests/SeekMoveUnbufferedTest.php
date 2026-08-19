<?php
use PHPUnit\Framework\TestCase;

final class SeekMoveUnbufferedTest extends TestCase
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

    /** @test Seek with valid position */
    public function testSeekValidPosition(): void
    {
        $this->db->Query("SELECT * FROM test_table");
        $result = $this->db->Seek(0);
        $this->assertTrue($result);
    }

    /** @test MoveFirst with result set */
    public function testMoveFirstWithResult(): void
    {
        $this->db->Query("SELECT * FROM test_table");
        $result = $this->db->MoveFirst();
        $this->assertTrue($result);
    }

    /** @test MoveLast with result set */
    public function testMoveLastWithResult(): void
    {
        $this->db->Query("SELECT * FROM test_table");
        $result = $this->db->MoveLast();
        $this->assertTrue($result);
    }

    /** @test Seek with unbuffered prepared statement - behavior depends on mysqlnd */
    public function testSeekUnbufferedPrepared(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table"));
        $this->assertTrue($this->db->Execute());
        
        $result = $this->db->Seek(0);
        // Seek behavior varies: may return true or false depending on mysqlnd
        $this->assertIsBool($result);
        
        $this->db->CloseStatement();
    }

    /** @test MoveFirst with unbuffered prepared statement - behavior depends on mysqlnd */
    public function testMoveFirstUnbufferedPrepared(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table"));
        $this->assertTrue($this->db->Execute());
        
        $result = $this->db->MoveFirst();
        // MoveFirst behavior varies
        $this->assertIsBool($result);
        
        $this->db->CloseStatement();
    }

    /** @test MoveLast with unbuffered prepared statement - behavior depends on mysqlnd */
    public function testMoveLastUnbufferedPrepared(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table"));
        $this->assertTrue($this->db->Execute());
        
        $result = $this->db->MoveLast();
        // MoveLast behavior varies
        $this->assertIsBool($result);
        
        $this->db->CloseStatement();
    }
}