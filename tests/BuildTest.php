<?php
use PHPUnit\Framework\TestCase;

final class BuildTest extends TestCase
{
    public function testBuildSqlDelete()
    {
        $expected = "DELETE FROM `test_table` WHERE `id` = 9";
        
        $filter["id"] = MySQL::SQLValue(9, MySQL::SQLVALUE_NUMBER);
        $actual = MySQL::BuildSQLDelete("test_table", $filter);
        
        $this->assertSame($expected, $actual);
    }
    
    public function testBuildSqlInsert()
    {
        $expected = "INSERT INTO `test_table` (`name`, `value`) VALUES ('Violet', 777)";
        
        $values["name"] = MySQL::SQLValue("Violet");
        $values["value"] = MySQL::SQLValue(777, MySQL::SQLVALUE_NUMBER);
        $actual = MySQL::BuildSQLInsert("test_table", $values);
        
        $this->assertSame($expected, $actual);
    }
    
    public function testBuildSqlSelect()
    {
        $expected = "SELECT * FROM `test_table` WHERE `name` = 'Violet' AND `value` = 777";
    
        $values["name"] = MySQL::SQLValue("Violet");
        $values["value"] = MySQL::SQLValue(777, MySQL::SQLVALUE_NUMBER);
        $actual = MySQL::BuildSQLSelect("test_table", $values);
        
        $this->assertSame($expected, $actual);
    }
    
    public function testBuildSqlUpdate()
    {
        $expected = "UPDATE `test_table` SET `name` = 'Violet', `value` = 777 WHERE `id` = 10";
        
        $values["name"] = MySQL::SQLValue("Violet");
        $values["value"] = MySQL::SQLValue(777, MySQL::SQLVALUE_NUMBER);
        $filter["id"] = MySQL::SQLValue(10, MySQL::SQLVALUE_NUMBER);
        $actual = MySQL::BuildSQLUpdate("test_table", $values, $filter);
        
        $this->assertSame($expected, $actual);
    }
    
    public function testBuildSqlWhereClause()
    {
        #1
        $expected = " WHERE `id` = 10 AND `name` = 'Violet'";
        
        $where["id"] = MySQL::SQLValue(10, MySQL::SQLVALUE_NUMBER);
        $where["name"] = MySQL::SQLValue("Violet");
        $actual = MySQL::BuildSQLWhereClause($where);
        
        $this->assertSame($expected, $actual);
        
        
        # 2
        $expected = " WHERE name = 'Violet' AND id >= 10";
        
        $where = array("name = 'Violet' AND id >= 10");
        $actual = MySQL::BuildSQLWhereClause($where);
        
        $this->assertSame($expected, $actual);
    }    

}
