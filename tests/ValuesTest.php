<?php
use PHPUnit\Framework\TestCase;

final class ValuesTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testGetBooleanValue()
    {
        $this->assertTrue(MySQL::GetBooleanValue("Y"));
        $this->assertFalse(MySQL::GetBooleanValue("no"));
        $this->assertTrue(MySQL::GetBooleanValue("TRUE"));
        $this->assertTrue(MySQL::GetBooleanValue("1"));
    }
    
    public function testIsDate()
    {
        $this->assertTrue(MySQL::IsDate("January 1, 2000"));
        $this->assertTrue(MySQL::IsDate("today"));
        $this->assertFalse(MySQL::IsDate("blue"));
    }    
 
    public function testSqlBooleanValue()
    {
        # 1
        $expected = "0";
        $actual = MySQL::SQLBooleanValue(false, "1", "0", MySQL::SQLVALUE_NUMBER);
        $this->assertSame($expected, $actual);
        
        # 2
        $expected = "'2022-01-01'";
        $actual = MySQL::SQLBooleanValue(true, "Jan 1, 2022", "2020/01/01", MySQL::SQLVALUE_DATE);
        $this->assertSame($expected, $actual);
        
        # 3
        $expected = "'Yes'";
        $actual = MySQL::SQLBooleanValue("ON", "Yes", "No");
        $this->assertSame($expected, $actual);
        
        # 4
        $expected = "'-'";
        $actual = MySQL::SQLBooleanValue(0, '+', '-'); 
        $this->assertSame($expected, $actual);
    }
    
    public function testSqlValue()
    {
        # 1
        $expected = "'it''s a string'";
        $actual = MySQL::SQLValue("it's a string", "text");
        $this->assertSame($expected, $actual);
        
        # 2
        $expected = MySQL::SQLValue("it's a string");
        $actual = MySQL::SQLValue("it's a string", "text");
        $this->assertSame($expected, $actual);     
        
        # 3
        $expected = MySQL::SQLValue("it's a string", "text");
        $actual = MySQL::SQLValue("it's a string", MYSQL::SQLVALUE_TEXT);
        $this->assertSame($expected, $actual);          
        
        # 4
        $expected = "SELECT * FROM test_table WHERE id = 1";
        $actual = "SELECT * FROM test_table WHERE id = " . MySQL::SQLValue("1", MySQL::SQLVALUE_NUMBER);
        $this->assertSame($expected, $actual);  
        
        # 5
        $expected = "UPDATE test_table SET value = '2007-07-04'";
        $actual =  "UPDATE test_table SET value = " . MySQL::SQLValue("July 4, 2007", MySQL::SQLVALUE_DATE);  
        $this->assertSame($expected, $actual);  
    }
    
    public function testSqlFix()
    {
        $expected = '\\\hello\\\ /world/';
        $actual = $this->db->SQLFix("\hello\ /world/");
        $this->assertSame($expected, $actual);  
    }
 
}
