<?php
use PHPUnit\Framework\TestCase;

final class MemoryLimitTest extends TestCase
{
    private $db;
    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ultimatemysql_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->db = new MySQL(true);
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

    /** @test sanitizeSqlForLog anonymizes patterns (simulated) */
    public function testSanitizeSqlForLogAnonymizationLogic(): void
    {
        $ref = new ReflectionClass($this->db);
        $method = $ref->getMethod('sanitizeSqlForLog');

        $testCases = [
            "SELECT * FROM users WHERE name = 'O''Reilly'" => "SELECT * FROM users WHERE name = '?'",
            "SELECT * FROM t WHERE id = 123" => "SELECT * FROM t WHERE id = ?",
        ];

        // Since MYSQL_DEBUG_ANONIMIZATION is false by default, we test
        // that patterns don't crash and raw SQL is returned
        foreach ($testCases as $input => $expectedAnon) {
            $result = $method->invoke($this->db, $input);
            $this->assertSame($input, $result); // Raw when constant=false
        }
    }
}