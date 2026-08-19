<?php
use PHPUnit\Framework\TestCase;

final class OpenSslCharsetTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new MySQL(false);
    }

    protected function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test Open accepts sslOptions array with all keys */
    public function testOpenAcceptsFullSslOptions(): void
    {
        // Test that sslOptions parameter is accepted (SSL connection requires proper certs)
        // We verify the method signature accepts sslOptions
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('Open');
        $params = $method->getParameters();
        $sslParam = null;
        foreach ($params as $p) {
            if ($p->getName() === 'sslOptions') {
                $sslParam = $p;
                break;
            }
        }
        $this->assertNotNull($sslParam);
        $this->assertTrue($sslParam->isOptional());
        $this->assertTrue($sslParam->allowsNull());
    }

    /** @test Open accepts charset parameter */
    public function testOpenAcceptsCharsetParameter(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('Open');
        $params = $method->getParameters();
        $charsetParam = null;
        foreach ($params as $p) {
            if ($p->getName() === 'charset') {
                $charsetParam = $p;
                break;
            }
        }
        $this->assertNotNull($charsetParam);
        $this->assertTrue($charsetParam->isOptional());
    }

    /** @test Open accepts persistent parameter */
    public function testOpenAcceptsPersistentParameter(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('Open');
        $params = $method->getParameters();
        $persistentParam = null;
        foreach ($params as $p) {
            if ($p->getName() === 'persistent') {
                $persistentParam = $p;
                break;
            }
        }
        $this->assertNotNull($persistentParam);
        $this->assertTrue($persistentParam->isOptional());
    }

    /** @test Open accepts connectTimeout parameter */
    public function testOpenAcceptsConnectTimeoutParameter(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('Open');
        $params = $method->getParameters();
        $timeoutParam = null;
        foreach ($params as $p) {
            if ($p->getName() === 'connectTimeout') {
                $timeoutParam = $p;
                break;
            }
        }
        $this->assertNotNull($timeoutParam);
        $this->assertTrue($timeoutParam->isOptional());
    }

    /** @test Open fails with wrong credentials (covers error path) */
    public function testOpenWrongCredentials(): void
    {
        $db = new MySQL(false);
        $result = $db->Open("testdb", "127.0.0.1", "wrong_user", "wrong_pass");
        $this->assertFalse($result);
        $this->assertNotEmpty($db->Error());
    }

    /** @test Open with short connectTimeout to avoid hang */
    public function testOpenShortTimeout(): void
    {
        $db = new MySQL(false);
        // Use a non-routable IP with short timeout
        $result = $db->Open("testdb", "10.255.255.1", "root", "root", connectTimeout: 1);
        $this->assertFalse($result);
        $this->assertNotEmpty($db->Error());
    }
}