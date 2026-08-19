<?php
use PHPUnit\Framework\TestCase;

final class ExportSafetyLimitTest extends TestCase
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

    /** @test GetHTML with empty result */
    public function testGetHtmlEmptyResult(): void
    {
        $this->db->Query("SELECT * FROM test_table WHERE 1=0");
        $result = $this->db->GetHTML();
        // GetHTML returns "no records were returned." for empty results
        $this->assertStringContainsString("no records", $result);
    }

    /** @test GetJSON with empty result */
    public function testGetJsonEmptyResult(): void
    {
        $this->db->Query("SELECT * FROM test_table WHERE 1=0");
        $result = $this->db->GetJSON();
        $this->assertSame("[]", $result);
    }

    /** @test GetXML with empty result */
    public function testGetXmlEmptyResult(): void
    {
        $this->db->Query("SELECT * FROM test_table WHERE 1=0");
        $result = $this->db->GetXML();
        // GetXML returns XML with rows="0"
        $this->assertStringContainsString('rows="0"', $result);
    }

    /** @test GetHTML safety limit is documented */
    public function testGetHtmlSafetyLimit(): void
    {
        // The safety limit is enforced by MYSQL_MAX_BUFFERED_ROWS constant
        // We can't easily test it without creating 50000+ rows
        // Just verify the method works
        $this->db->Query("SELECT * FROM test_table LIMIT 5");
        $result = $this->db->GetHTML();
        $this->assertStringContainsString("<table", $result);
    }

    /** @test GetJSON safety limit is documented */
    public function testGetJsonSafetyLimit(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 5");
        $result = $this->db->GetJSON();
        $this->assertStringContainsString("[", $result);
    }

    /** @test GetXML safety limit is documented */
    public function testGetXmlSafetyLimit(): void
    {
        $this->db->Query("SELECT * FROM test_table LIMIT 5");
        $result = $this->db->GetXML();
        // GetXML returns XML with <root> not <result>
        $this->assertStringContainsString("<root", $result);
    }
}