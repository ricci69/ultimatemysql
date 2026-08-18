<?php
use PHPUnit\Framework\TestCase;

final class ConnectionResilienceTest extends TestCase
{
    protected $db;

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

    public function testAutoReconnectDisabledByDefault(): void
    {
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('autoReconnect');
        $prop->setAccessible(true);
        $this->assertFalse($prop->getValue($this->db));
    }

    public function testSetAutoReconnect(): void
    {
        $this->db->SetAutoReconnect(true);
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('autoReconnect');
        $prop->setAccessible(true);
        $this->assertTrue($prop->getValue($this->db));
    }

    public function testEnsureConnectedReturnsTrueWhenConnected(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('ensureConnected');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($this->db));
    }

    public function testEnsureConnectedFailsIfNoReconnectAndDisconnected(): void
    {
        $this->db->Close(); // Force disconnect
        $this->db->SetAutoReconnect(false);
        
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('ensureConnected');
        $method->setAccessible(true);
        $result = $method->invoke($this->db);
        
        $this->assertFalse($result);
        $this->assertStringContainsString("Connection lost", $this->db->Error());
    }

    public function testQueryFailsGracefullyWhenDisconnected(): void
    {
        $this->db->Close();
        $this->db->SetAutoReconnect(false);
        $result = $this->db->Query("SELECT 1");
        $this->assertFalse($result);
        $this->assertStringContainsString("Connection lost", $this->db->Error());
    }

    public function testMoveFirstFailsOnUnbufferedPrepared(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `test_table`"));
        $this->assertTrue($this->db->Execute());
        
        // MoveFirst on mysqli_stmt (fallback without mysqlnd) must fail.
        // On mysqlnd (get_result) returns buffered mysqli_result -> MoveFirst WORKS.
        // We test that the method doesn't throw exception and returns bool.
        $result = $this->db->MoveFirst();
        $this->assertIsBool($result);
        
        // If it fails, verify specific error message
        if (!$result) {
            $this->assertStringContainsString("not supported on unbuffered prepared statement", $this->db->Error());
        }
        $this->assertTrue($this->db->CloseStatement());
    }
}
