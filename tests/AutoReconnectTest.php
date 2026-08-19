<?php
use PHPUnit\Framework\TestCase;

final class AutoReconnectTest extends TestCase
{
    private $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        // Set very low wait_timeout (2s) for fast test
        $this->db->Query("SET SESSION wait_timeout = 2");
    }

    public function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            try { $this->db->Query("SET SESSION wait_timeout = 28800"); } catch (\Throwable) {}
            $this->db->Close();
        }
    }

    /** @test Auto-reconnect disabled by default (legacy) */
    public function testAutoReconnectDisabledByDefault(): void
    {
        // Force dead link via reflection
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('mysql_link');
        $prop->setValue($this->db, null); // Simulate lost connection

        $result = $this->db->Query("SELECT 1");
        $this->assertFalse($result); // Must fail without attempting reconnect
        $this->assertStringContainsString("auto-reconnect disabled", $this->db->Error());
    }

    /** @test Auto-reconnect enabled: silently reconnects on next query */
    public function testAutoReconnectEnabledReconnectsOnQuery(): void
    {
        $this->db->SetAutoReconnect(true);

        // Close real connection and force "disconnected" state
        $this->db->Close();
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('mysql_link');
        $prop->setValue($this->db, null);

        // Next query must reopen connection
        $result = $this->db->Query("SELECT 1 AS test");
        $this->assertIsObject($result, "Reconnect failed: " . $this->db->Error());
        $row = $this->db->RowArray(null, MYSQLI_ASSOC);
        $this->assertSame('1', $row['test']);
    }

    /** @test Auto-reconnect does NOT happen if in transaction (safety) */
    public function testAutoReconnectSkippedInsideTransaction(): void
    {
        $this->db->SetAutoReconnect(true);
        $this->db->TransactionBegin();

        // Force dead link
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('mysql_link');
        $this->db->Close();
        $prop->setValue($this->db, null);

        // Query inside transaction must fail, NOT attempt reconnect
        $result = $this->db->Query("SELECT 1");
        $this->assertFalse($result);
        $this->assertStringContainsString("auto-reconnect disabled", $this->db->Error());

        // Clean rollback for tearDown
        $this->db->TransactionRollback();
    }

    /** @test ensureConnected() private: returns false if in transaction and link dead */
    public function testEnsureConnectedRespectsTransaction(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('ensureConnected');

        $this->db->SetAutoReconnect(true);
        $this->db->TransactionBegin();
        $this->db->Close(); // Dead link

        $result = $method->invoke($this->db);
        $this->assertFalse($result); // Must not reconnect

        $this->db->TransactionRollback();
    }
}
