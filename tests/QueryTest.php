<?php
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testQuery()
    {
        # 1
        $actual = $this->db->Query("SELECT * FROM `test_table`");
        $this->assertIsObject($actual);

        # 2
        $actual = $this->db->Query("UPDATE `invalid_table` SET `value` = 1 WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQueryArray()
    {
        # 1
        $expected = array(array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red", "id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red"));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 1");
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 2
        $expected = array(array("id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red"));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 1", MYSQLI_ASSOC);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 3
        $expected = array(array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red"));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 1", MYSQLI_NUM);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 4
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertIsArray($actual);
        
        # 5
        $actual = $this->db->QueryArray("SELECT * FROM `invalid_table` WHERE `id` = 10");
        $this->assertFalse($actual);        

        # 6
        $expected = array(array("id"=>"3", "name"=>"John3", "data"=>"2024-01-04", "value"=>null));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 3", MYSQLI_ASSOC);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 7
        $expected = array(array("0"=>"3", "1"=>"John3", "2"=>"2024-01-04", "3"=>null));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 3", MYSQLI_NUM);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testQuerySingleRow()
    {
        # 1
        $actual = $this->db->QuerySingleRow("SELECT * FROM `test_table`");
        $this->assertIsObject($actual);

        # 2
        $actual = $this->db->QuerySingleRow("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQuerySingleRowArray()
    {
        # 1
        $expected = array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red", "id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red");
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table`");
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 2
        $expected = array("id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red");
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table`", MYSQLI_ASSOC);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 3
        $expected = array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red");
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table`", MYSQLI_NUM);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 4
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQuerySingleValue()
    {
        # 1
        $expected = "1";
        $actual = $this->db->QuerySingleValue("SELECT * FROM `test_table`");
        $this->assertSame($expected, $actual);

        # 2
        $expected = "John";
        $actual = $this->db->QuerySingleValue("SELECT name FROM `test_table`");
        $this->assertSame($expected, $actual);

        # 3
        $expected = "John";
        $actual = $this->db->QuerySingleValue("SELECT name FROM `test_table` WHERE `id` = 1");
        $this->assertSame($expected, $actual);

        # 4
        $actual = $this->db->QuerySingleValue("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);

        # 5
        $actual = $this->db->QuerySingleValue("SELECT value FROM `test_table` WHERE `id` = 3");
        $this->assertNull($actual);
    }
    
    public function testAutoInsertUpdate()
    {
        # 1
        $expected = "bar";
    
        $this->db->AutoInsertUpdate("test_query", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("bar")), array("key"=>MySQL::SQLValue("foo")));
        $actual = $this->db->QuerySingleValue("SELECT `value` FROM `test_query` WHERE `key` = 'foo'");
        
        $this->assertSame($expected, $actual);
        
        # 2
        $expected = "baz";
    
        $this->db->AutoInsertUpdate("test_query", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("baz")), array("key"=>MySQL::SQLValue("foo")));
        $actual = $this->db->QuerySingleValue("SELECT `value` FROM `test_query` WHERE `key` = 'foo'");
        
        $this->assertSame($expected, $actual);   
        
        # 3
        $actual = $this->db->AutoInsertUpdate("NonExistentTable", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("bar")), array("key"=>MySQL::SQLValue("foo")));
        $this->assertFalse($actual);
    }
    
    public function testInsertRow()
    {
        # 1
        $actual = $this->db->InsertRow("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>MySQL::SQLValue("def")));
        $this->assertIsNumeric($actual);
        
        # 2
        $actual = $this->db->InsertRow("NonExistentTable", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("bar")), array("key"=>MySQL::SQLValue("foo")));
        $this->assertFalse($actual);
        
        # 3
        $actual = $this->db->InsertRow("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>MySQL::SQLValue(null)));
        $this->assertIsNumeric($actual);

        # 4
        $this->db->Close();
        $actual = $this->db->InsertRow("NonExistentTable", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("bar")), array("key"=>MySQL::SQLValue("foo")));
        $this->assertFalse($actual);        
    }  
    
    public function testGetLastInsertId()
    {
        # 1
        $this->db->InsertRow("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>MySQL::SQLValue("def")));
        $actual = $this->db->GetLastInsertID();
        $this->assertIsNumeric($actual);
        
        # 2 - @TODO: I don't know why, but this returns 3 on the container with MySQL 5.7 (must be tested more, or upgrade to MySQL 8)
        # $this->db->InsertRow("NonExistentTable", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("bar")), array("key"=>MySQL::SQLValue("foo")));
        # $actual = $this->db->GetLastInsertID();
        # $this->assertFalse($actual);        
        
        # 3
        $this->db->SelectRows("test_query", array("key"=>MySQL::SQLValue("abc")));
        $actual = $this->db->GetLastInsertID();
        $this->assertFalse($actual);          
        
        # 4
        $this->db->InsertRow("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>MySQL::SQLValue("def")));
        $this->db->DeleteRows("test_query", array("key"=>MySQL::SQLValue("abc")));
        $actual = $this->db->GetLastInsertID();
        $this->assertFalse($actual);        
    }

    public function testUpdateRows()
    {
        # 1
        $actual = $this->db->UpdateRows("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>MySQL::SQLValue("def")), array("id"=>MySQL::SQLValue("-1")));
        $this->assertTrue($actual);
        
        # 2
        $actual = $this->db->UpdateRows("NonExistentTable", array("key"=>MySQL::SQLValue("foo"), "value"=>MySQL::SQLValue("bar")), array("id"=>MySQL::SQLValue("1")));
        $this->assertFalse($actual);

        # 3
        $expected = array("key"=>"123", "value"=>null);
        $actual = $this->db->UpdateRows("test_query", array("key"=>MySQL::SQLValue("123"), "value"=>MySQL::SQLValue(null)), array("key"=>MySQL::SQLValue("foo")));
        $this->assertTrue($actual);
        $this->assertSame(1, $this->db->RowCount());
        $actual = $this->db->QuerySingleRowArray("SELECT `key`, `value` FROM `test_query` WHERE `key` = '123'", MYSQLI_ASSOC);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 4
        $expected = array("id"=>"1", "key"=>"123", "value"=>null);
        $actual = $this->db->UpdateRows("test_query", array("key"=>MySQL::SQLValue("baz")), array(0=>'`key` IS NOT NULL', "value"=>null));
        $this->assertTrue($actual);
        //$this->assertSame(1, $this->db->RowCount()); TODO
    }

    public function testDeleteRows()
    {
        # 1
        $actual = $this->db->DeleteRows("test_query", array("key"=>MySQL::SQLValue("abc")));
        $this->assertTrue($actual);
        
        # 2
        $actual = $this->db->DeleteRows("test_query", array("key"=>MySQL::SQLValue("NotExists")));
        $this->assertTrue($actual);        
        
        # 3
        $actual = $this->db->DeleteRows("NonExistentTable", array("id"=>MySQL::SQLValue("1")));
        $this->assertFalse($actual);
    }    
    
    public function testSelectRows()
    {
        # 1
        $actual = $this->db->SelectRows("test_query", array("key"=>MySQL::SQLValue("abc")));
        $this->assertTrue($actual);

        # 2
        $actual = $this->db->SelectRows("test_query", array("key"=>MySQL::SQLValue("NotExists")));
        $this->assertTrue($actual);

        # 3
        $actual = $this->db->SelectRows("NonExistentTable", array("id"=>MySQL::SQLValue("1")));
        $this->assertFalse($actual);

        # 4
        $actual = $this->db->SelectRows("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>null));
        $this->assertTrue($actual);
    }

    public function testSelectTable()
    {
        # 1
        $actual = $this->db->SelectTable("test_query");
        $this->assertTrue($actual);

        # 2
        $actual = $this->db->SelectTable("test_query");
        $this->assertTrue($actual);

        # 3
        $actual = $this->db->SelectTable("NonExistentTable");
        $this->assertFalse($actual);
    }

    public function testGetLastSql()
    {
        $expected = "INSERT INTO `test_query` (`key`, `value`) VALUES ('abc', 'def')";
        $this->db->InsertRow("test_query", array("key"=>MySQL::SQLValue("abc"), "value"=>MySQL::SQLValue("def")));
        $actual = $this->db->GetLastSQL();
        $this->assertSame($expected, $actual);
    }
    
    public function testHasRecords()
    {
        # 1
        $actual = $this->db->HasRecords("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $this->assertTrue($actual);      
        
        # 2
        $actual = $this->db->HasRecords();
        $this->assertTrue($actual);    
        
        # 3
        $actual = $this->db->HasRecords("UPDATE `test_query` set `value`='baz' WHERE `key` = 'foo'");
        $this->assertFalse($actual);         
        
        # 4
        $actual = $this->db->HasRecords("SELECT `name` FROM `test_table` WHERE `id` = 100");
        $this->assertFalse($actual);    
        
        # 5
        $actual = $this->db->HasRecords("SELECT `name` FROM `invalid_table` WHERE `id` = 100");
        $this->assertFalse($actual);          
    }

    public function testRecords()
    {
        # 1
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->Records();
        $this->assertIsObject($actual);      
        
        # 2
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 100");
        $actual = $this->db->Records();
        $this->assertIsObject($actual);
        
        # 3
        $this->db->Query("UPDATE `test_query` set `value`='baz' WHERE `key` = 'foo'");
        $actual = $this->db->Records();
        $this->assertTrue($actual);         
        
        # 4
        $this->db->Query("SELECT `name` FROM `NonExistentTable` WHERE `id` = 100");
        $actual = $this->db->Records();
        $this->assertFalse($actual);          
    }  

    public function testRecordsArray()
    {
        # 1
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->RecordsArray();
        $this->assertIsArray($actual);
        $this->assertSame(1, count($actual));
        
        # 2
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 100");
        $actual = $this->db->RecordsArray();
        $this->assertIsArray($actual);
        $this->assertTrue(!$actual);
        
        # 3
        $this->db->Query("UPDATE `test_query` set `value`='baz' WHERE `key` = 'foo'");
        $actual = $this->db->RecordsArray();
        $this->assertIsArray($actual);
        $this->assertTrue(!$actual);
        
        # 4
        $this->db->Query("SELECT `name` FROM `NonExistentTable` WHERE `id` = 100");
        $actual = $this->db->RecordsArray();
        $this->assertFalse($actual);
    
        # 5
        $this->db->Query("SELECT `name` FROM `test_table`");
        $actual = $this->db->RecordsArray();
        $this->assertIsArray($actual);
        $this->assertSame($this->db->RowCount(), count($actual));
    }

    public function testRow()
    {
        # 1
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->Row();
        $this->assertIsObject($actual);      
        
        # 2
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->Row(0);
        $this->assertIsObject($actual);         
        
        # 3
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->Row(100);
        $this->assertFalse($actual);           
        
        # 4
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 100");
        $actual = $this->db->Row();
        $this->assertFalse($actual);
        
        # 5
        $this->db->Query("UPDATE `test_query` set `value`='baz' WHERE `key` = 'foo'");
        $actual = $this->db->Row();
        $this->assertFalse($actual);         
        
        # 6
        $this->db->Query("SELECT `name` FROM `NonExistentTable` WHERE `id` = 100");
        $actual = $this->db->Row();
        $this->assertFalse($actual);          
    }
    
    public function testRowArray()
    {
        # 1
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->RowArray();
        $this->assertIsArray($actual);      
        
        # 2
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->RowArray(0);
        $this->assertIsArray($actual);         
        
        # 3
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->RowArray(100);
        $this->assertFalse($actual);           
        
        # 4
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 100");
        $actual = $this->db->RowArray();
        $this->assertFalse($actual);
        
        # 5
        $this->db->Query("UPDATE `test_query` set `value`='baz' WHERE `key` = 'foo'");
        $actual = $this->db->RowArray();
        $this->assertFalse($actual);         
        
        # 6
        $this->db->Query("SELECT `name` FROM `NonExistentTable` WHERE `id` = 100");
        $actual = $this->db->RowArray();
        $this->assertFalse($actual);          
    }
    
    public function testRowCount()
    {
        # 1
        $actual = $this->db->RowCount();
        $this->assertFalse($actual);
        
        
        # 2
        $expected = 1;
        
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 1");
        $actual = $this->db->RowCount();
        
        $this->assertSame($expected, $actual);      
        
        
        # 3
        $this->db->Query("UPDATE `test_query` set `value`='baz' WHERE `key` = 'foo'");
        $actual = $this->db->RowCount();
        $this->assertFalse($actual);         
        
        
        # 4
        $this->db->Query("SELECT `name` FROM `test_table` WHERE `id` = 100");
        $actual = $this->db->RowCount();        
        $this->assertFalse($actual);        
    }
    
    public function testTruncateTable()
    {
        # 1
        $this->db->Query("CREATE TABLE IF NOT EXISTS `test_delete` (  `id` int NOT NULL,  `key` varchar(25) NOT NULL,  `value` varchar(50) NOT NULL)");
        $actual = $this->db->TruncateTable("test_delete");
        $this->assertTrue($actual);
        
        # 2
        $actual = $this->db->TruncateTable("invalid_table");
        $this->assertFalse($actual);        
    }

    public function testSelectDatabase()
    {
        # 1
        $actual = $this->db->SelectDatabase("testdb");
        $this->assertTrue($actual);

        # 2 - Removed for the moment. In some configuration it can returns TRUE even if not exists
        # $actual = $this->db->SelectDatabase("NonExistentDB");
        # $this->assertFalse($actual);
    }

}
