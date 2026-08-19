<?php
use PHPUnit\Framework\TestCase;

final class CloseTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
    }

    protected function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            // Release any active result before closing
            $ref = new ReflectionClass($this->db);
            $prop = $ref->getProperty('last_result');
            $lastResult = $prop->getValue($this->db);

            if (is_object($lastResult) && $lastResult !== $this->db) {
                try {
                    @mysqli_free_result($lastResult);
                } catch (\Throwable) {}
            }

            // Set last_result to null to prevent double-free in destructor
            $prop->setValue($this->db, null);

            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test Close closes connection */
    public function testClose(): void
    {
        $this->assertTrue($this->db->IsConnected());
        $result = $this->db->Close();
        $this->assertTrue($result);
        $this->assertFalse($this->db->IsConnected());
    }

    /** @test Close handles already closed connection */
    public function testCloseAlreadyClosed(): void
    {
        $this->db->Close();
        $result = $this->db->Close();
        $this->assertTrue($result); // Should not fail
    }

    /** @test Close with active prepared statement */
    public function testCloseWithPreparedStatement(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table"));
        $result = $this->db->Close();
        $this->assertTrue($result);
        $this->assertFalse($this->db->IsConnected());
    }
}