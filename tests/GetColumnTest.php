<?php
use PHPUnit\Framework\TestCase;

final class GetColumnTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testGetColumnComments()
    {
        #1
        $expected = array("id"=>"", "name"=>"It contains the name", "data"=>"", "value"=>"");
        $actual = $this->db->GetColumnComments("test_table");
        $this->assertEqualsCanonicalizing($expected, $actual);
        
        #2
        $expected = array("id"=>"", "name"=>"It contains the name", "data"=>"", "value"=>"");
        $actual = $this->db->GetColumnComments("test_table","ASSOC");
        $this->assertEqualsCanonicalizing($expected, $actual);  
        
        #3
        $expected = array("0"=>"", "1"=>"It contains the name", "2"=>"", "3"=>"");
        $actual = $this->db->GetColumnComments("test_table","NUM");
        $this->assertEqualsCanonicalizing($expected, $actual);
        
        #4
        $expected = array("0"=>"", "1"=>"It contains the name", "2"=>"", "3"=>"", "id"=>"", "name"=>"It contains the name", "data"=>"", "value"=>"");
        $actual = $this->db->GetColumnComments("test_table","BOTH");
        $this->assertEqualsCanonicalizing($expected, $actual);        
        
    }
    
    public function testGetColumnCount()
    {
        # 1
        $expected = 3;
        $actual = $this->db->GetColumnDataType("id", "test_table");
        $this->assertSame($expected, $actual);
        
        # 2
        $expected = 253;
        $actual = $this->db->GetColumnDataType("name", "test_table");
        $this->assertSame($expected, $actual);
    }

    public function testGetColumnId()
    {
        $expected = 1;
        $actual = $this->db->GetColumnID("name", "test_table");
        $this->assertSame($expected, $actual);
    }
    
    public function testGetColumnLength()
    {
        $expected = 100;
        $actual = $this->db->GetColumnLength("name", "test_table");
        $this->assertSame($expected, $actual);
    }
    
    public function testGetColumnName()
    {
        $expected = "name";
        $actual = $this->db->GetColumnName(1, "test_table");
        $this->assertSame($expected, $actual);
    }
    
    public function testGetColumnNames()
    {
        $expected = array("0"=>"id", "1"=>"name", "2"=>"date", "3"=>"value");
        $actual = $this->db->GetColumnNames("test_table");
        $this->assertEqualsCanonicalizing($expected, $actual);
    }    
    
    public function testGetTablesList()
    {
        $expected = "test_table";
        $actual = $this->db->GetTables();
        $this->assertContains($expected, $actual);
    }    
    
    
}
