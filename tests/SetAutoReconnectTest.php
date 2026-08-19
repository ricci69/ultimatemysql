<?php
use PHPUnit\Framework\TestCase;

final class SetAutoReconnectTest extends TestCase
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

    /** @test SetAutoReconnect enables auto-reconnect */
    public function testSetAutoReconnectEnable(): void
    {
        $this->db->SetAutoReconnect(true);
        // Verify by reflection that property is set
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('autoReconnect');
        $this->assertTrue($prop->getValue($this->db));
    }

    /** @test SetAutoReconnect disables auto-reconnect */
    public function testSetAutoReconnectDisable(): void
    {
        $this->db->SetAutoReconnect(false);
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('autoReconnect');
        $this->assertFalse($prop->getValue($this->db));
    }
}