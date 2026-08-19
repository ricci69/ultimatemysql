<?php
use PHPUnit\Framework\TestCase;

final class SetUnbufferedModeTest extends TestCase
{
    private $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
    }

    public function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test Enable unbuffered mode */
    public function testSetUnbufferedMode(): void
    {
        // Enable unbuffered mode (default in v5.0)
        $this->db->SetUnbufferedMode(true);
        
        // Verify by executing a query
        $result = $this->db->Query("SELECT * FROM test_table");
        $this->assertIsObject($result);
        
        // With unbuffered mode enabled, we should get a result object
        // that can be iterated
        $this->db->Release();
    }

    /** @test Disable unbuffered mode */
    public function testSetUnbufferedModeDisabled(): void
    {
        // Disable unbuffered mode (buffered)
        $this->db->SetUnbufferedMode(false);
        
        // Verify by executing a query
        $result = $this->db->Query("SELECT * FROM test_table");
        $this->assertIsObject($result);
        
        $this->db->Release();
    }
}