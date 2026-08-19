<?php
use PHPUnit\Framework\TestCase;

final class QueryMultiStatementTest extends TestCase
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

    /** @test Query rejects multi-statement (covers line 1998-1999) */
    public function testQueryRejectsMultiStatement(): void
    {
        $result = $this->db->Query("SELECT 1; SELECT 2");
        $this->assertFalse($result);
        $this->assertStringContainsString("Multi-statement", $this->db->Error());
    }

    /** @test Query accepts single statement with trailing semicolon */
    public function testQueryAcceptsSingleStatementWithSemicolon(): void
    {
        $result = $this->db->Query("SELECT 1;");
        $this->assertIsObject($result, "Single statement with semicolon failed: " . $this->db->Error());
        $this->db->Release();
    }

    /** @test Query accepts single statement without semicolon */
    public function testQueryAcceptsSingleStatement(): void
    {
        $result = $this->db->Query("SELECT 1");
        $this->assertIsObject($result);
        $this->db->Release();
    }
}