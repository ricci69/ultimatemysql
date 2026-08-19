<?php
use PHPUnit\Framework\TestCase;

final class EnsureConnectedTest extends TestCase
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

    /** @test ensureConnected returns true when already connected */
    public function testEnsureConnectedAlreadyConnected(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('ensureConnected');

        $result = $method->invoke($this->db);
        $this->assertTrue($result);
    }

    /** @test ensureConnected attempts reconnect when autoReconnect enabled and connection lost */
    public function testEnsureConnectedReconnects(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('ensureConnected');

        // Enable auto-reconnect
        $this->db->SetAutoReconnect(true);

        // Kill the connection
        $prop = $ref->getProperty('mysql_link');
        $prop->setValue($this->db, null);

        $result = $method->invoke($this->db);
        $this->assertTrue($result);
    }
}