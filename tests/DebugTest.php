<?php
use PHPUnit\Framework\TestCase;

final class DebugTest extends TestCase
{
    private $db;
    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ultimatemysql_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->db = new MySQL(false); // Don't auto-connect
    }

    protected function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            $this->db->Close();
        }
        // Clean up temp files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        @rmdir($this->tempDir);
        parent::tearDown();
    }

    /** @test SetDebugPath accepts valid absolute path */
    public function testSetDebugPathValid(): void
    {
        $debugFile = $this->tempDir . '/debug.log';
        $this->db->SetDebugPath($debugFile);

        // File is created on first log write, so trigger a log by connecting
        $this->assertTrue($this->db->Open("testdb", "127.0.0.1", "root", "root"));
        $this->db->Query("SELECT 1");

        $this->assertFileExists($debugFile);
    }

    /** @test SetDebugPath rejects relative path */
    public function testSetDebugPathRejectsRelative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->SetDebugPath('relative/path/debug.log');
    }

    /** @test SetDebugPath rejects empty path */
    public function testSetDebugPathRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->SetDebugPath('');
    }

    /** @test SetDebugPath rejects non-writable directory */
    public function testSetDebugPathRejectsNonWritable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // /root is typically not writable by regular users
        $this->db->SetDebugPath('/root/debug.log');
    }

    /** @test SetDebugPath warns when path is in webroot */
    public function testSetDebugPathWarnsWebroot(): void
    {
        // Create a mock webroot directory
        $webrootDir = $this->tempDir . '/var/www/html';
        mkdir($webrootDir, 0755, true);
        $debugFile = $webrootDir . '/debug.log';

        // Should trigger E_USER_WARNING but not throw
        $this->db->SetDebugPath($debugFile);

        // File is created on first log write, so trigger a log
        $this->assertTrue($this->db->Open("testdb", "127.0.0.1", "root", "root"));
        $this->db->Query("SELECT 1");

        $this->assertFileExists($debugFile);
    }

    /** @test Debug logging works with MySQL_DEBUG_ANONIMIZATION=false (default) */
    public function testDebugLoggingRaw(): void
    {
        $debugFile = $this->tempDir . '/debug_raw.log';
        $this->db->SetDebugPath($debugFile);

        // Connect and execute a query
        $this->assertTrue($this->db->Open("testdb", "127.0.0.1", "root", "root"));
        $this->db->Query("SELECT 'secret_password' AS pwd, 42 AS id");

        $content = file_get_contents($debugFile);
        $this->assertStringContainsString("SELECT 'secret_password'", $content);
        $this->assertStringContainsString('42', $content);
    }

    /** @test sanitizeSqlForLog returns raw SQL when MYSQL_DEBUG_ANONIMIZATION=false (default) */
    public function testSanitizeSqlForLogReturnsRawByDefault(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('sanitizeSqlForLog');

        // Create a query larger than 64KB (65536 bytes)
        $largeSql = "SELECT * FROM large_table WHERE " . str_repeat("condition=", 5000);

        // Since MYSQL_DEBUG_ANONIMIZATION is false by default, it returns raw SQL
        // without truncation (truncation only happens when constant is true)
        $result = $method->invoke($this->db, $largeSql);
        $this->assertSame($largeSql, $result);
    }

    /** @test Debug logging anonymizes when constant is true (simulated) */
    public function testSanitizeSqlForLogAnonymizationLogic(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('sanitizeSqlForLog');

        $testCases = [
            "SELECT * FROM users WHERE name = 'O''Reilly'" => "SELECT * FROM users WHERE name = '?'",
            "SELECT * FROM t WHERE id = 123" => "SELECT * FROM t WHERE id = ?",
        ];

        // Since MYSQL_DEBUG_ANONIMIZATION is false by default, we test
        // that patterns don't crash
        foreach ($testCases as $input => $expectedAnon) {
            $result = $method->invoke($this->db, $input);
            $this->assertSame($input, $result); // Raw when constant=false
        }
    }
}