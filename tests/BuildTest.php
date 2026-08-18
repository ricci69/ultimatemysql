<?php
use PHPUnit\Framework\TestCase;

final class BuildTest extends TestCase
{
    private int $originalErrorReporting;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting();
        error_reporting($this->originalErrorReporting & ~E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        error_reporting($this->originalErrorReporting);
        parent::tearDown();
    }

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

        $expected = "SELECT * FROM `test_table` WHERE `id` = NULL AND `name` IS NULL AND `value` = 'foo'";
        $values = [];
        $values['id'] = MySQL::SQLValue(null);
        $values['name'] = null;
        $values['value'] = MySQL::SQLValue('foo', MySQL::SQLVALUE_TEXT);
        $actual = MySQL::BuildSQLSelect('test_table', $values);
        $this->assertSame($expected, $actual);
    }
    
    public function testBuildSqlSelectWithAllParameters()
    {
        $expected = "SELECT `id` FROM `test_table` WHERE `name` = 'Violet' AND `value` = 777 ORDER BY `id` ASC LIMIT 1";
        $values["name"] = MySQL::SQLValue("Violet");
        $values["value"] = MySQL::SQLValue(777, MySQL::SQLVALUE_NUMBER);
        $actual = MySQL::BuildSQLSelect("test_table", $values, 'id', 'id', "ASC", 1);
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

        $expected = "UPDATE `test_table` SET `name` = 'Violet', `value` = NULL WHERE `id` = 10 AND `name` IS NULL";
        $values["name"] = MySQL::SQLValue("Violet");
        $values["value"] = MySQL::SQLValue(null);
        $filter["id"] = MySQL::SQLValue(10, MySQL::SQLVALUE_NUMBER);
        $filter["name"] = null;
        $actual = MySQL::BuildSQLUpdate("test_table", $values, $filter);
        $this->assertSame($expected, $actual);
    }
    
    public function testBuildSqlWhereClause()
    {
        #1 Standard
        $expected = " WHERE `id` = 10 AND `name` = 'Violet'";
        $where["id"] = MySQL::SQLValue(10, MySQL::SQLVALUE_NUMBER);
        $where["name"] = MySQL::SQLValue("Violet");
        $actual = MySQL::BuildSQLWhereClause($where);
        $this->assertSame($expected, $actual);
        
        
         # 2 Operators in key (New syntax)
        $expected = " WHERE `name` = 'Violet' AND `id` >= 10";
        $where = [
            "name" => "'Violet'",  
            "id >=" => 10          
        ];
        $actual = MySQL::BuildSQLWhereClause($where);
        $this->assertSame($expected, $actual);
                                                                                                                                                                                                                                            
        # 3 IS NULL
        $expected = " WHERE `name` IS NULL AND `id` >= 10";
        $where["name"] = null; 
        $actual = MySQL::BuildSQLWhereClause($where);
        $this->assertSame($expected, $actual);
                                                                                                                                                                                                                                            
        # 4 Empty array
        $expected = "";
        $where = [];
        $actual = MySQL::BuildSQLWhereClause($where);
        $this->assertSame($expected, $actual);
                                                                                                                                                                                                                                            
        # 5 Explicit RAW fragment (_raw)
        $expected = " WHERE `abc` = 2 AND 1";
        $where = [
            "abc" => 2,       
            "_raw" => "AND 1"     
        ];
        $actual = MySQL::BuildSQLWhereClause($where);
        $this->assertSame($expected, $actual);             
    }    

    public function testBuildSqlWhereClauseAutoEscape(): void
    {
        $where = ["name" => "O'Reilly", "id" => 5];
        $sql = MySQL::BuildSQLWhereClause($where, true);
        $this->assertStringContainsString("O''Reilly", $sql);
        $this->assertStringContainsString("`id` = 5", $sql);
    }

    public function testBuildSqlInsertAutoEscape(): void
    {
        $values = ["name" => "Test's"];
        $sql = MySQL::BuildSQLInsert("t", $values, true);
        $this->assertStringContainsString("Test''s", $sql);
    }

    public function testBuildSqlUpdateAutoEscape(): void
    {
        $values = ["name" => "Upd'ate"];
        $where = ["id" => 1];
        $sql = MySQL::BuildSQLUpdate("t", $values, $where, true);
        $this->assertStringContainsString("Upd''ate", $sql);
    }

    public function testBuildSqlSelectAutoEscape(): void
    {
        $where = ["name" => "Sel'ect"];
        $sql = MySQL::BuildSQLSelect("t", $where, null, null, true, null, null, true);
        $this->assertStringContainsString("Sel''ect", $sql);
    }

    public function testBuildSQLWhereClauseColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLWhereClause(["id` = 1; --" => "value"]);
    }

    public function testBuildSQLUpdateColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLUpdate("users", ["email` = 'x' --" => "'test@test.com'"]);
    }

    public function testBuildSQLSelectColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLSelect("users", null, ["id`; DROP TABLE users; --"]);
    }

    public function testBuildSQLInsertColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLInsert("users", ["id`; DROP TABLE users; --" => "1"]);
    }

    public function testBuildSQLOrderByInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLSelect("users", null, null, "id; DROP TABLE users; --");
    }

    public function testValidIdentifiersWork(): void
    {
        $sql = MySQL::BuildSQLSelect("users", ["name" => "John"], ["id", "name", "email"], ["created_at"], true, 10);
        $this->assertStringContainsString("`name`", $sql);
        $this->assertStringContainsString("`created_at`", $sql);
    }
}
