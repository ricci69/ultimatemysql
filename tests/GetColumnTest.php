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
        # 1
        $expected = array("id"=>"", "name"=>"It contains the name", "data"=>"", "value"=>"");
        $actual = $this->db->GetColumnComments("test_table");
        $this->assertEqualsCanonicalizing($expected, $actual);
        
        # 2
        $expected = array("id"=>"", "name"=>"It contains the name", "data"=>"", "value"=>"");
        $actual = $this->db->GetColumnComments("test_table","ASSOC");
        $this->assertEqualsCanonicalizing($expected, $actual);  
        
        # 3
        $expected = array("0"=>"", "1"=>"It contains the name", "2"=>"", "3"=>"");
        $actual = $this->db->GetColumnComments("test_table","NUM");
        $this->assertEqualsCanonicalizing($expected, $actual);
        
        # 4
        $expected = array("0"=>"", "1"=>"It contains the name", "2"=>"", "3"=>"", "id"=>"", "name"=>"It contains the name", "data"=>"", "value"=>"");
        $actual = $this->db->GetColumnComments("test_table","BOTH");
        $this->assertEqualsCanonicalizing($expected, $actual);        
        
        # 5
        $actual = $this->db->GetColumnComments("test_table","INVALID");
        $this->assertFalse($actual);   
        
        # 6
        $actual = $this->db->GetColumnComments("invalid_table","ASSOC");
        $this->assertFalse($actual);             
    }
    
    public function testGetColumnCount()
    {
        # 1
        $expected = 4;
        $actual = $this->db->GetColumnCount("test_table");
        $this->assertSame($expected, $actual);
        
        # 2
        $actual = $this->db->GetColumnCount("invalid_table");
        $this->assertFalse($actual);        
    }    
    
    public function testGetColumnDataType()
    {
        # 1
        $expected = 3;
        $actual = $this->db->GetColumnDataType("id", "test_table");
        $this->assertSame($expected, $actual);
        
        # 2
        $expected = 253;
        $actual = $this->db->GetColumnDataType("name", "test_table");
        $this->assertSame($expected, $actual);
        
        # 3
        $expected = 253;
        $actual = $this->db->GetColumnDataType(1, "test_table");
        $this->assertSame($expected, $actual);   
        
        # 4
        $this->db->Query("SELECT `name` FROM `test_table`");
        $expected = 253;
        $actual = $this->db->GetColumnDataType(0);
        $this->assertSame($expected, $actual);  

        # 5
        $expected = 253;
        $actual = $this->db->GetColumnDataType("name");
        $this->assertSame($expected, $actual); 
        
        # 6
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id`=12345");
        $actual = $this->db->GetColumnDataType(0);
        $this->assertFalse($actual);          
    }

    public function testGetColumnDataTypeName()
    {
        # 1
        $expected = "int";
        $actual = $this->db->GetColumnDataTypeName("id", "test_table");
        $this->assertStringContainsString($expected, $actual);
        
        # 2
        $expected = "varchar";
        $actual = $this->db->GetColumnDataTypeName("name", "test_table");
        $this->assertStringContainsString($expected, $actual);
        
        # 3
        $expected = "varchar";
        $actual = $this->db->GetColumnDataTypeName(1, "test_table");
        $this->assertStringContainsString($expected, $actual);   
        
        # 4
        $expected = "varchar";
        $actual = $this->db->GetColumnDataTypeName(3, "test_table");
        $this->assertStringContainsString($expected, $actual);   
        
        # 5
        $this->db->Query("SELECT `name` FROM `test_table`");
        $expected = "int";
        $actual = $this->db->GetColumnDataTypeName(0);
        $this->assertStringContainsString($expected, $actual);

        # 6
        $expected = "date";
        $actual = $this->db->GetColumnDataTypeName("date", "test_table");
        $this->assertSame($expected, $actual); 
        
        # 7
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id`=12345");
        $actual = $this->db->GetColumnDataTypeName(0);
        $this->assertFalse($actual);          
    }    
    
    public function testGetColumnId()
    {
        # 1
        $expected = 1;
        $actual = $this->db->GetColumnID("name", "test_table");
        $this->assertSame($expected, $actual);
        
        # 2
        $actual = $this->db->GetColumnID("name", "invalid_table");
        $this->assertFalse($actual);        
    }
    
    public function testGetColumnLength()
    {
        # 1
        $expected = 0; # It depends on the system configuration, so we only check if it's greater than 0
        $actual = $this->db->GetColumnLength("name", "test_table");
        $this->assertGreaterThan($expected, $actual);
        
        # 2
        $expected = 0; # It depends on the system configuration, so we only check if it's greater than 0
        $actual = $this->db->GetColumnLength(0, "test_table");
        $this->assertGreaterThan($expected, $actual);
        
        # 3
        $actual = $this->db->GetColumnLength("name", "invalid_table");
        $this->assertFalse($actual);
        
        # 4
        $actual = $this->db->GetColumnLength("invalid_column", "invalid_table");
        $this->assertFalse($actual);      
    }
    
    public function testGetColumnName()
    {
        # 1
        $expected = "name";
        $actual = $this->db->GetColumnName(1, "test_table");
        $this->assertSame($expected, $actual);
        
        # 2
        $this->db->Query("SELECT * FROM `test_table`");
        $expected = "name";
        $actual = $this->db->GetColumnName(1);
        $this->assertSame($expected, $actual);        

        # 3
        $actual = $this->db->GetColumnName(123, "test_table");
        $this->assertFalse($actual);
        
        
    }
    
    public function testGetColumnNames()
    {
        # 1
        $expected = array("0"=>"id", "1"=>"name", "2"=>"date", "3"=>"value");
        $actual = $this->db->GetColumnNames("test_table");
        $this->assertEqualsCanonicalizing($expected, $actual);
        
        # 2
        $this->db->Query("SELECT * FROM `test_table`");
        $expected = array("0"=>"id", "1"=>"name", "2"=>"date", "3"=>"value");
        $actual = $this->db->GetColumnNames();
        $this->assertSame($expected, $actual);        

        # 3
        $actual = $this->db->GetColumnNames("invalid_table");
        $this->assertFalse($actual);        
    }    
    
    public function testGetTablesList()
    {
        $expected = "test_table";
        $actual = $this->db->GetTables();
        $this->assertContains($expected, $actual);
        
        $this->db->Close();
        $actual = $this->db->GetTables();
        $this->assertFalse($actual);   
    }    
    
    
}
