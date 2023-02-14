<?php
use PHPUnit\Framework\TestCase;

final class ExportTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testGetJson()
    {
        $this->db->Query("SELECT * FROM test_table WHERE id=1");
        $expected = '[{"id":"1","name":"John","date":"2022-01-01","value":"Red"}]';

        $actual = $this->db->GetJSON();

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }
    
    public function testGetXml()
    {
        $this->db->Query("SELECT * FROM test_table WHERE id=1");
        $expected = '<?xml version="1.0"?><root rows="1" query="SELECT * FROM test_table WHERE id=1" error=""><row index="1"><id>1</id><name>John</name><date>2022-01-01</date><value>Red</value></row></root>';

        $actual = $this->db->GetXML();

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }    
       
    public function testGetHtml()
    {
        $this->db->Query("SELECT * FROM test_table WHERE id=1");

        $actual = $this->db->GetHTML();

        $this->assertStringContainsStringIgnoringCase("Record count:", $actual);
        $this->assertStringContainsStringIgnoringCase("<table", $actual);
        $this->assertStringContainsStringIgnoringCase("<tr", $actual);
        $this->assertStringContainsStringIgnoringCase("<td", $actual);
    }        
       
}
